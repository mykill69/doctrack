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
    <link rel="stylesheet" href="{{ asset('template/plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css') }}">
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
    <!-- Select2 CSS -->
    <link rel="stylesheet" href="{{ asset('template/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('template/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">

    <link rel="stylesheet" href="{{ asset('template/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('template/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">

    <!-- Logo  -->
    <link rel="shortcut icon" type="" href="{{ asset('template/img/cpsu_logo.png') }}">

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

    .select2-container {
        z-index: 999999 !important;
        /* ensures dropdown appears inside SweetAlert */
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

    .swal-wide {
        width: 650px !important;
        max-width: 90%;
    }

    .no-left-radius {
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
    }

    .select2-container {
        width: 100% !important;
    }



    /* Hide × button in tag (optional) */
    .select2-selection__choice__remove {
        display: none !important;
    }

    /* Align Select2 to match Bootstrap form-control */
    .select2-container--default .select2-selection--multiple {
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
        height: auto;
        min-height: 38px;
        padding: 0.375rem 0.75rem;
        font-size: 1rem;
        line-height: 1.5;
    }

    .select2-selection__choice {
        background-color: #007bff !important;
        color: #fff !important;
        border: none !important;
        padding: 2px 10px;
        border-radius: 0.2rem;
        margin-top: 4px;
    }

    .select2-selection__rendered {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
    }

    /* Fix dropdown to appear above loader */
    .select2-dropdown {
        z-index: 9999 !important;
    }

    #page-loader {
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }

    .progress-loader {
        width: 200px;
        height: 10px;
        background-color: #dee2e6;
        border-radius: 5px;
        overflow: hidden;
        margin-top: 10px;
    }

    .progress-bar {
        width: 0%;
        height: 100%;
        background-color: #0d6efd;
        transition: width 0.5s ease;
    }

    #page-loader p {
        margin-top: 1.5rem;
        font-size: 1.2rem;
        font-weight: 500;
        color: #343a40;
        animation: fadeIn 0.6s ease-in-out;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }



    .pagination {
        margin: 0;
    }

    .pagination .page-link {
        border-radius: 4px;
        margin: 0 2px;
    }
</style>

<body
    class="hold-transition sidebar-mini  {{-- sidebar-collapse --}} layout-fixed layout-navbar-fixed layout-footer-fixed text-sm">

    <div id="page-loader"
        class="position-fixed top-0 start-0 w-100 h-100 flex-column justify-content-center align-items-center"
        style="z-index:1055; display:none; background:linear-gradient(135deg,#f8f9fa,#e9ecef); font-family:'Segoe UI',Tahoma,sans-serif">

        <img src="{{ asset('template/img/cpsu_logo.png') }}" alt="MIS logo"
            style="width:110px;height:auto;margin-bottom:28px">

        <div class="progress-loader"
            style="width:220px;height:12px;background:#dee2e6;border-radius:6px;overflow:hidden">
            <div id="progress-bar" style="width:0;height:100%;background:#0d6efd;transition:width .4s ease"></div>
        </div>

        <p style="margin-top:1.3rem;font-size:1.15rem;font-weight:500;color:#343a40">
            Sending notification, please wait...
        </p>
    </div>

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

                            @if (auth()->user()->id == 1235)
                                {{-- Only for user ID 1235 --}}
                                <button type="button" class="btn btn-warning" onclick="openDoctrackForm()">
                                    <i class="fa fa-plus"></i>
                                    <span class="d-none d-sm-inline text-bold"> Document Transmittal</span>
                                </button>
                            @elseif (in_array($user_role, ['Administrator', 'records_officer']))
                                {{-- Transaction Button --}}
                                <div class="btn-group">
                                    <button type="button" class="btn btn-warning dropdown-toggle"
                                        data-toggle="dropdown" aria-expanded="false">
                                        <i class="fa fa-plus"></i>
                                        <span class="d-none d-sm-inline text-bold"> Transaction</span>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li class="dropdown-item" data-toggle="modal" data-target="#routslip">
                                            <a href="#">for PRESIDENT'S Action</a>
                                        </li>
                                        <li class="dropdown-item">
                                            <a href="#" onclick="openDoctrackForm()">for PERSONNEL Action</a>
                                        </li>
                                    </ul>
                                </div>
                            @elseif (in_array($user_role, ['super_user', 'staff']))
                                {{-- Simple Button for Document Tracking Slip --}}
                                <button type="button" class="btn btn-warning" onclick="openDoctrackForm()">
                                    <i class="fa fa-plus"></i>
                                    <span class="d-none d-sm-inline text-bold">for PERSONNEL Action</span>
                                </button>
                            @endif

                        </div>
                    @endif

                </li>

            </ul>

            {{-- QR Scanner Button (mobile only) --}}
            {{-- <input type="file" id="qrInput" accept="image/*" capture="environment"
                                style="display: none;" onchange="scanQRCode(this)">

                            <button type="button" class="btn btn-default ml-1"
                                onclick="document.getElementById('qrInput').click();">
                                <i class="fa fa-qrcode"></i>
                            </button> --}}

            <ul class="navbar-nav ml-auto">
                {{-- <li class="nav-item dropdown" style="background-color: #FFFFFF; border-radius: 5px;">
                    <a href="{{ route('logout') }}" class="nav-link"
                        style="border: 1px solid grey; border-radius: 3px; color: black;"> --}}

                {{-- <span class="d-none d-sm-inline"> Sign out</span> --}}
                <div class="btn-group">
                    <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown"
                        aria-expanded="false">
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
                        <li class="dropdown-item" data-toggle="modal" data-target="#aboutDts">
                            <a href="#">
                                <i class="fa fa-info-circle nav-icon"></i>
                                About DTS</a>
                        </li>

                        <li class="dropdown-item" data-toggle="modal" data-target="#dataP">
                            <a href="#">
                                <i class="fa fa-scroll nav-icon"></i>
                                Terms & Conditions</a>
                        </li>
                        <li class="dropdown-item">
                            <a href="{{ route('logout') }}">
                                <i class="fas fa-sign-out-alt nav-icon"></i>
                                Logout</a>
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
                <img src="{{ asset('template/img/cpsu_logo.png') }}" alt="AdminLTE Logo"
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
                        <a href="#" class="d-block" style="font-size:12px;color:#000000;">
                            @if (auth()->user()->role === 'records_officer')
                                {{ auth()->user()->fname }} {{ auth()->user()->lname }} - {{ auth()->user()->mname }}
                            @else
                                {{ auth()->user()->fname }} {{ auth()->user()->lname }}
                            @endif
                        </a>
                    </div>
                </div>
                <div class="form-inline">
                    <form action="{{ route('tracking') }}" method="GET" onsubmit="return validateForm()">
                        @csrf
                        <div class="input-group" data-widget="sidebar">
                            <input class="form-control form-control-sidebar text-sm" type="search" name="route_id"
                                id="route_id" placeholder="Routed Docs CTRL #" aria-label="Search"
                                value="{{ request()->get('route_id') }}">
                            <div class="input-group-append">
                                <button class="btn btn-sidebar" type="submit" style="background-color: #1F5036">
                                    <i class="fas fa-search fa-fw" style="color: white;"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                    {{-- <form action="{{ route('documents.tracking') }}" method="GET"
                        onsubmit="return validateForm()">

                        <div class="input-group">
                            <input class="form-control form-control-sidebar text-sm" type="search" name="route_id"
                                id="route_id" placeholder="Routed Docs CTRL #" value="{{ request('route_id') }}">

                            <button class="btn btn-sidebar" type="submit" style="background:#1F5036">
                                <i class="fas fa-search fa-fw text-white"></i>
                            </button>
                        </div>
                    </form> --}}

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

    <!-- Bootstrap 4 -->
    <script src="{{ asset('template/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <!-- Select2 -->
    <script src="{{ asset('template/plugins/select2/js/select2.full.min.js') }}"></script>
    <!-- SweetAlert2 -->
    <script src="{{ asset('template/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    <script src="template/plugins/bs-custom-file-input/bs-custom-file-input.min.js"></script>

    <!-- jQuery (required by Select2) -->






    <script>
        function scanQRCode(input) {
            try {
                const file = input.files[0];
                if (!file) {
                    console.warn("No file selected.");
                    return;
                }

                const reader = new FileReader();

                reader.onerror = function(e) {
                    console.error("FileReader error:", e);
                    alert("Failed to read the file.");
                };

                reader.onload = function(e) {
                    try {
                        const img = new Image();

                        img.onerror = function(err) {
                            console.error("Image load error:", err);
                            alert("Could not load the image.");
                        };

                        img.onload = function() {
                            try {
                                const canvas = document.createElement('canvas');
                                const scale = 500 / img.width;
                                canvas.width = 500;
                                canvas.height = img.height * scale;

                                const ctx = canvas.getContext('2d');
                                ctx.drawImage(img, 0, 0, canvas.width, canvas.height);

                                const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);

                                let qrCode;
                                try {
                                    qrCode = jsQR(imageData.data, canvas.width, canvas.height);
                                } catch (qrErr) {
                                    console.error("Error decoding QR code:", qrErr);
                                    alert("Error decoding QR code.");
                                    return;
                                }

                                if (qrCode) {
                                    const trackingCode = qrCode.data;
                                    console.log("QR Code detected:", trackingCode);

                                    fetch(`{{ route('search') }}?query=${encodeURIComponent(trackingCode)}`)
                                        .then(response => {
                                            if (response.redirected) {
                                                console.log("Redirecting to:", response.url);
                                                window.location.href = response.url;
                                            } else {
                                                console.warn("Tracking code not found in system.");
                                                alert('Tracking code not found.');
                                            }
                                        })
                                        .catch(fetchErr => {
                                            console.error("Fetch error:", fetchErr);
                                            alert("An error occurred while processing the tracking code.");
                                        });
                                } else {
                                    console.warn("No QR Code detected.");
                                    alert('No QR Code detected in the image.');
                                }

                                input.value = ''; // Reset file input
                            } catch (canvasErr) {
                                console.error("Canvas or image processing error:", canvasErr);
                                alert("Error processing the image.");
                            }
                        };

                        img.src = e.target.result;
                    } catch (imgSetupErr) {
                        console.error("Error initializing image object:", imgSetupErr);
                        alert("Unexpected error occurred while preparing image.");
                    }
                };

                reader.readAsDataURL(file);
            } catch (err) {
                console.error("Unexpected error in scanQRCode function:", err);
                alert("An unexpected error occurred. Please try again.");
            }
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

    {{-- <script>
        // validate search bar entry
        function validateForm() {
            const docNumber = document.getElementById('route_id').value.trim();
            if (docNumber === "") {
                alert("Please enter a valid document number before searching.");
                return false; // Prevent form submission
            }
            return true; // Allow form submission
        }
    </script> --}}
    <script>
        function validateForm() {
            const input = document.getElementById('route_id');
            const value = input.value.trim();

            if (!value) {
                alert("Please enter a valid document number.");
                input.focus();
                return false;
            }

            if (!/^\d+$/.test(value)) {
                alert("Document number must be numeric.");
                input.focus();
                return false;
            }

            return true;
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


    @php
        $users = \App\Models\User::orderBy('fname')->get();
    @endphp
    <script>
        function openDoctrackForm() {
            Swal.fire({
                title: 'Document Transmittal',
                html: `
                        <form id="docForm" enctype="multipart/form-data">
                            <input type="hidden" name="_token" value="{{ csrf_token() }}">
                            <input type="hidden" name="user_id" value="{{ auth()->user()->id }}">

                            <div class="form-group text-left">
                                <label>Document Type</label>
                                <select name="doc_type" class="form-control" required>
                                    <option value="">Select</option>
                                    <option value="Issuance">Issuance</option>
                                    <option value="Correspondence">Correspondence</option>
                                    <option value="DPCR/IPCR">DPCR/IPCR</option>
                                    <option value="PAPS-PRE">PAPS-PRE</option>
                                    <option value="PPMP">PPMP</option>
                                    <option value="Reimbursement">Reimbursement</option>
                                    <option value="Travel Authority">Travel Authority</option>
                                    <option value="Other Document">Other Document</option>
                                </select>
                            </div>

                            <div class="form-group text-left">
                                <label>Document Title</label>
                                <textarea name="doc_title" class="form-control" rows="2" required></textarea>
                            </div>

                        <div class="form-group text-left">
            <label>Select Personnels</label>
            <select name="update_by[]" 
                    class="form-control select2" 
                    data-placeholder="Select users..."  
                    multiple="multiple"  
                    required>

                {{-- ✅ SHOW GROUP LIST ONLY IF NOT super_user OR staff --}}
                @if (!in_array(auth()->user()->role, ['super_user', 'staff']))
                    <option disabled>— Select by Group —</option>
                    @foreach ($groups as $group)
                        <option value="group:{{ $group->group_name }}">
                            {{ $group->group_name }}
                        </option>
                    @endforeach

                    <option disabled>──────────</option>
                @endif

                {{-- ✅ INDIVIDUAL USERS --}}
                <option disabled>— Select by Individual User —</option>
                @foreach ($users as $user)

                    {{-- ✅ REMOVE USER ID 1235 FOR super_user & staff --}}
                    @if (!(in_array(auth()->user()->role, ['super_user', 'staff']) && $user->id == 1235))
                        <option value="{{ $user->id }}">
                            {{ $user->fname }} {{ $user->lname }}
                        </option>
                    @endif

                @endforeach

            </select>
        </div>


                    <div class="form-group text-left">
                        <label>Attach File (optional)</label>
                        <input type="file" name="file" class="form-control" id="docFile">
                    </div>
                </form>
            `,
                showCancelButton: true,
                confirmButtonText: 'Submit',
                didOpen: () => {
                    setTimeout(() => {
                        $('.select2').select2({
                            theme: 'bootstrap4',
                            width: '100%',
                            dropdownParent: $('.swal2-popup')
                        });
                    }, 10);
                },
                preConfirm: () => {
                    const form = document.getElementById('docForm');
                    const formData = new FormData(form);
                    const fileInput = document.getElementById('docFile');

                    if (fileInput.files.length > 0) {
                        formData.append('file', fileInput.files[0]);
                    }

                    const loader = document.getElementById('page-loader');
                    const bar = document.getElementById('progress-bar');

                    // ✅ Close the modal so the loader becomes visible
                    Swal.close();

                    // ✅ Start loader animation
                    if (loader && bar) {
                        loader.style.display = 'flex';
                        animateBarTo(bar, 90, 8000);
                    }

                    return fetch("{{ route('storeDoctrack') }}", {
                            method: 'POST',
                            body: formData
                        })
                        .then(async response => {
                            if (!response.ok) {
                                const text = await response.text();
                                console.error("Error response:", response.status, text);
                                throw new Error(`HTTP ${response.status}: ${text}`);
                            }
                            return response.json();
                        })
                        //this is where the redirect to QR page happens 08/01/2025
                        // .then(data => {
                        //     if (loader) loader.style.display = 'none';
                        //     window.location.href = "{{ route('docslipForm', ['id' => '__REPLACE__']) }}"
                        //         .replace('__REPLACE__', data.id || '');
                        // })
                        .then(data => {
                            if (loader) loader.style.display = 'none';
                            window.location.href =
                                "{{ route('doctrackSlip') }}"; // ✅ REDIRECT TO THE LIST PAGE
                        })

                        .catch(error => {
                            if (loader) loader.style.display = 'none';
                            Swal.fire('Error', error.message, 'error');
                        });
                }
            });

            function animateBarTo(bar, target, duration) {
                const start = parseFloat(bar.style.width) || 0;
                const diff = target - start;
                const startTs = performance.now();
                requestAnimationFrame(function step(now) {
                    const pct = Math.min(1, (now - startTs) / duration);
                    bar.style.width = (start + diff * pct) + '%';
                    if (pct < 1) requestAnimationFrame(step);
                });
            }
        }
    </script>


</body>

</html>
@include('modal.dataP')
@include('modal.dpaPopup')
@include('modal.aboutDts')
