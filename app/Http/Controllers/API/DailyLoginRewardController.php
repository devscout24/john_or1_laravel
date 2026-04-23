<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CoinTransaction;
use App\Models\DailyLoginReward;
use App\Models\User;
use App\Models\UserDailyLoginState;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DailyLoginRewardController extends Controller
{
    use ApiResponse;

    private function nextResetAt(): Carbon
    {
        return now()->addDay()->startOfDay();
    }

    private function resetCountdownPayload(): array
    {
        $resetAt = $this->nextResetAt();
        $seconds = max(0, now()->diffInSeconds($resetAt, false));
        $hours = (int) floor($seconds / 3600);
        $minutes = (int) floor(($seconds % 3600) / 60);
        $remainingSeconds = (int) ($seconds % 60);

        return [
            'reset_at' => $resetAt->toIso8601String(),
            'reset_in_seconds' => $seconds,
            'reset_in' => sprintf('%02d:%02d:%02d', $hours, $minutes, $remainingSeconds),
        ];
    }

    private function authenticatedUser(): ?User
    {
        $user = Auth::guard('api')->user();

        return $user instanceof User ? $user : null;
    }

    private function ensureDefaultRewards(): void
    {
        if (DailyLoginReward::count() > 0) {
            return;
        }

        $defaults = [
            1 => 5,
            2 => 10,
            3 => 15,
            4 => 20,
            5 => 25,
            6 => 30,
            7 => 35,
        ];

        foreach ($defaults as $day => $coins) {
            DailyLoginReward::create([
                'day' => $day,
                'coins' => $coins,
                'is_active' => true,
            ]);
        }
    }

    private function getOrCreateState(int $userId): UserDailyLoginState
    {
        return UserDailyLoginState::firstOrCreate(
            ['user_id' => $userId],
            ['next_day' => 1]
        );
    }

    private function normalizeState(UserDailyLoginState $state): UserDailyLoginState
    {
        if (!$state->last_claimed_on) {
            return $state;
        }

        $diff = Carbon::parse($state->last_claimed_on)->startOfDay()->diffInDays(now()->startOfDay());

        if ($diff > 1) {
            $state->next_day = 1;
            $state->last_claimed_day = null;
            $state->last_claimed_on = null;
            $state->save();
        }

        return $state;
    }

    private function claimedToday(UserDailyLoginState $state): bool
    {
        return (bool) ($state->last_claimed_on && Carbon::parse($state->last_claimed_on)->isToday());
    }

    public function index()
    {
        $user = $this->authenticatedUser();

        if (!$user) {
            return $this->error([], 'User not found or invalid token', 401);
        }

        $this->ensureDefaultRewards();

        $state = $this->normalizeState($this->getOrCreateState($user->id));
        $rewards = DailyLoginReward::where('is_active', true)->orderBy('day')->get();

        $claimedToday = $this->claimedToday($state);
        $completedDay = $claimedToday ? (int) ($state->last_claimed_day ?? 0) : ((int) $state->next_day - 1);
        $totalDays = max(0, (int) $rewards->count());
        $usedDays = min(max(0, $completedDay), $totalDays);

        $days = $rewards->map(function (DailyLoginReward $reward) use ($completedDay, $claimedToday, $state) {
            $day = (int) $reward->day;

            return [
                'day' => $day,
                'coins' => (int) $reward->coins,
                'collected' => $day <= max($completedDay, 0),
                'claimable' => !$claimedToday && $day === (int) $state->next_day,
            ];
        })->values();

        $nextReward = $rewards->firstWhere('day', (int) $state->next_day);
        $countdown = $this->resetCountdownPayload();

        return $this->success([
            'days' => $days,
            'streak' => [
                'used' => $usedDays,
                'total' => $totalDays,
                'label' => sprintf('%d/%d used', $usedDays, $totalDays),
            ],
            'next_claim_day' => (int) $state->next_day,
            'next_claim_amount' => (int) ($nextReward->coins ?? 0),
            'can_claim_today' => !$claimedToday,
            'claimed_today' => $claimedToday,
            'reset_at' => $countdown['reset_at'],
            'reset_in_seconds' => $countdown['reset_in_seconds'],
            'reset_in' => $countdown['reset_in'],
        ], 'Daily login rewards fetched successfully', 200);
    }

    public function claim(Request $request)
    {
        $user = $this->authenticatedUser();

        if (!$user) {
            return $this->error([], 'User not found or invalid token', 401);
        }

        $this->ensureDefaultRewards();

        $state = $this->normalizeState($this->getOrCreateState($user->id));

        if ($this->claimedToday($state)) {
            return $this->error([], 'Daily login reward already claimed today', 422);
        }

        $claimDay = (int) $state->next_day;
        $reward = DailyLoginReward::where('is_active', true)->where('day', $claimDay)->first();

        if (!$reward) {
            return $this->error([], 'Reward config not found for claim day', 404);
        }

        DB::transaction(function () use (&$user, &$state, $reward, $claimDay) {
            $user = User::where('id', $user->id)->lockForUpdate()->firstOrFail();
            $state = UserDailyLoginState::where('id', $state->id)->lockForUpdate()->firstOrFail();

            $coins = (int) $reward->coins;

            $user->coins = (int) $user->coins + $coins;
            $user->save();

            CoinTransaction::create([
                'user_id' => $user->id,
                'type' => 'earn',
                'amount' => $coins,
                'source' => 'daily_login_reward',
                'reference_id' => $reward->id,
            ]);

            $state->last_claimed_day = $claimDay;
            $state->last_claimed_on = now()->toDateString();
            $state->next_day = $claimDay >= 7 ? 1 : $claimDay + 1;
            $state->save();
        });

        $rewards = DailyLoginReward::where('is_active', true)->orderBy('day')->get();
        $nextReward = $rewards->firstWhere('day', (int) $state->next_day);
        $totalDays = max(0, (int) $rewards->count());
        $usedDays = min(max(0, (int) $claimDay), $totalDays);
        $countdown = $this->resetCountdownPayload();

        return $this->success([
            'claimed_day' => $claimDay,
            'claimed_amount' => (int) $reward->coins,
            'total_coins' => (int) $user->coins,
            'streak' => [
                'used' => $usedDays,
                'total' => $totalDays,
                'label' => sprintf('%d/%d used', $usedDays, $totalDays),
            ],
            'next_claim_day' => (int) $state->next_day,
            'next_claim_amount' => (int) ($nextReward->coins ?? 0),
            'reset_at' => $countdown['reset_at'],
            'reset_in_seconds' => $countdown['reset_in_seconds'],
            'reset_in' => $countdown['reset_in'],
        ], 'Daily login reward claimed successfully', 200);
    }
}
