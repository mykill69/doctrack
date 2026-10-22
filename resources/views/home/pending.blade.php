@extends('layouts.main')

@php
    use App\Models\Log;

    $user = auth()->user();
    $userFullName = $user->fname . ' ' . $user->lname;
    $userDepartment = $user->department;
    $isRecordsOfficer = $user->role === 'records_officer';
@endphp

<style>
    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background-color: #007bff !important;
        border-color: #187744 !important;
        color: #fff;
        padding: 0 10px;
        margin-top: 0.31rem;
    }

    .dataTables_processing {
        background: rgba(255, 255, 255, 0.9);
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
                                <h3 class="card-title">PENDING DOCUMENTS</h3>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="pendingTable" class="table table-bordered table-hover"
                                        style="font-size: 0.8rem;">
                                        <thead>
                                            <tr>
                                                <th>CTRL #</th>
                                                <th>DATE RECEIVED</th>
                                                <th>SOURCE</th>
                                                <th>SUBJECT MATTER</th>
                                                <th>ACTION UNIT</th>
                                                <th>RECEIVED BY/DATE</th>
                                                <th>ACTION TAKEN</th>
                                                <th>DATE RELEASED</th>
                                                <th>REMARKS</th>
                                                <th>FILE NAME</th>
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
        <!-- /.content -->
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

    <script>
        $(document).ready(function() {
            // ✅ Initialize DataTable (server side - same logic as the dashboard)
            $('#pendingTable').DataTable({
                "processing": true,
                "serverSide": true,
                "ajax": {
                    "url": "{{ route('pending.data') }}",
                    "type": "GET",
                    "error": function(xhr, error, thrown) {
                        console.error('DataTables error:', error);
                    }
                },
                "columns": [
                    { "data": "route_display", "name": "route_id" },
                    { "data": "date_received", "name": "date_received" },
                    { "data": "source", "name": "source" },
                    { "data": "subject", "name": "subject" },
                    { "data": "action_unit", "name": "action_unit" },
                    { "data": "received_by_date", "name": "received_by_date" },
                    { "data": "action_taken", "name": "action_taken" },
                    { "data": "date_released", "name": "date_released" },
                    { "data": "remarks", "name": "remarks", "orderable": false },
                    { "data": "file_name", "name": "file_name" },
                    { "data": "action", "name": "action", "orderable": false, "searchable": false }
                ],
                "order": [[0, "desc"]],
                "pageLength": 20,
                "deferRender": true,
                "lengthMenu": [[10, 25, 50, 100], [10, 25, 50, 100]],
                "searching": true,
                // ✅ Wait before firing the search request (the table is server side)
                "searchDelay": 400,
                "language": {
                    "processing": '<i class="fas fa-spinner fa-spin"></i> Loading...',
                    "search": "Search:",
                    "searchPlaceholder": "Search pending records...",
                    "lengthMenu": "Show _MENU_ entries",
                    "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                    "infoFiltered": "(filtered from _MAX_ total entries)",
                    "paginate": {
                        "first": "First",
                        "last": "Last",
                        "next": "Next",
                        "previous": "Previous"
                    }
                }
            });
        });
    </script>

    @include('modal.docAdd')
    @include('modal.docEdit')
    @include('modal.addTrans')
    @include('modal.addRoutslip')
@endsection
