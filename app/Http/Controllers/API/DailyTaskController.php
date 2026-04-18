<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CompanySetting;
use App\Models\User;
use App\Services\DailyTaskService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class DailyTaskController extends Controller
{
    use ApiResponse;

    private function authenticatedUser(): ?User
    {
        $user = Auth::guard('api')->user();

        return $user instanceof User ? $user : null;
    }

    public function index(DailyTaskService $dailyTaskService)
    {
        if (! CompanySetting::dailyTasksEnabled()) {
            return $this->error([], 'Daily tasks are currently disabled by admin', 403);
        }

        $user = $this->authenticatedUser();

        if (! $user) {
            return $this->error([], 'User not found or invalid token', 401);
        }

        $payload = $dailyTaskService->listForUser((int) $user->id);

        return $this->success($payload, 'Daily tasks fetched successfully', 200);
    }

    public function claim(DailyTaskService $dailyTaskService, int $taskId)
    {
        if (! CompanySetting::dailyTasksEnabled()) {
            return $this->error([], 'Daily tasks are currently disabled by admin', 403);
        }

        $user = $this->authenticatedUser();

        if (! $user) {
            return $this->error([], 'User not found or invalid token', 401);
        }

        $result = $dailyTaskService->claim($user, $taskId);

        if (! ($result['ok'] ?? false)) {
            return $this->error($result['data'] ?? [], $result['message'] ?? 'Unable to claim daily task reward', (int) ($result['code'] ?? 422));
        }

        return $this->success($result['data'] ?? [], $result['message'] ?? 'Daily task reward claimed successfully', (int) ($result['code'] ?? 200));
    }

    public function progress(Request $request, DailyTaskService $dailyTaskService)
    {
        if (! CompanySetting::dailyTasksEnabled()) {
            return $this->error([], 'Daily tasks are currently disabled by admin', 403);
        }

        $user = $this->authenticatedUser();

        if (! $user) {
            return $this->error([], 'User not found or invalid token', 401);
        }

        $validated = $request->validate([
            'action_type' => ['required', Rule::in(['watch_episode', 'follow_series', 'invite_friend'])],
            'amount' => ['nullable', 'integer', 'min:1', 'max:10'],
        ]);

        $dailyTaskService->incrementProgress(
            (int) $user->id,
            (string) $validated['action_type'],
            (int) ($validated['amount'] ?? 1)
        );

        $payload = $dailyTaskService->listForUser((int) $user->id);

        return $this->success($payload, 'Daily task progress updated successfully', 200);
    }
}
