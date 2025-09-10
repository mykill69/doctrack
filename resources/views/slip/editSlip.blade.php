@extends('layouts.main')

<style>
    /* Align Select2 to match Bootstrap form-control */
    .select2-container--default .select2-selection--multiple {
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
        height: auto;
        min-height: 38px;
        padding: 0.375rem 0.75rem;
        font-size: 1rem;
        line-height: 1.5;
    }

    .select2-selection__rendered {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
    }

    /* Fix dropdown to appear above loader */
    .select2-dropdown {
        z-index: 9999 !important;
    }
</style>

@section('body')
    <div class="content-wrapper">
        <div class="content pt-4">
            <div class="container-fluid">
                <div class="col-md-12">
                    <div class="card shadow-sm">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div class="col-md-10">
                                <h4 class="text-bold mb-11">
                                    Attachment:
                                    <a href="{{ route('viewPdfslip', $routingSlips->id) }}" target="_blank"
                                        class="badge badge-primary text-white">
                                        {{ $routingSlips->document }} <i class="fas fa-download ml-1"></i>
                                    </a>
                                </h4>
                            </div>
                            <div class="col-md-1">
                                <span class="card-title mb-0 badge badge-danger">
                                    {{ $routingSlips->created_at->format('M j, Y H:i:s') }}
                                </span>
                            </div>
                        </div>

                        <div class="card-body">
                            <form action="{{ route('updateSlip', $routingSlips->id) }}" method="POST"
                                enctype="multipart/form-data">
                                @csrf
                                @method('PUT')

                                @php
                                    $userDepartment = auth()->user()->department;
                                @endphp

                                {{-- Control Number --}}
                                <div class="form-group row">
                                    <label for="op_ctrl" class="col-md-3 col-form-label">Control Number:</label>
                                    <div class="col-md-9">
                                        <input type="number" class="form-control" id="op_ctrl" name="op_ctrl"
                                            placeholder="Control number" required>
                                    </div>
                                </div>

                                {{-- Source --}}
                                <div class="form-group row">
                                    <label for="source" class="col-md-3 col-form-label">Source:</label>
                                    <div class="col-md-9">
                                        <input type="text" class="form-control" name="source"
                                            value="{{ $routingSlips->source }}" readonly>
                                    </div>
                                    <input type="hidden" name="pres_dept" value="{{ $userDepartment }}">
                                </div>

                                {{-- Subject Matter --}}
                                <div class="form-group row">
                                    <label class="col-md-3 col-form-label">Subject Matter:</label>
                                    <div class="col-md-9">
                                        <input type="text" class="form-control" name="subject"
                                            value="{{ $routingSlips->subject }}" readonly>
                                    </div>
                                </div>

                                {{-- Transaction Remarks --}}
                                <div class="form-group row">
                                    <label class="col-md-3 col-form-label">Transaction Remarks:</label>
                                    <div class="col-md-9">
                                        <select class="form-control" name="trans_remarks" required>
                                            <option value="">Select Remarks</option>
                                            @foreach (['Appropriate Action', 'Comment &/or Recommendation', 'Information', 'Endorsement', 'Edit/Correct', 'Review/Study', 'File', 'Draft Reply', 'See the Office', 'Calendar'] as $remark)
                                                <option value="{{ $remark }}">{{ $remark }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

  
                                {{-- Action Unit --}}
                                <div class="form-group row">
                                    <label class="col-md-3 col-form-label">Action Unit:</label>
                                    <div class="col-md-9">
                                        <select class="form-control select2" name="r_destination[]" id="r_destination"
                                            multiple required>
                                            @foreach ($users as $user)
                                                <option value="{{ $user->fname }} {{ $user->lname }}"
                                                    {{ in_array($user->id, explode(', ', $routingSlips->r_destination)) ? 'selected' : '' }}>
                                                    {{ $user->fname }} {{ $user->lname }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>


                                {{-- document update --}}
                                <div class="form-group row">
                                    <label class="col-md-3 col-form-label">Upload Document:</label>
                                    <div class="col-md-9">
                                        <div class="custom-file">
                                            <input type="file" class="custom-file-input" id="document" name="document"
                                                accept=".pdf">
                                            <label class="custom-file-label" for="document">Choose document</label>
                                        </div>
                                        @if ($routingSlips->document)
                                            <small class="form-text text-muted">Current:
                                                {{ $routingSlips->document }}</small>
                                        @endif
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label class="col-md-3 col-form-label">Additional Remarks (optional):</label>
                                    <div class="col-md-9">
                                        <input type="text" class="form-control" id="other_remarks" name="other_remarks"
                                            placeholder="Additional Remarks">
                                    </div>
                                </div>
                                {{-- Received Name --}}
                                <div class="form-group row">
                                    <label class="col-md-3 col-form-label">Received Name:</label>
                                    <div class="col-md-9">
                                        <input type="text" class="form-control" id="received_name" name="received_name[]"
                                            placeholder="Name on the Received stamp" required>
                                    </div>
                                </div>

                                {{-- Hidden Inputs --}}
                                <input type="hidden" name="user_id" value="{{ $routingSlips->user_id }}">
                                <input type="hidden" name="route_status" value="2">

                                {{-- Buttons --}}
                                <div class="form-group row mt-4">
                                    <div class="col-md-3"></div>
                                    <div class="col-md-9">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save mr-1"></i> Update Routing Slip
                                        </button>
                                        <a href="{{ route('viewSlip') }}" class="btn btn-danger">
                                            <i class="fas fa-times mr-1"></i> Cancel
                                        </a>
                                    </div>
                                </div>

                                {{-- Validation Errors --}}
                                @if ($errors->any())
                                    <div class="alert alert-danger mt-3">
                                        <ul class="mb-0">
                                            @foreach ($errors->all() as $error)
                                                <li>{{ $error }}</li>
                                            @endforeach
                                        </ul>
                                    </div>
                                @endif
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0/dist/js/select2.min.js"></script>

    {{-- File Input JS --}}
    <script>
        document.querySelectorAll('.custom-file-input').forEach(input => {
            input.addEventListener('change', function() {
                this.nextElementSibling.innerText = this.files[0]?.name || 'Choose file';
            });
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.getElementById('route-form');
            const loader = document.getElementById('page-loader');
            const bar = document.getElementById('progress-bar');
            const card = document.querySelector('.card'); // Target the card container to hide

            // Initialize Select2 for new_destination
            $(document).ready(function() {
                $('#r_destination').select2({
                    placeholder: "Select users...",
                    width: '100%'
                });
            });


            if (form) {
                form.addEventListener('submit', function(e) {
                    if (card) {
                        card.style.display = 'none';
                    }

                    setTimeout(() => {
                        loader.style.display = 'flex';
                    }, 100);

                    animateBarTo(90, 8000);
                });
            }

            function animateBarTo(target, duration) {
                const start = parseFloat(bar.style.width) || 0;
                const diff = target - start;
                const startTs = performance.now();
                requestAnimationFrame(function step(now) {
                    const pct = Math.min(1, (now - startTs) / duration);
                    bar.style.width = (start + diff * pct) + '%';
                    if (pct < 1) requestAnimationFrame(step);
                });
            }
        });
    </script>

@endsection
