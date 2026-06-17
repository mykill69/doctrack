@extends('layouts.main')

<style>
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
                                    CTRL #{{ $routingSlips->rslip_id }} - Edit
                                    <span class="badge badge-warning ml-2">Routed to President</span>
                                </h4>
                            </div>
                            <div class="col-md-1">
                                <span class="card-title mb-0 badge badge-danger">
                                    {{ $routingSlips->created_at->format('M j, Y H:i:s') }}
                                </span>
                            </div>
                        </div>

                        <div class="card-body">
                            <form action="{{ route('updateSubject', $routingSlips->id) }}" method="POST">
                                @csrf
                                @method('PUT')

                                {{-- Alert Info --}}
                                <div class="form-group row">
                                    <div class="col-md-12">
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle mr-2"></i>
                                            <strong>Note:</strong> You can edit Control Number, Source, Date Received, and Subject Matter.
                                        </div>
                                    </div>
                                </div>

                                {{-- Control Number (EDITABLE) --}}
                                <div class="form-group row">
                                    <label for="op_ctrl" class="col-md-3 col-form-label font-weight-bold">
                                        Control Number: <span class="text-danger">*</span>
                                    </label>
                                    <div class="col-md-9">
                                        <input type="number" class="form-control" name="op_ctrl" 
                                            value="{{ old('op_ctrl', $routingSlips->op_ctrl ?? $routingSlips->rslip_id) }}" 
                                            required>
                                        @error('op_ctrl')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                {{-- Source (EDITABLE) --}}
                                <div class="form-group row">
                                    <label for="source" class="col-md-3 col-form-label font-weight-bold">
                                        Source: <span class="text-danger">*</span>
                                    </label>
                                    <div class="col-md-9">
                                        <input type="text" class="form-control" name="source" 
                                            value="{{ old('source', $routingSlips->source) }}" 
                                            required>
                                        @error('source')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                {{-- Date Received (EDITABLE) --}}
                                <div class="form-group row">
                                    <label class="col-md-3 col-form-label font-weight-bold">
                                        Date Received: <span class="text-danger">*</span>
                                    </label>
                                    <div class="col-md-9">
                                        <input type="date" class="form-control" name="date_received" 
                                            value="{{ old('date_received', \Carbon\Carbon::parse($routingSlips->date_received)->format('Y-m-d')) }}" 
                                            required>
                                        @error('date_received')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                {{-- Subject Matter (EDITABLE - TEXTAREA) --}}
                                <div class="form-group row">
                                    <label class="col-md-3 col-form-label font-weight-bold">
                                        Subject Matter: <span class="text-danger">*</span>
                                    </label>
                                    <div class="col-md-9">
                                        <textarea class="form-control form-control-lg" name="subject" 
                                            rows="4" 
                                            placeholder="Enter corrected subject matter" 
                                            required 
                                            autofocus
                                            style="resize: vertical; min-height: 100px;">{{ old('subject', $routingSlips->subject) }}</textarea>
                                        @error('subject')
                                            <span class="text-danger">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>

                                {{-- Transaction Remarks (Read-only) --}}
                                <div class="form-group row">
                                    <label class="col-md-3 col-form-label">Transaction Remarks:</label>
                                    <div class="col-md-9">
                                        <input type="text" class="form-control" 
                                            value="{{ $routingSlips->trans_remarks }}" readonly>
                                    </div>
                                </div>

                                {{-- Action Unit (Read-only) --}}
                                <div class="form-group row">
                                    <label class="col-md-3 col-form-label">Action Unit:</label>
                                    <div class="col-md-9">
                                        <input type="text" class="form-control" 
                                            value="{{ $routingSlips->r_destination }}" readonly>
                                    </div>
                                </div>

                                {{-- Document (Read-only) --}}
                                <div class="form-group row">
                                    <label class="col-md-3 col-form-label">Document:</label>
                                    <div class="col-md-9">
                                        @if($routingSlips->document)
                                            <a href="{{ route('viewPdfslip', $routingSlips->id) }}" target="_blank"
                                                class="badge badge-primary text-white" style="font-size: 14px; padding: 8px 12px;">
                                                <i class="fas fa-file-pdf mr-1"></i> {{ $routingSlips->document }}
                                                <i class="fas fa-download ml-2"></i>
                                            </a>
                                        @else
                                            <span class="text-muted">No document attached</span>
                                        @endif
                                    </div>
                                </div>

                                {{-- Other Remarks (Read-only) --}}
                                <div class="form-group row">
                                    <label class="col-md-3 col-form-label">Other Remarks:</label>
                                    <div class="col-md-9">
                                        <input type="text" class="form-control" 
                                            value="{{ $routingSlips->other_remarks ?? 'N/A' }}" readonly>
                                    </div>
                                </div>

                                {{-- Buttons --}}
                                <div class="form-group row mt-4">
                                    <div class="col-md-3"></div>
                                    <div class="col-md-9">
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-save mr-1"></i> Update
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
@endsection