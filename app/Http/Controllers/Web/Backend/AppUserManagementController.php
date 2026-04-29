<?php

namespace App\Http\Controllers\Web\Backend;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\SavedEpisode;
use App\Models\EpisodeLike;
use App\Models\Subscription;
use App\Models\UserReferralUsage;
use Yajra\DataTables\DataTables;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AppUserManagementController extends Controller
{
    public function index()
    {
        // Get user statistics
        $allUsers = User::whereDoesntHave('roles', function ($q) {
            $q->where('name', 'admin');
        })->get();

        $activeUsersCount = $allUsers->where('status', 'active')->count();
        $blockedUsersCount = $allUsers->where('status', 'banned')->count();
        $guestUsersCount = $allUsers->where('provider', 'guest')->count();

        return view('backend.layouts.app_users.index', [
            'allUsers' => $allUsers,
            'activeUsersCount' => $activeUsersCount,
            'blockedUsersCount' => $blockedUsersCount,
            'guestUsersCount' => $guestUsersCount,
        ]);
    }

    public function data(Request $request)
    {
        $query = User::query()
            ->whereDoesntHave('roles', function ($q) {
                $q->where('name', 'admin');
            });

        // Filter by User Type
        if ($request->user_type && $request->user_type != 'All') {
            if ($request->user_type === 'guest') {
                $query->where('provider', 'guest');
            } elseif ($request->user_type === 'social') {
                $query->where('provider', '!=', 'guest');
            }
        }

        // Filter by Status
        if ($request->status && $request->status != 'All') {
            $query->where('status', $request->status);
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('user_type', function ($user) {
                if ($user->provider === 'guest') {
                    return '<span class="badge bg-info-subtle text-info">Guest</span>';
                }
                return '<span class="badge bg-success-subtle text-success">Social</span>';
            })
            ->addColumn('subscription', function ($user) {
                $subscription = Subscription::where('user_id', $user->id)
                    ->where('status', 'active')
                    ->latest()
                    ->first();

                if ($subscription) {
                    return '<span class="badge bg-success-subtle text-success">Active</span>';
                }
                return '<span class="badge bg-warning-subtle text-warning">Inactive</span>';
            })
            ->addColumn('coins', function ($user) {
                return '<span class="fw-semibold">' . ($user->coins ?? 0) . '</span>';
            })
            ->addColumn('series_watched', function ($user) {
                // Count unique episodes watched by this user
                $watched = DB::table('watch_histories')
                    ->where('user_id', $user->id)
                    ->distinct('episode_id')
                    ->count('episode_id');
                return $watched;
            })
            ->addColumn('series_liked', function ($user) {
                return EpisodeLike::where('user_id', $user->id)->count();
            })
            ->addColumn('series_saved', function ($user) {
                return SavedEpisode::where('user_id', $user->id)->count();
            })
            ->addColumn('referrals', function ($user) {
                // Count users who this user referred
                return UserReferralUsage::where('referrer_user_id', $user->id)->count();
            })
            ->addColumn('joined', function ($user) {
                return $user->created_at->format('d M Y');
            })
            ->addColumn('status_badge', function ($user) {
                if ($user->status == 'active') {
                    return '<span class="badge bg-success-subtle text-success">Active</span>';
                }
                if ($user->status == 'banned') {
                    return '<span class="badge bg-danger-subtle text-danger">Blocked</span>';
                }
                return '<span class="badge bg-warning-subtle text-warning">Inactive</span>';
            })
            ->addColumn('action', function ($user) {
                $toggleStatus = $user->status === 'banned' ? 'active' : 'banned';
                $toggleLabel = $user->status === 'banned' ? 'Unblock' : 'Block';
                $toggleClass = $user->status === 'banned' ? 'btn-success' : 'btn-danger';

                $actions = '
                    <div class="d-flex justify-content-center gap-1">
                        <a href="' . route('app-user.show', $user->id) . '"
                           class="btn btn-default btn-icon btn-sm" title="View Profile">
                            <i class="ti ti-eye fs-lg"></i>
                        </a>
                        <button type="button"
                            class="btn ' . $toggleClass . ' btn-icon btn-sm js-app-user-status-toggle"
                            title="' . $toggleLabel . ' User"
                            data-url="' . route('app-user.status.update', $user->id) . '"
                            data-status="' . $toggleStatus . '"
                            data-action-label="' . $toggleLabel . '"
                            data-user-name="' . e($user->name ?: $user->email) . '">
                            <i class="ti ti-user-x fs-lg"></i>
                        </button>
                    </div>
                ';
                return $actions;
            })
            ->rawColumns(['user_type', 'subscription', 'coins', 'status_badge', 'action'])
            ->make(true);
    }

    public function show(User $appUser)
    {
        // Verify it's not an admin user
        if ($appUser->hasRole('admin')) {
            abort(404);
        }

        // Get user statistics
        $subscription = Subscription::where('user_id', $appUser->id)
            ->where('status', 'active')
            ->latest()
            ->first();

        $coins = $appUser->coins ?? 0;
        $seriesWatched = DB::table('watch_histories')
            ->where('user_id', $appUser->id)
            ->distinct('episode_id')
            ->count('episode_id');
        $seriesLiked = EpisodeLike::where('user_id', $appUser->id)->count();
        $seriesSaved = SavedEpisode::where('user_id', $appUser->id)->count();
        $referrals = UserReferralUsage::where('referrer_user_id', $appUser->id)->count();

        // Get recent activities
        $recentWatched = DB::table('watch_histories')
            ->where('user_id', $appUser->id)
            ->latest('created_at')
            ->limit(5)
            ->get();

        $recentLiked = EpisodeLike::where('user_id', $appUser->id)
            ->latest('created_at')
            ->limit(5)
            ->get();

        $recentSaved = SavedEpisode::where('user_id', $appUser->id)
            ->latest('created_at')
            ->limit(5)
            ->get();

        return view('backend.layouts.app_users.show', [
            'user' => $appUser,
            'subscription' => $subscription,
            'coins' => $coins,
            'seriesWatched' => $seriesWatched,
            'seriesLiked' => $seriesLiked,
            'seriesSaved' => $seriesSaved,
            'referrals' => $referrals,
            'recentWatched' => $recentWatched,
            'recentLiked' => $recentLiked,
            'recentSaved' => $recentSaved,
        ]);
    }

    public function updateUserStatus(Request $request, User $appUser)
    {
        // Verify it's not an admin user
        if ($appUser->hasRole('admin')) {
            return response()->json(['error' => 'Cannot modify admin user'], 403);
        }

        $status = $request->input('status', 'active');

        if (!in_array($status, ['active', 'banned', 'inactive'])) {
            return response()->json(['error' => 'Invalid status'], 422);
        }

        $appUser->update(['status' => $status]);

        return response()->json([
            'message' => 'User status updated successfully',
            'status' => $status
        ]);
    }
}
