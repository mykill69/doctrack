@extends('layouts.main')
@php
    use App\Models\Log; // Ensure you import your Log model here
    use Illuminate\Support\Collection;

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
                                <h3 class="card-title">DOCUMENT TRACKING SLIP</h3>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="example1" class="table table-bordered table-hover"
                                        style="font-size: 0.8rem;">
                                        <thead>
                                            <tr>
                                                <th>TRACKING CODE</th>
                                                <th>DOCUMENT TYPE</th>
                                                <th>DOCUMENT TITLE</th>
                                                <th>FILE NAME</th>
                                                <th>STATUS</th>
                                                <th>CREATED BY</th>
                                                <th>COMMENTS</th>
                                                <th>DATE CREATED</th>
                                                <th>DATE RELEASED</th>
                                                <th>TOTAL DURATION</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($documentTrack as $record)
                                                <tr>
                                                    <td>
                                                        <a href="{{ route('slipMonitoring', ['docslip_id' => $record->docslip_id]) }}"
                                                            target="_blank" style="color: #007bff;">
                                                            {{ $record->docslip_id }}
                                                        </a>
                                                    </td>
                                                    <td>{{ $record->doc_type }}</td>
                                                    <td>{{ $record->doc_title }}</td>
                                                    <td>
                                                        @if ($record->doctrackFile)
                                                            <a href="{{ route('pdfDocSlip', $record->doctrackFile->id) }}"
                                                                target="_blank">
                                                                <i class="fas fa-file-pdf text-danger"></i>
                                                                <span>{{ $record->doctrackFile->file }}</span>
                                                            </a>
                                                        @else
                                                            <span class="text-muted">No file attached</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @switch($record->doctrack_stat)
                                                            @case(1)
                                                                <span class="badge badge-primary">Created</span>
                                                            @break

                                                            @case(2)
                                                                <span class="badge badge-warning">Pending</span>
                                                            @break

                                                            @case(3)
                                                                <span class="badge badge-success">Signed</span>
                                                            @break

                                                            @case(5)
                                                                <span class="badge badge-info">Checked</span>
                                                            @break

                                                            @default
                                                                <span class="badge badge-danger">Returned with comments</span>
                                                        @endswitch
                                                    </td>
                                                    <td>
                                                        @php
                                                            $user = $record->update_by
                                                                ? \App\Models\User::find($record->update_by)
                                                                : \App\Models\User::find($record->user_id);
                                                        @endphp
                                                        @if ($user)
                                                            <p class="text-red text-bold">{{ $user->fname }}
                                                                {{ $user->lname }}</p>
                                                        @else
                                                            <p class="text-muted"><i>User not found</i></p>
                                                        @endif
                                                    </td>
                                                    <td>{{ $record->comments ?? 'No comments' }}</td>
                                                    <td>{{ $record->created_at }}</td>
                                                    <td>{{ $record->updated_at }}</td>
                                                    <td>
                                                        @php
                                                            $diff = $record->time_diff ?? [
                                                                'days' => 0,
                                                                'hours' => 0,
                                                                'minutes' => 0,
                                                            ];
                                                        @endphp
                                                        @if ($diff['days'] === 0 && $diff['hours'] === 0)
                                                            {{ $diff['minutes'] }}
                                                            {{ Str::plural('minute', $diff['minutes']) }}
                                                        @else
                                                            @if ($diff['days'] > 0)
                                                                {{ $diff['days'] }}
                                                                {{ Str::plural('day', $diff['days']) }}
                                                            @endif
                                                            @if ($diff['hours'] > 0)
                                                                {{ $diff['days'] > 0 ? ', ' : '' }}{{ $diff['hours'] }}
                                                                {{ Str::plural('hr', $diff['hours']) }}
                                                            @endif
                                                            @if ($diff['minutes'] > 0)
                                                                {{ $diff['days'] > 0 || $diff['hours'] > 0 ? ' and ' : '' }}{{ $diff['minutes'] }}
                                                                {{ Str::plural('minute', $diff['minutes']) }}
                                                            @endif
                                                        @endif
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
    @include('modal.docAdd')
    @include('modal.docEdit')
    @include('modal.addTrans')
    @include('modal.addRoutslip')
@endsection
