@extends('layouts.main')

@php
    $title = 'Archived History Logbook';
@endphp

@section('body')
    <div class="content-wrapper">
        <div class="content" style="padding-top: 1%;">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card card-outline card-success">
                            <div class="card-header">
                                <h3 class="card-title"><i class="fas fa-clock-rotate-left mr-2"></i>ARCHIVED HISTORY LOGBOOK</h3>
                                <div class="card-tools">
                                    <span class="badge badge-dark">Read-only</span>
                                </div>
                            </div>
                            <div class="card-body">
                                <ul class="nav nav-tabs" role="tablist">
                                    <li class="nav-item">
                                        <a class="nav-link active" id="routing-tab" data-toggle="tab" href="#routingPane" role="tab">
                                            <i class="fas fa-route mr-1"></i> Routing Slip
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" id="document-tab" data-toggle="tab" href="#documentPane" role="tab">
                                            <i class="fas fa-file-alt mr-1"></i> Document Tracking
                                        </a>
                                    </li>
                                </ul>

                                <div class="tab-content pt-3">
                                    <div class="tab-pane fade show active" id="routingPane" role="tabpanel">
                                        <div class="row mb-3">
                                            <div class="col-md-2">
                                                <label class="small mb-1">Year</label>
                                                <select id="routingYear" class="form-control form-control-sm archive-filter">
                                                    <option value="">All archived years</option>
                                                    @foreach ($years as $year)
                                                        <option value="{{ $year }}">{{ $year }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="small mb-1">Date From</label>
                                                <input type="date" id="routingDateFrom" class="form-control form-control-sm archive-filter">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="small mb-1">Date To</label>
                                                <input type="date" id="routingDateTo" class="form-control form-control-sm archive-filter">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="small mb-1">CTRL #</label>
                                                <input type="text" id="routingCtrlNo" class="form-control form-control-sm archive-filter" placeholder="e.g. 1234">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="small mb-1">Subject</label>
                                                <input type="text" id="routingSubject" class="form-control form-control-sm archive-filter" placeholder="Subject matter">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="small mb-1">Source</label>
                                                <input type="text" id="routingSource" class="form-control form-control-sm archive-filter" placeholder="Source">
                                            </div>
                                            <div class="col-md-2 mt-2">
                                                <label class="small mb-1">Action Unit</label>
                                                <select id="routingActionUnit" class="form-control form-control-sm archive-filter">
                                                    <option value="">All action units</option>
                                                    @foreach ($actionUnits as $actionUnit)
                                                        <option value="{{ $actionUnit }}">{{ $actionUnit }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-10 mt-2 d-flex align-items-end">
                                                <button type="button" id="routingApply" class="btn btn-sm btn-success">
                                                    <i class="fas fa-filter mr-1"></i> Apply Filters
                                                </button>
                                                <button type="button" id="routingReset" class="btn btn-sm btn-outline-secondary ml-2">
                                                    <i class="fas fa-undo mr-1"></i> Reset
                                                </button>
                                            </div>
                                        </div>

                                        <div class="table-responsive">
                                            <table id="routingHistoryTable" class="table table-bordered table-hover"
                                                style="font-size: 0.78rem; width: 100%;">
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
                                                        <th>UPDATED DATE/BY</th>
                                                        <th>VALIDITY</th>
                                                        <th>LOGBOOK STATUS</th>
                                                        <th>ACTION</th>
                                                    </tr>
                                                </thead>
                                            </table>
                                        </div>
                                    </div>

                                    <div class="tab-pane fade" id="documentPane" role="tabpanel">
                                        <div class="row mb-3">
                                            <div class="col-md-2">
                                                <label class="small mb-1">Year</label>
                                                <select id="documentYear" class="form-control form-control-sm document-filter">
                                                    <option value="">All archived years</option>
                                                    @foreach ($years as $year)
                                                        <option value="{{ $year }}">{{ $year }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-2">
                                                <label class="small mb-1">Date From</label>
                                                <input type="date" id="documentDateFrom" class="form-control form-control-sm document-filter">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="small mb-1">Date To</label>
                                                <input type="date" id="documentDateTo" class="form-control form-control-sm document-filter">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="small mb-1">CTRL #</label>
                                                <input type="text" id="documentCtrlNo" class="form-control form-control-sm document-filter" placeholder="e.g. CPSU123">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="small mb-1">Subject</label>
                                                <input type="text" id="documentSubject" class="form-control form-control-sm document-filter" placeholder="Document title/type">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="small mb-1">Source</label>
                                                <input type="text" id="documentSource" class="form-control form-control-sm document-filter" placeholder="Source">
                                            </div>
                                            <div class="col-md-6 mt-2 d-flex align-items-end">
                                                <button type="button" id="documentApply" class="btn btn-sm btn-success">
                                                    <i class="fas fa-filter mr-1"></i> Apply Filters
                                                </button>
                                                <button type="button" id="documentReset" class="btn btn-sm btn-outline-secondary ml-2">
                                                    <i class="fas fa-undo mr-1"></i> Reset
                                                </button>
                                            </div>
                                        </div>

                                        <div class="table-responsive">
                                            <table id="documentHistoryTable" class="table table-bordered table-hover"
                                                style="font-size: 0.78rem; width: 100%;">
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
                                                        <th>UPDATED DATE/BY</th>
                                                        <th>VALIDITY</th>
                                                        <th>LOGBOOK STATUS</th>
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
        </div>
    </div>
@endsection

@push('page_scripts')
    <script>
        $(function() {
            var routingColumns = [
                { data: 'ctrl_no', name: 'ctrl_no' },
                { data: 'date_received', name: 'date_received' },
                { data: 'source', name: 'source' },
                { data: 'subject', name: 'subject' },
                { data: 'action_unit', name: 'action_unit' },
                { data: 'received_by_date', name: 'received_by_date' },
                { data: 'action_taken', name: 'action_taken' },
                { data: 'date_released', name: 'date_released' },
                { data: 'remarks', name: 'remarks', orderable: false, searchable: false },
                { data: 'file_name', name: 'file_name', orderable: false },
                { data: 'updated_by', name: 'updated_by' },
                { data: 'validity', name: 'validity', orderable: false, searchable: false },
                { data: 'logbook_status', name: 'logbook_status', orderable: false, searchable: false },
                { data: 'action', name: 'action', orderable: false, searchable: false }
            ];

            var documentColumns = routingColumns.map(function(column) {
                return Object.assign({}, column);
            });

            documentColumns[4].orderable = false;
            documentColumns[4].searchable = false;

            function filterData(prefix) {
                return {
                    year: $('#' + prefix + 'Year').val(),
                    date_from: $('#' + prefix + 'DateFrom').val(),
                    date_to: $('#' + prefix + 'DateTo').val(),
                    ctrl_no: $('#' + prefix + 'CtrlNo').val(),
                    subject: $('#' + prefix + 'Subject').val(),
                    source: $('#' + prefix + 'Source').val(),
                    action_unit: $('#' + prefix + 'ActionUnit').length ? $('#' + prefix + 'ActionUnit').val() : ''
                };
            }

            var commonOptions = {
                processing: true,
                serverSide: true,
                deferRender: true,
                pageLength: 25,
                lengthMenu: [
                    [25, 50, 100],
                    [25, 50, 100]
                ],
                searchDelay: 400,
                language: {
                    processing: '<i class="fas fa-spinner fa-spin"></i> Loading...',
                    search: 'Search:',
                    searchPlaceholder: 'Search archived records...',
                    lengthMenu: 'Show _MENU_ entries',
                    info: 'Showing _START_ to _END_ of _TOTAL_ entries',
                    infoFiltered: '(filtered from _MAX_ total entries)',
                    zeroRecords: 'No archived records found.',
                    emptyTable: 'No archived records found.',
                    paginate: {
                        first: 'First',
                        last: 'Last',
                        next: 'Next',
                        previous: 'Previous'
                    }
                }
            };

            var routingTable = $('#routingHistoryTable').DataTable($.extend({}, commonOptions, {
                ajax: {
                    url: "{{ route('archive.history.routingData') }}",
                    type: 'GET',
                    data: function(d) {
                        return $.extend({}, d, filterData('routing'));
                    }
                },
                columns: routingColumns,
                order: [[1, 'desc']]
            }));

            var documentTable = $('#documentHistoryTable').DataTable($.extend({}, commonOptions, {
                ajax: {
                    url: "{{ route('archive.history.documentData') }}",
                    type: 'GET',
                    data: function(d) {
                        return $.extend({}, d, filterData('document'));
                    }
                },
                columns: documentColumns,
                order: [[1, 'desc']]
            }));

            function reload(table) {
                table.ajax.reload();
            }

            $('#routingApply').on('click', function() {
                reload(routingTable);
            });

            $('#routingReset').on('click', function() {
                $('#routingYear, #routingActionUnit').val('');
                $('#routingDateFrom, #routingDateTo, #routingCtrlNo, #routingSubject, #routingSource').val('');
                routingTable.search('').draw();
            });

            $('#documentApply').on('click', function() {
                reload(documentTable);
            });

            $('#documentReset').on('click', function() {
                $('#documentYear').val('');
                $('#documentDateFrom, #documentDateTo, #documentCtrlNo, #documentSubject, #documentSource').val('');
                documentTable.search('').draw();
            });

            $('.archive-filter, .document-filter').on('keypress', function(event) {
                if (event.which === 13) {
                    event.preventDefault();
                    $(this).hasClass('archive-filter') ? reload(routingTable) : reload(documentTable);
                }
            });

            $('a[data-toggle="tab"]').on('shown.bs.tab', function(event) {
                if ($(event.target).attr('href') === '#documentPane') {
                    documentTable.columns.adjust().draw(false);
                } else {
                    routingTable.columns.adjust().draw(false);
                }
            });
        });
    </script>
@endpush
