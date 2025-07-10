@extends('layouts.main')
<style>
    .no-left-radius {
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
    }

    .select2-selection__choice {
        background-color: #007bff !important;
        /* Blue background */
        color: #fff !important;
        /* White text */
        border: none !important;
        padding: 2px 10px;
        border-radius: 0.2rem;
        margin-top: 4px;
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
                            <h3 class="card-title text-bold">CONTROL NUMBER: <a  class="text-md badge badge-primary" href="{{ route('viewPdfslip', $routingSlips->id) }}" target="_blank">
                                {{ $routingSlips->rslip_id }} &emsp; {{ $routingSlips->document }}&nbsp; <i class="fas fa-eye"></i>
                            </a></h3>
                        </div>
                        <div class="col-md-2 badge badge-danger">
                            <span class="card-title mb-0 badge badge-danger">{{ $routingSlips->created_at->format('M j, Y H:i:s') }}</span>
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
                            <input type="hidden" class="form-control" id="fullName" name="full_name" value="{{ auth()->user()->fname }} {{ auth()->user()->lname }}" readonly required>
                            <input type="hidden" class="form-control" id="department" name="department" value="{{ auth()->user()->department }}" readonly required>
                            <div class="form-group row">
                                <label for="subject" class="col-md-3 col-form-label">Source:</label>
                                <div class="col-md-9">
                                    <input type="text" class="form-control" name="source" value="{{ $routingSlips->source }}" readonly>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="subject" class="col-md-3 col-form-label">Subject Matter:</label>
                                <div class="col-md-9">
                                    <input type="text" class="form-control" name="subject" value="{{ $routingSlips->subject }}" readonly>
                                </div>
                            </div>
                            <input type="hidden" class="form-control" name="route_id" value="{{ $routingSlips->rslip_id }}" readonly required>
                            <input type="hidden" class="form-control" name="file_name" value="{{ $routingSlips->document }}"  >
                            <div class="form-group row" hidden>
                                <label for="subject" class="col-md-3 col-form-label">Purpose:</label>
                                <div class="col-md-9">
                                    <textarea class="form-control" id="purpose" name="purpose" rows="2" placeholder="Type your purpose here..."></textarea>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="subject" class="col-md-3 col-form-label">This Document was For/To:</label>
                                <div class="col-md-9">
                                    <input type="text" class="form-control" id="subject" name="for_to" rows="3" value="{{ $routingSlips->r_destination }}" readonly>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="subject" class="col-md-3 col-form-label">Re-assigned For/To:</label>
                                <div class="col-md-9">
                                    <input type="text" class="form-control" id="subject" name="for_to" rows="3" value="{{ $routingSlips->assigned_to }}" readonly>
                                </div>
                            </div>
                            <div class="form-group row">
                                    <label for="new_destination" class="col-md-3 col-form-label">Name of Users:</label>
                                    <div class="col-md-9">
                                        <select class="form-control select2" name="new_destination[]" id="new_destination" multiple required>
    @foreach ($users as $user)
        <option value="{{ $user->id }}">
            {{ $user->fname }} {{ $user->lname }}
        </option>
    @endforeach
</select>
                                    </div>
                                </div>
                            <div id="additional-destinations"></div>
                            <div class="form-group row">
                                <div class="col-md-9">
                                    <input type="hidden" class="form-control" name="doc_stat" value="2" readonly >
                                </div>
                            </div>
                            <div class="form-group row">
                                <div class="col-md-9">
                                    <input type="hidden" class="form-control" id="user_id" name="user_id" value="{{ (($routingSlips->user_id)) }}" readonly >
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


@endsection