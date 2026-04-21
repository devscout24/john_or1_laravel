<?php

namespace App\Services;

use App\Models\CoinTransaction;
use App\Models\User;
use App\Models\UserWatchDramaRewardState;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class WatchDramaRewardService
{
    /**
     * @return array<int, int>
     */
    public function milestoneMap(): array
    {
        return [
            2 => 15,
            10 => 20,
            20 => 25,
            30 => 30,
            60 => 30,
        ];
    }

    private function today(): string
    {
        return now()->toDateString();
    }

    private function getOrCreateState(int $userId): UserWatchDramaRewardState
    {
        return UserWatchDramaRewardState::query()->firstOrCreate(
            [
                'user_id' => $userId,
                'reward_date' => $this->today(),
            ],
            [
                'watched_seconds' => 0,
                'claimed_milestones' => [],
            ]
        );
    }

    public function listForUser(int $userId): array
    {
        $state = $this->getOrCreateState($userId);
        $milestones = $this->milestoneMap();

        $watchedSeconds = max(0, (int) $state->watched_seconds);
        $watchedMinutes = (int) floor($watchedSeconds / 60);
        $claimedMilestones = collect($state->claimed_milestones ?? [])->map(fn($m) => (int) $m)->values()->all();

        $milestonePayload = collect($milestones)
            ->map(function (int $coins, int $minutes) use ($watchedMinutes, $claimedMilestones) {
                $claimed = in_array($minutes, $claimedMilestones, true);
                $unlocked = $watchedMinutes >= $minutes;

                return [
                    'minutes' => $minutes,
                    'coins' => $coins,
                    'claimed' => $claimed,
                    'claimable' => $unlocked && ! $claimed,
                    'remaining_minutes' => max(0, $minutes - $watchedMinutes),
                ];
            })
            ->values();

        return [
            'reward_date' => $this->today(),
            'reset_at' => Carbon::tomorrow()->startOfDay()->toIso8601String(),
            'watched_seconds' => $watchedSeconds,
            'watched_minutes' => $watchedMinutes,
            'total_bonus' => array_sum($milestones),
            'milestones' => $milestonePayload,
            'summary' => [
                'total' => $milestonePayload->count(),
                'claimed' => $milestonePayload->where('claimed', true)->count(),
                'claimable' => $milestonePayload->where('claimable', true)->count(),
                'claimable_coins' => (int) $milestonePayload->where('claimable', true)->sum('coins'),
            ],
        ];
    }

    public function incrementWatchSeconds(int $userId, int $seconds): void
    {
        if ($seconds <= 0) {
            return;
        }

        DB::transaction(function () use ($userId, $seconds) {
            $state = UserWatchDramaRewardState::query()
                ->where('user_id', $userId)
                ->where('reward_date', $this->today())
                ->lockForUpdate()
                ->first();

            if (! $state) {
                $state = UserWatchDramaRewardState::query()->create([
                    'user_id' => $userId,
                    'reward_date' => $this->today(),
                    'watched_seconds' => 0,
                    'claimed_milestones' => [],
                ]);
            }

            $state->watched_seconds = max(0, (int) $state->watched_seconds + $seconds);
            $state->save();
        });
    }

    public function claim(User $user, int $milestoneMinutes): array
    {
        $milestones = $this->milestoneMap();
        $coins = $milestones[$milestoneMinutes] ?? null;

        if ($coins === null) {
            return [
                'ok' => false,
                'code' => 404,
                'message' => 'Reward milestone not found',
                'data' => [],
            ];
        }

        return DB::transaction(function () use ($user, $milestoneMinutes, $coins) {
            $state = UserWatchDramaRewardState::query()
                ->where('user_id', $user->id)
                ->where('reward_date', $this->today())
                ->lockForUpdate()
                ->first();

            if (! $state) {
                $state = UserWatchDramaRewardState::query()->create([
                    'user_id' => $user->id,
                    'reward_date' => $this->today(),
                    'watched_seconds' => 0,
                    'claimed_milestones' => [],
                ]);
                $state->refresh();
            }

            $watchedMinutes = (int) floor(max(0, (int) $state->watched_seconds) / 60);

            if ($watchedMinutes < $milestoneMinutes) {
                return [
                    'ok' => false,
                    'code' => 422,
                    'message' => 'Watch time milestone is not completed yet',
                    'data' => [
                        'minutes' => $milestoneMinutes,
                        'watched_minutes' => $watchedMinutes,
                    ],
                ];
            }

            $claimedMilestones = collect($state->claimed_milestones ?? [])->map(fn($m) => (int) $m)->values()->all();

            if (in_array($milestoneMinutes, $claimedMilestones, true)) {
                return [
                    'ok' => false,
                    'code' => 422,
                    'message' => 'Reward milestone already claimed today',
                    'data' => [
                        'minutes' => $milestoneMinutes,
                    ],
                ];
            }

            $lockedUser = User::query()->where('id', $user->id)->lockForUpdate()->firstOrFail();

            $lockedUser->coins = (int) $lockedUser->coins + (int) $coins;
            $lockedUser->save();

            CoinTransaction::create([
                'user_id' => $lockedUser->id,
                'type' => 'earn',
                'amount' => (int) $coins,
                'source' => 'watch_drama_reward',
                'reference_id' => (int) $milestoneMinutes,
            ]);

            $claimedMilestones[] = $milestoneMinutes;
            $state->claimed_milestones = collect($claimedMilestones)->unique()->sort()->values()->all();
            $state->save();

            return [
                'ok' => true,
                'code' => 200,
                'message' => 'Watch drama reward claimed successfully',
                'data' => [
                    'minutes' => $milestoneMinutes,
                    'claimed_amount' => (int) $coins,
                    'total_coins' => (int) $lockedUser->coins,
                ],
            ];
        });
    }
}
