@extends('layouts.main')
@section('body')
    <!-- Include CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style type="text/css">
        .no-left-radius {
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
        }

        .disabled-icon {
            color: lightgrey;
            /* Default color for disabled */
            pointer-events: none;
            /* Prevent any interaction */
            opacity: 0.2;
            /* Dim the icon to indicate it's disabled */
            transition: opacity 0.3s;
            /* Smooth transition for hover */
        }

        .disabled-icon:hover {
            opacity: 0.4;
            /* Change opacity on hover to indicate it's disabled */
            cursor: not-allowed;
            /* Change cursor to indicate it's not clickable */
            /* Optional: Add more styles for hover, like a shadow */
            box-shadow: 0 0 5px rgba(0, 0, 0, 0.3);
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
                                                    'served' => 'Completed',
                                                    'enroute' => 'Pending',
                                                ];

                                                // Reorder for super_user
                                                if ($role === 'super_user') {
                                                    $tabs = [
                                                        'routed1' => 'Routed to President',
                                                        'routed2' => 'Routed back to Records',
                                                        'served' => 'Completed',
                                                        'enroute' => 'Pending',
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
                                        /* Bootstrap bg-warning */
                                        color: #212529 !important;
                                        /* Bootstrap text-dark */
                                        font-weight: bold;
                                    }
                                </style>

                                <div class="card-body">
                                    <div class="tab-content">

                                        @php
                                            function determineTab($slip)
                                            {
                                                $routeId = $slip->rslip_id;
                                                $logStatusUpdates = \App\Models\Log::where('route_id', $routeId)->pluck(
                                                    'status_update',
                                                );
                                                $allServed = $logStatusUpdates->every(fn($status) => $status == 3);

                                                if ($slip->route_status == 2) {
                                                    return 'routed2';
                                                }
                                                if ($slip->route_status == 1) {
                                                    return 'routed1';
                                                }
                                                if ($slip->route_status == 3 && $allServed) {
                                                    return 'served';
                                                }
                                                return 'enroute';
                                            }
                                        @endphp

                                        @if ($role !== 'staff')
                                            @foreach ($tabs as $tabId => $label)
                                                <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}"
                                                    id="{{ $tabId }}">
                                                    <div class="table-responsive mt-3">
                                                        <table class="table table-bordered table-hover"
                                                            style="font-size: 0.8rem;">
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
                                                                        $logStatusUpdates = \App\Models\Log::where(
                                                                            'route_id',
                                                                            $routeId,
                                                                        )->pluck('status_update');
                                                                        $allServed = $logStatusUpdates->every(
                                                                            fn($status) => $status == 3,
                                                                        );
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
                                                                    @endphp

                                                                    @if ($currentTab === $tabId)
                                                                        {{-- Your <tr> block unchanged --}}
                                                                        <tr>
                                                                            <td>
                                                                                <a href="{{ route('slipForm', $slip->rslip_id) }}"
                                                                                    target="_blank" style="color: #007bff;">
                                                                                    @if (auth()->user()->role === 'super_user' || auth()->user()->role === 'Administrator')
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
                                                                                    target="_blank"
                                                                                    title="{{ $slip->document }}"
                                                                                    style="color: #007bff;">
                                                                                    {{ \Illuminate\Support\Str::limit($slip->document, 22) }}
                                                                                </a>
                                                                            </td>
                                                                            <td>{{ $slip->trans_remarks }}</td>
                                                                            <td>{{ $slip->other_remarks }}</td>
                                                                            <td>
                                                                                @php
                                                                                    $destinationUser = $users->firstWhere(
                                                                                        'id',
                                                                                        $slip->r_destination,
                                                                                    );
                                                                                    $assignedUser = $users->firstWhere(
                                                                                        'id',
                                                                                        $slip->assigned_to,
                                                                                    );
                                                                                @endphp

                                                                                @if ($destinationUser)
                                                                                    <strong
                                                                                        class="text-danger">{{ ucwords(strtolower($destinationUser->fname)) }}
                                                                                        {{ ucwords(strtolower($destinationUser->lname)) }}</strong>
                                                                                @else
                                                                                    <strong
                                                                                        class="text-danger">{{ $slip->r_destination }}</strong>
                                                                                @endif

                                                                                @if ($assignedUser)
                                                                                    , was re-assigned to <strong
                                                                                        class="text-danger">{{ $assignedUser->fname }}
                                                                                        {{ $assignedUser->lname }}</strong>
                                                                                @endif
                                                                            </td>
                                                                            <td>
                                                                                @switch($slip->route_status)
                                                                                    @case(1)
                                                                                        <p class="badge badge-warning"
                                                                                            style="font-size:9px;">Routed<br>to
                                                                                            President</p>
                                                                                    @break

                                                                                    @case(2)
                                                                                        <p class="badge badge-info"
                                                                                            style="font-size:9px;">Routed back<br>to
                                                                                            Records Office</p>
                                                                                    @break

                                                                                    @case(3)
                                                                                        @if ($allServed || $logStatusMatches)
                                                                                            <p class="badge badge-success text-center"
                                                                                                style="font-size:9px;">Served!</p>
                                                                                        @else
                                                                                            <p class="badge badge-danger"
                                                                                                style="font-size:9px;">En route</p>
                                                                                        @endif
                                                                                    @break

                                                                                    @default
                                                                                        <p>Unknown Status</p>
                                                                                @endswitch
                                                                            </td>
                                                                            <td>
                                                                                @if (!empty($slip->pres_dept))
                                                                                    {{ $slip->pres_dept }} /
                                                                                    {{ $slip->updated_at->format('F j, Y') }}
                                                                                @else
                                                                                    {{ $slip->pres_dept }}
                                                                                @endif
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

                                                                                    @if ($isRecordsOfficer && $slip->route_status == 2)
                                                                                        @if ($slip->assigned_to != null)
                                                                                            <a href="{{ route('editAssign', $slip->id) }}"
                                                                                                class="btn btn-primary"
                                                                                                style="text-decoration: none; color: white;">
                                                                                                <i class="fas fa-plus"></i>
                                                                                            </a>
                                                                                        @else
                                                                                            <a href="{{ route('editDest', $slip->id) }}"
                                                                                                class="btn btn-primary"
                                                                                                style="text-decoration: none; color: white;">
                                                                                                <i class="fas fa-plus"></i>
                                                                                            </a>
                                                                                        @endif
                                                                                    @elseif($isSuperUser && $slip->route_status == 1)
                                                                                        <a href="{{ route('editSlip', $slip->id) }}"
                                                                                            class="btn btn-primary"
                                                                                            style="text-decoration: none; color: white;">
                                                                                            <i class="fas fa-pen"></i>
                                                                                        </a>
                                                                                    @elseif($isSuperUser && $slip->route_status == 3)
                                                                                        <button class="btn btn-secondary"
                                                                                            disabled>
                                                                                            <i class="fas fa-pen"></i>
                                                                                        </button>
                                                                                    @elseif($slip->route_status == 3)
                                                                                        @if ($existsInDocuments)
                                                                                            <button
                                                                                                class="btn btn-secondary"
                                                                                                disabled>
                                                                                                <i class="fas fa-plus"></i>
                                                                                            </button>
                                                                                        @else
                                                                                            <a href="{{ route('editDest', $slip->id) }}"
                                                                                                class="btn btn-primary"
                                                                                                style="text-decoration: none; color: white;">
                                                                                                <i class="fas fa-plus"></i>
                                                                                            </a>
                                                                                        @endif
                                                                                    @elseif($slip->route_status == 2 && $logStatusMatches)
                                                                                        <button class="btn btn-secondary"
                                                                                            disabled>
                                                                                            <i class="fas fa-plus"></i>
                                                                                        </button>
                                                                                    @else
                                                                                        <button class="btn btn-secondary"
                                                                                            disabled>
                                                                                            <i class="fas fa-pen"></i>
                                                                                        </button>
                                                                                    @endif

                                                                                    <form
                                                                                        action="{{ route('routingSlip.destroy', $slip->id) }}"
                                                                                        method="POST"
                                                                                        onsubmit="return confirm('Are you sure you want to delete this routing slip?');">
                                                                                        @csrf
                                                                                        @method('DELETE')
                                                                                        <button type="submit"
                                                                                            class="btn btn-danger no-left-radius"
                                                                                            @if (
                                                                                                ($isRecordsOfficer && !empty($slip->trans_remarks) && !empty($slip->r_destination)) ||
                                                                                                    ($isSuperUser && !empty($slip->trans_remarks) && !empty($slip->r_destination))) disabled @endif>
                                                                                            <i class="fas fa-trash"></i>
                                                                                        </button>
                                                                                    </form>
                                                                                    @if ($slip->route_status == 3 && !$allServed)
                                                                                        @if (auth()->user()->role === 'records_officer' || auth()->user()->role === 'Administrator')
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
                                                                                                    style="text-decoration: none; color: white;"
                                                                                                    title="Recall">
                                                                                                    <i
                                                                                                        class="fas fa-undo"></i>
                                                                                                </a>
                                                                                            @endif
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

    @include('modal.addRoutslip')
    @include('modal.addDestination')
    {{-- @include('modal.pdfRoute') --}}
@endsection
