@extends('layouts.main')
@php
use App\Models\Log; // Ensure you import your Log model here
@endphp
<style>
.select2-container--default .select2-selection--multiple .select2-selection__choice {
background-color: #007bff !important;
border-color: #187744 !important;
color: #fff;
padding: 0 10px;
margin-top: 0.31rem;
}
</style>
@section('body')
<div class="content-wrapper">
    <div class="content" style="padding-top: 1%;">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">SERVED DOCUMENTS</h3>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table id="example1" class="table table-bordered table-hover text-sm">
                                    <thead>
                                        <tr>
                                            <th>CTRL #</th>
                                            <th>DATE RECEIVED</th>
                                            <th>SOURCE</th>
                                            <th>SUBJECT MATTER</th>
                                            <th>FILE NAME</th>
                                            <th>ACTION UNIT</th>
                                            <th>RECEIVED BY/DATE</th>
                                            <th>ACTION TAKEN</th>
                                            <th>DATE RELEASED</th>
                                            <th>DATE UPDATED</th>
                                            <th>REMARKS</th>
                                            <th>TOTAL DURATION</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($logs as $log)
                                        @php
                                        $document = $log->document; // Access the related document for each log
                                        @endphp
                                        <tr>
                                            <td><a href="{{ route('slipForm', $log->route_id) }}" target="_blank">{{ $log->route_id }}</a></td>
                                            
                                            <td>
                                                {{ optional($document->routingSlip)->date_received ? \Carbon\Carbon::parse($document->routingSlip->date_received)->format('F d, Y') : 'N/A' }}
                                            </td>
                                            <td>{{ $document->routingSlip->source }}</td>
                                            <td>{{ $document->routingSlip->subject }}</td>
                                            <td>
                                                <a href="{{ route('documents.viewPdf', $document->id) }}" target="_blank">
                                                    <i class="fas fa-file-pdf text-danger"></i> {{ \Illuminate\Support\Str::limit($document->file_name, 22) }}
                                                </a>
                                            </td>
                                            <td>{{ optional($document->routingSlip)->pres_dept ?? 'N/A' }}</td>
                                            <td>{{ optional($document->routingSlip)->updated_at ? $document->routingSlip->updated_at->format('F j, Y') : 'N/A' }}</td>
                                            <td>
                                                <strong class="text-danger">{{ $document->for_to }}</strong>
                                                @if($document->routingSlip->assigned_to != null)
                                                , was re-rerouted to <strong class="text-danger">{{ $document->routingSlip->assigned_to }}</strong>
                                                @endif
                                            </td>
                                            <td>{{ $document->created_at->format('m-d-Y h:i:s A') }}</td>
                                            <td>{{ $log->updated_at->format('m-d-Y h:i:s A') }}</td>
                                            <td style="font-size:10px;">
                                                {{-- <span class="badge badge-success">Served</span> --}}
                                                <span class="badge badge-success" style="font-size:10px;">{{ $document->routingSlip->trans_remarks }}</span>
                                                <span class="badge badge-warning" style="margin-top: 2px;font-size:10px;">{{ $log->comments ?? '' }}</span>
                                            </td>
                                            <td>
                                                @php
                                                // Calculate the difference between document created_at and log updated_at
                                                $documentCreatedAt = $document->created_at;
                                                $logUpdatedAt = $log->updated_at ?? null;
                                                if ($logUpdatedAt) {
                                                $difference = $documentCreatedAt->diff($logUpdatedAt);
                                                $formattedDiff = "{$difference->days} days, {$difference->h} hours, {$difference->i} minutes";
                                                } else {
                                                $formattedDiff = 'N/A'; // No log updated time to compare
                                                }
                                                @endphp
                                                {{ $formattedDiff }}
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@include('modal.docAdd')
@include('modal.docEdit')
@include('modal.addTrans')
@include('modal.addRoutslip')
@endsection