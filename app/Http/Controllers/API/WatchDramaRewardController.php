<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\WatchDramaRewardService;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class WatchDramaRewardController extends Controller
{
    use ApiResponse;

    private function authenticatedUser(): ?User
    {
        $user = Auth::guard('api')->user();

        return $user instanceof User ? $user : null;
    }

    public function index(WatchDramaRewardService $watchDramaRewardService)
    {
        $user = $this->authenticatedUser();

        if (! $user) {
            return $this->error([], 'User not found or invalid token', 401);
        }

        $payload = $watchDramaRewardService->listForUser((int) $user->id);

        return $this->success($payload, 'Watch drama rewards fetched successfully', 200);
    }

    public function claim(Request $request, WatchDramaRewardService $watchDramaRewardService)
    {
        $user = $this->authenticatedUser();

        if (! $user) {
            return $this->error([], 'User not found or invalid token', 401);
        }

        $validated = $request->validate([
            'minutes' => ['required', 'integer', Rule::in(array_keys($watchDramaRewardService->milestoneMap()))],
        ]);

        $result = $watchDramaRewardService->claim($user, (int) $validated['minutes']);

        if (! ($result['ok'] ?? false)) {
            return $this->error($result['data'] ?? [], $result['message'] ?? 'Unable to claim watch drama reward', (int) ($result['code'] ?? 422));
        }

        return $this->success($result['data'] ?? [], $result['message'] ?? 'Watch drama reward claimed successfully', (int) ($result['code'] ?? 200));
    }
}
