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


<!-- SweetAlert2 -->
<link rel="stylesheet" href="{{ asset('template/plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css') }}">
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
                                                <th>CTRL #</th>
                                                <th>DATE RECEIVED</th>
                                                <th>SOURCE</th>
                                                <th>SUBJECT MATTER</th>
                                                {{-- <th>FILE NAME</th> --}}
                                                <th>ACTION UNIT</th>
                                                <th>RECEIVED BY/DATE</th>
                                                <th>ACTION TAKEN</th>
                                                <th>DATE RELEASED</th>
                                                <th>REMARKS</th>
                                                <th>STATUS</th>
                                                <th>TOTAL DURATION</th>
                                                <th>TRACKING CODE</th>
                                                <th>ACTION</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($documentTrack as $record)
                                                <tr>
                                                    <td><input type="text" class="form-control" value=""></td>

                                                    <td data-order="{{ $record->created_at->timestamp }}">
                                                        {{ $record->created_at->format('M j, Y') }}
                                                    </td>
                                                    <td></td>
                                                    <td>{{ $record->doc_title }}</td>
                                                    <td class="text-center">--</td>
                                                    <td class="text-center">--</td>
                                                    <td> @php
                                                        $user = $record->update_by
                                                            ? \App\Models\User::find($record->update_by)
                                                            : \App\Models\User::find($record->user_id);
                                                    @endphp
                                                        @if ($user)
                                                            <p class="text-red text-bold">
                                                                {{ ucwords(strtolower($user->fname)) }}
                                                                {{ ucwords(strtolower($user->lname)) }}
                                                            </p>
                                                        @else
                                                            <p class="text-muted"><i>User not found</i></p>
                                                        @endif
                                                    </td>
                                                    <td>{{ $record->updated_at->format('M d, Y') }} </td>


                                                    <td>
                                                        @php $commentsDisplayed = false; @endphp

                                                        @foreach ($record->all_comments as $comment)
                                                            @if (!is_null($record->update_by))
                                                                <span
                                                                    class="badge badge-warning m-1">{{ $comment->comments }}</span><br>

                                                                <small class="text-muted">
                                                                    
                                                                    {{ \Carbon\Carbon::parse($comment->created_at)->format('M-d-Y h:i A') }}
                                                                </small>
                                                                <br>
                                                                @php $commentsDisplayed = true; @endphp
                                                            @else
                                                                @php $commentsDisplayed = true; @endphp
                                                            @endif
                                                        @endforeach

                                                        @if (!$commentsDisplayed && !is_null($record->update_by))
                                                            <span class="text-muted">No comments</span>
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
                                                    <td>
                                                        <a href="{{ route('slipMonitoring', ['docslip_id' => $record->docslip_id]) }}"
                                                            target="_blank" style="color: #007bff;">
                                                            {{ $record->docslip_id }}
                                                        </a>
                                                        <br>
                                                        @php $displayed = false; @endphp

                                                        @foreach ($record->views as $view)
                                                            @if (!is_null($record->update_by))
                                                                {{-- Updater row: show datetime only --}}
                                                                <small class="text-muted">
                                                                    Viewed on
                                                                    {{ \Carbon\Carbon::parse($view->viewed_at)->format('M j, Y h:i A') }}
                                                                </small><br>
                                                                @php $displayed = true; @endphp
                                                            @else
                                                                {{-- Owner row: leave blank --}}
                                                                @php $displayed = true; @endphp
                                                            @endif
                                                        @endforeach

                                                        {{-- Only show "Not yet viewed" for updater rows --}}
                                                        @if (!$displayed && !is_null($record->update_by))
                                                            <small class="text-muted">Not yet viewed</small>
                                                        @endif

                                                    </td>

                                                    <td>
                                                        <div class="btn-group">
                                                            <button type="button" class="btn btn-primary dropdown-toggle"
                                                                data-toggle="dropdown" aria-haspopup="true"
                                                                aria-expanded="false">
                                                                Click here
                                                            </button>
                                                            <div class="dropdown-menu">

                                                                @if ($record->doctrack_stat == 1)
                                                                    <!-- Delete Slip Form -->
                                                                    <form
                                                                        action="{{ route('deleteSlip', ['docslip_id' => $record->docslip_id]) }}"
                                                                        method="POST"
                                                                        onsubmit="return confirm('Are you sure you want to delete this document tracking slip?')">
                                                                        @csrf
                                                                        @method('DELETE')
                                                                        <button type="submit" class="dropdown-item">
                                                                            <i class="fas fa-trash-alt"></i> Delete
                                                                        </button>
                                                                    </form>
                                                                @endif

                                                                <!-- View Details -->
                                                                <a class="dropdown-item"
                                                                    href="{{ route('slipMonitoring', ['docslip_id' => $record->docslip_id]) }}"
                                                                    target="_blank">
                                                                    <i class="fas fa-eye"></i> View Details
                                                                </a>

                                                            </div>
                                                        </div>
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

    @if (session('success'))
        <div class="alert alert-success">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">
            {{ session('error') }}
        </div>
    @endif
    <!-- SweetAlert2 -->
    <script src="{{ asset('template/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
    <script src="template/plugins/bs-custom-file-input/bs-custom-file-input.min.js"></script>
    <script>
        $('#example1').DataTable({
            "responsive": true,
            "autoWidth": false,
            "order": [
                [0, "desc"]
            ],
            "columnDefs": [{
                "type": "num",
                "targets": 0
            }]
        });
    </script>

    @include('modal.docAdd')
    @include('modal.docEdit')
    @include('modal.addTrans')
    @include('modal.addRoutslip')
@endsection
