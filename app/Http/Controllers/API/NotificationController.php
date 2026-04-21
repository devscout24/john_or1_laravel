<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Notification;
use App\Models\User;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    use ApiResponse;

    private function authenticatedUser(): ?User
    {
        $user = Auth::guard('api')->user();

        return $user instanceof User ? $user : null;
    }

    private function resolveIconKey(Notification $notification): string
    {
        if (! empty($notification->icon_key)) {
            return (string) $notification->icon_key;
        }

        $type = strtolower((string) $notification->type);
        $title = strtolower((string) $notification->title);

        if (str_contains($type, 'reward') || str_contains($type, 'coin') || str_contains($title, 'reward') || str_contains($title, 'coin')) {
            return 'coin_icon';
        }

        if (str_contains($type, 'episode') || str_contains($type, 'watch') || str_contains($title, 'episode') || str_contains($title, 'watch')) {
            return 'play_icon';
        }

        return 'star_icon';
    }

    public function notification(Request $request)
    {
        $user = $this->authenticatedUser();

        if (! $user) {
            return $this->error([], 'User not found or invalid token', 401);
        }

        $notifications = Notification::query()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at');

        if ($request->filled('date')) {
            $filterDate = Carbon::parse($request->date)->toDateString();
            $notifications->whereDate('created_at', $filterDate);
        }

        if ($request->filled('is_read')) {
            $notifications->where('is_read', filter_var($request->input('is_read'), FILTER_VALIDATE_BOOLEAN));
        }

        $notifications = $notifications->get();

        $unreadCount = Notification::query()
            ->where('user_id', $user->id)
            ->where('is_read', false)
            ->count();

        $formattedNotifications = $notifications->map(function ($notification) {
            return [
                'id' => $notification->id,
                'title' => $notification->title,
                'message' => $notification->message,
                'type' => $notification->type,
                'icon' => $this->resolveIconKey($notification),
                'is_read' => (bool) $notification->is_read,
                'read_at' => $notification->read_at?->toIso8601String(),
                'reference_id' => $notification->reference_id,
                'reference_type' => $notification->reference_type,
                'created_at' => $notification->created_at?->toIso8601String(),
                'time_ago' => $notification->created_at->diffForHumans(),
            ];
        });

        return $this->success([
            'unread_count' => (int) $unreadCount,
            'items' => $formattedNotifications,
        ], 'Notifications fetched successfully', 200);
    }

    public function markAllRead()
    {
        $user = $this->authenticatedUser();

        if (! $user) {
            return $this->error([], 'User not found or invalid token', 401);
        }

        Notification::query()
            ->where('user_id', $user->id)
            ->where('is_read', false)
            ->update([
                'is_read' => true,
                'read_at' => now(),
            ]);

        return $this->success([], 'All notifications marked as read', 200);
    }

    public function markAllUnread()
    {
        $user = $this->authenticatedUser();

        if (! $user) {
            return $this->error([], 'User not found or invalid token', 401);
        }

        Notification::query()
            ->where('user_id', $user->id)
            ->update([
                'is_read' => false,
                'read_at' => null,
            ]);

        return $this->success([], 'All notifications marked as unread', 200);
    }

    public function deleteAll()
    {
        $user = $this->authenticatedUser();

        if (! $user) {
            return $this->error([], 'User not found or invalid token', 401);
        }

        Notification::query()->where('user_id', $user->id)->delete();

        return $this->success([], 'All notifications deleted', 200);
    }

    public function deleteNotification(Request $request)
    {
        $request->validate([
            'notification_id' => 'required|integer|exists:notifications,id',
        ]);

        $user = $this->authenticatedUser();

        if (! $user) {
            return $this->error([], 'User not found or invalid token', 401);
        }

        $notification = Notification::query()
            ->where('id', $request->notification_id)
            ->where('user_id', $user->id)
            ->first();

        if (! $notification) {
            return $this->error([], 'Notification not found', 404);
        }

        $notification->delete();

        return $this->success([], 'Notification deleted successfully', 200);
    }

    public function markNotificationRead(Request $request)
    {
        $request->validate([
            'notification_id' => 'required|integer|exists:notifications,id',
        ]);

        $user = $this->authenticatedUser();

        if (! $user) {
            return $this->error([], 'User not found or invalid token', 401);
        }

        $notification = Notification::query()
            ->where('id', $request->notification_id)
            ->where('user_id', $user->id)
            ->first();

        if (! $notification) {
            return $this->error([], 'Notification not found', 404);
        }

        $notification->is_read = true;
        $notification->read_at = now();
        $notification->save();

        return $this->success([], 'Notification marked as read', 200);
    }

    public function markNotificationUnread(Request $request)
    {
        $request->validate([
            'notification_id' => 'required|integer|exists:notifications,id',
        ]);

        $user = $this->authenticatedUser();

        if (! $user) {
            return $this->error([], 'User not found or invalid token', 401);
        }

        $notification = Notification::query()
            ->where('id', $request->notification_id)
            ->where('user_id', $user->id)
            ->first();

        if (! $notification) {
            return $this->error([], 'Notification not found', 404);
        }

        $notification->is_read = false;
        $notification->read_at = null;
        $notification->save();

        return $this->success([], 'Notification marked as unread', 200);
    }
}
