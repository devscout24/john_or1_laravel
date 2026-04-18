@extends('backend.master')

@section('page_title', 'User Management')

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
                                <option value="normal">Normal Users</option>
                                <option value="admin">Admin Users</option>
                            </select>
                            <i class="ti ti-users app-search-icon text-muted"></i>
                        </div>

                        <!-- Priority Filter -->
                        <div class="app-search">
                            <select data-table-filter="priority" class="form-select form-control my-1 my-md-0">
                                <option value="All">By Status</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="banned">Banned</option>
                            </select>
                            <i class="ti ti-list app-search-icon text-muted"></i>
                        </div>

                    </div>

                </div>

                <div class="card-header border-light pt-0">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <button type="button" class="btn btn-sm btn-outline-primary user-type-quick" data-user-type="All">
                            All
                            <span class="badge bg-primary-subtle text-primary ms-1">{{ $allUsers->count() }}</span>
                        </button>

                        <button type="button" class="btn btn-sm btn-outline-secondary user-type-quick"
                            data-user-type="guest">
                            Guest Users
                            <span class="badge bg-secondary-subtle text-secondary ms-1">{{ $guestUsersCount }}</span>
                        </button>

                        <button type="button" class="btn btn-sm btn-outline-info user-type-quick" data-user-type="normal">
                            Normal Users
                            <span class="badge bg-info-subtle text-info ms-1">{{ $normalUsersCount }}</span>
                        </button>

                        <button type="button" class="btn btn-sm btn-outline-danger user-type-quick" data-user-type="admin">
                            Admin Users
                            <span class="badge bg-danger-subtle text-danger ms-1">{{ $adminUsersCount }}</span>
                        </button>
                    </div>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table id="userTable" data-tables="basic"
                            class="table table-custom dt-responsive align-middle mb-0 table-centered table-select table-hover w-100 mb-0 p-4">
                            <thead class="bg-light align-middle bg-opacity-25 thead-sm text-nowrap">
                                <tr class="text-uppercase fs-xxs">
                                    <th>#</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Role</th>
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
                    <div data-table-pagination-info="Support Tickets"></div>
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

            let table = $('#userTable').DataTable({

                processing: true,
                serverSide: true,
                responsive: true,

                ajax: {
                    url: "{{ route('admin.user.data') }}",
                    data: function(d) {

                        d.user_type = $('[data-table-filter="user-type"]').val();
                        d.status = $('[data-table-filter="priority"]').val();
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
                        data: 'role',
                        name: 'role',
                        render: function(data) {

                            return `
                        <span class="text-uppercase fw-medium badge bg-success-subtle text-info">
                            ${data}
                        </span>
                    `;
                        }
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

            // Quick User Type Buttons
            $('.user-type-quick').on('click', function() {
                const userType = $(this).data('user-type');
                $('[data-table-filter="user-type"]').val(userType).trigger('change');
            });


            // Status Filter
            $('[data-table-filter="priority"]').on('change', function() {
                table.draw();
            });

            // Block/Unblock with password confirmation
            $(document).on('click', '.js-user-status-toggle', function() {
                const url = $(this).data('url');
                const status = $(this).data('status');
                const actionLabel = $(this).data('action-label') || 'Update';
                const userName = $(this).data('user-name') || 'this user';

                Swal.fire({
                    title: `${actionLabel} User?`,
                    html: `You are about to <strong>${actionLabel.toLowerCase()}</strong> <strong>${userName}</strong>.<br>Please enter your password to continue.`,
                    icon: 'warning',
                    input: 'password',
                    inputPlaceholder: 'Enter your current password',
                    inputAttributes: {
                        autocapitalize: 'off',
                        autocorrect: 'off',
                    },
                    showCancelButton: true,
                    confirmButtonText: actionLabel,
                    cancelButtonText: 'Cancel',
                    preConfirm: (password) => {
                        if (!password) {
                            Swal.showValidationMessage('Password is required');
                            return false;
                        }

                        return password;
                    }
                }).then((result) => {
                    if (!result.isConfirmed) {
                        return;
                    }

                    const form = $('<form>', {
                        method: 'POST',
                        action: url
                    });

                    form.append($('<input>', {
                        type: 'hidden',
                        name: '_token',
                        value: '{{ csrf_token() }}'
                    }));

                    form.append($('<input>', {
                        type: 'hidden',
                        name: 'status',
                        value: status
                    }));

                    form.append($('<input>', {
                        type: 'hidden',
                        name: 'current_password',
                        value: result.value
                    }));

                    $('body').append(form);
                    form.trigger('submit');
                });
            });

        });
    </script>
@endpush
