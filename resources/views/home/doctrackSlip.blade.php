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
                                    <table id="example1" class="table table-bordered table-hover text-sm">
                                        <thead>
                                            <tr>
                                                <th>TRACKING CODE</th>
                                                <th>DOCUMENT TYPE</th>
                                                <th>DOCUMENT TITLE</th>
                                                <th>NAME ON THE DOCUMENT</th>
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
                                            @foreach ($groupedTrack as $group)
                                                @foreach ($group as $documentTrackslip)
                                                    @if ($documentTrackslip->user_id == auth()->user()->id || $documentTrackslip->update_by == auth()->user()->id)
                                                        <tr>
                                                            <td>
                                                                <a href="{{ route('slipMonitoring', ['docslip_id' => $documentTrackslip->docslip_id]) }}"
                                                                    target="_blank" style="color: #007bff;">
                                                                    {{ $documentTrackslip->docslip_id }}
                                                                </a>
                                                            </td>
                                                            <td>{{ $documentTrackslip->doc_type }}</td>
                                                            <td>{{ $documentTrackslip->doc_title }}</td>
                                                            <td>{{ $documentTrackslip->user_name }}</td>
                                                            <td>
                                                                @if ($documentTrackslip->doctrackFile)
                                                                    <a href="{{ route('pdfDocSlip', $documentTrackslip->doctrackFile->id) }}"
                                                                        target="_blank">
                                                                        <i class="fas fa-file-pdf text-danger"></i>
                                                                        <span>{{ $documentTrackslip->doctrackFile->file }}</span>
                                                                    </a>
                                                                @else
                                                                    <span class="text-muted">No file attached</span>
                                                                @endif
                                                            </td>
                                                            <td>
                                                                @switch($documentTrackslip->doctrack_stat)
                                                                    @case(1)
                                                                        <span class="badge badge-primary">Created</span>
                                                                    @break

                                                                    @case(2)
                                                                        <span class="badge badge-warning">Pending</span>
                                                                    @break
                                                                    @case(3)
                                                                        <span class="badge badge-success">Signed</span>
                                                                    @break


                                                                    @default
                                                                        <span class="badge badge-danger">Returned with comments</span>
                                                                @endswitch
                                                            </td>
                                                            <td>
                                                                @if (is_null($documentTrackslip->update_by))
                                                                    @php
                                                                        $creator = \App\Models\User::find(
                                                                            $documentTrackslip->user_id,
                                                                        );
                                                                    @endphp
                                                                    @if ($creator)
                                                                        <p class="text-red text-bold">{{ $creator->fname }}
                                                                            {{ $creator->lname }}</p>
                                                                    @else
                                                                        <p class="text-muted"><i>User not found (ID:
                                                                                {{ $documentTrackslip->user_id }})</i></p>
                                                                    @endif
                                                                @else
                                                                    @php
                                                                        $updatedBy = \App\Models\User::find(
                                                                            $documentTrackslip->update_by,
                                                                        );
                                                                    @endphp
                                                                    @if ($updatedBy)
                                                                        <p class="text-red text-bold">
                                                                            {{ $updatedBy->fname }}
                                                                            {{ $updatedBy->lname }}
                                                                        </p>
                                                                    @else
                                                                        <p class="text-muted"><i>User not found (ID:
                                                                                {{ $documentTrackslip->update_by }})</i>
                                                                        </p>
                                                                    @endif
                                                                @endif
                                                            </td>
                                                            <td>
                                                                @if ($documentTrackslip->comments)
                                                                    <span class="text-muted">
                                                                        {{ $documentTrackslip->comments }}</span>
                                                                @else
                                                                    <span class="text-muted">No comments</span>
                                                                @endif </td>
                                                            <td>{{ $documentTrackslip->created_at }}</td>
                                                            <td>{{ $documentTrackslip->updated_at }}</td>

                                                            <td>
                                                                @php
                                                                    $diff = $documentTrackslip->time_diff ?? [
                                                                        'days' => 0,
                                                                        'hours' => 0,
                                                                        'minutes' => 0,
                                                                    ]; // Handle case where time_diff might be null
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
                                                    @endif
                                                @endforeach
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
