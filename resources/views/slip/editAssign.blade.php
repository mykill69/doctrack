@extends('layouts.main')
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
    .no-left-radius {
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
    }

    /* SELECT2 FIXES */
    .select2-container--default .select2-selection--multiple {
        border: 1px solid #ced4da !important;
        border-radius: 0.25rem !important;
        min-height: 38px !important;
        padding: 4px 8px !important;
        font-size: 1rem !important;
        line-height: 1.5 !important;
        width: 100% !important;
    }

    .select2-container {
        width: 100% !important;
        display: block !important;
    }

    .select2-selection__rendered {
        display: flex !important;
        flex-wrap: wrap !important;
        align-items: center !important;
        padding: 0 !important;
    }

    .select2-search--inline {
        flex: 1 !important;
        min-width: 100px !important;
    }

    .select2-search--inline .select2-search__field {
        margin-top: 0 !important;
        height: 28px !important;
    }

    .select2-selection__choice {
        margin: 2px !important;
    }

    .select2-dropdown {
        z-index: 99999 !important;
        position: absolute !important;
    }

    .select2-results__options {
        max-height: 300px !important;
    }

    #page-loader {
        z-index: 999999 !important;
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
        from { opacity: 0; transform: translateY(10px); }
        to { opacity: 1; transform: translateY(0); }
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
                        <div class="card-header d-flex justify-content-between">
                            <div class="col-md-10">
                                <h3 class="card-title text-bold">CONTROL NUMBER: 
                                    <a class="text-md badge badge-primary" href="{{ route('viewPdfslip', $routingSlips->id) }}" target="_blank">
                                        {{ $routingSlips->rslip_id }} &emsp; {{ $routingSlips->document }}&nbsp; <i class="fas fa-eye"></i>
                                    </a>
                                </h3>
                            </div>
                            <div class="col-md-2">
                                <span class="badge badge-danger">{{ $routingSlips->created_at->format('M j, Y H:i:s') }}</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('updateReroute', $routingSlips->id) }}" method="POST">
                                @csrf
                                <div class="form-group row">
                                    <label class="col-md-3 col-form-label">Documents Type:</label>
                                    <div class="col-md-9">
                                        <input type="text" class="form-control" value="{{ $document ? $document->doc_type : 'Not Found' }}" readonly>
                                    </div>
                                </div>
                                <input type="hidden" name="full_name" value="{{ auth()->user()->fname }} {{ auth()->user()->lname }}">
                                <input type="hidden" name="department" value="{{ auth()->user()->department }}">
                                
                                <div class="form-group row">
                                    <label class="col-md-3 col-form-label">Source:</label>
                                    <div class="col-md-9">
                                        <input type="text" class="form-control" value="{{ $routingSlips->source }}" readonly>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label class="col-md-3 col-form-label">Subject Matter:</label>
                                    <div class="col-md-9">
                                        <input type="text" class="form-control" value="{{ $routingSlips->subject }}" readonly>
                                    </div>
                                </div>
                                <input type="hidden" name="route_id" value="{{ $routingSlips->rslip_id }}">
                                <input type="hidden" name="file_name" value="{{ $routingSlips->document }}">
                                
                                <div class="form-group row">
                                    <label class="col-md-3 col-form-label">This Document was For/To:</label>
                                    <div class="col-md-9">
                                        <input type="text" class="form-control" value="{{ $routingSlips->r_destination }}" readonly>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label class="col-md-3 col-form-label">Re-assigned For/To:</label>
                                    <div class="col-md-9">
                                        <input type="text" class="form-control" value="{{ $routingSlips->assigned_to ?? 'Not yet reassigned' }}" readonly>
                                    </div>
                                </div>

                                {{-- Select2 with proper wrapper --}}
                                <div class="form-group row">
                                    <label class="col-md-3 col-form-label pt-2">Personnel & Group:</label>
                                    <div class="col-md-9" style="position: relative;">
                                        <select class="select2" name="new_destination[]" id="new_destination" 
                                            multiple="multiple" required
                                            data-placeholder="Select destinations..." 
                                            style="width: 100%;">
                                            <optgroup label="Groups">
                                                @foreach ($groups as $group)
                                                    <option value="group:{{ $group->group_name }}">{{ $group->group_name }}</option>
                                                @endforeach
                                            </optgroup>
                                            <optgroup label="Individual Users">
                                                @foreach ($users as $user)
                                                    <option value="{{ $user->fname }} {{ $user->lname }}">{{ $user->fname }} {{ $user->lname }}</option>
                                                @endforeach
                                            </optgroup>
                                        </select>
                                    </div>
                                </div>

                                <input type="hidden" name="doc_stat" value="2">
                                <input type="hidden" name="user_id" value="{{ $routingSlips->user_id }}">

                                <div class="form-group row mt-4">
                                    <div class="col-md-3"></div>
                                    <div class="col-md-9">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-paper-plane mr-1"></i> Submit Routing Slip
                                        </button>
                                        <a href="{{ route('viewSlip') }}" class="btn btn-danger">
                                            <i class="fas fa-times mr-1"></i> Cancel
                                        </a>
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
        $(document).ready(function() {
            // Destroy existing instance first
            if ($('#new_destination').hasClass("select2-hidden-accessible")) {
                $('#new_destination').select2('destroy');
            }
            
            // Initialize Select2 fresh
            $('#new_destination').select2({
                placeholder: "Select destinations...",
                width: '100%',
                allowClear: true,
                closeOnSelect: false,
                dropdownAutoWidth: false,
                dropdownCssClass: 'select2-dropdown-fix'
            });
        });
    </script>
@endsection