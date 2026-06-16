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
                                <h3 class="card-title">DOCUMENT LOGBOOK</h3>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="example1" class="table table-bordered table-hover"
                                        style="font-size: 0.8rem; width: 100%;">
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
                                                <th>DATE UPDATED</th>
                                                <th>TOTAL DURATION</th>
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
    @include('modal.docAdd')
    @include('modal.docEdit')
    @include('modal.addTrans')
    @include('modal.addRoutslip')

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>

    <script>
        $(document).ready(function() {
            // Destroy existing DataTable if already initialized
            if ($.fn.DataTable.isDataTable('#example1')) {
                $('#example1').DataTable().destroy();
            }

            // Initialize DataTable
            $('#example1').DataTable({
                "processing": true,
                "serverSide": true,
                "ajax": {
                    "url": "{{ route('served.data') }}",
                    "type": "GET"
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
                    { "data": "remarks", "name": "remarks" },
                    { "data": "file_name", "name": "file_name" },
                    { "data": "updated_at", "name": "updated_at" },
                    { "data": "duration", "name": "duration" }
                ],
                "order": [[0, "desc"]],
                "pageLength": 25,
                "deferRender": true,
                "lengthMenu": [[10, 25, 50, 100], [10, 25, 50, 100]],
                "language": {
                    "processing": '<i class="fas fa-spinner fa-spin"></i> Loading...',
                    "search": "Search:",
                    "lengthMenu": "Show _MENU_ entries",
                    "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                    "infoFiltered": "(filtered from _MAX_ total entries)"
                }
            });
        });
    </script>
@endsection