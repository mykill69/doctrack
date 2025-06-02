@extends('layouts.main')
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
@php
use App\Models\Log; // Ensure you import your Log model here
@endphp
<div class="content-wrapper">
    @if($documents->isNotEmpty())
    @foreach($documents as $document)
    <div class="col-md-12" style="padding-top: 1%;">
        <div class="card">
            <div class="card-header p-2">
                <ul class="nav nav-pills">
                    <li class="nav-item"><a class="nav-link active" href="#timeline" data-toggle="tab">Tracking</a></li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-pane" id="timeline">
                    <div class="timeline timeline-inverse">
                        <div class="time-label">
                            <span class="bg-danger">{{ $document->created_at->format('M j, Y') }}</span>
                        </div>
                        <div>
                            <i class="fas fa-file-alt bg-primary"></i>
                            <div class="timeline-item">
                                <span class="time"><i class="far fa-clock"></i> {{ $document->created_at->format('H:i:s A') }}</span>
                                <h3 class="timeline-header">
                                <p>{{ str_replace('_', ' ', $document->file_name) }}
                                    
                                    <a href="{{ route('documents.viewPdf', $document->id) }}" target="_blank" style="color: #007bff;">[view]</a>
                                </p>
                                </h3>
                                <div class="timeline-body">
                                    <table class="table" style="font-size: 13px;">
                                        <thead>
                                            <tr>
                                                <th>Subject</th>
                                                <th>Document No.</th>
                                                <th>Transaction Type</th>
                                                <th>Created by</th>
                                                <th>Recipient/s</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>{{ $document->subject }}</td>
                                                <td>{{ $document->route_id }}</td>
                                                <td>{{ $document->doc_type }}</td>
                                                {{-- <td>{{ $document->purpose }}</td> --}}
                                                <td>{{ $document->full_name }}</td>
                                                <td>{{ $document->for_to }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        @php
                        $logs = Log::where('doc_id', $document->id)
                        ->orderBy('created_at')
                        ->get()
                        ->groupBy(function($log) {
                        return $log->route_id . '_' . $log->new_destination;
                        })
                        ->map(function($groupedLogs) {
                        return $groupedLogs->last();
                        });
                        $previousLogTime = null;
                        $userDepartment = auth()->user()->department;
                        $allServed = $logs->every(function ($log) {
                        return $log->status_update == 3;
                        });
                        @endphp
                        @foreach ($logs as $log)
                        <div>
                            @php
                            $isDepartmentMatch = $log->new_destination == $userDepartment;
                            $isDisabled = ($log->status_update == 3 || !$isDepartmentMatch);
                            $iconClass = $log->status_update == 3 ? 'fas fa-check bg-success' : ($isDisabled ? 'fas fa-user bg-primary' : 'fa fa-user bg-primary');
                            @endphp
                            <i class="{{ $iconClass }}"></i>
                            <div class="timeline-item">
                                <div class="timeline-header">
                                    <div class="row">
                                        <div class="col-md-5">
                                            <a href="#" class="badge badge-danger text-lg" style="font-weight: bold;">{{ $log->new_destination }}</a>
                                        </div>
                                        <div class="col-md-2 text-center">
                                            <span class="text-sm" style="font-weight: bold;">Re-reoute to: </span>
                                        </div>
                                        <div class="col-md-3 text-center">
                                            <span class="text-sm" style="font-weight: bold;">Comments (optional): </span>
                                        </div>
                                        <div class="col-md-2 text-right" style="font-size:11px;color: #999999;">
                                            @if ($previousLogTime)
                                            @php
                                            $timeDifference = $log->updated_at->diffForHumans($previousLogTime);
                                            @endphp
                                            <span class="time"><i class="far fa-clock"></i> {{ $timeDifference }} previous entry</span>
                                            @else
                                            @php
                                            $timeDifference = $log->created_at->diffForHumans();
                                            @endphp
                                            <span class="time"><i class="far fa-clock"></i> {{ $timeDifference }}</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="timeline-footer mb-2">
                                    <div class="row">
                                        <div class="col-md-5">
                                            <div class="destination-item">
                                                <!-- Acknowledge button -->
                                                <button class="btn btn-sm acknowledge-btn {{ $log->status_update == 3 ? 'bg-primary' : 'bg-primary' }}"
                                                data-toggle="modal"
                                                data-target="#exampleModal1"
                                                onclick="captureTime('{{ $log->id }}')"
                                                @if($isDisabled) disabled @endif>
                                                {{ $log->status_update == 3 ? 'Acknowledged' : 'Acknowledge' }}
                                                </button>
                                                <!-- Re-assign button -->
                                                <button class="btn btn-sm bg-warning re-route-btn"
                                                data-toggle="modal"
                                                data-target="#reRouteModal"
                                                onclick="updateAssign('{{ $log->route_id }}')"
                                                @if($isDisabled) disabled @endif>
                                                Re-route
                                                </button>
                                            </div>
                                        </div>
                                        <div class="col-md-3">&emsp;
                                            <span class="badge badge-success">{{ $log->assigned_to }}</span>
                                        </div>
                                        <div class="col-md-4">
                                            <span class="badge badge-success">{{ $log->comments }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @php
                        $previousLogTime = $log->updated_at;
                        @endphp
                        @endforeach
                        @if ($allServed)
                        <div class="timeline-footer mb-2">
                            <i class="fa fa-check bg-success"></i>
                            <div class="text-muted" style="margin-left: 6%;margin-top: 5px;">All documents were served successfully.</div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endforeach
    @else
    <div class="card">
        <div class="card-header">
            <div class="col-md-12 text-center">
                <h1 class="badge badge-danger text-lg">No documents found or you do not have access to this document.</h1>
            </div>
        </div>
    </div>
    @endif
</div>
<script>
function updateAssign(routeId) {
$.ajax({
url: '/update-assign/' + routeId,
method: 'POST',
data: {
_token: '{{ csrf_token() }}'
},
success: function(response) {
alert(response.message);
},
error: function(xhr) {
alert(xhr.responseJSON.message);
}
});
}
</script>
@include('modal.addRoute')
@include('modal.reAssign')
@endsection