<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>{{ systemTitle() }} | Sign In</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />

    <link rel="shortcut icon" href="{{ systemFavicon() }}" />

    <script src="{{ asset('backend/assets/js/config.js') }}"></script>
    <script src="{{ asset('backend/demo.js') }}"></script>

    <link href="{{ asset('backend/assets/css/vendors.min.css') }}" rel="stylesheet" />
    <link href="{{ asset('backend/assets/css/app.min.css') }}" rel="stylesheet" />

    <style>
        :root {
            --login-primary: #ff3b5c;
            --login-secondary: #9810fa;
        }

        body.login-page {
            background:
                radial-gradient(circle at top left, rgba(255, 59, 92, 0.25), transparent 34%),
                radial-gradient(circle at bottom right, rgba(152, 16, 250, 0.22), transparent 32%),
                linear-gradient(135deg, #0f1220 0%, #17192b 48%, #0e1020 100%);
            color: #ffffff;
        }

        .login-shell {
            min-height: 100vh;
            position: relative;
            overflow: hidden;
        }

        .login-shell::before,
        .login-shell::after {
            content: '';
            position: absolute;
            inset: auto;
            border-radius: 999px;
            filter: blur(10px);
            opacity: 0.65;
            pointer-events: none;
        }

        .login-shell::before {
            width: 280px;
            height: 280px;
            top: -90px;
            left: -80px;
            background: rgba(255, 59, 92, 0.18);
        }

        .login-shell::after {
            width: 360px;
            height: 360px;
            bottom: -160px;
            right: -120px;
            background: rgba(152, 16, 250, 0.18);
        }

        .login-card {
            position: relative;
            background: rgba(15, 18, 32, 0.72);
            backdrop-filter: blur(18px);
            border: 1px solid rgba(255, 255, 255, 0.12);
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.35);
            border-radius: 28px;
        }

        .login-brand-logo {
            height: 54px;
            width: auto;
            max-width: 100%;
            object-fit: contain;
            filter: none;
        }

        .login-title {
            color: #ffffff;
            letter-spacing: 0.02em;
        }

        .login-copy {
            color: rgba(255, 255, 255, 0.74) !important;
        }

        .login-divider {
            color: rgba(255, 255, 255, 0.72);
        }

        .login-divider span {
            background: linear-gradient(90deg, rgba(255, 59, 92, 0.22), rgba(152, 16, 250, 0.22));
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.08);
        }

        .login-page .form-label,
        .login-page .text-muted,
        .login-page a {
            color: rgba(255, 255, 255, 0.82) !important;
        }

        .login-page .form-control {
            background: rgba(255, 255, 255, 0.06);
            border-color: rgba(255, 255, 255, 0.14);
            color: #ffffff;
        }

        .login-page .form-control::placeholder {
            color: rgba(255, 255, 255, 0.46);
        }

        .login-page .form-control:focus {
            border-color: rgba(255, 59, 92, 0.75);
            box-shadow: 0 0 0 0.2rem rgba(255, 59, 92, 0.18);
            background: rgba(255, 255, 255, 0.08);
            color: #ffffff;
        }

        .login-page .btn-primary {
            border: none;
            background: linear-gradient(135deg, var(--login-primary) 0%, var(--login-secondary) 100%);
            box-shadow: 0 16px 30px rgba(152, 16, 250, 0.3);
        }

        .login-page .btn-primary:hover {
            opacity: 0.94;
        }

        .login-illustration {
            position: absolute;
            inset: 24px 24px auto auto;
            width: 180px;
            opacity: 0.12;
            pointer-events: none;
        }
    </style>
</head>

<body class="login-page">

    <div class="position-absolute top-0 end-0">
        <img src="{{ asset('backend/assets/images/auth-card-bg.svg') }}" class="auth-card-bg-img" />
    </div>

    <div class="position-absolute bottom-0 start-0" style="transform: rotate(180deg)">
        <img src="{{ asset('backend/assets/images/auth-card-bg.svg') }}" class="auth-card-bg-img" />
    </div>

    <div class="login-shell auth-box overflow-hidden align-items-center d-flex">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-xxl-5 col-md-6 col-sm-8">
                    <div class="card p-4 login-card">

                        {{-- Session Status --}}
                        @if (session('status'))
                            <div class="alert alert-success mb-3">
                                {{ session('status') }}
                            </div>
                        @endif

                        <div class="auth-brand text-center mb-1">
                            <img src="{{ systemLogo() }}" class="login-brand-logo" alt="logo" />
                            <h4 class="fw-bold login-title mt-3 text-uppercase">{{ systemTitle() }}</h4>
                            <p class="login-copy w-lg-75 mx-auto">Let’s get you signed in. <br> Enter your email and
                                password
                                to continue.</p>
                        </div>

                        <p class="text-center my-1 auth-line login-divider"> <span> Welcome To {{ systemTitle() }}
                            </span>
                        </p>

                        <form method="POST" action="{{ route('login') }}">
                            @csrf

                            {{-- Email --}}
                            <div class="mb-3">
                                <label class="form-label">Email Address <span class="text-danger">*</span></label>
                                <input type="email" name="email"
                                    class="form-control @error('email') is-invalid @enderror"
                                    value="{{ old('email') }}" placeholder="you@example.com" required autofocus>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Password --}}
                            <div class="mb-3">
                                <label class="form-label">Password <span class="text-danger">*</span></label>
                                <input type="password" name="password"
                                    class="form-control @error('password') is-invalid @enderror" placeholder="••••••••"
                                    required>
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            {{-- Remember Me --}}
                            <div class="d-flex justify-content-end align-items-center mb-3">
                                <a href="{{ route('password.request') }}" class="text-decoration-underline text-muted">
                                    Forgot Password?
                                </a>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary fw-semibold py-2">
                                    Sign In
                                </button>
                            </div>
                        </form>
                    </div>

                    <p class="text-center login-copy mt-4 mb-0">
                        © {{ date('Y') }} {{ systemTitle() }}
                    </p>
                </div>
            </div>
        </div>
    </div>
    <!-- Jquery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    @include('backend.partial.sweetalert')

    <script src="{{ asset('backend/assets/js/vendors.min.js') }}"></script>
    <script src="{{ asset('backend/assets/js/app.js') }}"></script>


</body>

</html>
