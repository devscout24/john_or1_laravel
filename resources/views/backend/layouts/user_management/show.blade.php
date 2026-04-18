@extends('backend.master')

@section('page_title', 'View User')

@push('styles')
    <style>
        .user-detail-card {
            border: 0;
            border-radius: 16px;
            box-shadow: 0 8px 24px rgba(15, 23, 42, 0.08);
        }

        .metric-tile {
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 14px;
            padding: 1rem;
            background: #fff;
            height: 100%;
        }

        .metric-label {
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            color: #6b7280;
            text-transform: uppercase;
        }

        .metric-value {
            font-size: 1.4rem;
            font-weight: 800;
            color: #111827;
            line-height: 1.2;
        }

        .metric-note {
            color: #6b7280;
            font-size: 0.85rem;
        }

        .profile-meta-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
            color: #374151;
            font-size: 0.92rem;
        }

        .profile-meta-item i {
            color: #ff3b5c;
            font-size: 1rem;
        }

        .section-title {
            font-size: 1rem;
            font-weight: 700;
            margin-bottom: 0.75rem;
            color: #111827;
        }
    </style>
@endpush

@section('content')
    <div class="row g-3">
        <div class="col-12 col-xl-4">
            <div class="card user-detail-card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <img src="{{ asset($user->avatar == 'user.png' ? 'admin.png' : $user->avatar) }}"
                            class="rounded-circle" width="78" height="78" alt="Avatar">
                        <div>
                            <h5 class="mb-1">{{ $user->name ?: 'N/A' }}</h5>
                            <div class="d-flex flex-wrap gap-2">
                                <span class="badge text-bg-light">{{ $user->getRoleNames()->first() ?? 'No Role' }}</span>
                                <span class="badge text-bg-light text-capitalize">{{ $user->provider ?? 'email' }}</span>
                            </div>
                        </div>
                    </div>

                    <div class="profile-meta-item"><i class="ti ti-mail"></i> {{ $user->email }}</div>
                    <div class="profile-meta-item"><i class="ti ti-user"></i> {{ $user->username ?: 'No username' }}</div>
                    <div class="profile-meta-item"><i class="ti ti-phone"></i> {{ $user->phone ?: 'No phone' }}</div>
                    <div class="profile-meta-item"><i class="ti ti-map-pin"></i> {{ $user->address ?: 'No address' }}</div>
                    <div class="profile-meta-item"><i class="ti ti-map"></i> {{ $user->location ?: 'No location' }}</div>
                    <div class="profile-meta-item"><i class="ti ti-language"></i> {{ strtoupper($user->language ?? 'N/A') }}
                    </div>
                    <div class="profile-meta-item"><i class="ti ti-calendar"></i> Joined
                        {{ $user->created_at?->format('d M Y') }}</div>
                    <div class="profile-meta-item"><i class="ti ti-clock"></i> Last login
                        {{ $profileStats['last_login_at']?->format('d M Y H:i') ?? 'Never' }}</div>

                    <div class="mt-3">
                        @if ($user->status == 'active')
                            <span class="badge bg-success-subtle text-success">Account Active</span>
                        @elseif($user->status == 'inactive')
                            <span class="badge bg-warning-subtle text-warning">Account Inactive</span>
                        @else
                            <span class="badge bg-danger-subtle text-danger">Account Blocked</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-8">
            <div class="row g-3">
                <div class="col-md-4">
                    <div class="metric-tile">
                        <div class="metric-label">Coins Balance</div>
                        <div class="metric-value">{{ number_format($profileStats['coins_balance']) }}</div>
                        <div class="metric-note">Current available coins</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="metric-tile">
                        <div class="metric-label">Episodes Watched</div>
                        <div class="metric-value">{{ number_format($profileStats['episodes_watched']) }}</div>
                        <div class="metric-note">Episode watch history records</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="metric-tile">
                        <div class="metric-label">Series Started</div>
                        <div class="metric-value">{{ number_format($profileStats['series_started']) }}</div>
                        <div class="metric-note">Distinct contents viewed</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="metric-tile">
                        <div class="metric-label">Favorites</div>
                        <div class="metric-value">{{ number_format($profileStats['favorites_count']) }}</div>
                        <div class="metric-note">Favorite contents count</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="metric-tile">
                        <div class="metric-label">People Invited</div>
                        <div class="metric-value">{{ number_format($profileStats['invited_people_count']) }}</div>
                        <div class="metric-note">Referral uses by this user</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="metric-tile">
                        <div class="metric-label">Membership Days</div>
                        <div class="metric-value">{{ number_format($profileStats['membership_days']) }}</div>
                        <div class="metric-note">Days since joined</div>
                    </div>
                </div>
            </div>

            <div class="card user-detail-card mt-3">
                <div class="card-body">
                    <h6 class="section-title">Subscription & Referral Overview</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="metric-tile">
                                <div class="metric-label">Subscription Status</div>
                                <div class="metric-value" style="font-size:1.1rem;">
                                    @if ($profileStats['is_subscribed'])
                                        <span class="badge bg-success-subtle text-success">Subscribed (Active)</span>
                                    @else
                                        <span class="badge bg-warning-subtle text-warning">Not Subscribed</span>
                                    @endif
                                </div>
                                @if ($profileStats['latest_subscription'])
                                    <div class="metric-note mt-1">
                                        Plan: {{ $profileStats['latest_subscription']->plan?->name ?: 'N/A' }}<br>
                                        Platform: {{ ucfirst($profileStats['latest_subscription']->platform) }}<br>
                                        End:
                                        {{ $profileStats['latest_subscription']->end_date?->format('d M Y') ?: 'N/A' }}
                                    </div>
                                @else
                                    <div class="metric-note mt-1">No subscription records found.</div>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="metric-tile">
                                <div class="metric-label">Coins Ledger</div>
                                <div class="metric-note mt-1">
                                    Earned: <strong>{{ number_format($profileStats['coins_earned']) }}</strong><br>
                                    Spent: <strong>{{ number_format($profileStats['coins_spent']) }}</strong><br>
                                    Referral Used:
                                    <strong>{{ number_format($profileStats['used_referral_count']) }}</strong><br>
                                    Watch Time:
                                    <strong>{{ gmdate('H:i:s', max(0, (int) $profileStats['total_watch_seconds'])) }}</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card user-detail-card">
                <div class="card-body">
                    <h6 class="section-title">Recent Watch History</h6>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Content</th>
                                    <th>Episode</th>
                                    <th>Progress</th>
                                    <th>Last Watched</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentWatchHistory as $item)
                                    <tr>
                                        <td>{{ $item->content?->title ?: 'N/A' }}</td>
                                        <td>{{ $item->episode?->title ?: 'N/A' }}</td>
                                        <td>{{ (int) $item->progress }} sec</td>
                                        <td>{{ $item->last_watched?->format('d M Y H:i') ?: 'N/A' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-3">No watch history yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-6">
            <div class="card user-detail-card">
                <div class="card-body">
                    <h6 class="section-title">Recent Coin Transactions</h6>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Amount</th>
                                    <th>Source</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentCoinTransactions as $tx)
                                    <tr>
                                        <td class="text-capitalize">{{ $tx->type }}</td>
                                        <td>{{ (int) $tx->amount }}</td>
                                        <td>{{ $tx->source ?: 'N/A' }}</td>
                                        <td>{{ $tx->created_at?->format('d M Y H:i') ?: 'N/A' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-3">No coin transactions yet.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12">
            <div class="card user-detail-card">
                <div class="card-body">
                    <h6 class="section-title">Recent Invites (Referral)</h6>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Invited User</th>
                                    <th>Email</th>
                                    <th>Referral Code</th>
                                    <th>Used On</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($recentInvites as $invite)
                                    <tr>
                                        <td>{{ $invite->user?->name ?: 'N/A' }}</td>
                                        <td>{{ $invite->user?->email ?: 'N/A' }}</td>
                                        <td>{{ $invite->referral_code }}</td>
                                        <td>{{ $invite->used_on?->format('d M Y') ?: 'N/A' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-3">No invited users yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
