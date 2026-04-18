@extends('backend.master')

@section('page_title', 'Dashboard')

@push('styles')
    <style>
        .dashboard-shell {
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
        }

        .dashboard-hero {
            position: relative;
            overflow: hidden;
            border-radius: 24px;
            padding: 1.5rem;
            color: #ffffff;
            background: linear-gradient(135deg, #111827 0%, #1f2937 48%, #ff3b5c 120%);
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.16);
        }

        .dashboard-hero::after {
            content: '';
            position: absolute;
            right: -80px;
            bottom: -80px;
            width: 220px;
            height: 220px;
            border-radius: 999px;
            background: radial-gradient(circle, rgba(152, 16, 250, 0.38) 0%, rgba(152, 16, 250, 0) 72%);
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.4rem 0.8rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.12);
            font-size: 0.82rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .hero-title {
            margin: 1rem 0 0.35rem;
            font-size: clamp(1.8rem, 3vw, 2.8rem);
            font-weight: 800;
            line-height: 1.05;
        }

        .hero-subtitle {
            max-width: 52rem;
            margin-bottom: 0;
            color: rgba(255, 255, 255, 0.84);
            font-size: 0.98rem;
        }

        .hero-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-top: 1.25rem;
        }

        .hero-actions .btn {
            border: 0;
            color: #ffffff !important;
            font-weight: 700;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.18);
        }

        .hero-action-primary {
            background: #ff3b5c;
        }

        .hero-action-primary:hover,
        .hero-action-primary:focus {
            background: #e93555;
            color: #ffffff !important;
        }

        .hero-action-secondary {
            background: #9810fa;
        }

        .hero-action-secondary:hover,
        .hero-action-secondary:focus {
            background: #8610db;
            color: #ffffff !important;
        }

        .hero-quick {
            min-width: 260px;
            padding: 1rem 1.1rem;
            border-radius: 20px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.16);
            backdrop-filter: blur(8px);
        }

        .quick-label {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
        }

        .quick-value {
            margin-top: 0.45rem;
            font-size: 1.85rem;
            font-weight: 800;
            line-height: 1.05;
        }

        .quick-note {
            margin: 0.35rem 0 0;
            color: rgba(255, 255, 255, 0.72);
            font-size: 0.92rem;
        }

        .metric-card,
        .panel-card {
            border: 0;
            border-radius: 22px;
            box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
            overflow: hidden;
        }

        .metric-card .card-body {
            padding: 1.15rem;
        }

        .metric-label {
            color: #6b7280;
            font-size: 0.8rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .metric-number {
            margin-top: 0.2rem;
            font-size: 1.85rem;
            font-weight: 800;
            color: #111827;
            line-height: 1.1;
        }

        .metric-meta {
            margin-top: 0.25rem;
            color: #6b7280;
            font-size: 0.92rem;
        }

        .metric-icon {
            width: 50px;
            height: 50px;
            border-radius: 16px;
            display: grid;
            place-items: center;
            font-size: 1.2rem;
            color: #ffffff;
            flex: 0 0 auto;
        }

        .metric-icon.red {
            background: linear-gradient(135deg, #ff3b5c, #ff6f86);
        }

        .metric-icon.purple {
            background: linear-gradient(135deg, #9810fa, #c26cff);
        }

        .metric-icon.dark {
            background: linear-gradient(135deg, #111827, #334155);
        }

        .metric-icon.orange {
            background: linear-gradient(135deg, #f59e0b, #fb7185);
        }

        .panel-header {
            padding: 1.1rem 1.25rem;
            border-bottom: 1px solid rgba(15, 23, 42, 0.06);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
        }

        .panel-title {
            margin: 0;
            font-size: 1.05rem;
            font-weight: 800;
            color: #111827;
        }

        .panel-subtitle {
            margin: 0;
            color: #6b7280;
            font-size: 0.88rem;
        }

        .dashboard-clock-value {
            font-size: clamp(2rem, 5vw, 3.1rem);
            font-weight: 800;
            line-height: 1;
            color: #111827;
        }

        .dashboard-clock-date {
            margin-top: 0.35rem;
            color: #6b7280;
            font-size: 0.95rem;
        }

        .calendar-frame {
            background: linear-gradient(180deg, #ffffff 0%, #fff7fa 100%);
        }

        .calendar-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .calendar-title {
            font-size: 1.1rem;
            font-weight: 800;
            color: #111827;
        }

        .calendar-subtitle {
            color: #6b7280;
            font-size: 0.88rem;
        }

        .calendar-controls {
            display: flex;
            gap: 0.5rem;
        }

        .calendar-controls button {
            width: 38px;
            height: 38px;
            border: 0;
            border-radius: 12px;
            background: #ffffff;
            color: #111827;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.08);
        }

        .calendar-weekdays,
        .calendar-grid {
            display: grid;
            grid-template-columns: repeat(7, minmax(0, 1fr));
            gap: 0.55rem;
        }

        .calendar-weekday {
            text-align: center;
            font-size: 0.76rem;
            font-weight: 800;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #9ca3af;
        }

        .calendar-day {
            min-height: 56px;
            padding: 0.55rem 0.65rem;
            border-radius: 16px;
            border: 1px solid rgba(15, 23, 42, 0.06);
            background: #ffffff;
            display: flex;
            align-items: flex-start;
            justify-content: flex-end;
            font-weight: 700;
            color: #111827;
            overflow: hidden;
        }

        .calendar-day.is-muted {
            color: #cbd5e1;
            background: #fafafa;
        }

        .calendar-day.is-today {
            color: #ffffff;
            border-color: transparent;
            background: linear-gradient(135deg, #ff3b5c 0%, #9810fa 100%);
            box-shadow: 0 12px 22px rgba(255, 59, 92, 0.22);
        }

        .calendar-day.is-today::after {
            content: 'Today';
            position: absolute;
            left: 0.65rem;
            bottom: 0.5rem;
            font-size: 0.72rem;
            font-weight: 700;
            opacity: 0.95;
        }

        .calendar-day {
            position: relative;
        }

        .dashboard-tabs .nav-link {
            border: 0;
            color: #6b7280;
            font-weight: 700;
            border-radius: 999px;
        }

        .dashboard-tabs .nav-link.active {
            background: #ff3b5c;
            color: #ffffff;
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.35rem 0.62rem;
            border-radius: 999px;
            font-size: 0.74rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .status-active {
            background: rgba(34, 197, 94, 0.12);
            color: #166534;
        }

        .status-inactive {
            background: rgba(148, 163, 184, 0.16);
            color: #475569;
        }

        .status-expired {
            background: rgba(245, 158, 11, 0.14);
            color: #92400e;
        }

        .status-cancelled {
            background: rgba(239, 68, 68, 0.12);
            color: #991b1b;
        }

        .status-earn {
            background: rgba(34, 197, 94, 0.12);
            color: #166534;
        }

        .status-spend {
            background: rgba(59, 130, 246, 0.12);
            color: #1d4ed8;
        }

        .compact-note {
            color: #6b7280;
            font-size: 0.88rem;
        }

        .table thead th {
            color: #6b7280;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            border-top: 0;
        }

        @media (max-width: 991.98px) {

            .calendar-grid,
            .calendar-weekdays {
                gap: 0.4rem;
            }

            .calendar-day {
                min-height: 48px;
                border-radius: 14px;
            }
        }
    </style>
@endpush

@section('content')
    <div class="dashboard-shell">
        <div class="dashboard-hero">
            <div class="d-flex flex-column flex-xl-row align-items-xl-center gap-4 position-relative" style="z-index: 1;">
                <div class="flex-grow-1">
                    <span class="hero-badge"><i class="ti ti-sparkles"></i> Admin dashboard</span>
                    <h1 class="hero-title">Hello {{ $greetingName }},</h1>
                    <p class="hero-subtitle">Here is your dashboard overview. This screen is connected to the current
                        database so you can track users, content, episodes, subscriptions, and coin activity without
                        guessing.</p>

                    <div class="hero-actions">
                        <a href="{{ route('admin.user.lists') }}" class="btn btn-sm hero-action-primary">
                            <i class="ti ti-users me-1"></i> Manage Users
                        </a>
                        <a href="{{ route('dynamic.pages') }}" class="btn btn-sm hero-action-secondary">
                            <i class="ti ti-file-text me-1"></i> Open Dynamic Pages
                        </a>
                    </div>
                </div>

                <div class="hero-quick">
                    <div class="quick-label">Quick snapshot</div>
                    <div class="quick-value">{{ number_format($summary['active_users']) }} active users</div>
                    <p class="quick-note">{{ number_format($summary['active_contents']) }} active contents and
                        {{ number_format($summary['active_subscriptions']) }} active subscriptions.</p>
                </div>
            </div>
        </div>

        @php
            $metrics = [
                [
                    'label' => 'Total Users',
                    'value' => $summary['users_total'],
                    'meta' => $summary['active_users'] . ' active users',
                    'icon' => 'ti ti-users',
                    'tone' => 'red',
                ],
                [
                    'label' => 'Contents',
                    'value' => $summary['contents_total'],
                    'meta' => $summary['active_contents'] . ' active contents',
                    'icon' => 'ti ti-device-tv',
                    'tone' => 'purple',
                ],
                [
                    'label' => 'Episodes',
                    'value' => $summary['episodes_total'],
                    'meta' => 'Episode records in the library',
                    'icon' => 'ti ti-player-play',
                    'tone' => 'dark',
                ],
                [
                    'label' => 'Subscriptions',
                    'value' => $summary['subscriptions_total'],
                    'meta' => $summary['active_subscriptions'] . ' active subscriptions',
                    'icon' => 'ti ti-crown',
                    'tone' => 'orange',
                ],
            ];
        @endphp

        <div class="row g-3">
            <div class="col-12 col-xl-8">
                <div class="row g-3">
                    @foreach ($metrics as $metric)
                        <div class="col-12 col-md-6">
                            <div class="card metric-card h-100">
                                <div class="card-body">
                                    <div class="d-flex align-items-center justify-content-between gap-3">
                                        <div>
                                            <div class="metric-label">{{ $metric['label'] }}</div>
                                            <div class="metric-number">{{ number_format($metric['value']) }}</div>
                                            <div class="metric-meta">{{ $metric['meta'] }}</div>
                                        </div>
                                        <div class="metric-icon {{ $metric['tone'] }}">
                                            <i class="{{ $metric['icon'] }}"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="card panel-card mt-3">
                    <div class="panel-header">
                        <div>
                            <h5 class="panel-title">Recent records</h5>
                            <p class="panel-subtitle">A quick look at the latest data coming through the app.</p>
                        </div>
                        <div class="compact-note">Users, content, subscriptions, and coins</div>
                    </div>

                    <div class="card-body p-0">
                        <ul class="nav nav-pills dashboard-tabs px-3 pt-3 gap-2" id="dashboardTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="users-tab" data-bs-toggle="pill"
                                    data-bs-target="#users-pane" type="button" role="tab">Users</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="contents-tab" data-bs-toggle="pill"
                                    data-bs-target="#contents-pane" type="button" role="tab">Contents</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="subscriptions-tab" data-bs-toggle="pill"
                                    data-bs-target="#subscriptions-pane" type="button"
                                    role="tab">Subscriptions</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="coins-tab" data-bs-toggle="pill" data-bs-target="#coins-pane"
                                    type="button" role="tab">Coin transactions</button>
                            </li>
                        </ul>

                        <div class="tab-content p-3" id="dashboardTabsContent">
                            <div class="tab-pane fade show active" id="users-pane" role="tabpanel"
                                aria-labelledby="users-tab">
                                <div class="table-responsive">
                                    <table class="table align-middle mb-0 list-table">
                                        <thead>
                                            <tr>
                                                <th>User</th>
                                                <th>Status</th>
                                                <th>Coins</th>
                                                <th>Last login</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($latestUsers as $user)
                                                @php $userStatus = strtolower($user->status ?? 'inactive'); @endphp
                                                <tr>
                                                    <td>
                                                        <div class="fw-semibold text-dark">
                                                            {{ $user->name ?: $user->username ?: 'Unnamed user' }}</div>
                                                        <div class="text-muted small">{{ $user->email }}</div>
                                                    </td>
                                                    <td>
                                                        <span
                                                            class="status-pill status-{{ $userStatus }}">{{ ucfirst($user->status ?? 'inactive') }}</span>
                                                    </td>
                                                    <td class="fw-semibold">{{ number_format($user->coins ?? 0) }}</td>
                                                    <td class="text-muted">
                                                        {{ $user->last_login_at?->format('M d, Y H:i') ?? 'Never' }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="text-center text-muted py-4">No users found
                                                        yet.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="contents-pane" role="tabpanel" aria-labelledby="contents-tab">
                                <div class="table-responsive">
                                    <table class="table align-middle mb-0 list-table">
                                        <thead>
                                            <tr>
                                                <th>Title</th>
                                                <th>Type</th>
                                                <th>Access</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($latestContents as $content)
                                                @php $contentStatus = $content->is_active ? 'active' : 'inactive'; @endphp
                                                <tr>
                                                    <td class="fw-semibold text-dark">{{ $content->title }}</td>
                                                    <td class="text-muted text-capitalize">{{ $content->type }}</td>
                                                    <td class="text-muted text-capitalize">{{ $content->access_type }}</td>
                                                    <td>
                                                        <span
                                                            class="status-pill status-{{ $contentStatus }}">{{ $content->is_active ? 'Active' : 'Inactive' }}</span>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="text-center text-muted py-4">No content
                                                        records found yet.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="subscriptions-pane" role="tabpanel"
                                aria-labelledby="subscriptions-tab">
                                <div class="table-responsive">
                                    <table class="table align-middle mb-0 list-table">
                                        <thead>
                                            <tr>
                                                <th>User</th>
                                                <th>Plan</th>
                                                <th>Platform</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($latestSubscriptions as $subscription)
                                                @php $subscriptionStatus = strtolower($subscription->status ?? 'active'); @endphp
                                                <tr>
                                                    <td>
                                                        <div class="fw-semibold text-dark">
                                                            {{ $subscription->user?->name ?: 'Unnamed user' }}</div>
                                                        <div class="text-muted small">{{ $subscription->user?->email }}
                                                        </div>
                                                    </td>
                                                    <td class="fw-semibold">{{ $subscription->plan?->name ?: 'No plan' }}
                                                    </td>
                                                    <td class="text-muted text-capitalize">{{ $subscription->platform }}
                                                    </td>
                                                    <td>
                                                        <span
                                                            class="status-pill status-{{ $subscriptionStatus }}">{{ ucfirst($subscription->status ?? 'active') }}</span>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="text-center text-muted py-4">No
                                                        subscriptions found yet.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="coins-pane" role="tabpanel" aria-labelledby="coins-tab">
                                <div class="table-responsive">
                                    <table class="table align-middle mb-0 list-table">
                                        <thead>
                                            <tr>
                                                <th>User</th>
                                                <th>Type</th>
                                                <th>Amount</th>
                                                <th>Source</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($latestTransactions as $transaction)
                                                @php $coinType = strtolower($transaction->type ?? 'earn'); @endphp
                                                <tr>
                                                    <td>
                                                        <div class="fw-semibold text-dark">
                                                            {{ $transaction->user?->name ?: 'Unknown user' }}</div>
                                                        <div class="text-muted small">{{ $transaction->user?->email }}
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span
                                                            class="status-pill status-{{ $coinType }}">{{ ucfirst($transaction->type ?? 'earn') }}</span>
                                                    </td>
                                                    <td class="fw-semibold">{{ number_format($transaction->amount ?? 0) }}
                                                    </td>
                                                    <td class="text-muted">{{ $transaction->source ?: 'N/A' }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="text-center text-muted py-4">No coin
                                                        transactions found yet.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-4">
                <div class="card panel-card mb-3">
                    <div class="panel-header">
                        <div>
                            <h5 class="panel-title">Live clock</h5>
                            <p class="panel-subtitle">Updates in real time.</p>
                        </div>
                        <span class="status-pill status-active"><i class="ti ti-clock"></i> Live</span>
                    </div>
                    <div class="card-body">
                        <div id="dashboard-clock-time" class="dashboard-clock-value">--:--:--</div>
                        <div id="dashboard-clock-date" class="dashboard-clock-date">Loading date...</div>
                    </div>
                </div>

                <div class="card panel-card calendar-frame">
                    <div class="panel-header">
                        <div>
                            <h5 class="panel-title mb-1">Calendar</h5>
                            <p id="dashboard-calendar-subtitle" class="panel-subtitle">Current month view</p>
                        </div>
                        <div class="calendar-controls">
                            <button type="button" id="dashboard-calendar-prev" aria-label="Previous month">
                                <i class="ti ti-chevron-left"></i>
                            </button>
                            <button type="button" id="dashboard-calendar-next" aria-label="Next month">
                                <i class="ti ti-chevron-right"></i>
                            </button>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="calendar-toolbar">
                            <div>
                                <div id="dashboard-calendar-label" class="calendar-title">Month</div>
                                <div class="calendar-subtitle">Monday-first layout</div>
                            </div>
                            <span class="status-pill status-active">Today</span>
                        </div>

                        <div class="calendar-weekdays mb-2">
                            <div class="calendar-weekday">Mon</div>
                            <div class="calendar-weekday">Tue</div>
                            <div class="calendar-weekday">Wed</div>
                            <div class="calendar-weekday">Thu</div>
                            <div class="calendar-weekday">Fri</div>
                            <div class="calendar-weekday">Sat</div>
                            <div class="calendar-weekday">Sun</div>
                        </div>

                        <div id="dashboard-calendar-grid" class="calendar-grid"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function() {
            const clockTime = document.getElementById('dashboard-clock-time');
            const clockDate = document.getElementById('dashboard-clock-date');
            const calendarGrid = document.getElementById('dashboard-calendar-grid');
            const calendarLabel = document.getElementById('dashboard-calendar-label');
            const calendarSubtitle = document.getElementById('dashboard-calendar-subtitle');
            const prevButton = document.getElementById('dashboard-calendar-prev');
            const nextButton = document.getElementById('dashboard-calendar-next');

            if (!clockTime || !clockDate || !calendarGrid || !calendarLabel || !calendarSubtitle || !prevButton || !
                nextButton) {
                return;
            }

            let cursor = new Date();
            cursor.setDate(1);

            const renderClock = () => {
                const now = new Date();
                clockTime.textContent = now.toLocaleTimeString([], {
                    hour: '2-digit',
                    minute: '2-digit',
                    second: '2-digit',
                });

                clockDate.textContent = now.toLocaleDateString([], {
                    weekday: 'long',
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                });
            };

            const renderCalendar = () => {
                const year = cursor.getFullYear();
                const month = cursor.getMonth();
                const firstDay = new Date(year, month, 1);
                const daysInMonth = new Date(year, month + 1, 0).getDate();
                const previousMonthDays = new Date(year, month, 0).getDate();
                const startOffset = (firstDay.getDay() + 6) % 7;
                const today = new Date();

                calendarLabel.textContent = cursor.toLocaleDateString([], {
                    month: 'long',
                    year: 'numeric',
                });

                calendarSubtitle.textContent = `${daysInMonth} days this month`;
                calendarGrid.innerHTML = '';

                for (let index = 0; index < 42; index += 1) {
                    const dayCell = document.createElement('div');
                    dayCell.className = 'calendar-day';

                    const dayNumber = index - startOffset + 1;

                    if (index < startOffset) {
                        dayCell.classList.add('is-muted');
                        dayCell.textContent = previousMonthDays - startOffset + index + 1;
                    } else if (dayNumber > daysInMonth) {
                        dayCell.classList.add('is-muted');
                        dayCell.textContent = dayNumber - daysInMonth;
                    } else {
                        dayCell.textContent = dayNumber;

                        if (
                            year === today.getFullYear() &&
                            month === today.getMonth() &&
                            dayNumber === today.getDate()
                        ) {
                            dayCell.classList.add('is-today');
                        }
                    }

                    calendarGrid.appendChild(dayCell);
                }
            };

            prevButton.addEventListener('click', () => {
                cursor = new Date(cursor.getFullYear(), cursor.getMonth() - 1, 1);
                renderCalendar();
            });

            nextButton.addEventListener('click', () => {
                cursor = new Date(cursor.getFullYear(), cursor.getMonth() + 1, 1);
                renderCalendar();
            });

            renderClock();
            renderCalendar();
            setInterval(renderClock, 1000);
        })();
    </script>
@endpush
