@extends('layouts.main')

@section('body')
    <div class="content-wrapper">

        <!-- Main content -->
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
                                            <label for="date_from">From:</label>
                                            <input type="date" class="form-control" name="date_from"
                                                value="{{ request('date_from') }}" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="date_to">To:</label>
                                            <input type="date" class="form-control" name="date_to"
                                                value="{{ request('date_to') }}" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label for="status">Status:</label>
                                            <select class="form-control" name="status">
                                                <option value="">-- Select Status --</option>
                                                <option value="2" {{ request('status') == 2 ? 'selected' : '' }}>
                                                    Pending</option>
                                                <option value="3" {{ request('status') == 3 ? 'selected' : '' }}>
                                                    Completed</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3 d-flex align-items-end">
                                            <button type="submit" class="btn btn-primary w-100">Print</button>
                                        </div>
                                    </div>
                                </form>

                            </div>

                            <!-- PDF preview -->
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
                console.log("DPA modal trigger running");

                // Wait until Bootstrap modal is available
                if (typeof $ !== 'undefined' && typeof $.fn.modal !== 'undefined') {
                    $('#dpaPopupAuto').modal({
                        backdrop: 'static',
                        keyboard: false
                    });
                } else {
                    console.error("Modal cannot open because Bootstrap/jQuery is not loaded.");
                }
            });
        </script>
    @endif



    <script>
        $(document).ready(function() {
            var t = $('#dashboardTable').DataTable({
                "order": [
                    [0, "desc"]
                ], // sort CTRL # descending
                "pageLength": 20,
                "columnDefs": [{
                    "targets": 0,
                    "type": "num",
                    "orderable": true
                }]
            });

            // Re-apply sort in case AdminLTE interferes
            t.order([0, "desc"]).draw();
        });
    </script>






    {{-- @include('modal.docAdd')
    @include('modal.docEdit')
    @include('modal.addTrans')
    @include('modal.addRoutslip') --}}
    {{-- @include('modal.dpaPopup') --}}

    {{-- @include('modal.addIncoming') --}}
@endsection
