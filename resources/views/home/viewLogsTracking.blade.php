@extends('layouts.main')

@php
    $user = auth()->user();
    $userFullName = $user->fname . ' ' . $user->lname;
    $userDepartment = $user->department;
    $userRole = $user->role;
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
                                <h3 class="card-title">Tracking Documnets Logs</h3>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="example1" class="table dataTable no-footer" style="font-size:11px;">
                                        <thead>
                                            <tr>
                                                <th>Logs</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>

                                        <tbody>
                                            @foreach ($logsAll as $log)
                                                @php
                                                    $isAssignLog = $log->status_update == 2 && $log->assign_to; // Reassigned via assign_logs

                                                    $displayName = $isAssignLog
                                                        ? trim($log->assign_fname . ' ' . $log->assign_lname)
                                                        : ($log->status_update == 2
                                                            ? trim($log->original_fname . ' ' . $log->original_lname)
                                                            : trim($log->new_fname . ' ' . $log->new_lname));

                                                    $badgeClass = match ($log->status_update) {
                                                        2 => 'badge-warning',
                                                        3 => 'badge-success',
                                                        default => 'badge-secondary',
                                                    };

                                                    $badgeLabel = match ($log->status_update) {
                                                        2 => 'uploaded',
                                                        3 => 'acknowledged',
                                                        default => 'action',
                                                    };

                                                    $routedTo = $isAssignLog ? $log->assign_to : $log->new_destination;
                                                @endphp

                                                <tr>
                                                    <td>
                                                        <span style="font-weight:bold;">
                                                            {{ $displayName ?: 'Unknown User' }}
                                                        </span>

                                                        @if ($isAssignLog)
                                                            <span class="badge bg-info text-dark">re-assigned</span>
                                                        @else
                                                            <span class="badge {{ $badgeClass }}"
                                                                style="font-weight:bold;">
                                                                {{ $badgeLabel }}
                                                            </span>
                                                        @endif

                                                        the file
                                                        <span class="text-primary" style="font-weight:bold;">
                                                            {{ $log->new_file ?? 'N/A' }}
                                                        </span>

                                                        @if ($routedTo)
                                                            and routed it to
                                                            <span style="font-weight:bold;">{{ $routedTo }}</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <span style="font-weight:bold;">
                                                            {{ \Carbon\Carbon::parse($log->created_at)->format('M j, Y h:i:s A') }}
                                                        </span>
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
@endsection
