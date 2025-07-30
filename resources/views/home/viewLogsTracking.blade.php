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
                        <div class="card shadow">
                            <div class="card-header">
                                <h3 class="card-title">Tracking Documents Logs</h3>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="example1" class="table dataTable no-footer" style="font-size: 11px;">
                                        <thead>
                                            <tr>
                                                <th>Log Entry</th>
                                                <th>Date</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($logsAll as $log)
                                                @php
                                                    // Determine actor and target
                                                    $actor = match ($log->logs_status) {
                                                        3 => $log->updatedBy, // Acknowledged by recipient
                                                        default => $log->createdBy,
                                                    };

                                                    $target = match ($log->logs_status) {
                                                        1 => $log->updatedBy, // For "Created", show routed-to (if any)
                                                        2 => $log->updatedBy, // Routed to
                                                        default => null,
                                                    };

                                                    $fileName = $log->file_logs ?? 'N/A';

                                                    $statusBadge = match ($log->logs_status) {
                                                        1 => ['label' => 'Created', 'class' => 'badge-primary'],
                                                        2 => ['label' => 'Forwarded', 'class' => 'badge-warning'],
                                                        3 => ['label' => 'Acknowledged', 'class' => 'badge-success'],
                                                        4 => ['label' => 'Returned', 'class' => 'badge-danger'],
                                                        5 => ['label' => 'Checked', 'class' => 'badge-info'],
                                                        default => [
                                                            'label' => 'Updated',
                                                            'class' => 'badge-secondary',
                                                        ],
                                                    };

                                                @endphp

                                                <tr>
                                              
                                                    <td>
                                                        {{ $log->docslip_id }} -
                                                        <strong>{{ $actor->fname ?? 'N/A' }}
                                                            {{ $actor->lname ?? '' }}</strong>
                                                        <span
                                                            class="badge {{ $statusBadge['class'] }}">{{ $statusBadge['label'] }}</span>

                                                        file:
                                                        <span
                                                            class="text-primary font-weight-bold">{{ $fileName }}</span>

                                                        @if ($target)
                                                            → Routed to <strong>{{ $target->fname }}
                                                                {{ $target->lname }} </strong>and status is

                                                            @if ($log->logs_status == 2)
                                                                <span class="badge badge-warning">Pending</span>
                                                            @elseif ($log->logs_status == 5)
                                                                <span class="badge badge-info">Checked</span>
                                                            @endif
                                                        @endif

                                                        @if ($log->comments)
                                                            <br><em class="text-muted">Comment: "{{ $log->comments }}"</em>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <strong>{{ \Carbon\Carbon::parse($log->created_at)->format('M j, Y h:i A') }}</strong>
                                                    </td>
                                                </tr>


                                            @empty
                                                <tr>
                                                    <td colspan="2" class="text-center text-muted">No logs found.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>

                                    </table>
                                </div>
                            </div> <!-- /.card-body -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
