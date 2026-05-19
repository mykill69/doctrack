<!DOCTYPE html>
<html lang="en">

<head>
    {{-- @if (\Auth::guard('web')->check())
        <script>window.location = "{{ route('dashboard') }}";</script>
    @elseif(\Auth::guard('employee')->check())
        <script>window.location = "{{ route('drive') }}";</script>
    @endif --}}
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>CPSU DTS | Log in</title>

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet"
        href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="{{ asset('template/plugins/fontawesome-free/css/all.min.css') }}">
    <!-- icheck bootstrap -->
    <link rel="stylesheet" href="{{ asset('template/plugins/icheck-bootstrap/icheck-bootstrap.css') }}">
    <!-- Theme style -->
    <link rel="stylesheet" href="{{ asset('template/dist/css/adminlte.css') }}">
    <!-- Logo for demo purposes -->
    <link rel="shortcut icon" type="" href="{{ asset('template/img/cpsu_logo.png') }}">

    <style>
        /* Login box */
        .login-box {
            width: 90%;
            max-width: 400px;
            margin: auto;
            box-shadow: 0 20px 30px rgba(0, 0, 0, 0.2) !important;
        }

        /* Animated fade-in elements - keep the entrance animation */
        .login-logo,
        .input-group,
        .icheck-primary,
        .col-4 {
            -webkit-animation: showSlowlyElement 700ms !important;
            animation: showSlowlyElement 700ms !important;
        }

        /* Primary button */
        .btn-primary {
            background-color: #04401f !important;
            border: #1f5036 !important;
        }

        /* Particles background */
        #particles-js {
            position: fixed;
            width: 100%;
            height: 100%;
            background-color: #6c9076;
            background-repeat: no-repeat;
            background-size: cover;
            background-position: 100%;
        }

        /* Icon background */
        .iconBg {
            background-color: #bcbdbc !important;
        }

        /* Outer card wrapper with glowing border - keep border effect */
        .login-card {
            border: 1px solid #04401f;
            border-radius: 10px;
            padding: 3px;
            box-sizing: border-box;
            animation: borderGlow 3s infinite;
        }

        /* Inner card body - remove hover expand and background changes */
        .login-card-body {
            position: relative;
            border-radius: 10px;
            background-color: white;
            transition: none; /* Remove all transitions */
            box-sizing: border-box;
            box-shadow: 0 0 5px rgba(255, 255, 255, 0.5) !important;
            /* Fixed height - remove dynamic height on hover */
            height: auto;
            min-height: 380px;
        }

        /* Completely remove hover effects that change height/background */
        /* No hover styles for login-card-body */

        /* Form fields stay visible at all times - no opacity/transform tricks */
        .login-card-body form .input-group,
        .login-card-body form .row.mt-4,
        .login-card-body form span.form-text {
            opacity: 1;
            transform: translateY(0);
            transition: none;
            pointer-events: auto;
        }

        /* Ensure all form elements are always visible and interactive */
        .login-card-body form .input-group,
        .login-card-body form .row.mt-4,
        .login-card-body form span.form-text,
        .login-card-body form .icheck-success,
        .login-card-body form button {
            opacity: 1 !important;
            transform: translateY(0) !important;
            pointer-events: auto !important;
        }

        /* Mobile styles - consistent with desktop, no hidden elements */
        @media (max-width: 768px) {
            .login-card-body {
                height: auto !important;
                background-color: white !important;
            }
        }

        /* Keep the border glow animation if desired */
        @keyframes borderGlow {
            0% {
                border-color: #04401f;
                box-shadow: 0 0 5px rgba(4, 64, 31, 0.3);
            }
            50% {
                border-color: #0a7340;
                box-shadow: 0 0 12px rgba(4, 64, 31, 0.6);
            }
            100% {
                border-color: #04401f;
                box-shadow: 0 0 5px rgba(4, 64, 31, 0.3);
            }
        }

        /* Keep the fade-in animation for initial load */
        @keyframes showSlowlyElement {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Adjust spacing to maintain clean layout */
        .login-card-body {
            padding: 1rem;
        }
        
        .login-logo {
            margin-bottom: 1rem;
        }
        
        .login-box-msg {
            margin-bottom: 1rem;
        }
        
        /* Ensure alert messages are visible */
        .alert {
            margin-top: 1rem;
            margin-bottom: 0;
        }
        
        /* Keep show password checkbox styling */
        .icheck-success {
            margin-top: 0.5rem;
        }
    </style>
</head>

<body class="hold-transition login-page">
    <div id="particles-js"></div>
    <div class="login-box">
        <div class="login-card">
            <div class="login-card-body">
                <div class="login-logo">
                    <img src="{{ asset('template/img/cpsu_logo.png') }}" class="img-circle" width="103px"
                        height="100px" alt="CPSU Logo">
                    <h4 style="font-family: monospace;">Document Tracking System</h4>
                </div>
                <p class="login-box-msg">Sign in to start your session</p>
                
                <form action="{{ route('postLogin') }}" method="post">
                    @csrf

                    <div class="input-group mb-2">
                        <input type="text" class="form-control" name="email" placeholder="Institutional Email"
                            value="{{ old('email') }}" autofocus="">
                        <div class="input-group-append">
                            <div class="input-group-text iconBg">
                                <span class="fas fa-user text-light"></span>
                            </div>
                        </div>
                    </div>
                    <span style="color: #FF0000; font-size: 10pt;" class="form-text text-left">
                        @error('email')
                            {{ $message }}
                        @enderror
                    </span>

                    <div class="input-group mb-2">
                        <input type="password" class="form-control" name="password" id="myInput"
                            placeholder="Password">
                        <div class="input-group-append">
                            <div class="input-group-text iconBg">
                                <span class="fas fa-lock text-light"></span>
                            </div>
                        </div>
                    </div>
                    <span id="password" style="color: #FF0000; font-size: 10pt;" class="form-text text-left">
                        @error('password')
                            {{ $message }}
                        @enderror
                    </span>

                    <div class="row mt-4">
                        <div class="col-7">
                            <div class="icheck-success">
                                <input type="checkbox" id="show" onclick="myFunction()">
                                <label for="show">Show Password</label>
                            </div>
                        </div>
                        <div class="col-5">
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-sign-in-alt"></i> Sign In
                            </button>
                        </div>
                    </div>
                </form>

                @if (session('error'))
                    <div class="alert alert-danger" style="font-size: 12px;">
                        <i class="fas fa-exclamation-triangle"></i> {{ session('error') }}
                    </div>
                @endif

                @if (session('success'))
                    <div class="alert alert-success" style="font-size: 12px;">
                        <i class="fas fa-check"></i> {{ session('success') }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script src="{{ asset('particles/particles.js') }}"></script>
    <script src="{{ asset('particles/app.js') }}"></script>
    <!-- jQuery -->
    <script src="{{ asset('template/plugins/jquery/jquery.min.js') }}"></script>
    <!-- Bootstrap 4 -->
    <script src="{{ asset('template/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <!-- AdminLTE App -->
    <script src="{{ asset('template/dist/js/adminlte.min.js') }}"></script>

    <script>
        function myFunction() {
            var x = document.getElementById("myInput");
            if (x.type === "password") {
                x.type = "text";
            } else {
                x.type = "password";
            }
        }
    </script>
</body>

</html>