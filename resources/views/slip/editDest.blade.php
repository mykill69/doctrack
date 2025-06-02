@extends('layouts.main')
@section('body')
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
                        <form action="{{ route('storeRouteDoc') }}" method="POST">
                            @csrf
                            <div class="form-group row" hidden>
                                <label for="trans_remarks" class="col-md-3 col-form-label">Documents Type:</label>
                                <div class="col-md-9">
                                    
                                    <input type="text" class="form-control" name="doc_type" value="External Documents">
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
                                <label for="subject" class="col-md-3 col-form-label">This Document is For/To:</label>
                                <div class="col-md-9">
                                    <input type="text" class="form-control" id="subject" name="for_to" rows="3" value="{{ $routingSlips->r_destination }}" readonly>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="destination_1" class="col-md-3 col-form-label">Destination/Department:</label>
                                <div class="col-md-9">
                                    <select class="form-control" name="destination_1[]" data-placeholder="Select Offices">
                                        <option value="" disabled selected>Select from Offices</option>
                                        @foreach($offices as $office)
                                        <option value="{{ $office->office_name }}">
                                            {{ $office->office_name }}
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
                                <div class="col-md-9 offset-md-3">
                                    <button type="button" class="btn btn-warning" id="add-destination"><i class="fas fa-plus"></i> Add More</button>
                                </div>
                            </div>
                            <div class="form-group row">
                                <div class="col-md-3"></div>
                                <div class="col-md-9">
                                    <button type="submit" class="btn btn-primary">Submit Routing Slip</button>
                                    <a href="{{ route('viewSlip') }}" class="btn btn-danger">Cancel</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    $(document).ready(function() {
    var destinationCount = 1; // Keeps track of the number of destination fields
    // Function to add a new destination dropdown
    $('#add-destination').click(function() {
    destinationCount++;
    var newDestination = `
    <div class="form-group row">
        <label for="destination_${destinationCount}" class="col-md-3 col-form-label">Additional Destination ${destinationCount} (Optional):</label>
        <div class="col-md-9">
            <select class="form-control" name="destination_${destinationCount}[]" data-placeholder="Select Offices">
                <option value="" disabled selected>Select from Offices</option>
                @foreach($offices as $office)
                <option value="{{ $office->office_name }}">
                    {{ $office->office_name }}
                </option>
                @endforeach
            </select>
        </div>
    </div>`;
    
    // Append the new dropdown to the placeholder
    $('#additional-destinations').append(newDestination);
    });
    });
    </script>
    @endsection