@extends('backend.master')

@section('page_title', 'User Details - ' . $user->name)

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0">User Details</h5>
                <a href="{{ route('app-user.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="ti ti-arrow-left me-1"></i>Back to Users
                </a>
            </div>
        </div>

        <!-- User Profile Card -->
        <div class="col-md-4 col-lg-3">
            <div class="card">
                <div class="card-body text-center">
                    <img src="{{ $user->profile_photo_path ? asset($user->profile_photo_path) : asset('user.png') }}"
                        alt="user-image" class="rounded-circle mb-3 avatar-lg" />
                    <h5 class="mb-1">{{ $user->name }}</h5>
                    <p class="text-muted mb-3">{{ $user->email }}</p>

                    <div class="d-flex justify-content-center gap-2 mb-3">
                        @if($user->status === 'active')
                            <span class="badge bg-success-subtle text-success">Active</span>
                        @elseif($user->status === 'banned')
                            <span class="badge bg-danger-subtle text-danger">Blocked</span>
                        @else
                            <span class="badge bg-warning-subtle text-warning">Inactive</span>
                        @endif

                        @if($user->provider === 'guest')
                            <span class="badge bg-info-subtle text-info">Guest</span>
                        @else
                            <span class="badge bg-success-subtle text-success">{{ ucfirst($user->provider) }}</span>
                        @endif
                    </div>

                    <div class="list-group list-group-flush">
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <span class="text-muted">User ID:</span>
                            <span class="fw-semibold">{{ $user->id }}</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <span class="text-muted">Joined:</span>
                            <span class="fw-semibold">{{ $user->created_at->format('d M Y') }}</span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <span class="text-muted">Last Login:</span>
                            <span class="fw-semibold">
                                @if($user->last_login_at)
                                    {{ $user->last_login_at->diffForHumans() }}
                                @else
                                    Never
                                @endif
                            </span>
                        </div>
                    </div>

                    <div class="mt-3">
                        @if($user->status === 'banned')
                            <button type="button" class="btn btn-sm btn-success w-100 js-user-unblock"
                                data-url="{{ route('app-user.status.update', $user->id) }}"
                                data-status="active"
                                data-user-name="{{ $user->name }}">
                                <i class="ti ti-user-check me-1"></i>Unblock User
                            </button>
                        @else
                            <button type="button" class="btn btn-sm btn-danger w-100 js-user-block"
                                data-url="{{ route('app-user.status.update', $user->id) }}"
                                data-status="banned"
                                data-user-name="{{ $user->name }}">
                                <i class="ti ti-user-x me-1"></i>Block User
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="col-md-8 col-lg-9">
            <div class="row">
                <!-- Coins Card -->
                <div class="col-md-6 col-lg-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="text-muted mb-1">Total Coins</h6>
                                    <h3 class="mb-0">{{ $coins }}</h3>
                                </div>
                                <div class="avatar-sm bg-warning-subtle rounded d-flex align-items-center justify-content-center">
                                    <i class="ti ti-coin fs-24 text-warning"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Subscription Card -->
                <div class="col-md-6 col-lg-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="text-muted mb-1">Subscription</h6>
                                    @if($subscription)
                                        <h5 class="mb-0">
                                            <span class="badge bg-success-subtle text-success">Active</span>
                                        </h5>
                                        <small class="text-muted">Expires: {{ $subscription->expires_at->format('d M Y') }}</small>
                                    @else
                                        <h5 class="mb-0">
                                            <span class="badge bg-warning-subtle text-warning">Inactive</span>
                                        </h5>
                                    @endif
                                </div>
                                <div class="avatar-sm bg-success-subtle rounded d-flex align-items-center justify-content-center">
                                    <i class="ti ti-crown fs-24 text-success"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Referrals Card -->
                <div class="col-md-6 col-lg-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="text-muted mb-1">Referrals</h6>
                                    <h3 class="mb-0">{{ $referrals }}</h3>
                                    <small class="text-muted">People invited</small>
                                </div>
                                <div class="avatar-sm bg-info-subtle rounded d-flex align-items-center justify-content-center">
                                    <i class="ti ti-users fs-24 text-info"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Series Watched Card -->
                <div class="col-md-6 col-lg-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="text-muted mb-1">Series Watched</h6>
                                    <h3 class="mb-0">{{ $seriesWatched }}</h3>
                                </div>
                                <div class="avatar-sm bg-primary-subtle rounded d-flex align-items-center justify-content-center">
                                    <i class="ti ti-eye fs-24 text-primary"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Series Liked Card -->
                <div class="col-md-6 col-lg-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="text-muted mb-1">Series Liked</h6>
                                    <h3 class="mb-0">{{ $seriesLiked }}</h3>
                                </div>
                                <div class="avatar-sm bg-danger-subtle rounded d-flex align-items-center justify-content-center">
                                    <i class="ti ti-heart fs-24 text-danger"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Series Saved Card -->
                <div class="col-md-6 col-lg-4">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="text-muted mb-1">Series Saved</h6>
                                    <h3 class="mb-0">{{ $seriesSaved }}</h3>
                                </div>
                                <div class="avatar-sm bg-secondary-subtle rounded d-flex align-items-center justify-content-center">
                                    <i class="ti ti-bookmark fs-24 text-secondary"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Account Information -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Account Information</h6>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <div class="list-group-item">
                            <span class="text-muted">Phone:</span>
                            <p class="mb-0 fw-semibold">{{ $user->phone ?? 'Not provided' }}</p>
                        </div>
                        <div class="list-group-item">
                            <span class="text-muted">Provider:</span>
                            <p class="mb-0 fw-semibold">{{ ucfirst($user->provider) }}</p>
                        </div>
                        <div class="list-group-item">
                            <span class="text-muted">Email Verified:</span>
                            <p class="mb-0">
                                @if($user->email_verified_at)
                                    <span class="badge bg-success-subtle text-success">Yes</span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning">No</span>
                                @endif
                            </p>
                        </div>
                        <div class="list-group-item">
                            <span class="text-muted">Referral Code:</span>
                            <p class="mb-0 fw-semibold">{{ $user->referral_code ?? 'N/A' }}</p>
                        </div>
                        <div class="list-group-item">
                            <span class="text-muted">Account Status:</span>
                            <p class="mb-0">
                                @if($user->status === 'active')
                                    <span class="badge bg-success-subtle text-success">Active</span>
                                @elseif($user->status === 'banned')
                                    <span class="badge bg-danger-subtle text-danger">Blocked</span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning">Inactive</span>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activities -->
        <div class="col-md-8">
            <div class="row">
                <!-- Recently Watched -->
                @if($recentWatched->count() > 0)
                    <div class="col-md-6 col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Recently Watched</h6>
                            </div>
                            <div class="card-body p-0">
                                <div class="list-group list-group-flush">
                                    @foreach($recentWatched as $watch)
                                        <div class="list-group-item px-4 py-3">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h6 class="mb-1">Episode {{ $watch->episode_id }}</h6>
                                                    <small class="text-muted">{{ \Carbon\Carbon::parse($watch->created_at)->diffForHumans() }}</small>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Recently Liked -->
                @if($recentLiked->count() > 0)
                    <div class="col-md-6 col-lg-6">
                        <div class="card">
                            <div class="card-header">
                                <h6 class="mb-0">Recently Liked</h6>
                            </div>
                            <div class="card-body p-0">
                                <div class="list-group list-group-flush">
                                    @foreach($recentLiked as $like)
                                        <div class="list-group-item px-4 py-3">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h6 class="mb-1">Episode {{ $like->episode_id }}</h6>
                                                    <small class="text-muted">{{ $like->created_at->diffForHumans() }}</small>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function() {
            // Block User
            $(document).on('click', '.js-user-block', function() {
                const url = $(this).data('url');
                const status = $(this).data('status');
                const userName = $(this).data('user-name');

                Swal.fire({
                    title: 'Block User?',
                    text: `You are about to block ${userName}. They won't be able to access their account.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Block',
                    cancelButtonText: 'Cancel',
                }).then((result) => {
                    if (!result.isConfirmed) return;

                    $.ajax({
                        url: url,
                        method: 'POST',
                        data: {
                            status: status,
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            Swal.fire({
                                title: 'Success!',
                                text: response.message,
                                icon: 'success'
                            }).then(() => {
                                location.reload();
                            });
                        },
                        error: function(xhr) {
                            Swal.fire({
                                title: 'Error!',
                                text: xhr.responseJSON?.error || 'Something went wrong',
                                icon: 'error'
                            });
                        }
                    });
                });
            });

            // Unblock User
            $(document).on('click', '.js-user-unblock', function() {
                const url = $(this).data('url');
                const status = $(this).data('status');
                const userName = $(this).data('user-name');

                Swal.fire({
                    title: 'Unblock User?',
                    text: `You are about to unblock ${userName}. They will be able to access their account again.`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: 'Unblock',
                    cancelButtonText: 'Cancel',
                }).then((result) => {
                    if (!result.isConfirmed) return;

                    $.ajax({
                        url: url,
                        method: 'POST',
                        data: {
                            status: status,
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            Swal.fire({
                                title: 'Success!',
                                text: response.message,
                                icon: 'success'
                            }).then(() => {
                                location.reload();
                            });
                        },
                        error: function(xhr) {
                            Swal.fire({
                                title: 'Error!',
                                text: xhr.responseJSON?.error || 'Something went wrong',
                                icon: 'error'
                            });
                        }
                    });
                });
            });
        });
    </script>
@endpush
