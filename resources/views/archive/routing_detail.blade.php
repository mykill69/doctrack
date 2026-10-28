@extends('layouts.main')

@php
    $title = $title ?? 'Archived Routing Slip';
    $latest = $slips->last();
@endphp

@section('body')
    <div class="content-wrapper">
        <div class="content" style="padding-top: 1%;">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-outline card-dark">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-route mr-2"></i>{{ $title }}
                                    <span class="badge badge-dark ml-2">Read-only</span>
                                </h3>
                                <div class="card-tools">
                                    <a href="{{ route('archive.history') }}" class="btn btn-sm btn-outline-secondary">
                                        <i class="fas fa-arrow-left mr-1"></i> Back to Archived History
                                    </a>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-3">
                                        <small class="text-muted d-block">CTRL #</small>
                                        <strong>{{ $rslipId }}</strong>
                                    </div>
                                    <div class="col-md-3">
                                        <small class="text-muted d-block">Subject Matter</small>
                                        <strong>{{ $latest->subject ?: 'N/A' }}</strong>
                                    </div>
                                    <div class="col-md-3">
                                        <small class="text-muted d-block">Action Unit</small>
                                        <strong>{{ $latest->pres_dept ?: 'N/A' }}</strong>
                                    </div>
                                    <div class="col-md-3">
                                        <small class="text-muted d-block">Archived</small>
                                        <strong>
                                            {{ $latest->archived_at ? \Carbon\Carbon::parse($latest->archived_at)->format('M d, Y h:i A') : 'N/A' }}
                                        </strong>
                                        <small class="text-muted d-block">
                                            by {{ $archivedBy ? trim($archivedBy->fname . ' ' . $archivedBy->lname) : 'N/A' }}
                                        </small>
                                    </div>
                                </div>

                                <h5 class="mt-4"><i class="fas fa-layer-group mr-1"></i> Routing Slip Versions</h5>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm" style="font-size: 0.82rem;">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>SOURCE</th>
                                                <th>RECEIVED BY/DATE</th>
                                                <th>DESTINATION</th>
                                                <th>ASSIGNED TO</th>
                                                <th>REMARKS</th>
                                                <th>CREATED</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($slips as $index => $slip)
                                                <tr>
                                                    <td>{{ $index + 1 }}</td>
                                                    <td>{{ $slip->source ?: 'N/A' }}</td>
                                                    <td>
                                                        {{ $slip->received_name ?: 'N/A' }}
                                                        @if ($slip->date_received)
                                                            <br><small class="text-muted">{{ \Carbon\Carbon::parse($slip->date_received)->format('M d, Y') }}</small>
                                                        @endif
                                                    </td>
                                                    <td>{{ $slip->r_destination ?: 'N/A' }}</td>
                                                    <td>{{ $slip->assigned_to ?: 'N/A' }}</td>
                                                    <td>
                                                        @if ($slip->trans_remarks)
                                                            <span class="badge badge-success">{{ $slip->trans_remarks }}</span>
                                                        @endif
                                                        @if ($slip->other_remarks)
                                                            <span class="badge badge-danger">{{ $slip->other_remarks }}</span>
                                                        @endif
                                                        @if (!$slip->trans_remarks && !$slip->other_remarks)
                                                            <span class="text-muted">N/A</span>
                                                        @endif
                                                    </td>
                                                    <td>{{ $slip->created_at ? \Carbon\Carbon::parse($slip->created_at)->format('m-d-Y h:i A') : 'N/A' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <h5 class="mt-4"><i class="fas fa-paperclip mr-1"></i> Attached Files</h5>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm" style="font-size: 0.82rem;">
                                        <thead>
                                            <tr>
                                                <th>FILE NAME</th>
                                                <th>TYPE</th>
                                                <th>SUBJECT</th>
                                                <th>DEPARTMENT</th>
                                                <th>UPLOADED</th>
                                                <th>ACTION</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($documents as $document)
                                                <tr>
                                                    <td>{{ $document->file_name }}</td>
                                                    <td>{{ $document->doc_type ?: 'N/A' }}</td>
                                                    <td>{{ $document->subject ?: 'N/A' }}</td>
                                                    <td>{{ $document->department ?: 'N/A' }}</td>
                                                    <td>{{ $document->created_at ? \Carbon\Carbon::parse($document->created_at)->format('m-d-Y h:i A') : 'N/A' }}</td>
                                                    <td>
                                                        <a href="{{ route('archive.file.documentAttachment', $document->id) }}"
                                                            target="_blank" class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-file-pdf mr-1"></i> View
                                                        </a>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6" class="text-center text-muted">No attached files.</td>
                                                </tr>
                                            @endforelse
                                            @if ($latest->document)
                                                <tr>
                                                    <td>{{ $latest->document }}</td>
                                                    <td colspan="3" class="text-muted">Routing slip upload</td>
                                                    <td>{{ $latest->created_at ? \Carbon\Carbon::parse($latest->created_at)->format('m-d-Y h:i A') : 'N/A' }}</td>
                                                    <td>
                                                        <a href="{{ route('archive.file.routing', $latest->id) }}"
                                                            target="_blank" class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-file-pdf mr-1"></i> View
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endif
                                        </tbody>
                                    </table>
                                </div>

                                <h5 class="mt-4"><i class="fas fa-history mr-1"></i> Routing History</h5>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm" style="font-size: 0.82rem;">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>ACTION</th>
                                                <th>STATUS</th>
                                                <th>DESTINATION</th>
                                                <th>ASSIGNED TO</th>
                                                <th>REMARKS</th>
                                                <th>DATE</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($logs as $index => $log)
                                                @php
                                                    $actor = $usersById->get((int) $log->user_id);
                                                @endphp
                                                <tr>
                                                    <td>{{ $index + 1 }}</td>
                                                    <td>
                                                        <strong>{{ $actor ? trim($actor->fname . ' ' . $actor->lname) : 'N/A' }}</strong>
                                                        <br><small class="text-muted">{{ $log->action ?: 'N/A' }}</small>
                                                    </td>
                                                    <td>{{ $log->status_update ?? 'N/A' }}</td>
                                                    <td>{{ $log->new_destination ?: 'N/A' }}</td>
                                                    <td>{{ $log->assigned_to ?: 'N/A' }}</td>
                                                    <td>{{ $log->comments ?: 'N/A' }}</td>
                                                    <td>{{ $log->created_at ? \Carbon\Carbon::parse($log->created_at)->format('m-d-Y h:i A') : 'N/A' }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="7" class="text-center text-muted">No routing history.</td>
                                                </tr>
                                            @endforelse
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
@endsection
