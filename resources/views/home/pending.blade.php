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
                            <h3 class="card-title">PENDING DOCUMENTS</h3>
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
                                        <th>REMARKS</th>
                                        <th>OFFICE</th>
                                        <th>ACTION</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @php
                                    $userDepartment = auth()->user()->department;
                                    $userId = auth()->user()->id;
                                @endphp
                                @foreach($logs as $docId => $logGroup) 
                                    @foreach($logGroup as $log) 
                                        @if(isset($log) && $log && ($log->new_destination == $userDepartment || $userId == $log->user_id))
                                            <tr>
                                                <td><a href="{{ route('slipForm', $log->route_id) }}" target="_blank">{{ $log->route_id }}</a></td>
                                                <td>
                                                    {{ $log->date_received ? \Carbon\Carbon::parse($log->date_received)->format('F d, Y') : 'N/A' }}
                                                </td>
                                                <td>{{ $log->source ?? 'N/A' }}</td>
                                                <td>{{ $log->subject ?? 'N/A' }}</td>
                                                <td>
                                                    <a href="{{ route('documents.viewPdf', $log->id) }}" target="_blank">
                                                        <i class="fas fa-file-pdf text-danger"></i> {{ \Illuminate\Support\Str::limit($log->file_name, 22) }}
                                                    </a>
                                                </td>
                                                <td>{{ $log->pres_dept ?? 'N/A' }}</td>
                                                <td>{{ $log->updated_at ? \Carbon\Carbon::parse($log->updated_at)->format('F j, Y') : 'N/A' }}</td>

                                                <td>
                                                    <strong class="text-danger">{{ $log->for_to }}</strong>
                                                    @if($log->assigned_to)
                                                        , was re-rerouted to <strong class="text-danger">{{ $log->assigned_to }}</strong>
                                                    @endif
                                                </td>
                                                
                                                <td>{{ $log->created_at->format('m-d-Y h:i:s A') }}</td>
                                                <td style="font-size:10px;">
                                                    <span class="badge badge-success" style="font-size:10px;">{{ $log->trans_remarks ?? 'N/A' }}</span>
                                                    <span class="badge badge-warning" style="font-size:10px;">{{ $log->assign_com ?? '' }}</span>
                                                </td>
                                                <td>{{ $log->new_destination }}</td>
                                                <td>
                                                    @if($userId == $log->user_id)
                                                        <button class="btn btn-secondary" disabled><i class="fas fa-pen"></i></button>
                                                    @else
                                                        <a href="{{ route('tracking', ['route_id' => $log->route_id]) }}" class="btn btn-primary" style="text-decoration:none;">
                                                            <i class="fas fa-pen"></i>
                                                        </a>
                                                    @endif
                                                </td>    
                                            </tr>
                                        @endif
                                    @endforeach
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
    <!-- /.content -->
</div>
@include('modal.docAdd')
@include('modal.docEdit')
@include('modal.addTrans')
@include('modal.addRoutslip')
@endsection