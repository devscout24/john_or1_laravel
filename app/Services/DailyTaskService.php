<?php

namespace App\Services;

use App\Models\CompanySetting;
use App\Models\CoinTransaction;
use App\Models\DailyTask;
use App\Models\User;
use App\Models\UserDailyTaskState;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DailyTaskService
{
    private function today(): string
    {
        return now()->toDateString();
    }

    public function ensureDefaultTasks(): void
    {
        if (DailyTask::where('is_active', true)->exists()) {
            return;
        }

        $defaults = [
            [
                'title' => 'Watch 3 episodes',
                'action_type' => 'watch_episode',
                'target_count' => 3,
                'coins' => 15,
                'sort_order' => 1,
            ],
            [
                'title' => 'Follow 2 series',
                'action_type' => 'follow_series',
                'target_count' => 2,
                'coins' => 10,
                'sort_order' => 2,
            ],
            [
                'title' => 'Invite a friend',
                'action_type' => 'invite_friend',
                'target_count' => 1,
                'coins' => 50,
                'sort_order' => 3,
            ],
        ];

        foreach ($defaults as $task) {
            DailyTask::create($task + ['is_active' => true]);
        }
    }

    public function listForUser(int $userId): array
    {
        if (! CompanySetting::dailyTasksEnabled()) {
            return [
                'date' => $this->today(),
                'tasks' => [],
                'summary' => [
                    'total' => 0,
                    'completed' => 0,
                    'claimed' => 0,
                    'total_claimable_coins' => 0,
                ],
            ];
        }

        $this->ensureDefaultTasks();

        $today = $this->today();

        $tasks = DailyTask::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if (! CompanySetting::referralSystemEnabled()) {
            $tasks = $tasks->where('action_type', '!=', 'invite_friend')->values();
        }

        if ($tasks->isEmpty()) {
            return [
                'date' => $today,
                'tasks' => [],
                'summary' => [
                    'total' => 0,
                    'completed' => 0,
                    'claimed' => 0,
                    'total_claimable_coins' => 0,
                ],
            ];
        }

        $states = UserDailyTaskState::query()
            ->where('user_id', $userId)
            ->where('task_date', $today)
            ->whereIn('daily_task_id', $tasks->pluck('id'))
            ->get()
            ->keyBy('daily_task_id');

        $taskPayload = $tasks->map(function (DailyTask $task) use ($states, $today, $userId) {
            $state = $states->get($task->id);

            if (! $state) {
                $state = UserDailyTaskState::create([
                    'user_id' => $userId,
                    'daily_task_id' => $task->id,
                    'task_date' => $today,
                    'progress' => 0,
                    'is_claimed' => false,
                ]);
            }

            $target = max(1, (int) $task->target_count);
            $progress = min((int) $state->progress, $target);
            $completed = $progress >= $target;
            $claimed = (bool) $state->is_claimed;

            return [
                'task_id' => (int) $task->id,
                'title' => $task->title,
                'action_type' => $task->action_type,
                'target_count' => $target,
                'progress' => $progress,
                'remaining' => max(0, $target - $progress),
                'coins' => (int) $task->coins,
                'completed' => $completed,
                'claimed' => $claimed,
                'claimable' => $completed && ! $claimed,
            ];
        })->values();

        return [
            'date' => $today,
            'tasks' => $taskPayload,
            'summary' => [
                'total' => $taskPayload->count(),
                'completed' => $taskPayload->where('completed', true)->count(),
                'claimed' => $taskPayload->where('claimed', true)->count(),
                'total_claimable_coins' => (int) $taskPayload->where('claimable', true)->sum('coins'),
            ],
        ];
    }

    public function incrementProgress(int $userId, string $actionType, int $amount = 1): void
    {
        if (! CompanySetting::dailyTasksEnabled()) {
            return;
        }

        if ($actionType === 'invite_friend' && ! CompanySetting::referralSystemEnabled()) {
            return;
        }

        $amount = max(1, $amount);

        $tasks = DailyTask::query()
            ->where('is_active', true)
            ->where('action_type', $actionType)
            ->get();

        if ($tasks->isEmpty()) {
            return;
        }

        $today = $this->today();

        DB::transaction(function () use ($tasks, $today, $userId, $amount) {
            foreach ($tasks as $task) {
                $state = UserDailyTaskState::query()->firstOrCreate(
                    [
                        'user_id' => $userId,
                        'daily_task_id' => $task->id,
                        'task_date' => $today,
                    ],
                    [
                        'progress' => 0,
                        'is_claimed' => false,
                    ]
                );

                $target = max(1, (int) $task->target_count);
                $newProgress = min($target, (int) $state->progress + $amount);

                if ($newProgress !== (int) $state->progress) {
                    $state->progress = $newProgress;
                    $state->save();
                }
            }
        });
    }

    public function claim(User $user, int $taskId): array
    {
        if (! CompanySetting::dailyTasksEnabled()) {
            return [
                'ok' => false,
                'code' => 403,
                'message' => 'Daily tasks are currently disabled by admin',
                'data' => [],
            ];
        }

        $this->ensureDefaultTasks();

        $today = $this->today();

        return DB::transaction(function () use ($taskId, $today, $user) {
            $task = DailyTask::query()
                ->where('id', $taskId)
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();

            if (! $task) {
                return [
                    'ok' => false,
                    'code' => 404,
                    'message' => 'Daily task not found',
                    'data' => [],
                ];
            }

            if ($task->action_type === 'invite_friend' && ! CompanySetting::referralSystemEnabled()) {
                return [
                    'ok' => false,
                    'code' => 403,
                    'message' => 'Referral friend system is currently disabled by admin',
                    'data' => [],
                ];
            }

            $state = UserDailyTaskState::query()
                ->where('user_id', $user->id)
                ->where('daily_task_id', $task->id)
                ->where('task_date', $today)
                ->lockForUpdate()
                ->first();

            if (! $state) {
                $state = UserDailyTaskState::create([
                    'user_id' => $user->id,
                    'daily_task_id' => $task->id,
                    'task_date' => $today,
                    'progress' => 0,
                    'is_claimed' => false,
                ]);
                $state->refresh();
            }

            $target = max(1, (int) $task->target_count);
            $progress = min((int) $state->progress, $target);

            if ($progress < $target) {
                return [
                    'ok' => false,
                    'code' => 422,
                    'message' => 'Task is not completed yet',
                    'data' => [
                        'task_id' => (int) $task->id,
                        'progress' => $progress,
                        'target_count' => $target,
                    ],
                ];
            }

            if ((bool) $state->is_claimed) {
                return [
                    'ok' => false,
                    'code' => 422,
                    'message' => 'Task reward already claimed today',
                    'data' => [
                        'task_id' => (int) $task->id,
                    ],
                ];
            }

            $lockedUser = User::query()->where('id', $user->id)->lockForUpdate()->firstOrFail();

            $coins = (int) $task->coins;
            $lockedUser->coins = (int) $lockedUser->coins + $coins;
            $lockedUser->save();

            CoinTransaction::create([
                'user_id' => $lockedUser->id,
                'type' => 'earn',
                'amount' => $coins,
                'source' => 'daily_task_reward',
                'reference_id' => $task->id,
            ]);

            $state->is_claimed = true;
            $state->claimed_at = Carbon::now();
            $state->save();

            return [
                'ok' => true,
                'code' => 200,
                'message' => 'Daily task reward claimed successfully',
                'data' => [
                    'task_id' => (int) $task->id,
                    'claimed_amount' => $coins,
                    'total_coins' => (int) $lockedUser->coins,
                ],
            ];
        });
    }
}
