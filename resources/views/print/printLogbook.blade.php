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
                                <form method="GET" target="printFrame" action="{{ route('logbookPdf') }}">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <label for="ctrl_from">CTRL # From:</label>
                                            <input type="number" class="form-control" name="ctrl_from"
                                                value="{{ request('ctrl_from') }}" placeholder="e.g. 1000" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="ctrl_to">CTRL # To:</label>
                                            <input type="number" class="form-control" name="ctrl_to"
                                                value="{{ request('ctrl_to') }}" placeholder="e.g. 2000" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="status">Status:</label>
                                            <select class="form-control" name="status">
                                                <option value="">-- Select Status --</option>
                                                <option value="2" {{ request('status') == 2 ? 'selected' : '' }}>Pending</option>
                                                <option value="3" {{ request('status') == 3 ? 'selected' : '' }}>Completed</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3 d-flex align-items-end">
                                            <button type="submit" class="btn btn-primary w-100">
                                                <i class="fas fa-print mr-1"></i> Print
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            <iframe name="printFrame" src="" frameborder="0"
                                style="width:100%; height:800px;"></iframe>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

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