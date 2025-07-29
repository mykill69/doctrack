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
                                                {{-- <th>NAME ON THE DOCUMENT</th> --}}
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
                                                @php

                                                    $sortedGroup = $group
                                                        ->sortByDesc(function ($item) {
                                                            return $item->doctrack_stat == 2 ? 1 : 0;
                                                        })
                                                        ->values();

                                                    $firstRow = $sortedGroup->first();
                                                    $docslipId = $firstRow->docslip_id;
                                                    $collapseId = 'collapse-' . $docslipId;
                                                @endphp

                                                @endphp

                                                {{-- Primary Row (Visible Always) --}}
                                                <tr>
                                                    <td>
                                                        <a href="{{ route('slipMonitoring', ['docslip_id' => $docslipId]) }}"
                                                            target="_blank" style="color: #007bff;">
                                                            {{ $docslipId }}
                                                        </a>
                                                        @if ($group->count() > 1)
                                                            <a data-toggle="collapse" href="#{{ $collapseId }}"
                                                                role="button" class="toggle-collapse-link"
                                                                aria-expanded="false" aria-controls="{{ $collapseId }}">
                                                                <i class="fas fa-plus-circle ml-2 text-primary"></i>
                                                            </a>
                                                        @endif
                                                    </td>
                                                    <td>{{ $firstRow->doc_type }}</td>
                                                    <td>{{ $firstRow->doc_title }}</td>
                                                    <td>
                                                        @if ($firstRow->doctrackFile)
                                                            <a href="{{ route('pdfDocSlip', $firstRow->doctrackFile->id) }}"
                                                                target="_blank">
                                                                <i class="fas fa-file-pdf text-danger"></i>
                                                                <span>{{ $firstRow->doctrackFile->file }}</span>
                                                            </a>
                                                        @else
                                                            <span class="text-muted">No file attached</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        @switch($firstRow->doctrack_stat)
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
                                                            $user = $firstRow->update_by
                                                                ? \App\Models\User::find($firstRow->update_by)
                                                                : \App\Models\User::find($firstRow->user_id);
                                                        @endphp
                                                        @if ($user)
                                                            <p class="text-red text-bold">{{ $user->fname }}
                                                                {{ $user->lname }}</p>
                                                        @else
                                                            <p class="text-muted"><i>User not found</i></p>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        {{ $firstRow->comments ?? 'No comments' }}
                                                    </td>
                                                    <td>{{ $firstRow->created_at }}</td>
                                                    <td>{{ $firstRow->updated_at }}</td>
                                                    <td>
                                                        @php
                                                            $diff = $firstRow->time_diff ?? [
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

                                                {{-- Hidden Rows (Collapsible) --}}
                                                @foreach ($sortedGroup->skip(1) as $documentTrackslip)
                                                    <tr class="collapse" id="{{ $collapseId }}">
                                                        <td colspan="1">
                                                            <a href="{{ route('slipMonitoring', ['docslip_id' => $documentTrackslip->docslip_id]) }}"
                                                                target="_blank" style="color: #007bff;">
                                                                {{ $documentTrackslip->docslip_id }}
                                                            </a>
                                                        </td>
                                                        <td>{{ $documentTrackslip->doc_type }}</td>
                                                        <td>{{ $documentTrackslip->doc_title }}</td>
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

                                                                @case(5)
                                                                    <span class="badge badge-info">Checked</span>
                                                                @break

                                                                @default
                                                                    <span class="badge badge-danger">Returned with comments</span>
                                                            @endswitch
                                                        </td>
                                                        <td>
                                                            @php
                                                                $user = $documentTrackslip->update_by
                                                                    ? \App\Models\User::find(
                                                                        $documentTrackslip->update_by,
                                                                    )
                                                                    : \App\Models\User::find(
                                                                        $documentTrackslip->user_id,
                                                                    );
                                                            @endphp
                                                            @if ($user)
                                                                <p class="text-red text-bold">{{ $user->fname }}
                                                                    {{ $user->lname }}</p>
                                                            @else
                                                                <p class="text-muted"><i>User not found</i></p>
                                                            @endif
                                                        </td>
                                                        <td>{{ $documentTrackslip->comments ?? 'No comments' }}</td>
                                                        <td>{{ $documentTrackslip->created_at }}</td>
                                                        <td>{{ $documentTrackslip->updated_at }}</td>
                                                        <td>
                                                            @php
                                                                $diff = $documentTrackslip->time_diff ?? [
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

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        $(document).ready(function() {
            $('.collapse').on('shown.bs.collapse', function() {
                const collapseId = $(this).attr('id');
                const $icon = $('a[href="#' + collapseId + '"]').find('i');
                $icon.removeClass('fa-plus-circle').addClass('fa-minus-circle');
            });

            $('.collapse').on('hidden.bs.collapse', function() {
                const collapseId = $(this).attr('id');
                const $icon = $('a[href="#' + collapseId + '"]').find('i');
                $icon.removeClass('fa-minus-circle').addClass('fa-plus-circle');
            });
        });
    </script>
@endsection
