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
    <link rel="shortcut icon" type="" href="{{ asset('template/img/cpsu_logo.png') }}">
</head>




<div class="content" style="padding-top: 1%; width: 100%;">
    <div class="container-fluid">
        <div class="card shadow">
            <!-- Header stays -->
            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0"><i class="fas fa-route mr-2"></i>Tracking Code Entries</h4>
                <button class="btn btn-light btn-sm" data-toggle="modal" data-target="#addLogModal">
                    <i class="fas fa-plus text-success"></i> Add Entry
                </button>
            </div>

            <div class="card-body p-3" style="overflow-y: auto; max-height: 750px;">
                <div class="timeline timeline-inverse">
                    @foreach ($documentTrackid as $documentTrack)
                        @php
                            $loopIndex = $loop->index;
                            $currentUserId = auth()->user()->id;
                            $isFirst = $loop->first;
                            $isEnabled = ($currentUserId == $documentTrack->user_id && is_null($documentTrack->update_by)) || $currentUserId == $documentTrack->update_by;
                            $canComment = $documentTrack->update_by === $currentUserId;

                            $person = $isFirst
                                ? $documentTrack->createdBy->fname . ' ' . $documentTrack->createdBy->lname
                                : ($documentTrack->updatedBy->fname ?? 'N/A') . ' ' . ($documentTrack->updatedBy->lname ?? '');

                            switch ($documentTrack->doctrack_stat) {
                                case 1: $statusText = 'CREATED'; $bgColor = 'primary'; $icon = 'fas fa-plus'; break;
                                case 2: $statusText = 'PENDING'; $bgColor = 'warning'; $icon = 'fas fa-hourglass-half'; break;
                                case 3: $statusText = 'SIGNED'; $bgColor = 'success'; $icon = 'fas fa-check-circle'; break;
                                case 4: $statusText = 'RETURNED WITH COMMENT'; $bgColor = 'danger'; $icon = 'fas fa-comment-slash'; break;
                                default: $statusText = 'UNKNOWN'; $bgColor = 'secondary'; $icon = 'fas fa-question-circle'; break;
                            }
                        @endphp

                        <div>
                            <i class="{{ $icon }} bg-{{ $bgColor }}"></i>
                            <div class="timeline-item">
                                <span class="time">
                                    <i class="far fa-clock"></i> {{ $documentTrack->created_at->format('h:i A') }}
                                </span>
                                <h3 class="timeline-header">
                                    <strong class="text-{{ $bgColor }}">
                                        {{ $loop->iteration }}.
                                    </strong>
                                    <span class="ml-2">
                                        <i class="fas fa-file-alt mr-1"></i>{{ $documentTrack->doc_title ?? 'N/A' }}
                                    </span>
                                </h3>

                                <div class="timeline-body">
                                    <div class="row mb-2">
                                        <div class="col-md-4">
                                            <i class="fas fa-user mr-1"></i>
                                            <strong>Personnel:</strong> {{ $person }}
                                        </div>
                                        <div class="col-md-8">
                                            <i class="fas fa-paperclip text-muted mr-1"></i>
                                            <strong>File:</strong>
                                            @if ($documentTrack->doctrackFile)
                                                <a href="{{ route('pdfDocSlip', $documentTrack->doctrackFile->id) }}"
                                                   target="_blank"
                                                   class="text-danger font-weight-bold">
                                                    <i class="fas fa-file-pdf mr-1"></i>{{ $documentTrack->doctrackFile->file }}
                                                </a>
                                            @else
                                                <span class="text-muted">No File</span>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Status Dropdown -->
                                    <form id="statusForm{{ $loopIndex }}"
                                          action="{{ route('updateSlipStatus', $documentTrack->id) }}"
                                          method="POST"
                                          class="mb-2">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="doctrack_stat" id="doctrackStatInput{{ $loopIndex }}">
                                        <div class="dropdown d-inline-block">
                                            <button class="btn btn-sm btn-{{ $bgColor }} dropdown-toggle"
                                                    type="button"
                                                    data-toggle="dropdown"
                                                    aria-haspopup="true"
                                                    aria-expanded="false"
                                                    {{ !$isEnabled ? 'disabled' : '' }}>
                                                 {{ $statusText }}
                                            </button>
                                            <div class="dropdown-menu">
                                                <a class="dropdown-item" href="#" onclick="submitStatus({{ $loopIndex }}, 2)">
                                                    <i class="fas fa-hourglass-half mr-1"></i> PENDING
                                                </a>
                                                <a class="dropdown-item" href="#" onclick="submitStatus({{ $loopIndex }}, 3)">
                                                    <i class="fas fa-check-circle mr-1"></i> SIGNED
                                                </a>
                                                <a class="dropdown-item" href="#" onclick="submitStatus({{ $loopIndex }}, 4)">
                                                    <i class="fas fa-undo mr-1"></i> RETURN WITH COMMENT
                                                </a>
                                            </div>
                                        </div>
                                    </form>

                                    <!-- Comment Form -->
                                    <form id="commentForm{{ $loopIndex }}"
                                          action="{{ route('updateSlipStatus', $documentTrack->id) }}"
                                          method="POST"
                                          class="form-inline">
                                        @csrf
                                        @method('PUT')
                                        <div class="form-group w-100">
                                            <i class="fas fa-comment-dots text-muted mr-2"></i>
                                            <input type="text"
                                                   name="comments"
                                                   class="form-control form-control-sm mr-2 w-75"
                                                   placeholder="Add comment"
                                                   value="{{ $documentTrack->comments }}"
                                                   {{ !$canComment ? 'disabled' : '' }}>
                                            <button type="submit"
                                                    class="btn btn-sm btn-outline-primary"
                                                    {{ !$canComment ? 'disabled' : '' }}>
                                                <i class="fas fa-paper-plane"></i> Submit
                                            </button>
                                        </div>
                                    </form>
                                </div>

                                <div class="timeline-footer mt-2">
                                    <small class="text-muted">
                                        <i class="fas fa-calendar-alt mr-1"></i>
                                        {{ $documentTrack->created_at->format('F d, Y h:i A') }}
                                    </small>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    <!-- End marker -->
                    <div>
                        <i class="fas fa-clock bg-gray"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

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
