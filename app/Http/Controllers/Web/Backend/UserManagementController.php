<?php

namespace App\Http\Controllers\Web\Backend;

use App\Http\Controllers\Controller;
use App\Models\User;
use Yajra\DataTables\DataTables;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Mail\WelcomeUserMail;
use Illuminate\Support\Facades\Mail;
use App\Models\CompanySetting;
use Illuminate\Support\Facades\Auth;
use App\Models\WatchHistory;
use App\Models\Favorite;
use App\Models\Subscription;
use App\Models\UserReferralUsage;
use App\Models\CoinTransaction;
use Carbon\Carbon;

use Spatie\Permission\Models\Role;

class UserManagementController extends Controller
{
    public function data(Request $request)
    {
        $query = User::query()->with('roles');

        // Filter by User Type
        if ($request->user_type && $request->user_type != 'All') {
            if ($request->user_type === 'guest') {
                $query->where('provider', 'guest');
            }

            if ($request->user_type === 'admin') {
                $query->whereHas('roles', function ($q) {
                    $q->where('name', 'admin');
                });
            }

            if ($request->user_type === 'normal') {
                $query->where('provider', '!=', 'guest')
                    ->whereDoesntHave('roles', function ($q) {
                        $q->where('name', 'admin');
                    });
            }
        }

        // Filter by Role
        if ($request->role && $request->role != 'All') {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        // Filter by Status
        if ($request->status && $request->status != 'All') {
            $query->where('status', $request->status);
        }

        return DataTables::of($query)

            ->addIndexColumn()

            ->addColumn('role', function ($user) {
                return $user->getRoleNames()[0] ?? 'No Role';
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

                $isAdminUser = $user->hasRole('admin');
                $toggleStatus = $user->status === 'banned' ? 'active' : 'banned';
                $toggleLabel = $user->status === 'banned' ? 'Unblock' : 'Block';
                $toggleClass = $user->status === 'banned' ? 'btn-success' : 'btn-danger';
                $statusForm = '';

                if (! $isAdminUser) {
                    $statusForm = '
                            <button type="button"
                                class="btn ' . $toggleClass . ' btn-icon btn-sm js-user-status-toggle"
                                title="' . $toggleLabel . ' User"
                                data-url="' . route('admin.user.status.update', $user->id) . '"
                                data-status="' . $toggleStatus . '"
                                data-action-label="' . $toggleLabel . '"
                                data-user-name="' . e($user->name ?: $user->email) . '">
                                <i class="ti ti-user-x fs-lg"></i>
                            </button>
                    ';
                }

                return '
                <div class="d-flex justify-content-center gap-1">

                    <a href="' . route('admin.user.show', $user->id) . '"
                       class="btn btn-default btn-icon btn-sm">
                        <i class="ti ti-eye fs-lg"></i>
                    </a>

                    <a href="' . route('admin.user.edit', $user->id) . '"
                       class="btn btn-default btn-icon btn-sm">
                        <i class="ti ti-edit fs-lg"></i>
                    </a>

                    ' . $statusForm . '

                </div>
            ';
            })

            ->rawColumns(['status_badge', 'action'])

            ->make(true);
    }

    // Index
    public function create()
    {
        return view('backend.layouts.user_management.create');
    }

    public function store(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'name'     => 'required|string|max:100',
            'email'    => 'required|email|unique:users,email',
            'username' => 'nullable|unique:users,username',
            'phone'    => 'nullable|max:20',
            'password' => 'required|string|min:6|confirmed',
            'avatar'   => 'nullable|image|mimes:jpg,png,jpeg|max:2048',
            'admin_access_ack' => 'accepted',
            'current_admin_password' => 'required|string',
        ]);

        if ($validation->fails()) {
            return redirect()
                ->route('admin.user.create')
                ->with('error', $validation->errors()->first())
                ->withInput();
        }

        if (!Hash::check($request->current_admin_password, Auth::user()->password)) {
            return redirect()
                ->route('admin.user.create')
                ->with('error', 'Current password is incorrect. Account was not created.')
                ->withInput();
        }


        /* Upload Avatar */
        $avatar = 'user.png';

        if ($request->hasFile('avatar')) {

            $avatar = $this->uploadImage($request->file('avatar'), null, 'uploads/avatar', true, 150, 150, 'avatar_' . time());
        }

        $createdAdminPassword = $request->password;

        /* Create User */
        $user = User::create([

            'name'     => $request->name,
            'email'    => $request->email,
            'username' => $request->username,
            'phone'    => $request->phone,

            'password' => Hash::make($createdAdminPassword),

            'avatar'   => $avatar,

            'status'   => 'active',
        ]);



        /* Assign Role */
        $user->assignRole('admin');

        /* Get Company Info */
        $company = CompanySetting::first();


        /* Login URL Based On Role */
        $loginUrl = url('/login');


        /* Send Welcome Email */
        Mail::to($user->email)->send(
            new WelcomeUserMail(
                $user,
                $createdAdminPassword,
                $company,
                $loginUrl
            )
        );




        return redirect()
            ->route('admin.user.lists')
            ->with('success', 'Admin created successfully');
    }

    // Index
    public function index()
    {
        $allUsers = User::all();

        $guestUsersCount = User::query()->where('provider', 'guest')->count();
        $adminUsersCount = User::query()->whereHas('roles', function ($q) {
            $q->where('name', 'admin');
        })->count();

        $normalUsersCount = User::query()
            ->where('provider', '!=', 'guest')
            ->whereDoesntHave('roles', function ($q) {
                $q->where('name', 'admin');
            })
            ->count();

        return view('backend.layouts.user_management.index', compact(
            'allUsers',
            'guestUsersCount',
            'normalUsersCount',
            'adminUsersCount'
        ));
    }

    // Show
    public function show($id)
    {
        $user = User::with('roles')->findOrFail($id);

        $episodesWatched = WatchHistory::query()
            ->where('user_id', $user->id)
            ->whereNotNull('episode_id')
            ->count();

        $seriesStarted = WatchHistory::query()
            ->where('user_id', $user->id)
            ->where('progress', '>', 0)
            ->distinct('content_id')
            ->count('content_id');

        $totalWatchSeconds = (int) WatchHistory::query()
            ->where('user_id', $user->id)
            ->sum('progress');

        $favoritesCount = Favorite::query()
            ->where('user_id', $user->id)
            ->count();

        $subscription = Subscription::query()
            ->with('plan')
            ->where('user_id', $user->id)
            ->latest('id')
            ->first();

        $activeSubscription = Subscription::query()
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->where('end_date', '>=', now())
            ->latest('id')
            ->first();

        $invitedPeopleCount = UserReferralUsage::query()
            ->where('referrer_user_id', $user->id)
            ->count();

        $usedReferralCount = UserReferralUsage::query()
            ->where('user_id', $user->id)
            ->count();

        $coinsEarned = (int) CoinTransaction::query()
            ->where('user_id', $user->id)
            ->where('type', 'earn')
            ->sum('amount');

        $coinsSpent = (int) CoinTransaction::query()
            ->where('user_id', $user->id)
            ->where('type', 'spend')
            ->sum('amount');

        $recentWatchHistory = WatchHistory::query()
            ->with(['content:id,title', 'episode:id,title,episode_number'])
            ->where('user_id', $user->id)
            ->orderByDesc('last_watched')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        $recentCoinTransactions = CoinTransaction::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->limit(5)
            ->get();

        $recentInvites = UserReferralUsage::query()
            ->with('user:id,name,email')
            ->where('referrer_user_id', $user->id)
            ->latest('id')
            ->limit(5)
            ->get();

        $membershipDurationDays = Carbon::parse($user->created_at)->diffInDays(now());

        $profileStats = [
            'coins_balance' => (int) ($user->coins ?? 0),
            'coins_earned' => $coinsEarned,
            'coins_spent' => $coinsSpent,
            'episodes_watched' => $episodesWatched,
            'series_started' => $seriesStarted,
            'total_watch_seconds' => $totalWatchSeconds,
            'favorites_count' => $favoritesCount,
            'invited_people_count' => $invitedPeopleCount,
            'used_referral_count' => $usedReferralCount,
            'membership_days' => $membershipDurationDays,
            'last_login_at' => $user->last_login_at,
            'is_subscribed' => (bool) $activeSubscription,
            'latest_subscription' => $subscription,
            'active_subscription' => $activeSubscription,
        ];

        return view('backend.layouts.user_management.show', compact(
            'user',
            'profileStats',
            'recentWatchHistory',
            'recentCoinTransactions',
            'recentInvites'
        ));
    }

    public function edit($id)
    {
        $user = User::with('roles')->findOrFail($id);
        $roles = Role::all();

        return view('backend.layouts.user_management.edit', compact('user', 'roles'));
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);


        $validation = Validator::make($request->all(), [

            'name'     => 'required|max:100',
            'email'    => 'required|email|unique:users,email,' . $user->id,
            'username' => 'nullable|unique:users,username,' . $user->id,

            'phone'    => 'nullable|max:20',

            'avatar'   => 'nullable|image|max:2048',

            'role'     => 'required|exists:roles,name',

            'password' => 'nullable|min:6|confirmed',
        ]);


        if ($validation->fails()) {
            return back()
                ->with('error', $validation->errors()->first())
                ->withInput();
        }


        /* Upload Avatar */
        if ($request->hasFile('avatar')) {

            if ($user->avatar && file_exists(public_path($user->avatar))) {
                unlink(public_path($user->avatar));
            }

            $user->avatar = $this->uploadImage(
                $request->file('avatar'),
                null,
                'uploads/avatar',
                true,
                150,
                150,
                'avatar_' . time()
            );
        }


        /* Update Data */

        $user->name     = $request->name;
        $user->email    = $request->email;
        $user->username = $request->username;
        $user->phone    = $request->phone;

        $user->address  = $request->address;
        $user->location = $request->location;
        $user->title    = $request->title;

        $user->status   = $request->status;

        $user->provider_status = $request->provider_status ?? $user->provider_status;

        $user->reason = $request->reason;


        /* Update Password (Optional) */

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }


        $user->save();


        /* Update Role */

        $user->syncRoles([$request->role]);


        return redirect()
            ->route('admin.user.lists')
            ->with('success', 'User updated successfully');
    }

    public function updateUserStatus(Request $request, User $user)
    {
        $validated = $request->validate([
            'status' => 'required|in:active,inactive,banned',
            'current_password' => 'required|string',
        ]);

        if (!Hash::check($validated['current_password'], Auth::user()->password)) {
            return back()->with('error', 'Current password is incorrect. User status was not changed.');
        }

        if ($user->hasRole('admin') && $validated['status'] === 'banned') {
            return back()->with('error', 'Admin users cannot be blocked from this panel.');
        }

        if ((int) $user->id === (int) Auth::id() && $validated['status'] === 'banned') {
            return back()->with('error', 'You cannot block your own account.');
        }

        $user->status = $validated['status'];
        $user->save();

        $statusMessage = $validated['status'] === 'banned'
            ? 'User has been blocked successfully.'
            : 'User status updated successfully.';

        return back()->with('success', $statusMessage);
    }
}
