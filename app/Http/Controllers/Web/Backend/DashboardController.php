<?php

namespace App\Http\Controllers\Web\Backend;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\CoinTransaction;
use App\Models\Content;
use App\Models\DynamicPage;
use App\Models\Episode;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    // Dashboard
    public function index(Request $request)
    {
        $currentUser = Auth::user();
        $greetingName = $currentUser?->name ?: $currentUser?->username ?: $currentUser?->email ?: 'Admin';

        $summary = [
            'users_total' => User::count(),
            'active_users' => User::where('status', 'active')->count(),
            'contents_total' => Content::count(),
            'active_contents' => Content::where('is_active', true)->count(),
            'episodes_total' => Episode::count(),
            'subscriptions_total' => Subscription::count(),
            'active_subscriptions' => Subscription::where('status', 'active')->count(),
            'coin_transactions_total' => CoinTransaction::count(),
            'plans_total' => Plan::count(),
            'categories_total' => Category::count(),
            'dynamic_pages_total' => DynamicPage::count(),
        ];

        $latestUsers = User::query()
            ->select('id', 'name', 'username', 'email', 'status', 'coins', 'last_login_at', 'created_at')
            ->latest()
            ->limit(5)
            ->get();

        $latestContents = Content::query()
            ->select('id', 'title', 'type', 'access_type', 'is_active', 'created_at')
            ->latest()
            ->limit(5)
            ->get();

        $latestSubscriptions = Subscription::query()
            ->with([
                'user:id,name,email',
                'plan:id,name',
            ])
            ->latest()
            ->limit(5)
            ->get();

        $latestTransactions = CoinTransaction::query()
            ->with('user:id,name,email')
            ->latest()
            ->limit(5)
            ->get();

        return view('backend.layouts.index', compact(
            'greetingName',
            'summary',
            'latestUsers',
            'latestContents',
            'latestSubscriptions',
            'latestTransactions'
        ));
    }
}
