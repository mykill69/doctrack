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

    /* Center pagination below the table */
    .pagination {
        justify-content: center;
        /* center alignment */
        margin-top: 10px;
        margin-bottom: 0;
    }

    /* Adjust size of arrows and numbers */
     .pagination {
    justify-content: center;
    margin-top: 1px;
    padding-bottom: 5%;
}

.pagination li a,
.pagination li span {
    font-size: 0.85rem;
    padding: 0.25rem 0.5rem;
    min-width: 32px;
    text-align: center;
}

.pagination .page-item.active .page-link {
    background-color: #007bff;
    border-color: #007bff;
    color: #fff;
}

.pagination .page-link {
    color: #007bff;
}

.pagination .page-link:hover {
    background-color: #007bff;
    color: #fff;
    border-color: #007bff;
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
                                    <table class="table table-bordered table-hover" style="font-size: 0.8rem;">
                                        <thead>
                                            <tr>
                                                @if (auth()->user()->id == 1235)
                                                    <th>CTRL #</th>
                                                @endif
                                                <th>DATE RECEIVED</th>
                                                <th>SOURCE</th>
                                                <th>SUBJECT MATTER</th>
                                                <th>ACTION UNIT</th>
                                                <th>RECEIVED BY/DATE</th>
                                                <th>ACTION TAKEN</th>
                                                <th>DATE RELEASED</th>
                                                <th style="width: 15%;">REMARKS</th>
                                                <th>STATUS</th>
                                                <th>TRACKING CODE</th>
                                                <th>TOTAL DURATION</th>
                                                <th>ACTION</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($documentTrack as $record)
                                                <tr>
                                                    @if (auth()->user()->id == 1235)
                                                        <td>
                                                            <input type="text" class="form-control doctrack-input"
                                                                data-id="{{ $record->id }}" data-field="ctrl_no"
                                                                value="{{ $record->ctrl_no }}">
                                                        </td>
                                                    @endif

                                                    <td>{{ $record->created_at->format('M j, Y') }}</td>
                                                    <td>{{ $record->user_name }}</td>
                                                    <td>{{ $record->doc_title }} - {{ $record->doc_type }}</td>
                                                    <td class="text-center">--</td>
                                                    <td class="text-center">--</td>
                                                    <td>
                                                        @php
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
                                                    <td>{{ $record->updated_at->format('M d, Y') }}</td>
                                                    <td>
                                                        @php $commentsDisplayed = false; @endphp
                                                        @foreach ($record->all_comments as $comment)
                                                            @if (!is_null($record->update_by))
                                                                <div style="word-break: break-word;">
                                                                    <span class="badge badge-warning m-1 d-block text-left">
                                                                        {{ $comment->comments }}
                                                                    </span>
                                                                    <small class="text-muted d-block">
                                                                        {{ \Carbon\Carbon::parse($comment->created_at)->format('M-d-Y h:i A') }}
                                                                    </small>
                                                                </div>
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

                                                            @case(6)
                                                                <span class="badge badge-success">Acknowledged</span>
                                                            @break

                                                            @default
                                                                <span class="badge badge-danger">Returned with comments</span>
                                                        @endswitch
                                                    </td>
                                                    <td>
                                                        <a href="{{ route('slipMonitoring', ['docslip_id' => $record->docslip_id]) }}"
                                                            target="_blank" style="color: #007bff;">
                                                            {{ $record->docslip_id }}
                                                        </a>
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
                                                                {{ $diff['hours'] }}
                                                                {{ Str::plural('hr', $diff['hours']) }}
                                                            @endif
                                                            @if ($diff['minutes'] > 0)
                                                                {{ $diff['minutes'] }}
                                                                {{ Str::plural('minute', $diff['minutes']) }}
                                                            @endif
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <div class="btn-group">
                                                            <button type="button" class="btn btn-primary dropdown-toggle"
                                                                data-toggle="dropdown">Click here</button>
                                                            <div class="dropdown-menu">
                                                                @if ($record->doctrack_stat == 1)
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
                                <div class="d-flex justify-content-center mt-3">
                                        {{ $documentTrack->links('pagination::bootstrap-4') }}
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // Store original value when focusing
        $(document).on('focus', '.doctrack-input', function() {
            $(this).data('original-value', $(this).val());
        });

        // Update on blur if value changed
        $(document).on('blur', '.doctrack-input', function() {
            let doctrackId = $(this).data('id');
            let field = $(this).data('field');
            let value = $(this).val();
            let originalValue = $(this).data('original-value');

            if (value === originalValue) return; // skip if nothing changed

            $.ajax({
                url: "{{ url('doctrack-slip') }}/" + doctrackId,
                type: 'POST',
                data: {
                    _method: 'PUT',
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    [field]: value
                },
                success: function(response) {
                    console.log('CTRL # updated successfully:', response);
                },
                error: function(xhr) {
                    console.error(xhr.responseText);
                    alert('Error updating CTRL #');
                }
            });
        });
    </script>

    @include('modal.docAdd')
    @include('modal.docEdit')
    @include('modal.addTrans')
    @include('modal.addRoutslip')
@endsection
