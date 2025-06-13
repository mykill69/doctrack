<!DOCTYPE html>
<html lang="en">
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>CPSU | DTS {{ isset($title) ? '| ' . $title : '' }}</title>
    <!-- Google Font: Source Sans Pro -->
    <!-- Bootstrap JS (include this before closing body tag) -->

    {{-- <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback"> --}}
    <!-- Toastr -->
    <link rel="stylesheet" href="{{ asset('template/plugins/toastr/toastr.min.css') }}">
    <!-- SweetAlert2 -->
    <!-- Font Awesome -->
    <link rel="stylesheet" href="{{ asset('template/plugins/fontawesome-free/css/all.min.css') }}">
    <!-- Theme style -->
    <link rel="stylesheet" href="{{ asset('template/dist/css/adminlte.min.css') }}">
    <!-- DataTables -->
    <link rel="stylesheet" href="{{ asset('template/plugins/datatables-bs4/css/dataTables.bootstrap4.min.css') }}">
    <link rel="stylesheet"
        href="{{ asset('template/plugins/datatables-responsive/css/responsive.bootstrap4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('template/plugins/datatables-buttons/css/buttons.bootstrap4.min.css') }}">
    <!-- Select2 -->
    <link rel="stylesheet" href="{{ asset('template/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('template/plugins/select2-bootstrap4-theme/select2-bootstrap4.min.css') }}">
    <!-- Logo  -->
    <link rel="shortcut icon" type="" href="{{ asset('template/img/CPSU_L.png') }}">
</head>


<!-- Main content -->
<div class="content" style="padding-top: 1%;width:100%;">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="row align-items-center w-100">
                            {{-- Left Column: Tracking Code --}}
                            <div class="col-12 col-md-6 mb-2 mb-md-0">
                                <h3 class="card-title mb-0">
                                    TRACKING CODE RESULT:
                                    <span class="badge badge-success" style="font-size: 1rem;">
                                        @foreach ($documentTrackid as $documentTrack)
                                            {{ $documentTrack->docslip_id }}
                                        @break
                                    @endforeach
                                </span>
                            </h3>
                        </div>

                        {{-- Right Column: Button aligned right --}}
                        <div class="col-12 col-md-6 text-md-right">
                            <button class="btn btn-success w-md-auto" data-toggle="modal"
                                data-target="#addLogModal">
                                <i class="fa fa-plus"></i> Entry
                            </button>
                        </div>
                    </div>
                </div>


                <div class="card-body" style="overflow-y: auto; max-height: calc(750px - 60px);">
                    <div class="row justify-content-center">
                        <!-- Loop through each document -->
                        @foreach ($documentTrackid as $documentTrack)
                            @php
                                $currentUserId = auth()->user()->id;

                                $isEnabled =
                                    ($currentUserId == $documentTrack->user_id && is_null($documentTrack->update_by)) ||
                                    $currentUserId == $documentTrack->update_by;

                                switch ($documentTrack->doctrack_stat) {
                                    case 1:
                                        $bgColor = '#007bff'; // Blue
                                        $textColor = 'white';
                                        $statusText = 'CREATED';
                                        break;
                                    case 2:
                                        $bgColor = '#ffc107'; // Yellow
                                        $textColor = '#212529';
                                        $statusText = 'PENDING';
                                        break;
                                    case 3:
                                        $bgColor = '#28a745'; // Green
                                        $textColor = 'white';
                                        $statusText = 'SIGNED';
                                        break;
                                    case 4:
                                        $bgColor = 'red'; // Red
                                        $textColor = 'white';
                                        $statusText = 'RETURNED<br>WITH COMMENTS';
                                        break;
                                    default:
                                        $bgColor = '#6c757d'; // Gray
                                        $textColor = 'white';
                                        $statusText = 'UNKNOWN';
                                        break;
                                }
                            @endphp

                            <div class="col-md-2 p-4 rounded shadow mb-3 position-relative"
                                style="background-color: {{ $loop->first ? 'white' : 'white' }}!important; color: #2b2b2b; border:1px solid green; font-weight: bold; text-align: center;">

                                <!-- Delete "X" Button -->
                                @if ($isEnabled)
                                    <form action="{{ route('deleteSlip', $documentTrack->id) }}" method="POST"
                                        style="position: absolute; top: 5px; right: 10px;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            onclick="return confirm('Are you sure you want to delete this item?')"
                                            style="background: transparent; border: none; color: red; font-size: 1rem; margin-top:0;">
                                            <i class="fa fa-times"></i>
                                        </button>
                                    </form>
                                @endif

                                <!-- Status in the center -->
                                <div class="mb-4 dropdown">
                                    <form id="statusForm{{ $loop->index }}"
                                        action="{{ route('updateSlipStatus', $documentTrack->id) }}"
                                        method="POST">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="doctrack_stat"
                                            id="doctrackStatInput{{ $loop->index }}">

                                        <button class="btn dropdown-toggle btn-lg w-100 text-truncate"
                                            type="button" id="statusDropdown{{ $loop->index }}"
                                            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
                                            style="font-size: 1.3rem; font-weight: bold; text-transform: uppercase; padding: 8px; background-color: {{ $bgColor }}; color: {{ $textColor }};"
                                            {{ !$isEnabled ? 'disabled' : '' }}>
                                            {!! $statusText !!}
                                        </button>

                                        <div class="dropdown-menu w-100 text-center"
                                            aria-labelledby="statusDropdown{{ $loop->index }}">
                                            <span class="dropdown-item disabled">Status Options</span>
                                            <div class="dropdown-divider"></div>
                                            <a class="dropdown-item" href="#"
                                                onclick="submitStatus({{ $loop->index }}, 2)">PENDING</a>
                                            <a class="dropdown-item" href="#"
                                                onclick="submitStatus({{ $loop->index }}, 3)">SIGNED</a>
                                            <a class="dropdown-item" href="#"
                                                onclick="submitStatus({{ $loop->index }}, 4)">RETURN WITH
                                                COMMENT</a>
                                        </div>
                                    </form>
                                </div>

                                <!-- Document Details Below -->
                                <div>
                                    <!-- First Row: Document Type -->
                                    <p class="text-lg font-bold mb-2">{{ $documentTrack->doc_type }}</p>

                                    <p class="text-sm mb-2 text-primary">
                                        @if ($documentTrack->doctrackFile)
                                            <a href="{{ route('pdfDocSlip', $documentTrack->doctrackFile->id) }}"
                                                target="_blank">
                                                <i class="fas fa-file-pdf text-danger"></i>
                                                {{ $documentTrack->doctrackFile->file }}
                                            </a>
                                        @else
                                            <span class="text-muted">No file attached</span>
                                        @endif
                                    </p>


                                    <!-- Second Row: Created By or Updated By -->
                                    @if ($loop->first)
                                        <p class="text-sm mb-0">Created By:</p>
                                        <p class="mb-1">
                                            {{ $documentTrack->createdBy->fname ?? 'N/A' }}
                                            {{ $documentTrack->createdBy->lname ?? '' }}
                                        </p>
                                    @else
                                        <p class="text-sm mb-0">Updated By:</p>
                                        <p class="mb-1">
                                            @if (!empty($documentTrack->updatedBy->fname) || !empty($documentTrack->updatedBy->lname))
                                                {{ $documentTrack->updatedBy->fname }}
                                                {{ $documentTrack->updatedBy->lname }}
                                            @else
                                                N/A
                                            @endif
                                        </p>
                                    @endif

                                    <!-- Third Row: Created At -->
                                    <p class="text-sm mb-0">Created At:</p>
                                    <p class="mb-0 bg-warning">
                                        {{ $documentTrack->created_at ? $documentTrack->created_at->format('F d, Y h:i:s A') : 'N/A' }}
                                    </p>
                                </div>

                               @php
    $currentUserId = auth()->user()->id;
    $canComment = $documentTrack->update_by === $currentUserId;
@endphp

@if ($documentTrack->update_by !== null)
    <div class="mt-2">
        <h5 class="text-muted">Comments:</h5>
        <form id="commentForm{{ $loop->index }}"
              action="{{ route('updateSlipStatus', $documentTrack->id) }}"
              method="POST" class="comment-form d-flex gap-2">
            @csrf
            @method('PUT')

            <textarea class="form-control comment-input" name="comments" rows="3"
                      placeholder="Add a comment"
                      {{ !$canComment ? 'disabled' : '' }}>{{ $documentTrack->comments }}</textarea>

            <button type="submit" class="btn btn-primary"
                    {{ !$canComment ? 'disabled' : '' }}>
                <i class="fa fa-paper-plane"></i>
            </button>
        </form>
    </div>
@endif

                            </div>

                            <!-- FontAwesome Arrow Icon between the boxes -->
                            @if (!$loop->last)
                                <div class="col-md-1 d-flex justify-content-center align-items-center"
                                    style="font-size: 4rem; line-height: 2rem;">
                                    <i class="fa fa-arrow-right"></i>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
</div>

{{-- @include('modal.docAdd') --}}
@include('modal.addLog')



<script>
    function submitStatus(index, status) {
        document.getElementById(`doctrackStatInput${index}`).value = status;
        document.getElementById(`statusForm${index}`).submit();
    }
</script>



<script src="{{ asset('template/plugins/jquery/jquery.min.js') }}"></script>
<!-- Bootstrap 4 -->
<script src="{{ asset('template/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<!-- AdminLTE App -->
<script src="{{ asset('template/dist/js/adminlte.min.js') }}"></script>
<!-- Toastr -->
<script src="{{ asset('template/plugins/toastr/toastr.min.js') }}"></script>
<!-- DataTables  & Plugins -->
<script src="{{ asset('template/plugins/datatables/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('template/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
<script src="{{ asset('template/plugins/datatables-responsive/js/dataTables.responsive.min.js') }}"></script>
<script src="{{ asset('template/plugins/datatables-responsive/js/responsive.bootstrap4.min.js') }}"></script>
<script src="{{ asset('template/plugins/datatables-buttons/js/dataTables.buttons.min.js') }}"></script>
<script src="{{ asset('template/plugins/datatables-buttons/js/buttons.bootstrap4.min.js') }}"></script>
<script src="{{ asset('template/plugins/jszip/jszip.min.js') }}"></script>
<script src="{{ asset('template/plugins/pdfmake/pdfmake.min.js') }}"></script>
<script src="{{ asset('template/plugins/pdfmake/vfs_fonts.js') }}"></script>
<script src="{{ asset('template/plugins/datatables-buttons/js/buttons.html5.min.js') }}"></script>
<script src="{{ asset('template/plugins/datatables-buttons/js/buttons.print.min.js') }}"></script>
<script src="{{ asset('template/plugins/datatables-buttons/js/buttons.colVis.min.js') }}"></script>
<!-- Select2 -->
<script src="{{ asset('template/plugins/select2/js/select2.full.min.js') }}"></script>
