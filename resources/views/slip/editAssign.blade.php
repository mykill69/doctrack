@extends('layouts.main')
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    .no-left-radius {
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
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

    .select2-selection__rendered {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
    }

    /* Fix dropdown to appear above loader */
    .select2-dropdown {
        z-index: 9999 !important;
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
</style>
@section('body')
    {{-- Page Loader --}}
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
                        <div class="card-header d-flex justify-content-between">
                            <div class="col-md-10">
                                <h3 class="card-title text-bold">CONTROL NUMBER: <a class="text-md badge badge-primary"
                                        href="{{ route('viewPdfslip', $routingSlips->id) }}" target="_blank">
                                        {{ $routingSlips->rslip_id }} &emsp; {{ $routingSlips->document }}&nbsp; <i
                                            class="fas fa-eye"></i>
                                    </a></h3>
                            </div>
                            <div class="col-md-2 badge badge-danger">
                                <span
                                    class="card-title mb-0 badge badge-danger">{{ $routingSlips->created_at->format('M j, Y H:i:s') }}</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('updateReroute', $routingSlips->id) }}" method="POST">
                                @csrf
                                <div class="form-group row">
                                    <label for="trans_remarks" class="col-md-3 col-form-label">Documents Type:</label>
                                    <div class="col-md-9">
                                        <input type="text" class="form-control" id="doc_type" name="doc_type"
                                            value="{{ $document ? $document->doc_type : 'Not Found' }}" readonly>
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
                                <input type="hidden" class="form-control" name="route_id"
                                    value="{{ $routingSlips->rslip_id }}" readonly required>
                                <input type="hidden" class="form-control" name="file_name"
                                    value="{{ $routingSlips->document }}">
                                <div class="form-group row" hidden>
                                    <label for="subject" class="col-md-3 col-form-label">Purpose:</label>
                                    <div class="col-md-9">
                                        <textarea class="form-control" id="purpose" name="purpose" rows="2" placeholder="Type your purpose here..."></textarea>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="subject" class="col-md-3 col-form-label">This Document was For/To:</label>
                                    <div class="col-md-9">
                                        <input type="text" class="form-control" id="subject" name="for_to"
                                            rows="3" value="{{ $routingSlips->r_destination }}" readonly>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="subject" class="col-md-3 col-form-label">Re-assigned For/To:</label>
                                    <div class="col-md-9">
                                        <input type="text" class="form-control" id="subject" name="for_to"
                                            rows="3" value="{{ $routingSlips->assigned_to }}" readonly>
                                    </div>
                                </div>
                                <div id="destination-select-container">
                                    <div class="form-group row">
                                        <label for="new_destination" class="col-md-3 col-form-label">Name of
                                            Users:</label>
                                        <div class="col-md-9">
                                            <select class="form-control select2" name="new_destination[]"
                                                id="new_destination" multiple required>
                                                <option disabled>— Select by Position —</option>
                                                <option value="position:1">President</option>
                                                <option value="position:2">VPAA</option>
                                                <option value="position:3">VPAF</option>
                                                <option value="position:4">Office Heads</option>
                                                <option value="position:5">Deans</option>
                                                <option value="position:6">Campus Administrators</option>
                                                <option value="position:7">Directors</option>

                                                <option disabled>──────────</option>
                                                <option disabled>— Select by Group —</option>
                                                @foreach ($groups as $group)
                                                    <option value="group:{{ $group->group_name }}">
                                                        {{ $group->group_name }}</option>
                                                @endforeach

                                                <option disabled>──────────</option>
                                                <option disabled>— Select by Individual User —</option>
                                                @foreach ($users as $user)
                                                    <option value="{{ $user->fname }} {{ $user->lname }}">
                                                        {{ $user->fname }} {{ $user->lname }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div id="additional-destinations"></div>
                                <div class="form-group row">
                                    <div class="col-md-9">
                                        <input type="hidden" class="form-control" name="doc_stat" value="2"
                                            readonly>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <div class="col-md-9">
                                        <input type="hidden" class="form-control" id="user_id" name="user_id"
                                            value="{{ $routingSlips->user_id }}" readonly>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <div class="col-md-3"></div>
                                    <div class="col-md-9">
                                        <button type="submit" class="btn btn-primary">Submit Routing Slip</button>
                                        <a href="{{ route('viewSlip') }}" class="btn btn-danger">Cancel</a>
                                    </div>
                                </div>
                            </form>
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
            const card = document.querySelector('.card'); // Target the card container to hide

            // Initialize Select2 for new_destination
            $('#new_destination').select2({
                placeholder: "Select users...",
                width: '100%',
                dropdownParent: $(
                    '#destination-select-container') // IMPORTANT: make sure this container exists
            });

            if (form) {
                form.addEventListener('submit', function(e) {
                    if (card) {
                        card.style.display = 'none';
                    }

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
        });
    </script>
    <script>
        $(document).ready(function() {
            $('#destination_1').select2({
                placeholder: "Select users..."
            });
        });
    </script>
@endsection
