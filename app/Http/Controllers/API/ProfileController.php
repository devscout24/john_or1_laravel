<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use App\Models\User;
use App\Models\WatchHistory;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

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
}