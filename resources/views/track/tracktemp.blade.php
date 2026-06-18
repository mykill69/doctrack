@extends('layouts.main')
<style>
    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background-color: #007bff !important;
        border-color: #187744 !important;
        color: #fff;
        padding: 0 10px;
        margin-top: 0.31rem;
    }

    .swal2-large-text {
        font-size: 1.2rem;
    }

    .swal2-article-popup {
        font-family: 'Segoe UI', sans-serif;
        font-size: 1rem;
        text-align: left;
        line-height: 1.6;
    }

    .swal-article-title {
        font-size: 1.5rem;
        font-weight: bold;
        color: #2c3e50;
        border-bottom: 2px solid #f4f4f4;
        padding-bottom: 0.5rem;
    }

    .swal-article-content {
        max-height: 400px;
        overflow-y: auto;
        white-space: pre-line;
        font-family: 'Segoe UI', sans-serif;
        font-size: 1rem;
        color: #333;
    }

    .swal-article-content::-webkit-scrollbar {
        width: 6px;
    }

    .swal-article-content::-webkit-scrollbar-thumb {
        background-color: #ccc;
        border-radius: 4px;
    }

    .swal-article-meta {
        display: flex;
        justify-content: space-between;
        flex-wrap: wrap;
        font-size: 0.9rem;
        color: #666;
    }

    .swal2-article-popup .swal-article-body {
        text-align: left;
    }

    .swal-article-title {
        font-weight: bold;
        font-size: 1.4rem;
    }

    .swal-article-content {
        font-size: 1rem;
        line-height: 1.6;
        white-space: pre-wrap;
    }

    .swal-article-meta {
        font-size: 0.9rem;
        color: #666;
    }
</style>

@section('body')
    @php
        use App\Models\Log; // Ensure you import your Log model here
    @endphp
    <div class="content-wrapper">
        @php
            // $userFullName = auth()->user()->fname . ' ' . auth()->user()->lname;
            $userFullName = strtolower(trim(auth()->user()->fname . ' ' . auth()->user()->lname));
        @endphp

        @php
            // Get all unique doc_ids (regardless of route_id)
            $uniqueDocs = $documents->unique('id');
        @endphp

        @if ($uniqueDocs->isNotEmpty())
            @foreach ($uniqueDocs as $doc)
                @php
                    $hasAccess =
                        \App\Models\Log::where('doc_id', $doc->id)
                            ->whereRaw('LOWER(TRIM(new_destination)) = ?', [$userFullName])
                            ->exists() || $doc->routing_slip_user_id === auth()->id();
                @endphp

                @if (!$hasAccess)
                    @continue
                @endif

                <div class="col-md-12" style="padding-top: 1%;">
                    <div class="card">
                        <div class="card-header p-2">
                            <ul class="nav nav-pills">
                                <li class="nav-item"><a class="nav-link active" href="#timeline" data-toggle="tab">Tracking</a>
                                </li>
                            </ul>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- LEFT COLUMN (Routing Timeline) -->
                                <div class="col-md-8">
                                    <div class="card shadow-sm">
                                        <div class="card-header bg-primary text-white">
                                            <h5 class="mb-0">Routing Timeline</h5>
                                        </div>
                                        <div class="card-body p-0">
                                            <div class="timeline timeline-inverse pt-3">
                                                @php
                                                    // Fetch logs for this doc_id
                                                    $logs = \App\Models\Log::where('doc_id', $doc->id)
                                                        ->orderBy('created_at')
                                                        ->get();
                                                    $previousLogTime = null;
                                                @endphp

                                                @foreach ($logs as $log)
                                                    @php
                                                        $isUserMatched =
                                                            strtolower(trim($log->new_destination)) === $userFullName;
                                                        $hasComment = !is_null($log->comments);
                                                        $isDisabled =
                                                            $log->status_update == 3 || !$isUserMatched || $hasComment;
                                                        $iconClass =
                                                            $log->status_update == 3
                                                                ? 'fas fa-check bg-success'
                                                                : 'fas fa-user bg-primary';
                                                    @endphp

                                                    <div>
                                                        <i class="{{ $iconClass }}"></i>
                                                        <div class="timeline-item">
                                                            <div class="timeline-header">
                                                                <div class="row">
                                                                    <div class="col-md-4 col-12">
                                                                        <span
                                                                            class="badge text-black text-lg d-block text-wrap text-left"
                                                                            style="font-weight: bold; white-space: normal; word-break: break-word;">
                                                                            {{ $log->new_destination }}
                                                                        </span>
                                                                    </div>
                                                                    <div class="col-md-2 text-center">
                                                                        <span class="text-sm"
                                                                            style="font-weight: bold;">Re-route to:</span>
                                                                    </div>
                                                                    <div class="col-md-3 text-center">
                                                                        <span class="text-sm"
                                                                            style="font-weight: bold;">Comments
                                                                            (optional)
                                                                            :</span>
                                                                    </div>
                                                                    <div class="col-md-3 text-right text-muted"
                                                                        style="font-size:11px;">
                                                                        @if ($previousLogTime)
                                                                            <span><i class="far fa-clock"></i>
                                                                                {{ $log->updated_at->diffForHumans($previousLogTime) }}
                                                                                previous</span>
                                                                        @else
                                                                            <span><i class="far fa-clock"></i>
                                                                                {{ $log->created_at->diffForHumans() }}</span>
                                                                        @endif
                                                                        <br>
                                                                        <small>{{ $log->updated_at->format('F j, Y h:i A') }}</small>
                                                                    </div>
                                                                </div>
                                                            </div>

                                                            <div class="timeline-footer mb-2 mt-2">
                                                                <div class="row align-items-center">
                                                                    <div class="col-md-4">
                                                                        <button type="button"
                                                                            class="btn btn-sm btn-primary swalAcknowledge"
                                                                            data-doc-id="{{ $log->doc_id }}"
                                                                            data-route-id="{{ $log->route_id }}"
                                                                            data-user-id="{{ $log->user_id }}"
                                                                            @if ($isDisabled) disabled @endif>
                                                                            {{ $log->status_update == 3 ? 'Acknowledged' : 'Acknowledge' }}

                                                                        </button>

                                                                        {{-- <button type="button"
                                                                            class="btn btn-sm btn-warning swalReRoute"
                                                                            data-route-id="{{ $log->route_id }}"
                                                                            data-user-id="{{ $log->user_id }}"
                                                                            @if ($isDisabled) disabled @endif>
                                                                            Re-route
                                                                        </button> --}}

                                                                        <button type="button"
                                                                            class="btn btn-sm btn-warning swalReRoute"
                                                                            data-route-id="{{ $log->route_id }}"
                                                                            data-routing-slip-id="{{ request('routing_slip_id') }}"
                                                                            data-user-id="{{ $log->user_id }}"
                                                                            @if ($isDisabled) disabled @endif>
                                                                            Re-route
                                                                        </button>

                                                                        @if (auth()->id() == 56)
                                                                            <button type="button"
                                                                                class="btn btn-sm btn-danger btnReopen"
                                                                                data-doc-id="{{ $log->doc_id }}"
                                                                                data-log-id="{{ $log->id }}"
                                                                                data-route-id="{{ $log->route_id }}"
                                                                                data-user-id="{{ $log->user_id }}">
                                                                                <i class="fas fa-undo"></i> Re-open
                                                                            </button>
                                                                        @endif
                                                                    </div>
                                                                    <div class="col-md-3">
                                                                        <span
                                                                            class="badge badge-success">{{ $log->assigned_to ?? '—' }}</span>
                                                                    </div>
                                                                    <div class="col-md-5">
                                                                        <span class="badge badge-info text-break d-block"
                                                                            style="white-space: pre-line; word-break: break-word; max-width: 100%;">
                                                                            {{ $log->comments ?? 'No comments' }}
                                                                        </span>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    @php $previousLogTime = $log->updated_at; @endphp
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- RIGHT COLUMN (Document Info Table) -->
                                <div class="col-md-4">
                                    <div class="card shadow-sm">
                                        <div class="card-header bg-primary text-white">
                                            <h5 class="mb-0">Document Information</h5>
                                        </div>
                                        <div class="card-body">
                                            @php
                                                $slip = $doc->matched_slip ?? null;
                                            @endphp
                                            <p>
                                                <strong>File Name:</strong>
                                                {{ str_replace('_', ' ', $doc->file_name) }}r
                                                <a href="{{ route('documents.viewPdf', $doc->id) }}" target="_blank"
                                                    class="ml-2 btn btn-info btn-sm text-white">
                                                    View PDF <i class="fas fa-file-pdf ml-1"></i>
                                                </a>
                                            </p>
                                            <table class="table table-sm table-bordered text-sm">
                                                <tr>
                                                    <th>Control No.</th>
                                                    <td>{{ $doc->route_id }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Subject</th>
                                                    <td>{{ $slip->subject ?? ($doc->subject ?? 'N/A') }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Source</th>
                                                    <td>{{ $slip->source ?? 'N/A' }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Action</th>
                                                    <td>{{ $slip->trans_remarks ?? 'N/A' }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Created By</th>
                                                    <td>{{ $doc->full_name }}</td>
                                                </tr>
                                                <tr>
                                                    <th>Recipient/s</th>
                                                    <td>
                                                        @foreach (explode(',', $slip->r_destination ?? '') as $dest)
                                                            @if (trim($dest))
                                                                <strong class="text-danger">{{ trim($dest) }}</strong>
                                                                @if (!$loop->last)
                                                                    ,
                                                                @endif
                                                            @endif
                                                        @endforeach
                                                        @if ($slip && $slip->assigned_to)
                                                            @php $au = $users->firstWhere('id', $slip->assigned_to); @endphp
                                                            , was re-assigned to
                                                            <strong class="text-danger">
                                                                {{ $au ? ucwords(strtolower($au->fname)) . ' ' . ucwords(strtolower($au->lname)) : $slip->assigned_to }}
                                                            </strong>
                                                        @endif
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <th>Date Received</th>
                                                    <td>{{ $slip && $slip->date_received ? \Carbon\Carbon::parse($slip->date_received)->format('F j, Y') : $doc->created_at->format('F j, Y h:i A') }}
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
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
                        <h1 class="badge badge-danger text-lg">No documents found or you do not have access to this
                            document.</h1>
                    </div>
                </div>
            </div>
        @endif


        <!-- SweetAlert2 -->
        <script src="{{ asset('template/plugins/sweetalert2/sweetalert2.min.js') }}"></script>


        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const csrfToken = '{{ csrf_token() }}';
                const newUser = {{ auth()->id() }};
                const redirectUrl = window.location.href;

                // Acknowledge Button
                $('.swalAcknowledge').click(function() {
                    const docId = $(this).data('doc-id');
                    const routeId = $(this).data('route-id');
                    const userId = $(this).data('user-id');

                    if (!docId || !routeId || !userId) {
                        console.error('Missing required data attributes.');
                        return;
                    }

                    const acknowledgeUrl = `/doctrack/public/documents/${docId}`;

                    Swal.fire({
                        title: 'Acknowledge Document',
                        icon: 'info',
                        html: `
                    <form id="swal-acknowledge-form" method="POST" action="${acknowledgeUrl}" enctype="multipart/form-data">
                        <input type="hidden" name="_token" value="${csrfToken}">
                        <input type="hidden" name="route_id" value="${routeId}">
                        <input type="hidden" name="user_id" value="${userId}">
                        <input type="hidden" name="new_user" value="${newUser}">
                        <input type="hidden" name="redirectUrl" value="${redirectUrl}">
                        <input type="hidden" name="status_update" value="3">
                        <div class="form-group mb-2">
                            <textarea class="form-control" id="comments" name="comments" rows="3"
                                placeholder="Add your comments here..." style="resize: none;" hidden></textarea>
                        </div>
                        <div class="text-end mt-3">
                            <button type="submit" class="btn btn-primary" form="swal-acknowledge-form">Acknowledge</button>
                        </div>
                    </form>
                `,
                        showConfirmButton: false,
                        customClass: {
                            popup: 'p-3 rounded-lg'
                        },
                        width: 600,
                        padding: '1.5rem',
                        heightAuto: false
                    });
                });

               // Re-route Button
$('.swalReRoute').click(function() {
    const routeId = $(this).data('route-id');
    const routingSlipId = $(this).data('routing-slip-id');
    const userId = $(this).data('user-id');

    if (!routeId || !userId) {
        console.error('Missing required data attributes.');
        return;
    }

    const reRouteUrl = "{{ route('updateAssign', ['routeId' => '__ID__']) }}".replace('__ID__', routeId);

    Swal.fire({
        title: 'Re-route Slip Form',
        icon: 'info',
        html: `
            <form id="swal-reroute-form" method="POST" action="${reRouteUrl}" enctype="multipart/form-data">
                <input type="hidden" name="_token" value="${csrfToken}">
                <input type="hidden" name="route_id" value="${routeId}">
                <input type="hidden" name="routing_slip_id" value="${routingSlipId}">
                <input type="hidden" name="user_id" value="${userId}">
                <input type="hidden" name="new_user" value="${newUser}">
                <input type="hidden" name="redirectUrl" value="${redirectUrl}">
                <div class="input-group mb-3">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fa fa-users"></i></span>
                    </div>
                    <textarea class="form-control" name="assigned_to" rows="2" placeholder="Type the name of the Personnel/s to be assigned here" required></textarea>
                </div>
                <div class="input-group mb-3">
                    <div class="input-group-prepend">
                        <span class="input-group-text"><i class="fa fa-comment"></i></span>
                    </div>
                    <textarea class="form-control" name="assign_com" rows="2" placeholder="Write your comments here" required></textarea>
                </div>
                <div class="text-end mt-3">
                    <button type="submit" class="btn btn-warning" form="swal-reroute-form">Submit</button>
                </div>
            </form>
        `,
        showConfirmButton: false,
        width: 650,
        padding: '2em'
    });
});

                // Re-open Button (ONLY USER 56)
                $('.btnReopen').click(function() {
                    const docId = $(this).data('doc-id');
                    const logId = $(this).data('log-id');
                    const routeId = $(this).data('route-id');
                    const userId = $(this).data('user-id');

                    if (!docId || !logId || !routeId || !userId) return;

                    const reopenUrl = `/doctrack/public/documents/${docId}`;

                    Swal.fire({
                        title: 'Re-open Document',
                        icon: 'warning',
                        html: `
                    <form method="POST" action="${reopenUrl}">
                        <input type="hidden" name="_token" value="${csrfToken}">
                        <input type="hidden" name="route_id" value="${routeId}">
                        <input type="hidden" name="user_id" value="${userId}">
                        <input type="hidden" name="new_user" value="${newUser}">
                        <input type="hidden" name="log_id" value="${logId}">
                        <input type="hidden" name="status_update" value="2">
                        <input type="hidden" name="redirectUrl" value="${redirectUrl}">
                        <div class="text-end mt-3">
                            <button type="submit" class="btn btn-danger">Re-open</button>
                        </div>
                    </form>
                `,
                        showConfirmButton: false,
                        width: 600
                    });
                });
            });
        </script>








        {{-- <script>
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
    </script> --}}
        @include('modal.addRoute')
        @include('modal.reAssign')
    @endsection
