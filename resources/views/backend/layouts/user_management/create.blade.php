@extends('backend.master')

@section('page_title', 'Create Admin')

@section('content')
    <div class="px-3 pt-2">
        <div class="row">
            <div class="col-12">


                <div class="card">
                    <div class="card-body">


                        <form method="POST" action="{{ route('admin.user.store') }}" enctype="multipart/form-data">

                            @csrf


                            <h5 class="mb-4 text-uppercase bg-light-subtle p-2 border rounded text-center">
                                <i class="ti ti-user-plus fs-lg"></i>
                                Create New Admin
                            </h5>

                            <div class="alert alert-danger" role="alert">
                                <h6 class="fw-bold mb-2">
                                    <i class="ti ti-alert-triangle me-1"></i>
                                    Alert
                                </h6>
                                <p class="mb-2">
                                    If you create a new admin, this user will get whole access over the admin panel and can
                                    control the admin panel.
                                </p>
                                <p class="mb-0">
                                    Please make sure you provide correct information. Are you sure you want to create a new
                                    admin?
                                </p>
                            </div>


                            {{-- Name --}}
                            <div class="mb-3">
                                <label class="form-label">Full Name *</label>

                                <input type="text" name="name" class="form-control" value="{{ old('name') }}"
                                    required>
                            </div>


                            {{-- Username --}}
                            <div class="mb-3">
                                <label class="form-label">Username</label>

                                <input type="text" name="username" class="form-control" value="{{ old('username') }}">
                            </div>


                            {{-- Email --}}
                            <div class="mb-3">
                                <label class="form-label">Email *</label>

                                <input type="email" name="email" class="form-control" value="{{ old('email') }}"
                                    required>
                            </div>


                            {{-- Phone --}}
                            <div class="mb-3">
                                <label class="form-label">Phone</label>

                                <input type="text" name="phone" class="form-control" value="{{ old('phone') }}">
                            </div>

                            {{-- Password --}}
                            <div class="mb-3">
                                <label class="form-label">Password *</label>

                                <input type="password" name="password" class="form-control" required>
                            </div>

                            {{-- Confirm Password --}}
                            <div class="mb-3">
                                <label class="form-label">Confirm Password *</label>

                                <input type="password" name="password_confirmation" class="form-control" required>
                            </div>


                            {{-- Avatar --}}
                            <div class="mb-3">
                                <label class="form-label">Profile Picture</label>

                                <input type="file" name="avatar" class="form-control" accept="image/*">
                            </div>

                            <hr>

                            <p class="text-muted mb-2">
                                To create this user for security purpose we need your password.
                            </p>

                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" value="1" id="admin_access_ack"
                                    name="admin_access_ack" {{ old('admin_access_ack') ? 'checked' : '' }} required>
                                <label class="form-check-label fw-semibold" for="admin_access_ack">
                                    Yes, I am sure to create a new admin with full admin panel access.
                                </label>
                            </div>

                            {{-- Creator Password --}}
                            <div class="mb-3">
                                <label class="form-label">Your Password *</label>

                                <input type="password" name="current_admin_password" id="current_admin_password"
                                    class="form-control" required autocomplete="current-password"
                                    placeholder="Enter your current password">
                            </div>


                            {{-- Submit --}}
                            <div class="text-end mt-4">

                                <button type="submit" class="btn btn-success px-4">
                                    Create Admin
                                </button>

                            </div>


                        </form>


                    </div>
                </div>

            </div>
        </div>
    </div>

@endsection



@push('scripts')
    <script>
        $(document).ready(function() {

            $('form').on('submit', function(e) {
                if (!$('#current_admin_password').val()) {
                    e.preventDefault();
                    alert('Your password is required to create this admin.');
                    return;
                }

                if (!$('#admin_access_ack').is(':checked')) {
                    e.preventDefault();
                    alert('Please confirm that you are sure to create a new admin with full access.');
                    return;
                }
            });

        });
    </script>
@endpush
