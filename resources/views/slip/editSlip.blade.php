@extends('layouts.main')
@section('body')
    <div class="content-wrapper">
        <div class="content" style="padding-top: 1%;">
            <div class="container-fluid">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between">
                            <div class="col-md-10">

                                <h3 class="card-title text-bold">ATTACHEMENT:
                                    <a class="text-md badge badge-primary"
                                        href="{{ route('viewPdfslip', $routingSlips->id) }}" target="_blank">
                                        {{ $routingSlips->document }} &nbsp; <i class="fas fa-download"></i>
                                    </a>
                                </h3>

                            </div>
                            <div class="col-md-2 badge badge-danger">
                                <span
                                    class="card-title mb-0 badge badge-danger">{{ $routingSlips->created_at->format('M j, Y H:i:s') }}</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('updateSlip', $routingSlips->id) }}" method="POST"
                                enctype="multipart/form-data">
                                @csrf
                                @method('PUT')
                                @php
                                    $userDepartment = auth()->user()->department;
                                @endphp
                                <div class="form-group row">
                                    <label for="subject" class="col-md-3 col-form-label">Control Number:</label>
                                    <div class="col-md-9">
                                        <input type="number" class="form-control float-right" id="op_ctrl" name="op_ctrl"
                                            placeholder="Control number" required>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="source" class="col-md-3 col-form-label">Source:</label>
                                    <div class="col-md-9">
                                        <input type="text" class="form-control" name="source"
                                            value="{{ $routingSlips->source }}" readonly>
                                    </div>
                                    <input type="hidden" class="form-control" name="pres_dept"
                                        value="{{ $userDepartment }}" readonly>
                                </div>
                                <div class="form-group row">
                                    <label for="subject" class="col-md-3 col-form-label">Subject Matter:</label>
                                    <div class="col-md-9">
                                        <input type="text" class="form-control" name="subject"
                                            value="{{ $routingSlips->subject }}" readonly>
                                    </div>
                                </div>
                                {{-- <div class="form-group row">
                                <label for="subject" class="col-md-3 col-form-label">Document:</label>
                                <div class="col-md-9">
                                    <input type="file" class="form-control" name="document" value="{{ $routingSlips->document }}" readonly>
                                </div>
                            </div> --}}
                                <div class="form-group row">
                                    <label for="trans_remarks" class="col-md-3 col-form-label">Transaction Remarks:</label>
                                    <div class="col-md-9">
                                        <select class="form-control" id="transRemarks" name="trans_remarks" required>
                                            <option value="">Select Remarks</option>
                                            <option value="Appropriate Action">Appropriate Action</option>
                                            <option value="Comment &/or Recommendation">Comment &/or Recommendation</option>
                                            <option value="Information">Information</option>
                                            <option value="Endorsement">Endorsement</option>
                                            <option value="Edit/Correct">Edit/Correct</option>
                                            <option value="Review/Study">Review/Study</option>
                                            <option value="File">File</option>
                                            <option value="Draft Reply">Draft Reply</option>
                                            <option value="See the Office">See the Office</option>
                                            <option value="Calendar">Calendar</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <label for="r_destination" class="col-md-3 col-form-label">Action Unit:</label>
                                    <div class="col-md-9">
                                        <textarea class="form-control" id="r_destination" name="r_destination" rows="2"
                                            placeholder="This Document is For/To..." required></textarea>
                                    </div>
                                </div>
                                {{-- <div class="form-group row">
                                <label for="esig" class="col-md-3 col-form-label">Browse Documents:</label>
                                <div class="col-md-9">
                                    <input type="file" class="form-control" name="document" required>
                                </div>
                            </div> --}}
                                <!-- Added Upload Image Section -->
                                <div class="form-group row">
                                    <label for="esig" class="col-md-3 col-form-label">Upload E-Signature:</label>
                                    <div class="col-md-9">
                                        <input type="file" class="form-control" name="esig" accept="image/*">
                                    </div>
                                </div>
                                {{-- <div class="form-group row">
                                <label for="esig" class="col-md-3 col-form-label">Received by:</label>
                                <div class="col-md-9">
                                    <input type="text" class="form-control" name="received" required>
                                </div>
                            </div> --}}

                                <div class="form-group row">
                                    <label for="received_name" class="col-md-3 col-form-label">Received Name:</label>
                                    <div class="col-md-9">
                                        <input type="text" class="form-control float-right" id="received_name"
                                            name="received_name[]" placeholder="Name on the Received stamp" required>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <div class="col-md-3"></div>
                                    <div class="col-md-9">
                                        <input type="text" class="form-control" id="user_id" name="user_id"
                                            value="{{ $routingSlips->user_id }}" readonly hidden>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <div class="col-md-3"></div>
                                    <div class="col-md-9">
                                        <input type="text" class="form-control" name="route_status" value="2"
                                            readonly hidden>
                                    </div>
                                </div>
                                <div class="form-group row">
                                    <div class="col-md-3"></div>
                                    <div class="col-md-9">
                                        <button type="submit" class="btn btn-primary">Update Routing Slip</button>
                                        <a href="{{ route('viewSlip') }}" class="btn btn-danger">Cancel</a>
                                    </div>
                                </div>
                                @if ($errors->any())
                                    <div class="alert alert-danger">
                                        <ul>
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
