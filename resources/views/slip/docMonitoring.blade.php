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
    <link rel="stylesheet" href="{{ asset('template/plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css') }}">
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

<style>
    .swal-wide {
        width: 650px !important;
        max-width: 90%;
    }

    .no-left-radius {
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
    }
</style>


<div class="container">
    <div class="row justify-content-center">
        <div class="col-12 col-sm-10 col-md-8 col-lg-10">
            <div class="content py-3">
                <div class="container-fluid">
                    <div class="card shadow">
                        <div
                            class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                            <h4 class="mb-0"><i class="fas fa-route mr-2"></i>Tracking Code Entries:
                                {{ $docslip_id }}</h4>

                            @if (auth()->id() === $creatorId)
                                <div class="col-12 col-md-6 text-md-right">
                                    <button class="btn btn-primary w-md-auto" data-toggle="modal"
                                        data-target="#addLogModal">
                                        <i class="fa fa-plus"></i> Entry
                                    </button>
                                </div>
                            @endif
                        </div>

                        <div class="card-body p-3" id="accordion">
                            @foreach ($documentTrackid as $documentTrack)
                                @php
                                    $loopIndex = $loop->index;
                                    $collapseId = 'collapseTrack' . $loopIndex;
                                    $currentUserId = auth()->user()->id;
                                    $isFirst = $loop->first;
                                    $isEnabled =
                                        ($currentUserId == $documentTrack->user_id &&
                                            is_null($documentTrack->update_by)) ||
                                        $currentUserId == $documentTrack->update_by;
                                    $canComment = $documentTrack->update_by === $currentUserId;

                                    // ✅ Fix: Show the correct person (creator only for the very first CREATED entry)
                                    if ($documentTrack->doctrack_stat == 1) {
                                        $person =
                                            $documentTrack->createdBy->fname . ' ' . $documentTrack->createdBy->lname;
                                    } else {
                                        $person =
                                            ($documentTrack->updatedBy->fname ?? 'N/A') .
                                            ' ' .
                                            ($documentTrack->updatedBy->lname ?? '');
                                    }

                                    switch ($documentTrack->doctrack_stat) {
                                        case 1:
                                            $statusText = 'CREATED';
                                            $bgColor = 'primary';
                                            $icon = 'fas fa-plus';
                                            break;
                                        case 2:
                                            $statusText = 'PENDING';
                                            $bgColor = 'warning';
                                            $icon = 'fas fa-hourglass-half';
                                            break;
                                        case 3:
                                            $statusText = 'SIGNED';
                                            $bgColor = 'success';
                                            $icon = 'fas fa-check-circle';
                                            break;
                                        case 4:
                                            $statusText = 'RETURNED WITH COMMENT';
                                            $bgColor = 'danger';
                                            $icon = 'fas fa-comment-slash';
                                            break;
                                        case 5:
                                            $statusText = 'CHECKED';
                                            $bgColor = 'info';
                                            $icon = 'fas fa-user-check';
                                            break;
                                        case 6:
                                            $statusText = 'ACKNOWLEDGED';
                                            $bgColor = 'success';
                                            $icon = 'fas fa-handshake';
                                            break;
                                        default:
                                            $statusText = 'UNKNOWN';
                                            $bgColor = 'secondary';
                                            $icon = 'fas fa-question-circle';
                                            break;
                                    }
                                @endphp

                                <div class="card card-{{ $bgColor }} card-outline">
                                    <a class="d-block w-100 collapsed" data-toggle="collapse"
                                        href="#{{ $collapseId }}">
                                        <div class="card-header">
                                            <div class="row w-100 align-items-left">
                                                <!-- Icon Column -->
                                                <div class="col-auto text-center mr-3"> {{-- or me-3 if using Bootstrap 5 --}}
                                                    <div class="icon-box bg-{{ $bgColor }} text-white d-flex align-items-center justify-content-center"
                                                        style="width: 48px; height: 48px; border-radius: 8px;">
                                                        <i class="{{ $icon }}"></i>
                                                    </div>
                                                </div>

                                                <!-- Text Column -->
                                                <div class="col pl-0">
                                                    <h5 class="card-title mb-0">
                                                        <span
                                                            class="font-weight-bold text-dark">{{ $person }}</span>
                                                        <br>
                                                        <span>
                                                            {{ $documentTrack->doc_title ?? 'N/A' }}
                                                            <small class="text-muted"><em>(click to view)</em></small>
                                                        </span>
                                                    </h5>
                                                </div>

                                                <!-- Right: Timestamp -->
                                                <div class="col-md-3 col-12 text-md-right text-left mt-2 mt-md-0">
                                                    <small class="text-muted">
                                                        <i class="far fa-clock mr-1"></i>
                                                        {{ $documentTrack->updated_at->format('F d, Y h:i A') }}
                                                    </small>
                                                </div>
                                            </div>
                                        </div>

                                    </a>
                                    <div id="{{ $collapseId }}" class="collapse" data-parent="#accordion">
                                        <div class="card-body">
                                            <div class="row">
                                                <!-- Attached File Section -->
                                                <div
                                                    class="col-md-12 d-flex justify-content-between align-items-center flex-wrap mb-3">
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-paperclip text-muted mr-2"></i>
                                                        @if ($documentTrack->doctrackFile)
                                                            <a href="{{ asset('storage/doc_track/' . $documentTrack->doctrackFile->file) }}"
                                                                target="_blank">
                                                                <i class="fas fa-file-pdf text-danger"></i>
                                                                <span>{{ $documentTrack->doctrackFile->file }}</span>
                                                            </a>
                                                        @else
                                                            <span class="text-muted">No file attached</span>
                                                        @endif
                                                    </div>

                                                    <!-- Upload Button -->
                                                    <button
                                                        onclick="openFileUpload('{{ $documentTrack->docslip_id }}')"
                                                        class="btn btn-sm btn-outline-primary ml-auto"
                                                        {{ !$isEnabled ? 'disabled' : '' }}>
                                                        <i class="fas fa-upload mr-1"></i>Upload File
                                                    </button>
                                                </div>
                                            </div>

                                            <!-- Status Update Dropdown -->
                                            <form id="statusForm{{ $loopIndex }}"
                                                action="{{ route('updateSlipStatus', $documentTrack->id) }}"
                                                method="POST" class="mb-3">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="doctrack_stat"
                                                    id="doctrackStatInput{{ $loopIndex }}">

                                                <div class="dropdown">
                                                    <button
                                                        class="btn btn-sm dropdown-toggle font-weight-bold px-4 py-2 
                                                         {{ $isEnabled ? 'btn-' . $bgColor : 'btn-outline-secondary text-muted' }}"
                                                        type="button" data-toggle="dropdown"
                                                        {{ !$isEnabled ? 'disabled' : '' }}
                                                        style="{{ $isEnabled ? 'box-shadow: 0 0 10px rgba(0,123,255,0.3);' : '' }}">
                                                        <i class="fas fa-sync-alt mr-1"></i> {{ $statusText }}
                                                    </button>

                                                    @if ($isEnabled)
                                                        <div class="dropdown-menu">
                                                            <a class="dropdown-item" href="#"
                                                                onclick="submitStatus({{ $loopIndex }}, 2)">
                                                                <i class="fas fa-hourglass-half mr-1"></i> Pending
                                                            </a>
                                                            <a class="dropdown-item" href="#"
                                                                onclick="submitStatus({{ $loopIndex }}, 3)">
                                                                <i class="fas fa-check-circle mr-1"></i> Signed
                                                            </a>
                                                            <a class="dropdown-item" href="#"
                                                                onclick="submitStatus({{ $loopIndex }}, 4)">
                                                                <i class="fas fa-undo mr-1"></i> Return with Comment
                                                            </a>
                                                            <a class="dropdown-item" href="#"
                                                                onclick="submitStatus({{ $loopIndex }}, 5)">
                                                                <i class="fas fa-user-check mr-1"></i> Checked
                                                            </a>
                                                            <a class="dropdown-item" href="#"
                                                                onclick="submitStatus({{ $loopIndex }}, 6)">
                                                                <i class="fas fa-handshake mr-1"></i> Acknowledged
                                                            </a>
                                                        </div>
                                                    @endif


                                                </div>
                                            </form>

                                            <!-- Comment Box -->
                                            <form id="commentForm{{ $loopIndex }}"
                                                action="{{ route('updateSlipStatus', $documentTrack->id) }}"
                                                method="POST" class="w-100">
                                                @csrf
                                                @method('PUT')
                                                <div class="input-group input-group-sm">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text bg-white border-right-0">
                                                            <i class="fas fa-comment-dots text-muted"></i>
                                                        </span>
                                                    </div>
                                                    <input type="text" name="comments"
                                                        class="form-control border-left-0" placeholder="Add comment"
                                                        value="{{ $documentTrack->comments }}"
                                                        {{ !$canComment ? 'disabled' : '' }}>
                                                    <div class="input-group-append">
                                                        <button type="submit" class="btn btn-outline-primary"
                                                            {{ !$canComment ? 'disabled' : '' }}>
                                                            Submit
                                                        </button>
                                                    </div>
                                                </div>
                                            </form>

                                        </div>
                                    </div>

                                </div>

                                {{-- ↓ Add Arrow After Each Card (except last) --}}
                                @if (!$loop->last)
                                    <div class="text-center my-3">
                                        <div class="chevron-stack">
                                            <svg class="chevron chevron1" viewBox="0 0 24 24" width="28"
                                                height="28" stroke="#007bff" fill="none" stroke-width="4"
                                                stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="6 9 12 15 18 9" />
                                            </svg>
                                            <svg class="chevron chevron2" viewBox="0 0 24 24" width="28"
                                                height="28" stroke="#007bff" fill="none" stroke-width="4"
                                                stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="6 9 12 15 18 9" />
                                            </svg>

                                        </div>
                                    </div>

                                    <style>
                                        .chevron-stack {
                                            display: flex;
                                            flex-direction: column;
                                            align-items: center;
                                            gap: 2px;
                                            /* ↓ Reduced from 4px to 2px for tighter spacing */

                                        }

                                        .chevron {
                                            animation: bounceChevron 1.2s infinite;
                                            opacity: 0.6;
                                        }

                                        .chevron1 {
                                            animation-delay: 0s;
                                        }

                                        .chevron2 {
                                            animation-delay: 0.2s;
                                        }

                                        @keyframes bounceChevron {
                                            0% {
                                                transform: translateY(0);
                                                opacity: 0.6;
                                            }

                                            50% {
                                                transform: translateY(6px);
                                                opacity: 1;
                                            }

                                            100% {
                                                transform: translateY(0);
                                                opacity: 0.6;
                                            }
                                        }

                                        .icon-box {
                                            font-size: 20px;
                                            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.15);
                                        }
                                    </style>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


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

<!-- SweetAlert2 -->
<script src="{{ asset('template/plugins/sweetalert2/sweetalert2.min.js') }}"></script>
<script src="template/plugins/bs-custom-file-input/bs-custom-file-input.min.js"></script>

<script src="{{ asset('template/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    function openFileUpload(doctrackId) {
        Swal.fire({
            title: 'Upload File',
            html: `
            
                    <input type="hidden" value="${doctrackId}" name="docslip_id">
                
                <form id="uploadForm" enctype="multipart/form-data">
                    <input type="file" name="file" id="fileInput"
                        class="form-control border border-secondary rounded" required>
                    <small class="form-text text-muted">Accepted: pdf, doc(x), jpg, png, xls(x), max 20MB</small>
                </form>
            `,
            showCancelButton: true,
            confirmButtonText: '<i class="fas fa-upload mr-1"></i> Upload',
            cancelButtonText: 'Cancel',
            customClass: {
                confirmButton: 'btn btn-primary',
                cancelButton: 'btn btn-secondary ml-2'
            },
            buttonsStyling: false,
            focusConfirm: false,
            preConfirm: () => {
                const fileInput = document.getElementById('fileInput');
                const formData = new FormData();
                const token = '{{ csrf_token() }}';

                if (fileInput.files.length === 0) {
                    Swal.showValidationMessage('Please select a file.');
                    return false;
                }

                formData.append('_token', token);
                formData.append('docslip_id', doctrackId);
                formData.append('file', fileInput.files[0]);

                Swal.close();

                return fetch("{{ route('uploadDoctrackFile') }}", {
                        method: 'POST',
                        body: formData
                    })
                    .then(async response => {
                        const contentType = response.headers.get('content-type');
                        if (!response.ok) {
                            const text = await response.text();
                            throw new Error(`Upload failed:\n\n${text}`);
                        }

                        if (contentType && contentType.includes('application/json')) {
                            return response.json();
                        } else {
                            throw new Error('Invalid server response (not JSON)');
                        }
                    })
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: 'File uploaded successfully.',
                                confirmButtonText: 'OK',
                                customClass: {
                                    confirmButton: 'btn btn-success'
                                },
                                buttonsStyling: false
                            }).then(() => location.reload());
                        } else {
                            throw new Error(data.message || 'Something went wrong');
                        }
                    })
                    .catch(error => {
                        Swal.fire('Upload Error', error.message, 'error');
                    });
            }
        });
    }
</script>
