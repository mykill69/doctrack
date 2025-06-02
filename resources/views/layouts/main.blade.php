@php
    $current_route = request()->route()->getName();
    $user_role = auth()->user()->role; // Get the logged-in user's role
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>CPSU | DTS {{ isset($title) ? '| ' . $title : '' }}</title>
    <!-- Google Font: Source Sans Pro -->
    <!-- Bootstrap JS (include this before closing body tag) -->

    {{-- <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback"> --}}
    <!-- Toastr -->
    <link rel="stylesheet" href="{{ asset('template/plugins/toastr/toastr.min.css') }}">
    <!-- SweetAlert2 -->
    <!-- Font Awesome -->
    <link rel="stylesheet" href="{{ asset('template/plugins/fontawesome-free/css/all.min.css') }}">
    <!-- Theme style -->
    <link rel="stylesheet" href="{{ asset('template/dist/css/adminlte.min.css') }}">
    <!-- DataTables -->
    <link rel="stylesheet" href="{{ asset('template/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet"
        href="{{ asset('template/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('template/plugins/datatables-buttons/css/buttons.bootstrap4.min.css') }}">
    <!-- Select2 -->
    <link rel="stylesheet" href="{{ asset('template/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('template/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    <!-- Logo  -->
    <link rel="shortcut icon" type="" href="{{ asset('template/img/CPSU_L.png') }}">

</head>

<style>
    /* Sidebar links text color */
    .main-sidebar .nav-sidebar .nav-link {
        color: #1F5036 !important;
        font-weight: bold;
    }

    /* Sidebar links hover */
    .main-sidebar .nav-sidebar .nav-link:hover {
        color: white !important;
        background-color: black;
        /* optional hover background */
    }

    /* Active menu item */
    .main-sidebar .nav-sidebar .nav-link.active {
        color: #1F5036 !important;
        background-color: darkgray;
        /* slightly highlight active */
    }

    /* Icon colors to match text */
    .main-sidebar .nav-sidebar .nav-link i {
        color: #1F5036 !important;
    }

    .main-sidebar .nav-sidebar .nav-link:hover i {
        color: white !important;
    }

    a {
        color: #000000;
    }
</style>

<body
    class="hold-transition sidebar-mini  {{-- sidebar-collapse --}} layout-fixed layout-navbar-fixed layout-footer-fixed text-sm">
    <div class="wrapper">
        <!-- Navbar -->
        <nav class="main-header navbar navbar-expand" style="background-color: #1F5036;">
            <!-- Left navbar links -->
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i
                            class="fas fa-bars text-white"></i></a>
                </li>
                <li class="nav-item">
                    @if (request()->routeIs('dashboard'))
                        <div class="input-group">

                            {{-- Transaction Button --}}
                            @if (in_array($user_role, ['Administrator', 'records_officer']))
                                <div class="btn-group">
                                    <button type="button" class="btn btn-warning dropdown-toggle"
                                        data-toggle="dropdown" aria-expanded="false">
                                        <i class="fa fa-plus"></i>
                                        <span class="d-none d-sm-inline text-bold"> Transaction</span>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li class="dropdown-item" data-toggle="modal" data-target="#routslip"><a
                                                href="#">Create Routing Slip</a></li>
                                        <li class="dropdown-item" data-toggle="modal" data-target="#exampleModalTrans">
                                            <a href="#">Document Tracking Slip</a>
                                        </li>
                                    </ul>
                                </div>
                            @elseif ($user_role === 'staff')
                                <div class="btn-group">
                                    <button type="button" class="btn btn-warning dropdown-toggle"
                                        data-toggle="dropdown" aria-expanded="false">
                                        <i class="fa fa-plus"></i>
                                        <span class="d-none d-sm-inline text-bold"> Transaction</span>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li class="dropdown-item" data-toggle="modal" data-target="#exampleModalTrans">
                                            <a href="#">Document Tracking Slip</a>
                                        </li>
                                    </ul>
                                </div>
                            @endif

                            {{-- QR Scanner Button (mobile only) --}}
                            <!-- Hidden file input to trigger camera -->
                            <input type="file" id="qrInput" accept="image/*" capture="environment"
                                style="display: none;" onchange="scanQRCode(this)">

                            <!-- Trigger button -->
                            <button type="button" class="btn btn-default ml-1"
                                onclick="document.getElementById('qrInput').click();">
                                <i class="fa fa-qrcode"></i>
                            </button>



                        </div>
                    @endif
                </li>
            </ul>



            <ul class="navbar-nav ml-auto">
                {{-- <li class="nav-item dropdown" style="background-color: #FFFFFF; border-radius: 5px;">
                    <a href="{{ route('logout') }}" class="nav-link"
                        style="border: 1px solid grey; border-radius: 3px; color: black;"> --}}
                        
                        {{-- <span class="d-none d-sm-inline"> Sign out</span> --}}
                        <div class="btn-group">
                            <button type="button" class="btn btn-default dropdown-toggle"
                                data-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-cogs"></i>
                                <span class="d-none d-sm-inline"> Settings</span>
                            </button>
                            <ul class="dropdown-menu">
                                @if (auth()->check() && auth()->user()->role !== 'Administrator')
                                <li class="dropdown-item">
                                    <a href="{{ route('userPassword', ['id' => Auth::user()->id]) }}">
                                    <i class="fas fa-user-edit nav-icon"></i>
                                    Edit Account</a>
                                </li>
                                @endif
                                <li class="dropdown-item">
                                    <a href="#">
                                    <i class="fa fa-info-circle nav-icon"></i>
                                    About</a>
                                </li>
                                <li class="dropdown-item">
                                    <a href="{{ route('logout') }}">
                                    <i class="fas fa-sign-out-alt nav-icon"></i>
                                    Logout</a>
                                </li>
                                <li class="dropdown-item" data-toggle="modal" data-target="#dataP">
                                    <a href="#">
                                    <i class="fa fa-scroll nav-icon"></i>
                                    Terms & Conditions</a>
                                    </li>
                                <li class="dropdown-item">
                                <i class="fas fa-code-branch nav-icon"></i>
                                <a href="#">System Version 1.0</a>
                                </li>
                            </ul>
                        </div>
               
            </ul>

        </nav>

        {{-- <p id="qr-result" style="color: green; font-size: 16px; font-weight: bold;"></p> --}}

        <aside class="main-sidebar elevation-4" style="background-color: #1F5036;">
            <a href="#" class="brand-link">
                <img src="{{ asset('template/img/CPSU_L.png') }}" alt="AdminLTE Logo"
                    class="brand-image img-circle elevation-3" style="opacity: .8">
                <span class="brand-text" style="font-size:15px;color:#FFFFFF;">Document Tracking System</span>
            </a>
            <div class="sidebar" style="background-color: white;">
                <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                    <div class="image">
                        <img src="{{ asset('template/img/user.png') }}" class="img-circle elevation-2"
                            alt="User Image">
                    </div>
                    <div class="info">
                        <a href="#" class="d-block"
                            style="font-size:12px;color:#000000;">{{ auth()->user()->fname }}
                            {{ auth()->user()->lname }}</a>
                    </div>
                </div>
                <div class="form-inline">
                    <form action="{{ route('tracking') }}" method="GET" onsubmit="return validateForm()">
                        @csrf
                        <div class="input-group" data-widget="sidebar">
                            <input class="form-control form-control-sidebar text-sm" type="search" name="route_id"
                                id="route_id" placeholder="Control number here..." aria-label="Search"
                                value="{{ request()->get('route_id') }}">
                            <div class="input-group-append">
                                <button class="btn btn-sidebar" type="submit" style="background-color: #1F5036">
                                    <i class="fas fa-search fa-fw" style="color: white;"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
                @include('menu.sidebar')
            </div>
        </aside>
        <footer class="main-footer">
            <i>Maintained and Managed by Management Information System Office. All rights reserved.</i>
        </footer>
        @yield('body')


    </div>
    
    <!-- ./wrapper -->
    <script src="{{ asset('template/plugins/jquery/jquery.min.js') }}"></script>
    <!-- Bootstrap 4 -->
    <script src="{{ asset('template/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <!-- AdminLTE App -->
    <script src="{{ asset('template/dist/js/adminlte.min.js') }}"></script>
    <!-- Toastr -->
    <script src="{{ asset('template/plugins/toastr/toastr.min.js') }}"></script>
    <!-- DataTables  & Plugins -->
    <script src="{{ asset('template/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('template/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('template/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
    <script src="{{ asset('template/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('template/plugins/datatables-buttons/js/dataTables.buttons.min.js') }}"></script>
    <script src="{{ asset('template/plugins/datatables-buttons/js/buttons.bootstrap4.min.js') }}"></script>
    <script src="{{ asset('template/plugins/jszip/jszip.min.js') }}"></script>
    <script src="{{ asset('template/plugins/pdfmake/pdfmake.min.js') }}"></script>
    <script src="{{ asset('template/plugins/pdfmake/vfs_fonts.js') }}"></script>
    <script src="{{ asset('template/plugins/datatables-buttons/js/buttons.html5.min.js') }}"></script>
    <script src="{{ asset('template/plugins/datatables-buttons/js/buttons.print.min.js') }}"></script>
    <script src="{{ asset('template/plugins/datatables-buttons/js/buttons.colVis.min.js') }}"></script>
    <!-- Select2 -->
    <script src="{{ asset('template/plugins/select2/js/select2.full.min.js') }}"></script>

    <script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>

    <script>
        function scanQRCode(input) {
            const file = input.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = function(e) {
                const img = new Image();
                img.onload = function() {
                    const canvas = document.createElement('canvas');
                    const scale = 500 / img.width;
                    canvas.width = 500;
                    canvas.height = img.height * scale;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0, canvas.width, canvas.height);

                    const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
                    const qrCode = jsQR(imageData.data, canvas.width, canvas.height);

                    if (qrCode) {
                        const trackingCode = qrCode.data;

                        // 🔁 Use same logic as search form
                        fetch(`{{ route('search') }}?query=${trackingCode}`)
                            .then(response => {
                                if (response.redirected) {
                                    // ✅ Follow Laravel redirect to slipMonitoring
                                    window.location.href = response.url;
                                } else {
                                    alert('Tracking code not found.');
                                }
                            });
                    } else {
                        alert('No QR Code detected in the image.');
                    }

                    input.value = ''; // Reset file input
                };
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    </script>

    <script>
        @if (Session::has('error'))
            toastr.options = {
                "closeButton": true,
                "progressBar": true,
                'positionClass': 'toast-bottom-right'
            }
            toastr.error("{{ session('error') }}")
        @endif

        @if (Session::has('error1'))
            toastr.options = {
                "closeButton": true,
                "progressBar": true,
                'positionClass': 'toast-bottom-center'
            }
            toastr.error("{{ session('error1') }}")
        @endif
        @if (Session::has('success'))
            toastr.options = {
                "closeButton": true,
                "progressBar": true,
                'positionClass': 'toast-bottom-right'
            }
            toastr.success("{{ session('success') }}")
        @endif
        @if ($errors->any())
            var errorMessage = "";
            @foreach ($errors->all() as $error)
                errorMessage += "{{ $error }}" + "<br>";
            @endforeach
            toastr.options = {
                "closeButton": true,
                "progressBar": true,
                "positionClass": "toast-bottom-right"
            };
            toastr.error(errorMessage);
        @endif
    </script>
    <script>
        $(function() {
            $("#example1").DataTable({
                "responsive": false,
                "lengthChange": true,
                "autoWidth": true,
                //"buttons": ["copy", "csv", "excel", "pdf", "print", "colvis"]
            }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');
            $('#example2').DataTable({
                "paging": true,
                "lengthChange": false,
                "searching": false,
                "ordering": true,
                "info": true,
                "autoWidth": false,
                "responsive": true,
            });
        });
    </script>
    <script>
        $(function() {

            $('.select2').select2();

            $('.select2bs4').select2({
                theme: 'bootstrap4',
                height: '100'
            })
        });
    </script>
    <script>
        // validate search bar entry
        function validateForm() {
            const docNumber = document.getElementById('route_id').value.trim();
            if (docNumber === "") {
                alert("Please enter a valid document number before searching.");
                return false; // Prevent form submission
            }
            return true; // Allow form submission
        }
    </script>
    <script>
        $(document).ready(function() {
            // When the edit button is clicked
            $('.edit-btn').on('click', function() {
                // Retrieve the values from the clicked row's data attributes
                var docNumber = $(this).data('docnumber');
                var subject = $(this).data('subject');
                var docType = $(this).data('doctype');
                var purpose = $(this).data('purpose');
                // Populate the modal fields with these values
                $('#docNumber').val(docNumber);
                $('#subject').val(subject);
                $('#documentType').val(docType);
                $('#purpose').val(purpose);
                console.log(docNumber, subject, docType, purpose);
            });
        });
    </script>



</body>

</html>
@include('modal.dataP')
@include('modal.dpaPopup')