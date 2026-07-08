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

    .select2-dropdown {
        z-index: 9999 !important;
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
                                    Recall - CTRL #
                                    <a class="text-md badge badge-primary"
                                        href="{{ route('viewPdfslip', $routingSlips->id) }}" target="_blank">
                                        {{ $routingSlips->rslip_id }}
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
                            <form id="recall-form" action="{{ route('updateRouteDocRecall', $routingSlips->rslip_id) }}"
                                method="POST" enctype="multipart/form-data">
                                @csrf
                                @method('PUT')

                                <input type="hidden" name="full_name"
                                    value="{{ auth()->user()->fname }} {{ auth()->user()->lname }}">
                                <input type="hidden" name="department" value="{{ auth()->user()->department }}">
                                <input type="hidden" name="route_id" value="{{ $routingSlips->rslip_id }}">
                                <input type="hidden" name="doc_stat" value="2">
                                <input type="hidden" name="user_id" value="{{ $routingSlips->user_id }}">

                                {{-- Source --}}
                                <div class="form-group row">
                                    <label class="col-md-3 col-form-label font-weight-bold">Source: <span
                                            class="text-danger">*</span></label>
                                    <div class="col-md-9">
                                        <input type="text" class="form-control" name="source"
                                            value="{{ old('source', $routingSlips->source) }}" required>
                                    </div>
                                </div>

                                {{-- Subject Matter --}}
                                <div class="form-group row">
                                    <label class="col-md-3 col-form-label font-weight-bold">Subject Matter: <span
                                            class="text-danger">*</span></label>
                                    <div class="col-md-9">
                                        <textarea class="form-control" name="subject" rows="4" required style="resize: vertical; min-height: 80px;">{{ old('subject', $routingSlips->subject) }}</textarea>
                                    </div>
                                </div>

                                {{-- File --}}
                                <div class="form-group row">
                                    <label class="col-md-3 col-form-label">File Name:</label>
                                    <div class="col-md-9">
                                        <div class="input-group mb-2">
                                            <input type="file" class="form-control" name="file_name" accept=".pdf">
                                            @if (!empty($routingSlips->document))
                                                <span class="input-group-text">
                                                    <a href="{{ asset('storage/documents/' . $routingSlips->document) }}"
                                                        target="_blank" style="color: #007bff;">
                                                        {{ $routingSlips->document }}
                                                    </a>
                                                </span>
                                            @endif
                                        </div>
                                        @if (!empty($routingSlips->document))
                                            <small class="text-danger">Leave blank if you do not want to change the
                                                file.</small>
                                        @endif
                                    </div>
                                </div>

                                {{-- For/To --}}
                                <div class="form-group row">
                                    <label class="col-md-3 col-form-label">This Document is For/To:</label>
                                    <div class="col-md-9">
                                        <input type="text" class="form-control"
                                            value="{{ $routingSlips->r_destination }}" readonly>
                                    </div>
                                </div>

                                {{-- Personnel --}}
                                <div class="form-group row">
                                    <label class="col-md-3 col-form-label">Name of Personnel and Group Name:</label>
                                    <div class="col-md-9">
                                        <select class="form-control select2" name="routed_users[]" id="routed_users"
                                            data-placeholder="Select users..." multiple>
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
                                </div>
                            </form>

                            {{-- Buttons --}}
                            <div class="form-group row mt-4">
                                <div class="col-md-3"></div>
                                <div class="col-md-9">
                                    <button type="submit" form="recall-form" class="btn btn-primary">
                                        <i class="fas fa-save mr-1"></i> Submit Recall
                                    </button>
                                    <a href="{{ route('viewSlip') }}" class="btn btn-danger">
                                        <i class="fas fa-times mr-1"></i> Cancel
                                    </a>
                                </div>
                            </div>

                            {{-- Validation Errors --}}
                            @if ($errors->any())
                                <div class="alert alert-danger mt-3">
                                    <ul class="mb-0">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
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
            const form = document.getElementById('recall-form');
            const loader = document.getElementById('page-loader');
            const bar = document.getElementById('progress-bar');
            const card = document.querySelector('.card');

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
        });
    </script>
@endsection
