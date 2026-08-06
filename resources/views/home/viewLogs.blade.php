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
                                <h3 class="card-title">Routed Documents Logs</h3>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="logsTable" class="table table-bordered" style="font-size:11px; width:100%;">
                                        <thead>
                                            <tr>
                                                <th>Logs</th>
                                                <th>Date</th>
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
        $(document).ready(function() {
            $('#logsTable').DataTable({
                "processing": true,
                "serverSide": true,
                "ajax": {
                    "url": "{{ route('viewLogs.data') }}",
                    "type": "GET"
                },
                "columns": [
                    { "data": "log_entry", "name": "log_entry", "orderable": false },
                    { "data": "date", "name": "created_at" }
                ],
                "order": [[1, "desc"]],
                "pageLength": 50,
                "deferRender": true,
                "language": {
                    "processing": '<i class="fas fa-spinner fa-spin"></i> Loading...',
                    "search": "Search:",
                    "lengthMenu": "Show _MENU_ entries",
                    "info": "Showing _START_ to _END_ of _TOTAL_ entries"
                }
            });
        });
    </script>
@endsection