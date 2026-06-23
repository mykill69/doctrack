@extends('layouts.main')

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

<style type="text/css">
    .no-left-radius {
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
    }

    .select2-container--default .select2-selection--multiple {
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
        height: auto;
        min-height: 38px;
        padding: 0.375rem 0.75rem;
        font-size: 1rem;
        line-height: 1.5;
    }

    .select2-selection__rendered {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
    }


    #page-loader {
        z-index: 99999 !important;
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.95);
        justify-content: center;
        align-items: center;
        flex-direction: column;
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

    /* OVERRIDE main.blade's high z-index for Select2 */
    .select2-container {
        z-index: 1000 !important;
    }

    .select2-dropdown {
        z-index: 1000 !important;
    }

    /* SweetAlert must be above everything */
    .swal2-container {
        z-index: 99999 !important;
    }

    .swal2-overlay {
        z-index: 99998 !important;
    }
</style>

@section('body')
    <div id="page-loader"
        class="position-fixed top-0 start-0 w-100 h-100 flex-column justify-content-center align-items-center"
        style="z-index:1055; display:none; background:linear-gradient(135deg,#f8f9fa,#e9ecef); font-family:'Segoe UI',Tahoma,sans-serif">
        <img src="{{ asset('template/img/cpsu_logo.png') }}" alt="MIS logo" style="width:110px;height:auto;margin-bottom:28px">
        <div class="progress-loader" style="width:220px;height:12px;background:#dee2e6;border-radius:6px;overflow:hidden">
            <div id="progress-bar" style="width:0;height:100%;background:#0d6efd;transition:width .4s ease"></div>
        </div>
        <p style="margin-top:1.3rem;font-size:1.15rem;font-weight:500;color:#343a40">
            Sending notification, please wait...
        </p>
    </div>

    <div class="content-wrapper">
        <div class="content" style="padding-top: 1%;">
            <div class="container-fluid">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header d-flex flex-wrap align-items-center justify-content-between">
                            <div class="d-flex flex-column flex-md-row align-items-md-center col-md-8 col-12 mb-2 mb-md-0">
                                <h3 class="card-title text-bold mb-0">
                                    CONTROL NUMBER:
                                    <a class="text-md badge badge-primary"
                                        href="{{ route('viewPdfslip', $routingSlips->id) }}" target="_blank">
                                        {{ $routingSlips->rslip_id }} &emsp; {{ $routingSlips->document }}&nbsp; <i
                                            class="fas fa-eye"></i>
                                    </a>
                                </h3>
                            </div>
                            <div
                                class="d-flex align-items-center col-md-4 col-12 justify-content-md-end justify-content-start gap-2">
                                <span class="badge bg-danger">
                                    {{ $routingSlips->created_at->format('M j, Y H:i:s') }}
                                </span>
                            </div>
                        </div>
                        <div class="card-body">
                            <form id="route-form"
                                action="{{ isset($document) ? route('updateRouteDoc', $document->route_id) : route('storeRouteDoc') }}"
                                method="POST">
                                @csrf
                                @if (isset($document))
                                    @method('PUT')
                                @endif

                                <div class="form-group row" hidden>
                                    <label for="trans_remarks" class="col-md-3 col-form-label">Documents Type:</label>
                                    <div class="col-md-9">
                                        <input type="text" class="form-control" name="doc_type"
                                            value="External Documents">
                                    </div>
                                </div>
                                <input type="hidden" class="form-control" id="fullName" name="full_name"
                                    value="{{ auth()->user()->fname }} {{ auth()->user()->lname }}" readonly required>
                                <input type="hidden" class="form-control" id="department" name="department"
                                    value="{{ auth()->user()->department }}" readonly required>
                                <div class="form-group row">
                                    <label for="subject" class="col-md-3 col-form-label">Source:</label>
                                    <div class="col-md-9">
                                        <input type="text" class="form-control" name="source"
                                            value="{{ $routingSlips->source }}" readonly>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="subject" class="col-md-3 col-form-label">Subject Matter:</label>
                                    <div class="col-md-9">
                                        <input type="text" class="form-control" name="subject"
                                            value="{{ $routingSlips->subject }}" readonly>
                                    </div>
                                </div>
                                <input type="hidden" name="routing_slip_id" value="{{ $routingSlips->id }}">
                                <input type="hidden" class="form-control" name="route_id"
                                    value="{{ $routingSlips->rslip_id }}" readonly required>
                                <input type="hidden" class="form-control" name="file_name"
                                    value="{{ $routingSlips->document }}" readonly>

                                <div class="form-group row" hidden>
                                    <label for="subject" class="col-md-3 col-form-label">Purpose:</label>
                                    <div class="col-md-9">
                                        <textarea class="form-control" id="purpose" name="purpose" rows="2" placeholder="Type your purpose here..."></textarea>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="subject" class="col-md-3 col-form-label">This Document is For/To:</label>
                                    <div class="col-md-9">
                                        <input type="text" class="form-control" id="subject" name="for_to"
                                            value="{{ $routingSlips->r_destination }}" readonly>
                                    </div>
                                </div>
                                <div id="user-select-container" class="form-group row align-items-center">
                                    <label for="routed_to" class="col-md-3 col-form-label">Name of Personnel and Group
                                        Name:</label>
                                    <div class="col-md-9">
                                        <select class="form-control select2" name="routed_users[]" id="routed_users"
                                            data-placeholder="Select users..." multiple required>
                                            <option disabled>— Select by Group —</option>
                                            @foreach ($groups as $group)
                                                <option value="group:{{ $group->group_name }}"
                                                    {{ in_array('group:' . $group->group_name, $selectedUsers) ? 'selected' : '' }}>
                                                    {{ $group->group_name }}
                                                </option>
                                            @endforeach
                                            <option disabled>──────────</option>
                                            <option disabled>— Select by Individual User —</option>
                                            @foreach ($users as $user)
                                                <option value="{{ $user->fname }} {{ $user->lname }}"
                                                    {{ in_array($user->fname . ' ' . $user->lname, $selectedUsers) ? 'selected' : '' }}>
                                                    {{ $user->fname }} {{ $user->lname }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div id="additional-destinations"></div>
                                    <input type="hidden" class="form-control" name="doc_stat" value="2" readonly>
                                    <input type="hidden" class="form-control" id="user_id" name="user_id"
                                        value="{{ $routingSlips->user_id }}" readonly>
                                </div>
                            </form>

                            {{-- BUTTON GROUP --}}
                            <div class="form-group row">
                                <div class="col-md-3"></div>
                                <div class="col-md-9">
                                    {{-- Submit main Routing Slip form --}}
                                    <button type="submit" form="route-form" class="btn btn-primary">
                                        <i class="fas fa-check mr-1"></i> Submit Routing Slip
                                    </button>

                                    {{-- Route Back to President with SweetAlert --}}
                                    <button type="button" class="btn btn-warning" id="routeBackBtn">
                                        <i class="fas fa-undo-alt mr-1"></i> Route back to President
                                    </button>

                                    {{-- Hidden form for route back --}}
                                    <form id="routeBackForm"
                                        action="{{ route('routeBackToPresident', $routingSlips->id) }}" method="POST"
                                        class="d-inline">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="other_remarks" id="otherRemarksInput">
                                    </form>

                                    {{-- Cancel --}}
                                    <a href="{{ route('viewSlip') }}" class="btn btn-danger">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/js/select2.min.js"></script>


    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('route-form');
            const loader = document.getElementById('page-loader');
            const bar = document.getElementById('progress-bar');
            const card = document.querySelector('.card');

            // Remove dropdownParent - let Select2 append to body
            $('#routed_users').select2({
                placeholder: "Select users...",
                width: '100%'
            });

            if (form) {
                form.addEventListener('submit', function(e) {
                    if (card) card.style.display = 'none';
                    setTimeout(() => {
                        loader.style.display = 'flex';
                    }, 100);
                    animateBarTo(90, 8000);
                });
            }

            function animateBarTo(target, duration) {
                const start = parseFloat(bar.style.width) || 0;
                const diff = target - start;
                const startTs = performance.now();
                requestAnimationFrame(function step(now) {
                    const pct = Math.min(1, (now - startTs) / duration);
                    bar.style.width = (start + diff * pct) + '%';
                    if (pct < 1) requestAnimationFrame(step);
                });
            }

            // Route Back Button - SweetAlert
            document.getElementById('routeBackBtn').addEventListener('click', function() {
                // Force close Select2
                $('#routed_users').select2('close');

                Swal.fire({
                    title: 'Route Back to President',
                    text: 'Please provide remarks (optional):',
                    icon: 'question',
                    input: 'textarea',
                    inputPlaceholder: 'Enter your remarks here...',
                    inputAttributes: {
                        'aria-label': 'Enter your remarks here'
                    },
                    showCancelButton: true,
                    confirmButtonColor: '#ffc107',
                    confirmButtonText: '<i class="fas fa-undo-alt mr-1"></i> Route Back',
                    cancelButtonText: '<i class="fas fa-times mr-1"></i> Cancel',
                    customClass: {
                        popup: 'rounded-lg'
                    },
                    didOpen: () => {
                        // Force hide Select2 when SweetAlert opens
                        $('.select2-dropdown, .select2-dropdown--below, .select2-results')
                        .hide();
                        $('.select2-container--open').removeClass('select2-container--open');
                    },
                    preConfirm: (remarks) => {
                        document.getElementById('otherRemarksInput').value = remarks || '';
                        document.getElementById('routeBackForm').submit();
                    }
                });
            });
        });

        $(document).ready(function() {
            $('#destination_1').select2({
                placeholder: "Select users..."
            });
        });
    </script>
@endsection
