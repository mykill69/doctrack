<!DOCTYPE html>
<html lang="en">
<head>
    {{-- @if(\Auth::guard('web')->check())
        <script>window.location = "{{ route('dashboard') }}";</script>
    @elseif(\Auth::guard('employee')->check())
        <script>window.location = "{{ route('drive') }}";</script>
    @endif --}}
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <title>CPSU DTS | Log in</title>

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="{{ asset('template/plugins/fontawesome-free/css/all.min.css') }}">
    <!-- icheck bootstrap -->
    <link rel="stylesheet" href="{{ asset('template/plugins/icheck-bootstrap/icheck-bootstrap.css') }}">
    <!-- Theme style -->
    <link rel="stylesheet" href="{{ asset('template/dist/css/adminlte.css') }}">
    <!-- Logo for demo purposes -->
    <link rel="shortcut icon" type="" href="{{ asset('template/img/CPSU_L.png') }}">

    <style type="text/css">
        .login-box {
            box-shadow: 0 20px 30px rgba(0, 0, 0, 0.2) !important;
            width: 90%;
            max-width: 400px;
            margin: auto;
        }
        .login-logo{
            -webkit-animation: showSlowlyElement 700ms !important; 
            animation: showSlowlyElement 700ms !important;
        }
        .input-group{
            -webkit-animation: showSlowlyElement 700ms !important; 
            animation: showSlowlyElement 700ms !important;
        }
        .icheck-primary{
            -webkit-animation: showSlowlyElement 700ms !important; 
            animation: showSlowlyElement 700ms !important;
        }
        .col-4{
            -webkit-animation: showSlowlyElement 700ms !important; 
            animation: showSlowlyElement 700ms !important;
        }
        .btn-primary{
            background-color: #04401f !important;
            border: #1f5036 !important;
        }
        #particles-js {
            position: fixed;
            width: 100%;
            height: 100%;
            background-color: #6c9076;
            background-repeat: no-repeat;
            background-size: cover;
            background-position: 100%;
            /*z-index: -1;*/
        }
        .iconBg {
            background-color: #bcbdbc !important;
        }
    </style>
</head>

<body class="hold-transition login-page">
    <div id="particles-js"></div>
    <div class="login-box">
    
        <div class="card">
            <div class="card-body login-card-body" style="background-color:white; border-radius: 5px;">
                <div class="row">
                   {{--  <div class="col-md-7">
                        <p style="font-size: 19pt; font-family: 'Oxygen',sans-serif;" class="card-body">
                            CPSU - Document Tracking System
                        </p>

                        <br><br><br><br><br><br>
                        <p style="font-size: 9pt; font-family: 'Oxygen',sans-serif;" class="card-footer">
                            Develop and maintain by: Management Information System Office
                        </p>
                    </div> --}}

                    <div class="col-md-12">
                        <div class="login-logo">
                            <a href="">
                                <img src="{{ asset('template/img/CPSU_L.png') }}" class="img-circle" width="103px" height="100px">
                            </a>
                            <h4 style="font-family: monospace;">Document Tracking System</h4>
                        </div>

                        <p class="login-box-msg">Sign in to start your session</p>
                        <form action="{{route('postLogin')}}" method="post">
                            @csrf
                            @if(session('error'))
                                <div class="alert alert-danger" style="font-size: 12pt;">
                                    <i class="fas fa-exclamation-triangle "></i> {{session('error')}}
                                </div>
                            @endif

                            @if(session('success'))
                                <div class="alert alert-success" style="font-size: 10pt;">
                                <i class="fas fa-check"></i> {{session('success')}}
                                </div>
                            @endif

                            <div class="input-group mb-2">
                                <input type="text" class="form-control" name="username" placeholder="Username" value="{{ old('username') }}" autofocus="">
                                <div class="input-group-append">
                                    <div class="input-group-text iconBg">
                                        <span class="fas fa-user text-light"></span>
                                    </div>
                                </div>
                            </div>
                            <span style="color: #FF0000; font-size: 10pt;" class="form-text text-left">@error('username') {{ $message }} @enderror</span>

                            <div class="input-group mb-2">
                                <input type="password" class="form-control" name="password" id="myInput" placeholder="Password">
                                <div class="input-group-append">
                                    <div class="input-group-text iconBg">
                                        <span class="fas fa-lock text-light"></span>
                                    </div>
                                </div>
                            </div>
                            <span id="password" style="color: #FF0000; font-size: 10pt;" class="form-text text-left">@error('password') {{ $message }} @enderror</span>
                            
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
                    </div>   
                </div>

                
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