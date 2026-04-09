@extends('backend.master')

@section('page_title', 'Confirm Email Change')

@section('content')

<div class="row">
    <div class="col-12">
        <article class="card overflow-hidden mb-0">
            <div class="position-relative card-side-img overflow-hidden"
                style="min-height: 200px; background-image: url({{ asset('backend') }}/assets/images/profile-bg.jpg)">
                <div
                    class="p-4 card-img-overlay rounded-start-0 auth-overlay d-flex align-items-center flex-column justify-content-center">
                    <h3 class="text-white">Confirm Email Change</h3>
                </div>
            </div>
        </article>
    </div>
</div>

<div class="px-3 mt-n5">
    <div class="row">
        <div class="col-md-6 offset-md-3">

            <div class="card">
                <div class="card-body">

                    {{-- Success Message --}}
                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    {{-- Error Message --}}
                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('email.change.confirm') }}">
                        @csrf

                        <!-- Token -->
                        <input type="hidden" name="token" value="{{ $token }}">

                        <!-- Current Password -->
                        <div class="mb-3">
                            <label class="form-label">Current Password</label>
                            <input
                                type="password"
                                name="password"
                                class="form-control"
                                placeholder="Enter your current password"
                                required>
                        </div>

                        <!-- OTP -->
                        <div class="mb-3">
                            <label class="form-label">OTP Code</label>
                            <input
                                type="text"
                                name="otp"
                                class="form-control"
                                placeholder="Enter OTP from email"
                                required>
                        </div>

                        <!-- Submit -->
                        <div class="text-end mt-4">
                            <button type="submit" class="btn btn-primary">
                                Confirm & Update Email
                            </button>
                        </div>

                    </form>

                </div>
            </div>

        </div>
    </div>
</div>

@endsection
