@extends('backend.master')

@section('page_title', 'Mobile App Users Management')

@push('styles')
    <!-- Datatables css -->
    <link href="{{ asset('backend') }}/assets/plugins/datatables/responsive.bootstrap5.min.css" rel="stylesheet"
        type="text/css" />
@endpush

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header border-light justify-content-between">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <span class="me-2 fw-semibold">Filter By:</span>

                        <!-- User Type Filter -->
                        <div class="app-search">
                            <select data-table-filter="user-type" class="form-select form-control my-1 my-md-0">
                                <option value="All">All Users</option>
                                <option value="guest">Guest Users</option>
                                <option value="social">Social Users</option>
                            </select>
                            <i class="ti ti-users app-search-icon text-muted"></i>
                        </div>

                        <!-- Status Filter -->
                        <div class="app-search">
                            <select data-table-filter="status" class="form-select form-control my-1 my-md-0">
                                <option value="All">All Status</option>
                                <option value="active">Active</option>
                                <option value="banned">Blocked</option>
                                <option value="inactive">Inactive</option>
                            </select>
                            <i class="ti ti-list app-search-icon text-muted"></i>
                        </div>

                    </div>

                </div>

                <div class="card-header border-light pt-0">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <button type="button" class="btn btn-sm btn-outline-primary user-type-quick" data-user-type="All">
                            All Users
                            <span class="badge bg-primary-subtle text-primary ms-1">{{ $allUsers->count() }}</span>
                        </button>

                        <button type="button" class="btn btn-sm btn-outline-info user-type-quick"
                            data-user-type="social">
                            Social Users
                            <span class="badge bg-info-subtle text-info ms-1">{{ $allUsers->where('provider', '!=', 'guest')->count() }}</span>
                        </button>

                        <button type="button" class="btn btn-sm btn-outline-secondary user-type-quick"
                            data-user-type="guest">
                            Guest Users
                            <span class="badge bg-secondary-subtle text-secondary ms-1">{{ $guestUsersCount }}</span>
                        </button>

                        <button type="button" class="btn btn-sm btn-outline-success status-type-quick" data-status="active">
                            Active
                            <span class="badge bg-success-subtle text-success ms-1">{{ $activeUsersCount }}</span>
                        </button>

                        <button type="button" class="btn btn-sm btn-outline-danger status-type-quick" data-status="banned">
                            Blocked
                            <span class="badge bg-danger-subtle text-danger ms-1">{{ $blockedUsersCount }}</span>
                        </button>
                    </div>
                </div>

                <div class="card-body pt-0">
                    <div class="table-responsive mt-3">
                        <table id="appUserTable" data-tables="basic"
                            class="table table-custom dt-responsive align-middle mb-0 table-centered table-select table-hover w-100 mb-0 p-4">
                            <thead class="bg-light align-middle bg-opacity-25 thead-sm text-nowrap">
                                <tr class="text-uppercase fs-xxs">
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>User Type</th>
                                    <th>Subscription</th>
                                    <th>Coins</th>
                                    <th>Watched</th>
                                    <th>Liked</th>
                                    <th>Saved</th>
                                    <th>Referrals</th>
                                    <th>Joined Date</th>
                                    <th data-table-sort data-column="status">Status</th>
                                    <th class="text-center" style="width: 1%">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="text-nowrap">
                                <!-- Yajra Data Here -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card-footer border-0">
                <div class="d-flex justify-content-between align-items-center">
                    <div data-table-pagination-info="Mobile App Users"></div>
                    <div data-table-pagination></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <!-- Jquery for Datatables-->
    <script src="{{ asset('backend') }}/assets/plugins/jquery/jquery.min.js"></script>

    <!-- Datatables js -->
    <script src="{{ asset('backend') }}/assets/plugins/datatables/dataTables.min.js"></script>
    <script src="{{ asset('backend') }}/assets/plugins/datatables/dataTables.bootstrap5.min.js"></script>
    <script src="{{ asset('backend') }}/assets/plugins/datatables/dataTables.responsive.min.js"></script>
    <script src="{{ asset('backend') }}/assets/plugins/datatables/responsive.bootstrap5.min.js"></script>

    <!-- Page js -->
    <script src="assets/js/pages/datatables-basic.js"></script>

    <script>
        $(function() {

            let table = $('#appUserTable').DataTable({

                processing: true,
                serverSide: true,
                responsive: true,

                ajax: {
                    url: "{{ route('app-user.data') }}",
                    data: function(d) {
                        d.user_type = $('[data-table-filter="user-type"]').val();
                        d.status = $('[data-table-filter="status"]').val();
                    }
                },

                columns: [

                    {
                        data: null,
                        name: 'index',
                        orderable: false,
                        searchable: false,
                        render: function(data, type, row, meta) {
                            return meta.row + meta.settings._iDisplayStart + 1;
                        }
                    },

                    {
                        data: 'name',
                        name: 'name'
                    },

                    {
                        data: 'email',
                        name: 'email'
                    },

                    {
                        data: 'user_type',
                        name: 'user_type',
                        orderable: false,
                        searchable: false
                    },

                    {
                        data: 'subscription',
                        name: 'subscription',
                        orderable: false,
                        searchable: false
                    },

                    {
                        data: 'coins',
                        name: 'coins',
                        orderable: false,
                        searchable: false
                    },

                    {
                        data: 'series_watched',
                        name: 'series_watched',
                        orderable: false,
                        searchable: false
                    },

                    {
                        data: 'series_liked',
                        name: 'series_liked',
                        orderable: false,
                        searchable: false
                    },

                    {
                        data: 'series_saved',
                        name: 'series_saved',
                        orderable: false,
                        searchable: false
                    },

                    {
                        data: 'referrals',
                        name: 'referrals',
                        orderable: false,
                        searchable: false
                    },

                    {
                        data: 'joined',
                        name: 'joined'
                    },

                    {
                        data: 'status_badge',
                        name: 'status',
                        orderable: false,
                        searchable: false
                    },

                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    }

                ],

                order: [
                    [0, 'desc']
                ],
            });


            // User Type Filter
            $('[data-table-filter="user-type"]').on('change', function() {
                table.draw();
            });

            // Status Filter
            $('[data-table-filter="status"]').on('change', function() {
                table.draw();
            });

            // User Type Quick Filter
            $('.user-type-quick').on('click', function(e) {
                e.preventDefault();
                const userType = $(this).data('user-type');
                $('[data-table-filter="user-type"]').val(userType).change();
            });

            // Status Quick Filter
            $('.status-type-quick').on('click', function(e) {
                e.preventDefault();
                const status = $(this).data('status');
                $('[data-table-filter="status"]').val(status).change();
            });

            // Block/Unblock User
            $(document).on('click', '.js-app-user-status-toggle', function() {
                const url = $(this).data('url');
                const status = $(this).data('status');
                const actionLabel = $(this).data('action-label');
                const userName = $(this).data('user-name');

                Swal.fire({
                    title: `${actionLabel} User?`,
                    text: `You are about to ${actionLabel.toLowerCase()} ${userName}.`,
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: actionLabel,
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
                            });
                            table.draw();
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
