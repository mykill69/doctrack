@extends('layouts.main')
@php
    use App\Models\Log;

    $user = auth()->user();
    $userFullName = $user->fname . ' ' . $user->lname;
    $userDepartment = $user->department;
    $isRecordsOfficer = $user->role === 'records_officer';
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
                                <h3 class="card-title">DOCUMENT LOGBOOK</h3>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="example1" class="table table-bordered table-hover"
                                        style="font-size: 0.8rem;">
                                        <thead>
                                            <tr>
                                                <th>CTRL #</th>
                                                <th>DATE RECEIVED</th>
                                                <th>SOURCE</th>
                                                <th>SUBJECT MATTER</th>
                                                <th>ACTION UNIT</th>
                                                <th>RECEIVED BY/DATE</th>
                                                <th>ACTION TAKEN</th>
                                                <th>DATE RELEASED</th>
                                                <th>REMARKS</th>
                                                <th>FILE NAME</th>
                                                <th>DATE UPDATED</th>
                                                <th>TOTAL DURATION</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($logs as $log)
                                                @php
                                                    $document = $log->document;
                                                @endphp
                                                @if (
                                                    $isRecordsOfficer ||
                                                        $log->new_user == auth()->id() ||
                                                        $log->user_id == auth()->id() ||
                                                        trim($log->new_destination) === trim($userFullName) ||
                                                        trim($log->new_destination) === trim($userDepartment))
                                                    <tr>
                                                        @php
                                                            $routingSlipId = \App\Models\RoutingSlip::where(
                                                                'rslip_id',
                                                                $log->route_id,
                                                            )
                                                                ->orderBy('id', 'desc')
                                                                ->value('id');
                                                        @endphp

                                                        <td data-order="{{ $log->route_id ?? 0 }}">
                                                            <a href="{{ route('slipForm', ['id' => $log->route_id]) . '?routing_slip_id=' . $routingSlipId }}"
                                                                target="_blank" style="color: #007bff;">
                                                                {{ $log->route_id }}
                                                            </a>
                                                        </td>
                                                        <td>
                                                            {{ optional($document->routingSlip)->date_received
                                                                ? \Carbon\Carbon::parse($document->routingSlip->date_received)->format('F d, Y')
                                                                : 'N/A' }}
                                                        </td>
                                                        <td>{{ $document->routingSlip->source }}</td>
                                                        <td>{{ $document->routingSlip->subject }}</td>

                                                        <td>{{ optional($document->routingSlip)->pres_dept ?? 'N/A' }}</td>
                                                        <td>{{ optional($document->routingSlip)->updated_at ? $document->routingSlip->updated_at->format('F j, Y') : 'N/A' }}
                                                        </td>
                                                        <td>
                                                            <strong
                                                                class="text-danger">{{ ucwords(strtolower($document->for_to)) }}</strong>

                                                            @php
                                                                $destinationUser = $users->firstWhere(
                                                                    'id',
                                                                    $document->routingSlip->r_destination ?? null,
                                                                );
                                                                $assignedUser = $users->firstWhere(
                                                                    'id',
                                                                    $document->routingSlip->assigned_to ?? null,
                                                                );
                                                            @endphp

                                                            @if ($document->routingSlip && $destinationUser)
                                                                <strong class="text-danger">
                                                                    {{ ucwords(strtolower($destinationUser->fname)) }}
                                                                    {{ ucwords(strtolower($destinationUser->lname)) }}
                                                                </strong>
                                                            @elseif ($document->routingSlip && $document->routingSlip->r_destination)
                                                                <strong class="text-danger">
                                                                    {{ ucwords(strtolower($document->routingSlip->r_destination)) }}
                                                                </strong>
                                                            @endif

                                                            @if ($document->routingSlip && $assignedUser)
                                                                , was re-assigned to
                                                                <strong class="text-danger">
                                                                    {{ ucwords(strtolower($assignedUser->fname)) }}
                                                                    {{ ucwords(strtolower($assignedUser->lname)) }}
                                                                </strong>
                                                            @elseif ($document->routingSlip && $document->routingSlip->assigned_to)
                                                                , was re-assigned to
                                                                <strong class="text-danger">
                                                                    {{ ucwords(strtolower($document->routingSlip->assigned_to)) }}
                                                                </strong>
                                                            @endif
                                                        </td>



                                                        <td>{{ $document->created_at->format('m-d-Y h:i:s A') }}</td>

                                                        <td style="font-size:10px;width:5%;">
                                                            @if (!empty($document->routingSlip->trans_remarks))
                                                                <span class="badge badge-success"
                                                                    style="font-size:10px; display: block;">
                                                                    {{ $document->routingSlip->trans_remarks }}
                                                                </span>
                                                            @endif

                                                            @if (!empty($document->routingSlip->other_remarks))
                                                                <span class="badge badge-danger"
                                                                    style="font-size:10px; display: block;">
                                                                    {{ $document->routingSlip->other_remarks }}
                                                                </span>
                                                            @endif

                                                            @php
                                                                $comment = $log->comments ?? '';
                                                                $wrappedComment = preg_replace(
                                                                    '/((?:\S+\s+){4})/',
                                                                    '$1<br>',
                                                                    $comment,
                                                                );
                                                            @endphp

                                                            @if (!empty($comment))
                                                                <span class="badge badge-warning"
                                                                    style="margin-top: 2px; font-size:10px; max-width: 150px; display: inline-block; word-wrap: break-word; white-space: normal;">
                                                                    {!! $wrappedComment !!}
                                                                </span>
                                                            @endif

                                                        </td>
                                                        <td>
                                                            <a href="{{ route('documents.viewPdf', $document->id) }}"
                                                                style="color: #007bff;" target="_blank">
                                                                <i class="fas fa-file-pdf text-danger"></i>
                                                                {{ \Illuminate\Support\Str::limit($document->file_name, 22) }}
                                                            </a>
                                                            <p>
                                                                <small class="text-muted">
                                                                    @if ($log->viewed_status)
                                                                        Viewed on <br>
                                                                        {{ \Carbon\Carbon::parse($log->viewed_at)->format('M j, Y h:i A') }}
                                                                    @else
                                                                    @endif
                                                                </small>
                                                            </p>
                                                        </td>
                                                        <td>{{ $log->updated_at->format('m-d-Y h:i:s A') }}</td>
                                                        <td>
                                                            @php
                                                                $created = $document->created_at;
                                                                $updated = $log->updated_at;
                                                                $diff = $updated ? $created->diff($updated) : null;
                                                            @endphp
                                                            {{ $diff ? "{$diff->days} days, {$diff->h} hours, {$diff->i} minutes" : 'N/A' }}
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
    @include('modal.docAdd')
    @include('modal.docEdit')
    @include('modal.addTrans')
    @include('modal.addRoutslip')

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

    <script>
        $('#example1').DataTable({
            "pageLength": 50
        });
    </script>
@endsection
