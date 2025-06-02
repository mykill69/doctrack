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

        <!-- Main content -->
        <div class="content" style="padding-top: 1%;">
            <div class="container-fluid">
                <div class="row">

                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">DOCUMENT LOGBOOK</h3>
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
                                            @php
                                                $processedLogs = [];
                                                $logsToShow = [];
                                                $currentUserDepartment = auth()->user()->department;
                                                $currentUserId = auth()->user()->id;
                                            @endphp
                                            @foreach ($logs as $log)
                                                @php
                                                    $document = $log->document; // Added the missing semicolon here
                                                    $uniqueIdentifier =
                                                        $log->route_id .
                                                        '-' .
                                                        $log->doc_id .
                                                        '-' .
                                                        $log->new_destination;
                                                    // Skip logs if they have a new user already processed
                                                    if (
                                                        isset($processedLogs[$uniqueIdentifier]) &&
                                                        $processedLogs[$uniqueIdentifier]['hasNewUser']
                                                    ) {
                                                        continue;
                                                    }
                                                    // Process the log based on whether it has a new user
                                                    if (!is_null($log->new_user)) {
                                                        $processedLogs[$uniqueIdentifier] = ['hasNewUser' => true];
                                                        $logsToShow[$uniqueIdentifier] = $log;
                                                    } else {
                                                        if (!isset($processedLogs[$uniqueIdentifier])) {
                                                            $processedLogs[$uniqueIdentifier] = ['hasNewUser' => false];
                                                            $logsToShow[$uniqueIdentifier] = $log;
                                                        }
                                                    }
                                                    // Ensure the current user department is included in the logs to show
                                                    if ($currentUserDepartment === $log->new_destination) {
                                                        $logsToShow[$uniqueIdentifier] = $log;
                                                    }
                                                @endphp
                                            @endforeach
                                            @foreach ($logsToShow as $log)
                                                @php
                                                    $document = $log->document;
                                                @endphp
                                                <tr>
                                                    <td>
                                                        @if ($log->route_id == 0)
                                                            N/A
                                                        @else
                                                            <a href="{{ route('slipForm', ['id' => $log->route_id]) }}"
                                                                target="_blank"
                                                                style="color: #007bff;">{{ $log->route_id }}</a>
                                                        @endif
                                                    </td>

                                                    <td>
                                                        {{ optional($document->routingSlip)->date_received
                                                            ? \Carbon\Carbon::parse($document->routingSlip->date_received)->format('F d, Y')
                                                            : ($document->created_at
                                                                ? \Carbon\Carbon::parse($document->created_at)->format('F d, Y')
                                                                : 'N/A') }}
                                                    </td>
                                                    <td>{{ optional($document->routingSlip)->source ?? ($document->department ?? 'N/A') }}
                                                    </td>
                                                    <td>{{ optional($document->routingSlip)->subject ?? ($document->subject ?? 'N/A') }}
                                                    </td>
                                                    <td>
                                                        <a href="{{ route('documents.viewPdf', $document->id) }}"
                                                            target="_blank" style="color: #007bff;">
                                                            <i class="fas fa-file-pdf text-danger"></i>
                                                            {{ \Illuminate\Support\Str::limit($document->file_name, 22) }}
                                                        </a>
                                                    </td>
                                                    <td>{{ optional($document->routingSlip)->pres_dept ?? 'N/A' }}</td>
                                                    <td>{{ optional($document->routingSlip)->updated_at ? $document->routingSlip->updated_at->format('F j, Y') : 'N/A' }}
                                                    </td>
                                                    <td>
                                                        <strong class="text-danger">{{ $document->for_to }}</strong>
                                                        @if (optional($document->routingSlip)->assigned_to)
                                                            , was re-routed to <strong
                                                                class="text-danger">{{ optional($document->routingSlip)->assigned_to }}</strong>
                                                        @endif
                                                    </td>
                                                    <td>{{ $document->created_at->format('m-d-Y h:i:s A') }}</td>
                                                    <td>{{ $log->updated_at->format('m-d-Y h:i:s A') }}</td>
                                                    <td style="font-size:10px;">
                                                        {{--  @if ($document->assn_code != null && $log->status_update == 2)
                                                <span class="badge badge-info">Routed back to <br> Records Office</span>
                                                @elseif($log->status_update == 3)
                                                <span class="badge badge-success">Served</span>
                                                @else
                                                <span class="badge badge-danger">En route</span>
                                                @endif --}}
                                                        <span class="badge badge-success"
                                                            style="font-size:10px;">{{ $document->routingSlip->trans_remarks ?? '' }}</span><span
                                                            class="badge badge-warning"
                                                            style="font-size:10px;">{{ $document->routingSlip->assign_com ?? '' }}</span>

                                                        <span class="badge badge-warning"
                                                            style="margin-top: 2px; font-size:10px;">{{ $log->comments ?? '' }}</span>

                                                    </td>
                                                    <td>
                                                        @php
                                                            $documentCreatedAt = \Carbon\Carbon::parse(
                                                                $document->created_at,
                                                            );
                                                            $logUpdatedAt = $log->updated_at
                                                                ? \Carbon\Carbon::parse($log->updated_at)
                                                                : null;

                                                            if ($logUpdatedAt) {
                                                                $totalMinutes = $documentCreatedAt->diffInMinutes(
                                                                    $logUpdatedAt,
                                                                );

                                                                $days = floor($totalMinutes / 1440);
                                                                $hours = floor(($totalMinutes % 1440) / 60);
                                                                $minutes = $totalMinutes % 60;

                                                                if ($days === 0 && $hours === 0) {
                                                                    $formattedDiff =
                                                                        "{$minutes} " .
                                                                        \Illuminate\Support\Str::plural(
                                                                            'minute',
                                                                            $minutes,
                                                                        );
                                                                } else {
                                                                    $formattedDiff = '';

                                                                    if ($days > 0) {
                                                                        $formattedDiff .=
                                                                            "{$days} " .
                                                                            \Illuminate\Support\Str::plural(
                                                                                'day',
                                                                                $days,
                                                                            );
                                                                    }

                                                                    if ($hours > 0) {
                                                                        $formattedDiff .=
                                                                            ($formattedDiff ? ', ' : '') .
                                                                            "{$hours} " .
                                                                            \Illuminate\Support\Str::plural(
                                                                                'hr',
                                                                                $hours,
                                                                            );
                                                                    }

                                                                    if ($minutes > 0) {
                                                                        $formattedDiff .=
                                                                            ($formattedDiff ? ' and ' : '') .
                                                                            "{$minutes} " .
                                                                            \Illuminate\Support\Str::plural(
                                                                                'minute',
                                                                                $minutes,
                                                                            );
                                                                    }
                                                                }
                                                            } else {
                                                                $formattedDiff = 'N/A';
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
    @if (!$dpa)
    <script>
        window.onload = function () {
            console.log("Modal trigger running");
            $('#dpaPopup').modal({
                backdrop: 'static',
                keyboard: false
            });
        };
    </script>
    @endif
    

    @include('modal.docAdd')
    @include('modal.docEdit')
    @include('modal.addTrans')
    @include('modal.addRoutslip')
    {{-- @include('modal.dpaPopup') --}}
    
    {{-- @include('modal.addIncoming') --}}
@endsection
