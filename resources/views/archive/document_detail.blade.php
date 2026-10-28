@extends('layouts.main')

@php
    $title = 'ARCHIVED DOCUMENT TRACKING HISTORY';
    $latest = $entries->last();
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
                                    <i class="fas fa-file-alt mr-2"></i>{{ $title }}
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
                                        <small class="text-muted d-block">Tracking Code</small>
                                        <strong>{{ $docslipId }}</strong>
                                    </div>
                                    <div class="col-md-3">
                                        <small class="text-muted d-block">Document Title</small>
                                        <strong>{{ $latest->doc_title ?: 'N/A' }}</strong>
                                    </div>
                                    <div class="col-md-3">
                                        <small class="text-muted d-block">Document Type</small>
                                        <strong>{{ $latest->doc_type ?: 'N/A' }}</strong>
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

                                <h5 class="mt-4"><i class="fas fa-layer-group mr-1"></i> Tracking Slip Versions</h5>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm" style="font-size: 0.82rem;">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>CTRL #</th>
                                                <th>PERSONNEL</th>
                                                <th>UPDATED BY</th>
                                                <th>STATUS</th>
                                                <th>COMMENTS</th>
                                                <th>CREATED</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($entries as $index => $entry)
                                                @php
                                                    $updater = $usersById->get((int) $entry->update_by);
                                                @endphp
                                                <tr>
                                                    <td>{{ $index + 1 }}</td>
                                                    <td>{{ $entry->ctrl_no ?: 'N/A' }}</td>
                                                    <td>{{ $entry->user_name ?: 'N/A' }}</td>
                                                    <td>{{ $updater ? trim($updater->fname . ' ' . $updater->lname) : 'N/A' }}</td>
                                                    <td>{{ $entry->doctrack_stat }}</td>
                                                    <td>{{ $entry->comments ?: 'N/A' }}</td>
                                                    <td>{{ $entry->created_at ? \Carbon\Carbon::parse($entry->created_at)->format('m-d-Y h:i A') : 'N/A' }}</td>
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
                                                <th>#</th>
                                                <th>FILE NAME</th>
                                                <th>UPLOADED</th>
                                                <th>ACTION</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($files as $index => $file)
                                                <tr>
                                                    <td>{{ $index + 1 }}</td>
                                                    <td>{{ $file->file ?: 'N/A' }}</td>
                                                    <td>{{ $file->created_at ? \Carbon\Carbon::parse($file->created_at)->format('m-d-Y h:i A') : 'N/A' }}</td>
                                                    <td>
                                                        <a href="{{ route('archive.file.document', $file->id) }}"
                                                            target="_blank" class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-file-pdf mr-1"></i> View
                                                        </a>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="4" class="text-center text-muted">No attached files.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>

                                <h5 class="mt-4"><i class="fas fa-history mr-1"></i> Tracking History</h5>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm" style="font-size: 0.82rem;">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>PERSONNEL</th>
                                                <th>STATUS</th>
                                                <th>FILE</th>
                                                <th>COMMENTS</th>
                                                <th>DATE</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($trackingLogs as $index => $log)
                                                @php
                                                    $actor = $usersById->get((int) ($log->update_by ?: $log->user_id));
                                                @endphp
                                                <tr>
                                                    <td>{{ $index + 1 }}</td>
                                                    <td>
                                                        <strong>{{ $actor ? trim($actor->fname . ' ' . $actor->lname) : 'N/A' }}</strong>
                                                    </td>
                                                    <td>{{ $log->logs_status }}</td>
                                                    <td>{{ $log->file_logs ?: 'N/A' }}</td>
                                                    <td>{{ $log->comments ?: 'N/A' }}</td>
                                                    <td>{{ $log->created_at ? \Carbon\Carbon::parse($log->created_at)->format('m-d-Y h:i A') : 'N/A' }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6" class="text-center text-muted">No tracking history.</td>
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
