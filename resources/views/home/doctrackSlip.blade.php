@extends('layouts.main')

<style>
    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background-color: #007bff !important;
        border-color: #187744 !important;
        color: #fff;
        padding: 0 10px;
        margin-top: 0.31rem;
    }
    .dataTables_processing {
        background: rgba(255,255,255,0.9);
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 10px;
        font-weight: bold;
        z-index: 1000;
    }
</style>

@section('body')
    <div class="content-wrapper">
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
                                        style="font-size: 0.8rem; width: 100%;">
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
                                                <th>REMARKS</th>
                                                <th>STATUS</th>
                                                <th>TRACKING CODE</th>
                                                <th>TOTAL DURATION</th>
                                                <th>ACTION</th>
                                            </tr>
                                        </thead>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

    <script>
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $(document).ready(function() {
            var columns = [
                { "data": "date_received", "name": "created_at" },
                { "data": "source", "name": "user_name" },
                { "data": "subject", "name": "doc_title" },
                { "data": "action_unit" },
                { "data": "received_by_date" },
                { "data": "action_taken" },
                { "data": "date_released", "name": "updated_at" },
                { "data": "remarks", "orderable": false },
                { "data": "status", "name": "doctrack_stat" },
                { "data": "tracking_code", "orderable": false },
                { "data": "duration" },
                { "data": "action", "orderable": false, "searchable": false }
            ];

            @if (auth()->user()->id == 1235)
                columns.unshift({ "data": "ctrl_input", "name": "ctrl_no", "orderable": true });
            @endif

            $('#example1').DataTable({
                "processing": true,
                "serverSide": true,
                "ajax": {
                    "url": "{{ route('doctrackSlip.data') }}",
                    "type": "GET"
                },
                "columns": columns,
                "order": [[1, "desc"]],
                "pageLength": 50,
                "deferRender": true,
                "lengthMenu": [[10, 25, 50, 100], [10, 25, 50, 100]],
                "language": {
                    "processing": '<i class="fas fa-spinner fa-spin"></i> Loading...',
                    "search": "Search:",
                    "lengthMenu": "Show _MENU_ entries",
                    "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                    "infoFiltered": "(filtered from _MAX_ total entries)"
                },
                "drawCallback": function() {
                    // Re-attach inline edit handlers
                    $('.doctrack-input').off('focus').on('focus', function() {
                        $(this).data('original-value', $(this).val());
                    });
                    $('.doctrack-input').off('blur').on('blur', function() {
                        let doctrackId = $(this).data('id');
                        let field = $(this).data('field');
                        let value = $(this).val();
                        let originalValue = $(this).data('original-value');
                        if (value === originalValue) return;

                        $.ajax({
                            url: "{{ url('doctrack-slip') }}/" + doctrackId,
                            type: 'POST',
                            data: {
                                _method: 'PUT',
                                [field]: value
                            },
                            success: function(response) {
                                console.log('Updated:', response);
                            },
                            error: function(xhr) {
                                console.error(xhr.responseText);
                            }
                        });
                    });
                }
            });
        });
    </script>

    @include('modal.docAdd')
    @include('modal.docEdit')
    @include('modal.addTrans')
    @include('modal.addRoutslip')
@endsection