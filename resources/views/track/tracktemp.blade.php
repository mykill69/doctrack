@extends('layouts.main')
<style>
.select2-container--default .select2-selection--multiple .select2-selection__choice {
background-color: #007bff !important;
border-color: #187744 !important;
color: #fff;
padding: 0 10px;
margin-top: 0.31rem;
}

.swal2-article-popup .swal-article-body {
    text-align: left;
}
.swal2-article-popup textarea {
    width: 100%;
    font-size: 1rem;
}
</style>

@section('body')
@php
use App\Models\Log; // Ensure you import your Log model here
@endphp
<div class="content-wrapper">
    @php
        $userFullName = auth()->user()->fname . ' ' . auth()->user()->lname;
    @endphp

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

                                <!-- Original File Entry -->
                                <div>
                                    <i class="fas fa-file-alt bg-primary"></i>
                                    <div class="timeline-item">
                                        <span class="time">
                                            <i class="far fa-clock"></i> {{ $document->created_at->format('H:i:s A') }}
                                        </span>
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
                                                        <td>{{ $document->full_name }}</td>
                                                        <td>{{ $document->for_to }}</td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>

                                @php
                                    $logs = \App\Models\Log::where('doc_id', $document->id)
                                        ->orderBy('created_at')
                                        ->get()
                                        ->groupBy(fn($log) => $log->route_id . '_' . $log->new_destination)
                                        ->map(fn($groupedLogs) => $groupedLogs->last());

                                    $previousLogTime = null;
                                    $allServed = $logs->every(fn($log) => $log->status_update == 3);
                                @endphp

                                <!-- Log Entries -->
                                @foreach ($logs as $log)
                                    @php
                                        $isUserMatched = $log->new_destination === $userFullName;
                                        $isDisabled = ($log->status_update == 3 || !$isUserMatched);
                                        $iconClass = $log->status_update == 3 ? 'fas fa-check bg-success' : ($isDisabled ? 'fas fa-user bg-primary' : 'fa fa-user bg-primary');
                                    @endphp
                                    <div>
                                        <i class="{{ $iconClass }}"></i>
                                        <div class="timeline-item">
                                            <div class="timeline-header">
                                                <div class="row">
                                                    <div class="col-md-5">
                                                        <a href="#" class="badge badge-danger text-lg" style="font-weight: bold;">{{ $log->new_destination }}</a>
                                                    </div>
                                                    <div class="col-md-2 text-center">
                                                        <span class="text-sm" style="font-weight: bold;">Re-route to:</span>
                                                    </div>
                                                    <div class="col-md-3 text-center">
                                                        <span class="text-sm" style="font-weight: bold;">Comments (optional):</span>
                                                    </div>
                                                    <div class="col-md-2 text-right" style="font-size:11px;color: #999999;">
                                                        @if ($previousLogTime)
                                                            @php
                                                                $timeDifference = $log->updated_at->diffForHumans($previousLogTime);
                                                            @endphp
                                                            <span class="time"><i class="far fa-clock"></i> {{ $timeDifference }} previous entry</span>
                                                        @else
                                                            <span class="time"><i class="far fa-clock"></i> {{ $log->created_at->diffForHumans() }}</span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="timeline-footer mb-2">
                                                <div class="row">
                                                    <div class="col-md-5">
                                                       <button type="button"
    class="btn btn-sm bg-primary swalAcknowledge"
    data-doc-id="{{ $document->id }}"
    data-route-id="{{ $log->route_id }}"
    data-user-id="{{ $document->user_id }}"
    @if($isDisabled) disabled @endif>
    {{ $log->status_update == 3 ? 'Acknowledged' : 'Acknowledge' }}
</button>

                                                        <button type="button"
    class="btn btn-sm bg-warning swalReRoute"
    data-route-id="{{ $log->route_id }}"
    data-user-id="{{ $document->user_id }}"
    @if($isDisabled) disabled @endif>
    Re-route
</button>
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
                                    @php $previousLogTime = $log->updated_at; @endphp
                                @endforeach

                                @if ($allServed)
                                    <div class="timeline-footer mb-2">
                                        <i class="fa fa-check bg-success"></i>
                                        <div class="text-muted" style="margin-left: 6%;margin-top: 5px;">
                                            All documents were served successfully.
                                        </div>
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


<!-- SweetAlert2 -->
        <script src="{{ asset('template/plugins/sweetalert2/sweetalert2.min.js') }}"></script>



<script>
document.addEventListener('DOMContentLoaded', function () {
    // SweetAlert for Acknowledge
    $('.swalAcknowledge').click(function () {
        const docId = $(this).data('doc-id');
        const routeId = $(this).data('route-id');
        const userId = $(this).data('user-id');
        const newUser = {{ auth()->id() }};
        const redirectUrl = window.location.href;

        Swal.fire({
            title: 'Acknowledge Document',
            icon: 'info',
            html: `
                <form id="swal-acknowledge-form"
                      action="/documents/update/${docId}"
                      method="POST">
                    @csrf
                    <input type="hidden" name="route_id" value="${routeId}">
                    <input type="hidden" name="user_id" value="${userId}">
                    <input type="hidden" name="new_user" value="${newUser}">
                    <input type="hidden" name="redirectUrl" value="${redirectUrl}">
                    <input type="hidden" name="status_update" value="3">

                    <div class="form-group text-left mb-3">
                        <label><strong>Comments (optional):</strong></label>
                        <textarea class="form-control" name="comments" rows="3"
                            placeholder="Add your comments here..."></textarea>
                    </div>
                </form>
            `,
            showCancelButton: true,
            confirmButtonText: 'Acknowledge',
            cancelButtonText: 'Cancel',
            width: '600px',
            padding: '2em',
            preConfirm: () => {
                document.getElementById('swal-acknowledge-form').submit();
            }
        });
    });

    // SweetAlert for Re-route
    $('.swalReRoute').click(function () {
        const routeId = $(this).data('route-id');
        const userId = $(this).data('user-id');
        const newUser = {{ auth()->id() }};
        const redirectUrl = window.location.href;

        Swal.fire({
            title: 'Re-route Slip Form',
            icon: 'info',
            html: `
                <form id="swal-reroute-form"
                      action="/updateAssign/${routeId}"
                      method="POST">
                    @csrf
                    <input type="hidden" name="route_id" value="${routeId}">
                    <input type="hidden" name="user_id" value="${userId}">
                    <input type="hidden" name="new_user" value="${newUser}">
                    <input type="hidden" name="redirectUrl" value="${redirectUrl}">

                    <div class="form-group text-left mb-3">
                        <label><strong>Assign to:</strong></label>
                        <textarea class="form-control" name="assigned_to" rows="3"
                            placeholder="Type the name of the Personnel/s to be assigned here" required></textarea>
                    </div>

                    <div class="form-group text-left mb-3">
                        <label><strong>Comments:</strong></label>
                        <textarea class="form-control" name="assign_com" rows="3"
                            placeholder="Write your comments here" required></textarea>
                    </div>
                </form>
            `,
            width: '650px',
            padding: '2em',
            showCancelButton: true,
            confirmButtonText: 'Submit',
            cancelButtonText: 'Cancel',
            preConfirm: () => {
                const form = document.getElementById('swal-reroute-form');
                const assignedTo = form.assigned_to.value.trim();
                const comments = form.assign_com.value.trim();

                if (!assignedTo || !comments) {
                    Swal.showValidationMessage('All fields are required.');
                    return false;
                }

                form.submit();
            }
        });
    });
});
</script>


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