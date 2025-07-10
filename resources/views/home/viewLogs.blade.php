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
                                <h3 class="card-title">All Logs Details</h3>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="example1" class="table dataTable no-footer" style="font-size:11px;">
                                        <thead>
                                            <tr>
                                                <th>Logs</th>
                                                <th>Date Time</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($logsAll as $log)
                                                @php
                                                    $displayName =
                                                        $log->status_update == 2
                                                            ? $log->original_fname . ' ' . $log->original_lname
                                                            : $log->new_fname . ' ' . $log->new_lname;

                                                    $isMatchOriginal =
                                                        $log->original_user_department == $log->new_destination;
                                                    $isMatchNew = $log->new_user_department == $log->new_destination;
                                                    $shouldSkipRoutingInfo = $isMatchOriginal || $isMatchNew;

                                                    $isVisible =
                                                        $userRole === 'records_officer' ||
                                                        $log->user_id == $user->id ||
                                                        $log->new_user == $user->id ||
                                                        $log->new_destination === $userDepartment ||
                                                        $log->new_destination === $userFullName;
                                                @endphp

                                                @if ($isVisible)
                                                    <tr>
                                                        <td>
                                                            <span style="font-weight:bold;">
                                                                {{ $displayName ?? 'Unknown User' }}
                                                            </span>

                                                            @if ($log->status_update == 2)
                                                                <span class="badge badge-warning"
                                                                    style="font-weight:bold;">uploaded</span>
                                                            @elseif($log->status_update == 3)
                                                                <span class="badge badge-success"
                                                                    style="font-weight:bold;">acknowledged</span>
                                                            @else
                                                                <span class="badge badge-secondary"
                                                                    style="font-weight:bold;">{{ $log->status_update }}</span>
                                                            @endif

                                                            @if (!$shouldSkipRoutingInfo)
                                                                the file <span class="text-primary"
                                                                    style="font-weight:bold;">{{ $log->new_file }}</span>
                                                                and routed it to <span
                                                                    style="font-weight:bold;">{{ $log->new_destination }}</span>
                                                            @else
                                                                the file <span class="text-primary"
                                                                    style="font-weight:bold;">{{ $log->new_file }}</span>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            <span style="font-weight:bold;">
                                                                {{ \Carbon\Carbon::parse($log->created_at)->format('M j, Y h:i:s A') }}
                                                            </span>
                                                        </td>
                                                    </tr>
                                                @endif
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
