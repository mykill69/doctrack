@extends('layouts.main')

@section('body')
    <div class="content-wrapper">
        <div class="content" style="padding-top: 1%;">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">PRINT LOGBOOK</h3>
                            </div>
                            <div class="card-body">
                                <form id="printForm" method="GET" target="printFrame" action="{{ route('logbookPdf') }}">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <label for="ctrl_from">CTRL # From: (Optional)</label>
                                            <input type="number" class="form-control" name="ctrl_from"
                                                value="{{ request('ctrl_from') }}" placeholder="e.g. 1000">
                                        </div>
                                        <div class="col-md-3">
                                            <label for="ctrl_to">CTRL # To: (Optional)</label>
                                            <input type="number" class="form-control" name="ctrl_to"
                                                value="{{ request('ctrl_to') }}" placeholder="e.g. 2000">
                                        </div>
                                        <div class="col-md-2">
                                            <label for="month">Month:</label>
                                            <select class="form-control" name="month">
                                                <option value="">-- All Months --</option>
                                                @for ($m = 1; $m <= 12; $m++)
                                                    <option value="{{ $m }}" {{ request('month') == $m ? 'selected' : '' }}>
                                                        {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                                                    </option>
                                                @endfor
                                            </select>
                                        </div>
                                        <div class="col-md-2">
                                            <label for="status">Status:</label>
                                            <select class="form-control" name="status">
                                                <option value="">-- Select Status --</option>
                                                <option value="2" {{ request('status') == 2 ? 'selected' : '' }}>Pending</option>
                                                <option value="3" {{ request('status') == 3 ? 'selected' : '' }}>Completed</option>
                                            </select>
                                        </div>
                                        <div class="col-md-2 d-flex align-items-end">
                                            <button type="submit" class="btn btn-primary w-100" id="printBtn">
                                                <i class="fas fa-print mr-1"></i> Print
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>

                            {{-- PDF Viewer Area --}}
                            <div style="position: relative;">
                                {{-- Loading Overlay --}}
                                <div id="loadingOverlay" style="display: none; position: absolute; top: 0; left: 0; 
                                    width: 100%; height: 100%; background: rgba(255,255,255,0.9); z-index: 1000; 
                                    flex-direction: column; justify-content: center; align-items: center;">
                                    <div class="spinner-border text-primary" style="width: 4rem; height: 4rem;" role="status">
                                        <span class="sr-only">Loading...</span>
                                    </div>
                                    <h5 class="mt-3 text-primary font-weight-bold">Generating PDF...</h5>
                                    <p class="text-muted">Please wait while your document is being prepared.</p>
                                </div>
                                
                                {{-- iframe --}}
                                <iframe name="printFrame" id="printFrame" src="" frameborder="0"
                                    style="width:100%; height:800px; display: none;"></iframe>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#printForm').on('submit', function() {
                // Show loading and iframe
                $('#loadingOverlay').css('display', 'flex');
                $('#printFrame').show();
                // Clear iframe src to show loading state
                $('#printFrame').attr('src', '');
            });
            
            // When iframe finishes loading, hide the loading overlay
            $('#printFrame').on('load', function() {
                $('#loadingOverlay').fadeOut(500);
            });
        });
    </script>

    @if (!$dpa)
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                if (typeof $ !== 'undefined' && typeof $.fn.modal !== 'undefined') {
                    $('#dpaPopupAuto').modal({
                        backdrop: 'static',
                        keyboard: false
                    });
                }
            });
        </script>
    @endif
@endsection