<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CompanySetting;
use App\Models\CoinTransaction;
use App\Models\DailyTask;
use App\Models\Favorite;
use App\Models\User;
use App\Models\UserReferralUsage;
use App\Models\WatchHistory;
use App\Services\DailyTaskService;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    use ApiResponse;

    private function authenticatedUser(): ?User
    {
        $user = Auth::guard('api')->user();

        return $user instanceof User ? $user : null;
    }

    private function avatarUrl(?string $avatar): string
    {
        $avatarPath = $avatar ?: 'user.png';

        if (preg_match('/^https?:\/\//i', $avatarPath)) {
            return $avatarPath;
        }

        return asset($avatarPath);
    }

    // Profile
    public function profile()
    {
        $user = $this->authenticatedUser();

        if (!$user) {
            return $this->error([], 'User not found or invalid token', 401);
        }

        $this->ensureReferralCode($user);

        $favoritesCount = Favorite::query()
            ->where('user_id', $user->id)
            ->count();

        $watchedSeriesCount = WatchHistory::query()
            ->where('user_id', $user->id)
            ->where('progress', '>', 0)
            ->whereHas('content', function ($query) {
                $query->where('type', 'series');
            })
            ->distinct('content_id')
            ->count('content_id');

        $profile_data = [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'profile_photo' => $this->avatarUrl($user->avatar),
            'address' => $user->address,
            'phone' => $user->phone,
            'location' => $user->location,
            'title' => $user->title,
            'language' => $user->language,
            'provider' => $user->provider,
            'coins' => (int) $user->coins,
            'favorites_count' => (int) $favoritesCount,
            'watched_series_count' => (int) $watchedSeriesCount,
            'referral_code' => $user->referral_code,
            'referral_eligible_until' => $this->referralEligibleUntil($user)->toIso8601String(),
            'can_use_referral_code' => CompanySetting::referralSystemEnabled() && $this->canUseReferralCode($user),
        ];

        return $this->success($profile_data, 'Profile Information', 200);
    }

    public function updateProfile(Request $request)
    {
        $user = $this->authenticatedUser();

        if (!$user) {
            return $this->error([], 'User not found or invalid token', 401);
        }

        $validate = Validator::make($request->all(), [
            'name'     => 'sometimes|nullable|string|max:255',
            'email'    => 'sometimes|email|max:255|unique:users,email,' . $user->id,
            'phone'    => 'sometimes|nullable|string|max:20',
            'address'  => 'sometimes|nullable|string|max:500',
            'location' => 'sometimes|nullable|string|max:255',
            'avatar'   => 'sometimes|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        if ($validate->fails()) {
            return $this->error($validate->errors(), $validate->errors()->first(), 422);
        }

        try {
            if ($request->exists('name')) {
                $user->name = $request->name;
            }
            if ($request->exists('email')) {
                $user->email = $request->email;
            }
            if ($request->exists('phone')) {
                $user->phone = $request->phone;
            }
            if ($request->exists('address')) {
                $user->address = $request->address;
            }
            if ($request->exists('location')) {
                $user->location = $request->location;
            }

            if ($request->hasFile('avatar')) {
                $oldImage = $user->avatar != 'user.png' ? $user->avatar : null;
                $avatar = $this->uploadImage($request->file('avatar'), $oldImage, 'uploads/avatar', 300, 300, 'avatar_' . $user->id);
                $user->avatar = $avatar;
            }

            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
            }

            $user->save();

            return $this->success([
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'profile_photo' => $this->avatarUrl($user->avatar),
                'address' => $user->address,
                'phone' => $user->phone,
                'location' => $user->location,
                'title' => $user->title,
                'language' => $user->language,
                'provider' => $user->provider,
            ], 'Profile updated successfully', 200);
        } catch (\Exception $e) {
            return $this->error([], 'Failed to update profile: ' . $e->getMessage(), 500);
        }
    }

    public function applyReferralCode(Request $request, DailyTaskService $dailyTaskService)
    {
        if (! CompanySetting::referralSystemEnabled()) {
            return $this->error([], 'Referral friend system is currently disabled by admin', 403);
        }

        $user = $this->authenticatedUser();

        if (! $user) {
            return $this->error([], 'User not found or invalid token', 401);
        }

        $validated = Validator::make($request->all(), [
            'referral_code' => 'required|string|max:20',
        ]);

        if ($validated->fails()) {
            return $this->error($validated->errors(), $validated->errors()->first(), 422);
        }

        $this->ensureReferralCode($user);

        if (! $this->canUseReferralCode($user)) {
            return $this->error([
                'referral_eligible_until' => $this->referralEligibleUntil($user)->toIso8601String(),
            ], 'Referral code can only be used within 3 days of registration', 422);
        }

        $today = now()->toDateString();

        $alreadyUsedToday = UserReferralUsage::query()
            ->where('user_id', $user->id)
            ->where('used_on', $today)
            ->exists();

        if ($alreadyUsedToday) {
            return $this->error([], 'Referral code can only be used once per day', 422);
        }

        $referralCode = Str::upper(trim((string) $request->referral_code));

        $referrer = User::query()
            ->where('referral_code', $referralCode)
            ->where('id', '!=', $user->id)
            ->first();

        if (! $referrer) {
            return $this->error([], 'Invalid referral code', 404);
        }

        $result = DB::transaction(function () use ($dailyTaskService, $referralCode, $referrer, $today, $user) {
            $invitee = User::query()->where('id', $user->id)->lockForUpdate()->firstOrFail();
            $lockedReferrer = User::query()->where('id', $referrer->id)->lockForUpdate()->firstOrFail();

            $duplicateToday = UserReferralUsage::query()
                ->where('user_id', $invitee->id)
                ->where('used_on', $today)
                ->lockForUpdate()
                ->exists();

            if ($duplicateToday) {
                return [
                    'ok' => false,
                    'code' => 422,
                    'message' => 'Referral code can only be used once per day',
                    'data' => [],
                ];
            }

            $referralBonusCoins = 15;

            $invitee->coins = (int) $invitee->coins + $referralBonusCoins;

            if (! $invitee->referred_by_user_id) {
                $invitee->referred_by_user_id = $lockedReferrer->id;
            }

            $invitee->save();

            $lockedReferrer->coins = (int) $lockedReferrer->coins + $referralBonusCoins;
            $lockedReferrer->save();

            UserReferralUsage::create([
                'user_id' => $invitee->id,
                'referrer_user_id' => $lockedReferrer->id,
                'referral_code' => $referralCode,
                'used_on' => $today,
            ]);

            CoinTransaction::create([
                'user_id' => $invitee->id,
                'type' => 'earn',
                'amount' => $referralBonusCoins,
                'source' => 'referral_bonus_invitee',
                'reference_id' => $lockedReferrer->id,
            ]);

            CoinTransaction::create([
                'user_id' => $lockedReferrer->id,
                'type' => 'earn',
                'amount' => $referralBonusCoins,
                'source' => 'referral_bonus_referrer',
                'reference_id' => $invitee->id,
            ]);

            if (CompanySetting::dailyTasksEnabled()) {
                $dailyTaskService->incrementProgress((int) $invitee->id, 'invite_friend', 1);
            }

            $dailyTaskRewardCoins = 0;
            $dailyTaskClaimed = false;

            $inviteTask = DailyTask::query()
                ->where('is_active', true)
                ->where('action_type', 'invite_friend')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->first();

            if ($inviteTask && CompanySetting::dailyTasksEnabled()) {
                $claimResult = $dailyTaskService->claim($invitee, (int) $inviteTask->id);

                if (($claimResult['ok'] ?? false) === true) {
                    $dailyTaskClaimed = true;
                    $dailyTaskRewardCoins = (int) ($claimResult['data']['claimed_amount'] ?? 0);
                }
            }

            $invitee->refresh();

            return [
                'ok' => true,
                'code' => 200,
                'message' => 'Referral code applied successfully',
                'data' => [
                    'referral_code' => $referralCode,
                    'referral_bonus_coins' => $referralBonusCoins,
                    'daily_task_reward_claimed' => $dailyTaskClaimed,
                    'daily_task_reward_coins' => $dailyTaskRewardCoins,
                    'invitee_total_coins' => (int) $invitee->coins,
                    'referrer_total_coins' => (int) $lockedReferrer->coins,
                ],
            ];
        });

        if (! ($result['ok'] ?? false)) {
            return $this->error($result['data'] ?? [], $result['message'] ?? 'Unable to apply referral code', (int) ($result['code'] ?? 422));
        }

        return $this->success($result['data'] ?? [], $result['message'] ?? 'Referral code applied successfully', (int) ($result['code'] ?? 200));
    }

    private function canUseReferralCode(User $user): bool
    {
        return now()->lessThanOrEqualTo($this->referralEligibleUntil($user));
    }

    private function referralEligibleUntil(User $user): Carbon
    {
        return Carbon::parse($user->created_at)->addDays(3);
    }

    private function ensureReferralCode(User $user): void
    {
        if (! empty($user->referral_code)) {
            return;
        }

        $user->referral_code = $this->generateUniqueReferralCode();
        $user->save();
    }

    private function generateUniqueReferralCode(): string
    {
        do {
            $code = Str::upper(Str::random(8));
        } while (User::query()->where('referral_code', $code)->exists());

        return $code;
    }
}
