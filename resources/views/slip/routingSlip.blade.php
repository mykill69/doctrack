@extends('layouts.main')
@section('body')
    <!-- Include CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Include DataTables CSS and JS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap4.min.css">
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>

    <style type="text/css">
        .no-left-radius {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
        }

        .disabled-icon {
            color: lightgrey;
            pointer-events: none;
            opacity: 0.2;
            transition: opacity 0.3s;
        }

        .disabled-icon:hover {
            opacity: 0.4;
            cursor: not-allowed;
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.3);
        }

        .dataTables_wrapper .dataTables_filter {
            margin-bottom: 15px;
        }

        .dataTables_wrapper .dataTables_filter input {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 6px 12px;
            margin-left: 8px;
        }

        .dataTables_wrapper .dataTables_length select {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 4px 8px;
        }

        .search-container {
            margin-bottom: 15px;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 10px;
        }

        .search-container label {
            margin-bottom: 0;
            font-weight: 500;
        }

        .search-container input {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 6px 12px;
            width: 250px;
        }

        .dataTables_wrapper .dataTables_paginate .paginate_button {
            padding: 0.25rem 0.75rem;
        }

        table.dataTable tbody tr:hover {
            background-color: #f5f5f5;
        }
    </style>

    <div class="content-wrapper">
        <div class="content" style="padding-top: 1%;">
            <div class="container-fluid">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h3 class="card-title">List of Routing Slips</h3>
                        </div>
                        <div class="card-body">
                            <div class="card card-success card-outline">

                                <div class="card-header p-2">
                                    @php
                                        $role = auth()->user()->role;
                                    @endphp

                                    @if ($role !== 'staff')
                                        <ul class="nav nav-tabs" id="routingTabs" role="tablist">
                                            @php
                                                $tabs = [
                                                    'routed2' => 'Routed back to Records',
                                                    'routed1' => 'Routed to President',
                                                    'enroute' => 'Pending',
                                                ];

                                                if ($role === 'super_user') {
                                                    $tabs = [
                                                        'routed1' => 'Routed to President',
                                                        'routed2' => 'Routed back to Records',
                                                        // 'enroute' => 'Pending',
                                                    ];
                                                }
                                            @endphp

                                            @foreach ($tabs as $tabId => $label)
                                                <li class="nav-item">
                                                    <a class="nav-link {{ $loop->first ? 'active' : '' }}"
                                                        href="#{{ $tabId }}" data-toggle="tab">
                                                        {{ $label }}
                                                    </a>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>

                                <style>
                                    .nav-tabs .nav-link.active {
                                        background-color: #ffc107 !important;
                                        color: #212529 !important;
                                        font-weight: bold;
                                    }
                                </style>

                                <div class="card-body">
                                    <div class="tab-content">

                                        {{-- determineTab WITHOUT status_update == 3 --}}
                                        @php
                                            function determineTab($slip)
                                            {
                                                if ($slip->route_status == 2) {
                                                    return 'routed2';
                                                }

                                                if ($slip->route_status == 1) {
                                                    return 'routed1';
                                                }

                                                return 'enroute';
                                            }
                                        @endphp

                                        @if ($role !== 'staff')
                                            @foreach ($tabs as $tabId => $label)
                                                <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}"
                                                    id="{{ $tabId }}">

                                                    <div class="table-responsive mt-3">
                                                        <table class="table table-bordered table-hover datatable-table"
                                                            style="font-size: 0.8rem; width: 100%;"
                                                            id="table-{{ $tabId }}">
                                                            <thead>
                                                                <tr>
                                                                    <th>CTRL #</th>
                                                                    <th>DATE RECEIVED</th>
                                                                    <th>SOURCE</th>
                                                                    <th>SUBJECT MATTER</th>
                                                                    <th>FILE NAME</th>
                                                                    <th>TRANSACTION REMARKS</th>
                                                                    <th>OTHER REMARKS</th>
                                                                    <th>ACTION TAKEN</th>
                                                                    <th>STATUS</th>
                                                                    <th>RECEIVED BY/DATE</th>
                                                                    <th>ACTION</th>
                                                                </tr>
                                                            </thead>

                                                            <tbody>
                                                                @foreach ($routingSlips as $slip)
                                                                    @php
                                                                        $routeId = $slip->rslip_id;

                                                                        $logStatusMatches = \App\Models\Log::where(
                                                                            'route_id',
                                                                            $routeId,
                                                                        )
                                                                            ->where(
                                                                                'status_update',
                                                                                $slip->route_status,
                                                                            )
                                                                            ->exists();

                                                                        $existsInDocuments = \App\Models\Document::where(
                                                                            'route_id',
                                                                            $routeId,
                                                                        )->exists();

                                                                        $currentTab = determineTab($slip);

                                                                        $isRecordsOfficer =
                                                                            auth()->user()->role === 'records_officer';
                                                                        $isSuperUser =
                                                                            auth()->user()->role === 'super_user';
                                                                    @endphp

                                                                    @if ($currentTab === $tabId)
                                                                        <tr>
                                                                            @php
                                                                                $routingSlipId = \App\Models\RoutingSlip::where(
                                                                                    'rslip_id',
                                                                                    $slip->rslip_id,
                                                                                )
                                                                                    ->orderBy('id', 'desc')
                                                                                    ->value('id');
                                                                            @endphp

                                                                            <td>
                                                                                <a href="{{ route('slipForm', ['id' => $slip->rslip_id]) . '?routing_slip_id=' . $routingSlipId }}"
                                                                                    target="_blank" style="color:#007bff;">
                                                                                    @if ($isSuperUser || auth()->user()->role === 'Administrator')
                                                                                        {{ $slip->op_ctrl }}
                                                                                    @else
                                                                                        {{ $slip->rslip_id }}
                                                                                    @endif
                                                                                </a>
                                                                            </td>

                                                                            <td>{{ \Carbon\Carbon::parse($slip->date_received)->format('F j, Y') }}
                                                                            </td>
                                                                            <td>{{ $slip->source }}</td>
                                                                            <td>{{ $slip->subject }}</td>

                                                                            <td>
                                                                                <a href="{{ route('viewPdfslip', $slip->id) }}"
                                                                                    target="_blank" style="color:#007bff;">
                                                                                    {{ \Illuminate\Support\Str::limit($slip->document, 22) }}
                                                                                </a>
                                                                            </td>

                                                                            <td>{{ $slip->trans_remarks }}</td>
                                                                            <td>{{ $slip->other_remarks }}</td>

                                                                            <td>
                                                                                <strong
                                                                                    class="text-danger">{{ $slip->r_destination }}</strong>
                                                                                @if ($slip->assigned_to != null)
                                                                                    , was re-assigned to <strong
                                                                                        class="text-danger">{{ $slip->assigned_to }}</strong>
                                                                                @endif
                                                                            </td>

                                                                            <td>
                                                                                @switch($slip->route_status)
                                                                                    @case(1)
                                                                                        <span class="badge badge-warning"
                                                                                            style="font-size:9px;">
                                                                                            Routed to President
                                                                                        </span>
                                                                                    @break

                                                                                    @case(2)
                                                                                        <span class="badge badge-info"
                                                                                            style="font-size:9px;">
                                                                                            Routed back to Records
                                                                                        </span>
                                                                                    @break

                                                                                    @case(3)
                                                                                        <span class="badge badge-success"
                                                                                            style="font-size:9px;">
                                                                                            Served!
                                                                                        </span>
                                                                                    @break
                                                                                @endswitch
                                                                            </td>

                                                                            <td>
                                                                                {{ $slip->pres_dept }} /
                                                                                {{ $slip->updated_at->format('F j, Y') }}
                                                                            </td>

                                                                            <td>
                                                                                <div class="btn-group btn-group-sm">
                                                                                    @php
                                                                                        $isRecordsOfficer =
                                                                                            auth()->user()->role ===
                                                                                            'records_officer';
                                                                                        $isSuperUser =
                                                                                            auth()->user()->role ===
                                                                                            'super_user';
                                                                                    @endphp

                                                                                    {{-- Records Officer: Routed back to Records (route_status == 2) --}}
                                                                                    @if ($isRecordsOfficer && $slip->route_status == 2)
                                                                                        @if ($slip->assigned_to != null)
                                                                                            <a href="{{ route('editAssign', $slip->id) }}"
                                                                                                class="btn btn-info"
                                                                                                style="text-decoration: none; color: white;">
                                                                                                <i class="fas fa-plus"></i>
                                                                                            </a>
                                                                                        @else
                                                                                            <a href="{{ route('editDest', $slip->id) }}"
                                                                                                class="btn btn-info"
                                                                                                style="text-decoration: none; color: white;">
                                                                                                <i class="fas fa-plus"></i>
                                                                                            </a>
                                                                                        @endif

                                                                                        {{-- Routed to President (route_status == 1) --}}
                                                                                        @elseif
                                                                                        ($slip->route_status == 1)
                                                                                        @if ($isSuperUser)
                                                                                            {{-- Super user: Full edit --}}
                                                                                            <a href="{{ route('editSlip', $slip->id) }}"
                                                                                                class="btn btn-info"
                                                                                                style="text-decoration: none; color: white;"
                                                                                                title="Edit Routing Slip">
                                                                                                <i class="fas fa-pen"></i>
                                                                                            </a>
                                                                                            @elseif
                                                                                            ($isRecordsOfficer)
                                                                                            {{-- Records officer: Edit subject only --}}
                                                                                            <a href="{{ route('editSubject', $slip->id) }}"
                                                                                                class="btn btn-warning"
                                                                                                style="text-decoration: none; color: white;"
                                                                                                title="Edit Subject Only">
                                                                                                <i class="fas fa-edit"></i>
                                                                                            </a>
                                                                                        @else
                                                                                            <button
                                                                                                class="btn btn-secondary"
                                                                                                disabled>
                                                                                                <i class="fas fa-pen"></i>
                                                                                            </button>
                                                                                        @endif

                                                                                        {{-- Served (route_status == 3) - Super user --}}
                                                                                        @elseif
                                                                                        ($isSuperUser && $slip->route_status == 3)
                                                                                        <button class="btn btn-secondary"
                                                                                            disabled>
                                                                                            <i class="fas fa-pen"></i>
                                                                                        </button>

                                                                                        {{-- Served (route_status == 3) - Others --}}
                                                                                        @elseif
                                                                                        ($slip->route_status == 3)
                                                                                        @if ($existsInDocuments)
                                                                                            <button
                                                                                                class="btn btn-secondary"
                                                                                                disabled>
                                                                                                <i class="fas fa-plus"></i>
                                                                                            </button>
                                                                                        @else
                                                                                            <a href="{{ route('editDest', $slip->id) }}"
                                                                                                class="btn btn-info"
                                                                                                style="text-decoration: none; color: white;">
                                                                                                <i class="fas fa-plus"></i>
                                                                                            </a>
                                                                                        @endif

                                                                                        {{-- Routed back to Records but log status matches --}}
                                                                                        @elseif
                                                                                        ($slip->route_status == 2 && $logStatusMatches)
                                                                                        <button class="btn btn-secondary"
                                                                                            disabled>
                                                                                            <i class="fas fa-plus"></i>
                                                                                        </button>

                                                                                        {{-- Default: Disabled --}}
                                                                                    @else
                                                                                        <button class="btn btn-secondary"
                                                                                            disabled>
                                                                                            <i class="fas fa-pen"></i>
                                                                                        </button>
                                                                                    @endif

                                                                                    {{-- Delete button: Records officer only --}}
                                                                                    @if (auth()->user()->role === 'records_officer')
                                                                                        <form
                                                                                            action="{{ route('routingSlip.destroy', $slip->id) }}"
                                                                                            method="POST"
                                                                                            onsubmit="return confirm('Are you sure you want to delete this routing slip?');">
                                                                                            @csrf
                                                                                            @method('DELETE')
                                                                                            <button type="submit"
                                                                                                class="btn btn-danger no-left-radius"
                                                                                                title="Delete Routing Slip">
                                                                                                <i class="fas fa-trash"></i>
                                                                                            </button>
                                                                                        </form>
                                                                                    @endif

                                                                                    {{-- Recall button: Served slips with status 2 log --}}
                                                                                    @if ($slip->route_status == 3 && ($isRecordsOfficer || auth()->user()->role === 'Administrator'))
                                                                                        @php
                                                                                            $hasStatus2Log = \App\Models\Log::where(
                                                                                                'route_id',
                                                                                                $slip->rslip_id,
                                                                                            )
                                                                                                ->where(
                                                                                                    'status_update',
                                                                                                    2,
                                                                                                )
                                                                                                ->exists();
                                                                                        @endphp

                                                                                        @if ($hasStatus2Log)
                                                                                            <a href="{{ route('recallSlip', $slip->id) }}"
                                                                                                class="btn btn-primary no-left-radius"
                                                                                                title="Recall">
                                                                                                <i class="fas fa-undo"></i>
                                                                                            </a>
                                                                                        @endif
                                                                                    @endif
                                                                                </div>
                                                                            </td>
                                                                        </tr>
                                                                    @endif
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            @endforeach
                                        @endif

                                    </div>
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
            // Initialize DataTables for each tab
            @if ($role !== 'staff')
                @foreach ($tabs as $tabId => $label)
                    $('#table-{{ $tabId }}').DataTable({
                        "paging": true,
                        "pageLength": 10,
                        "lengthMenu": [
                            [10, 25, 50, 100, -1],
                            [10, 25, 50, 100, "All"]
                        ],
                        "searching": true,
                        "ordering": true,
                        "info": true,
                        "responsive": true,
                        "autoWidth": false,
                        "language": {
                            "search": "Search:",
                            "lengthMenu": "Show _MENU_ entries",
                            "info": "Showing _START_ to _END_ of _TOTAL_ entries",
                            "infoEmpty": "Showing 0 to 0 of 0 entries",
                            "infoFiltered": "(filtered from _MAX_ total entries)",
                            "zeroRecords": "No matching records found",
                            "paginate": {
                                "first": "First",
                                "last": "Last",
                                "next": "→",
                                "previous": "←"
                            }
                        },
                        "order": [
                            [1, 'desc']
                        ] // Sort by date received by default (column index 1)
                    });
                @endforeach
            @endif

            // Reinitialize DataTables when tab is shown (fixes responsiveness issues)
            $('a[data-toggle="tab"]').on('shown.bs.tab', function(e) {
                var target = $(e.target).attr("href");
                var tableId = target.replace('#', '');
                var table = $('#table-' + tableId).DataTable();
                if (table) {
                    table.columns.adjust().responsive.recalc();
                }
            });
        });
    </script>

    @include('modal.addRoutslip')
    @include('modal.addDestination')
@endsection
