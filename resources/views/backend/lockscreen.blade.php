<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <title>{{ systemTitle() }} | Lock Screen</title>
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

        body.lockscreen-page {
            background:
                radial-gradient(circle at top left, rgba(255, 59, 92, 0.25), transparent 34%),
                radial-gradient(circle at bottom right, rgba(152, 16, 250, 0.22), transparent 32%),
                linear-gradient(135deg, #0f1220 0%, #17192b 48%, #0e1020 100%);
            color: #ffffff;
        }

        .lockscreen-shell {
            min-height: 100vh;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .lockscreen-shell::before,
        .lockscreen-shell::after {
            content: '';
            position: absolute;
            inset: auto;
            border-radius: 999px;
            filter: blur(10px);
            opacity: 0.65;
            pointer-events: none;
        }

        .lockscreen-shell::before {
            width: 280px;
            height: 280px;
            top: -90px;
            left: -80px;
            background: rgba(255, 59, 92, 0.18);
        }

        .lockscreen-shell::after {
            width: 360px;
            height: 360px;
            bottom: -160px;
            right: -120px;
            background: rgba(152, 16, 250, 0.18);
        }

        .lockscreen-card {
            position: relative;
            background: rgba(15, 18, 32, 0.72);
            backdrop-filter: blur(18px);
            border: 1px solid rgba(255, 255, 255, 0.12);
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.35);
            border-radius: 28px;
            max-width: 400px;
            width: 100%;
            padding: 40px;
        }

        .lockscreen-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin: 0 auto 20px;
            object-fit: cover;
            border: 3px solid rgba(255, 59, 92, 0.4);
            box-shadow: 0 0 30px rgba(255, 59, 92, 0.2);
        }

        .lockscreen-username {
            color: #ffffff;
            text-align: center;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 8px;
            letter-spacing: 0.02em;
        }

        .lockscreen-subtitle {
            color: rgba(255, 255, 255, 0.6);
            text-align: center;
            font-size: 14px;
            margin-bottom: 30px;
        }

        .lockscreen-page .form-label {
            color: rgba(255, 255, 255, 0.82) !important;
        }

        .lockscreen-page .form-control {
            background: rgba(255, 255, 255, 0.06);
            border-color: rgba(255, 255, 255, 0.14);
            color: #ffffff;
            padding: 12px 16px;
        }

        .lockscreen-page .form-control::placeholder {
            color: rgba(255, 255, 255, 0.46);
        }

        .lockscreen-page .form-control:focus {
            border-color: rgba(255, 59, 92, 0.75);
            box-shadow: 0 0 0 0.2rem rgba(255, 59, 92, 0.18);
            background: rgba(255, 255, 255, 0.08);
            color: #ffffff;
        }

        .lockscreen-page .btn-primary {
            border: none;
            background: linear-gradient(135deg, var(--login-primary) 0%, var(--login-secondary) 100%);
            box-shadow: 0 16px 30px rgba(152, 16, 250, 0.3);
            width: 100%;
            padding: 12px 24px;
            font-weight: 600;
            margin-top: 20px;
        }

        .lockscreen-page .btn-primary:hover {
            opacity: 0.94;
        }

        .lockscreen-error {
            background: rgba(255, 59, 92, 0.1);
            border: 1px solid rgba(255, 59, 92, 0.3);
            color: #ff6b7a;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .lockscreen-lock-icon {
            font-size: 48px;
            text-align: center;
            margin-bottom: 20px;
            color: rgba(255, 59, 92, 0.7);
        }
    </style>
</head>

<body class="lockscreen-page">

    <div class="position-absolute top-0 end-0">
        <img src="{{ asset('backend/assets/images/auth-card-bg.svg') }}" class="auth-card-bg-img" />
    </div>

    <div class="position-absolute bottom-0 start-0" style="transform: rotate(180deg)">
        <img src="{{ asset('backend/assets/images/auth-card-bg.svg') }}" class="auth-card-bg-img" />
    </div>

    <div class="lockscreen-shell">
        <div class="lockscreen-card">
            <div class="lockscreen-lock-icon">
                <i class="ti ti-lock"></i>
            </div>

            <div class="lockscreen-avatar"
                style="background-image: url('{{ asset(Auth::user()->avatar == 'user.png' ? 'admin.png' : Auth::user()->avatar) }}'); background-size: cover; background-position: center;">
            </div>

            <div class="lockscreen-username">{{ Auth::user()->name }}</div>
            <div class="lockscreen-subtitle">{{ Auth::user()->email }}</div>
            <div class="lockscreen-subtitle">Screen is locked. Enter your password to continue.</div>

            @if ($errors->has('password'))
                <div class="lockscreen-error">
                    <i class="ti ti-alert-circle me-2"></i>
                    {{ $errors->first('password') }}
                </div>
            @endif

            <form action="{{ route('screen.lock.unlock') }}" method="POST" class="lockscreen-page">
                @csrf

                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group input-group-merge">
                        <input type="password" class="form-control @error('password') is-invalid @enderror"
                            id="password" name="password" placeholder="Enter your password" required
                            autocomplete="off" />
                        <button class="btn btn-outline-secondary toggle-password" type="button" tabindex="-1"
                            data-password-id="password" style="border-color: rgba(255, 255, 255, 0.14);">
                            <i class="ti ti-eye-off"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary fw-semibold">Unlock</button>
            </form>

            <div style="text-align: center; margin-top: 20px;">
                <a href="{{ route('logout') }}" class="text-decoration-none"
                    style="color: rgba(255, 255, 255, 0.6); font-size: 14px;">
                    Logout instead <i class="ti ti-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>

    <script src="{{ asset('backend/assets/libs/jquery/jquery.min.js') }}"></script>
    <script src="{{ asset('backend/assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script>
        // Toggle password visibility
        document.querySelectorAll('.toggle-password').forEach(button => {
            button.addEventListener('click', function() {
                const passwordId = this.dataset.passwordId;
                const passwordInput = document.getElementById(passwordId);
                const icon = this.querySelector('i');

                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    icon.classList.remove('ti-eye-off');
                    icon.classList.add('ti-eye');
                } else {
                    passwordInput.type = 'password';
                    icon.classList.remove('ti-eye');
                    icon.classList.add('ti-eye-off');
                }
            });
        });

        // Focus on password field
        document.getElementById('password').focus();
    </script>

</body>

</html>
