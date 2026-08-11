@php
    $current_route = request()->route()->getName();
    $user_role = auth()->user()->role;
    use App\Models\Log;
@endphp

<nav class="mt-2">
    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
        <li class="nav-item">
            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="nav-icon fas fa-home"></i>
                <p>Home
                </p>
            </a>
        </li>

        <li class="nav-item">
            @if (auth()->check() &&
                    auth()->user()->id != 1235 &&
                    (auth()->user()->hasRole('Administrator') ||
                        auth()->user()->hasRole('super_user') ||
                        auth()->user()->hasRole('records_officer')))
                <a href="{{ route('viewSlip') }}"
                    class="nav-link {{ request()->routeIs('viewSlip') || request()->routeIs('editDest') || request()->routeIs('editSlip') ? 'active' : '' }}">
                    <i class="nav-icon fas fa-exclamation-circle"></i>
                    <p>Documents for Action
                        @php
                            $user = auth()->user();
                            $userRole = $user->role;
                            $userId = $user->id;

                            if ($userRole === 'super_user') {
                                // Super user: count ALL route_status == 1
                                $userCount = \App\Models\RoutingSlip::where('route_status', 1)->count();
                            } elseif ($userRole === 'records_officer') {
                                // Records officer: count ONLY their created routing slips with route_status == 2
                                $userCount = \App\Models\RoutingSlip::where('route_status', 2)
                                    ->where('user_id', $userId)
                                    ->count();
                            } else {
                                // Other roles: count their own records
                                $userCount = \App\Models\RoutingSlip::where('user_id', $userId)->count();
                            }
                        @endphp
                        <span class="badge badge-info ml-2">{{ $userCount }}</span>
                    </p>
                </a>
            @endif
        </li>

        {{-- @php

            $user = auth()->user();
            $userId = $user->id;
            $userDepartment = $user->department;
            $userFullName = $user->fname . ' ' . $user->lname;
            $userRole = $user->role;

            $query = Log::leftJoin('routing_slip', 'logs.route_id', '=', 'routing_slip.rslip_id')->where(
                'logs.status_update',
                2,
            );

            // If not records officer, apply more detailed matching
            if ($userRole !== 'records_officer') {
                $query->where(function ($q) use ($userDepartment, $userId, $userFullName) {
                    $q->where('logs.new_destination', $userDepartment)
                        ->orWhere('logs.user_id', $userId)
                        ->orWhereRaw('FIND_IN_SET(?, routing_slip.routed_users)', [$userFullName]);
                });
            }

            // Count only unique documents

            $user = auth()->user();
            $userId = $user->id;
            $userDepartment = $user->department;
            $userFullName = $user->fname . ' ' . $user->lname;
            $userRole = $user->role;

            $query = Log::where('status_update', 2)->where(function ($q) {
                // This ensures: only exclude if BOTH new_user AND assigned_to are NOT NULL
                $q->whereNull('new_user')->orWhereNull('assigned_to');
            });

            // Access control for non-records_officer
            if ($userRole !== 'records_officer') {
                $query->where(function ($q) use ($userDepartment, $userId, $userFullName) {
                    $q->where('new_destination', $userDepartment)
                        ->orWhere('new_destination', $userFullName)
                        ->orWhere('user_id', $userId);
                });
            }

            // Now count all matching logs (including duplicates if needed)
            $statusUpdateCount1 = $query->count();

        @endphp --}}

        {{-- @php
            $user = auth()->user();
            $userId = $user->id;
            $userFullName = $user->fname . ' ' . $user->lname;
            $userRole = $user->role;

            $query = Log::leftJoin('routing_slip', 'logs.route_id', '=', 'routing_slip.rslip_id')
                ->where('logs.status_update', 2)
                ->where(function ($q) {
                    // This ensures: only exclude if BOTH new_user AND assigned_to are NOT NULL
                    $q->whereNull('logs.new_user')->orWhereNull('logs.assigned_to');
                });

            if ($userRole === 'records_officer') {
                // ✅ Only records_officer who created the routing slip can see their count
                $query->where('routing_slip.user_id', $userId);
            } else {
                // ✅ Non-records_officer: filter only by fullname or user_id
                $query->where(function ($q) use ($userId, $userFullName) {
                    $q->where('logs.new_destination', $userFullName)->orWhere('logs.user_id', $userId);
                });
            }

            $statusUpdateCount1 = $query->count();
        @endphp

        <li class="nav-item">
            <a href="{{ route('pending') }}" class="nav-link {{ request()->routeIs('pending') ? 'active' : '' }}">
                <i class="nav-icon fas fa-hourglass"></i>
                <p>
                    Pending
                    <span class="badge badge-warning ml-2">
                        {{ $statusUpdateCount1 }}
                    </span>
                </p>
            </a>
        </li> --}}

        {{-- @php
            $user = auth()->user();
            $userId = $user->id;
            $userFullName = $user->fname . ' ' . $user->lname;
            $userRole = $user->role;

            $query = Log::leftJoin('routing_slip', 'logs.route_id', '=', 'routing_slip.rslip_id')
                ->where('logs.status_update', 2)
                ->where(function ($q) {
                    $q->whereNull('logs.new_user')->orWhereNull('logs.assigned_to');
                });

            if ($userRole === 'records_officer') {
                // ✅ Only records_officer who created the routing slip can see their count
                $query->where('routing_slip.user_id', $userId);
            } else {
                // ✅ Non-records_officer: filter only by fullname or user_id
                $query->where(function ($q) use ($userId, $userFullName) {
                    $q->where('logs.new_destination', $userFullName)->orWhere('logs.user_id', $userId);
                });
            }

            // ✅ Count only distinct route_id
            $statusUpdateCount1 = $query->distinct('logs.route_id')->count('logs.route_id');
        @endphp

        <li class="nav-item">
            <a href="{{ route('pending') }}" class="nav-link {{ request()->routeIs('pending') ? 'active' : '' }}">
                <i class="nav-icon fas fa-hourglass"></i>
                <p>
                    Pending
                    <span class="badge badge-warning ml-2">
                        {{ $statusUpdateCount1 }}
                    </span>
                </p>
            </a>
        </li> --}}

        @php
            $user = auth()->user();
            $userId = $user->id;
            $userFullName = $user->fname . ' ' . $user->lname;
            $userRole = $user->role;

            // Get logs with status_update = 2 and null conditions
            $pendingLogs = Log::leftJoin('documents', 'logs.doc_id', '=', 'documents.id')
                ->leftJoin('routing_slip', function ($join) {
                    $join
                        ->on('logs.route_id', '=', 'routing_slip.rslip_id')
                        ->on('logs.user_id', '=', 'routing_slip.user_id');
                })
                ->where('logs.status_update', 2)
                ->where(function ($q) {
                    $q->whereNull('logs.new_user')->orWhereNull('logs.assigned_to');
                });

            // Apply role-based filtering (same as pending page)
            if ($userRole === 'records_officer') {
                $pendingLogs->where('routing_slip.user_id', $userId);
            } else {
                $pendingLogs->where(function ($q) use ($userId, $userFullName) {
                    $q->where('logs.new_destination', $userFullName)->orWhere('logs.user_id', $userId);
                });
            }

            // Get the logs and then apply unique doc_id filter (same as pending page)
            $logs = $pendingLogs->select('logs.*', 'documents.id as doc_id')->orderByDesc('logs.created_at')->get();

            // Filter out null doc_ids and unique by doc_id (exactly like pending page)
            $statusUpdateCount1 = $logs->whereNotNull('doc_id')->unique('doc_id')->count();
        @endphp

        <li class="nav-item">
            <a href="{{ route('pending') }}" class="nav-link {{ request()->routeIs('pending') ? 'active' : '' }}">
                <i class="nav-icon fas fa-hourglass"></i>
                <p>
                    Pending
                    <span class="badge badge-warning ml-2">
                        {{ $statusUpdateCount1 }}
                    </span>
                </p>
            </a>
        </li>

        {{-- <li class="nav-item">
            <a href="{{ route('served') }}" class="nav-link {{ request()->routeIs('served') ? 'active' : '' }}">
                <i class="nav-icon fas fa-check"></i>
                <p>Completed

                    @php
                        $user = auth()->user();
                        $userId = $user->id;
                        $userDepartment = trim($user->department);
                        $userFullName = trim($user->fname . ' ' . $user->lname);
                        $userRole = $user->role;

                        $servedQuery = Log::whereNotNull('new_user');

                        if ($userRole !== 'records_officer') {
                            $servedQuery->where(function ($q) use ($userId, $userDepartment, $userFullName) {
                                $q->where('new_user', $userId)
                                    ->orWhere('user_id', $userId)
                                    ->orWhere('new_destination', $userDepartment)
                                    ->orWhere('new_destination', $userFullName);
                            });
                        }

                        $statusUpdateCount = $servedQuery->distinct('route_id')->count();
                    @endphp

                    <span class="badge badge-success ml-2">{{ $statusUpdateCount }}</span>
                </p>
            </a>
        </li> --}}
        {{-- <li class="nav-item">
            <a href="{{ route('served') }}" class="nav-link {{ request()->routeIs('served') ? 'active' : '' }}">
                <i class="nav-icon fas fa-check"></i>
                <p>Completed

                    @php
                        $user = auth()->user();
                        $userId = $user->id;
                        $userFullName = $user->fname . ' ' . $user->lname;
                        $userRole = $user->role;

                        // Get logs with status_update = 3 (completed/acknowledged)
                        $completedLogs = Log::leftJoin('documents', 'logs.doc_id', '=', 'documents.id')
                            ->leftJoin('routing_slip', function ($join) {
                                $join
                                    ->on('logs.route_id', '=', 'routing_slip.rslip_id')
                                    ->on('logs.user_id', '=', 'routing_slip.user_id');
                            })
                            ->where('logs.status_update', 3) // Changed from 2 to 3 for completed
                            ->whereNotNull('logs.new_user'); // Has been acted upon

                        // Apply role-based filtering (same as pending page)
                        if ($userRole === 'records_officer') {
                            $completedLogs->where('routing_slip.user_id', $userId);
                        } else {
                            $completedLogs->where(function ($q) use ($userId, $userFullName) {
                                $q->where('logs.new_destination', $userFullName)->orWhere('logs.user_id', $userId);
                            });
                        }

                        // Get the logs and then apply unique doc_id filter (same as pending page)
                        $completedLogsList = $completedLogs
                            ->select('logs.*', 'documents.id as doc_id')
                            ->orderByDesc('logs.created_at')
                            ->get();

                        // Filter out null doc_ids and unique by doc_id (exactly like pending page)
                        $statusUpdateCount = $completedLogsList->whereNotNull('doc_id')->unique('doc_id')->count();
                    @endphp

                    <span class="badge badge-success ml-2">{{ $statusUpdateCount }}</span>
                </p>
            </a>
        </li> --}}

        @if (auth()->user()->id != 1235)
            <li class="nav-item">
                <a href="{{ route('served') }}" class="nav-link {{ request()->routeIs('served') ? 'active' : '' }}">
                    <i class="nav-icon fas fa-check"></i>
                    <p>Completed
                        @php
                            $user = auth()->user();
                            $userId = $user->id;
                            $userFullName = trim(
                                $user->fname . ' ' . ($user->mname ? $user->mname . ' ' : '') . $user->lname,
                            );
                            $userRole = $user->role;

                            $completedLogs = Log::leftJoin('documents', 'logs.doc_id', '=', 'documents.id')
                                ->leftJoin('routing_slip', function ($join) {
                                    $join
                                        ->on('logs.route_id', '=', 'routing_slip.rslip_id')
                                        ->on('logs.user_id', '=', 'routing_slip.user_id');
                                })
                                ->where('logs.status_update', 3)
                                ->whereNotNull('logs.new_user');

                            if ($userRole === 'records_officer') {
                                $completedLogs->where(function ($q) use ($userId, $userFullName) {
                                    $q->where('routing_slip.user_id', $userId)
                                        ->orWhere('logs.new_destination', $userFullName)
                                        ->orWhere('logs.user_id', $userId);
                                });
                            } else {
                                $completedLogs->where(function ($q) use ($userId, $userFullName) {
                                    $q->where('logs.new_destination', $userFullName)->orWhere('logs.user_id', $userId);
                                });
                            }

                            $completedLogsList = $completedLogs
                                ->select('logs.*', 'documents.id as doc_id')
                                ->orderByDesc('logs.created_at')
                                ->get();

                            $statusUpdateCount = $completedLogsList->whereNotNull('doc_id')->unique('doc_id')->count();
                        @endphp

                        <span class="badge badge-success ml-2">{{ $statusUpdateCount }}</span>
                    </p>
                </a>
            </li>
        @endif

        @php
            $trackingActive = request()->routeIs('doctrackSlip', 'incoming');
        @endphp

        <li class="nav-item">
            <a href="{{ route('doctrackSlip') }}"
                class="nav-link {{ request()->routeIs('doctrackSlip') ? 'active' : '' }}">
                <i class="nav-icon fas fa-route"></i>
                <p>
                    Tracking Document
                    <span class="right badge badge-primary">
                        {{ $doctrackCount ?? 0 }}
                    </span>
                </p>
            </a>
        </li>
        {{-- @if ($user_role == 'Administrator' || $user_role == 'records_officer') --}}
        {{-- @if ($user_role == 'Administrator')
            <li class="nav-item">
                <a href="{{ route('outgoingDocs') }}"
                    class="nav-link {{ request()->routeIs('outgoingDocs') ? 'active' : '' }}">
                    <i class="fas fa-share-square nav-icon"></i>
                    <p>Outgoing Document</p>
                </a>
            </li>
        @endif --}}

        @if ($user_role == 'Administrator' || $user_role == 'records_officer')
            @php
                $distributionActive = request()->routeIs('distributionList', 'trackingDistributionList');
            @endphp
            <li class="nav-item {{ $distributionActive ? 'menu-open menu-is-opening' : '' }}">
                <a href="#" class="nav-link">
                    <i class="fas fa-list nav-icon"></i>
                    <p>
                        Distribution List
                        <i class="right fas fa-angle-left"></i>
                    </p>
                </a>
                <ul class="nav nav-treeview" style="{{ $distributionActive ? 'display: block;' : 'display: none;' }}">
                    <li class="nav-item">
                        <a href="{{ route('distributionList') }}"
                            class="nav-link {{ request()->routeIs('distributionList') ? 'active' : '' }}">
                            <i class="fas fa-share nav-icon"></i>
                            <p>Routed Distribution List</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('trackingDistributionList') }}"
                            class="nav-link {{ request()->routeIs('trackingDistributionList') ? 'active' : '' }}">
                            <i class="fas fa-map nav-icon"></i>
                            <p>Tracking Distribution List</p>
                        </a>
                    </li>
                </ul>
            </li>
        @endif

        @php
            $showPrintLogbook =
                in_array($user_role, ['Administrator', 'records_officer', 'president']) || auth()->user()->id == 38;
        @endphp

        @if ($showPrintLogbook)
            <li class="nav-item">
                <a href="{{ route('printLogbook') }}"
                    class="nav-link {{ request()->routeIs('printLogbook') ? 'active' : '' }}">
                    <i class="fas fa-print nav-icon"></i>
                    <p>Print Logbook</p>
                </a>
            </li>
        @endif

        @if ($user_role == 'Administrator' || $user_role == 'records_officer')
            <li class="nav-item">
                <a href="{{ route('userView') }}"
                    class="nav-link {{ request()->routeIs('userView') ? 'active' : '' }}">
                    <i class="fas fa-users-cog nav-icon"></i>
                    <p>User Management
                        @php
                            $userCount = \App\Models\User::count();
                        @endphp
                        <span class="badge badge-danger ml-2">{{ $userCount ?? 0 }}</span>
                    </p>
                </a>
            </li>
        @endif
        {{-- @if (auth()->check() && auth()->user()->role !== 'Administrator')
        <li class="nav-item">
            <a href="{{ route('userPassword', ['id' => Auth::user()->id]) }}" class="nav-link {{ request()->routeIs('userPassword') ? 'active' : '' }}">
                <i class="fas fa-user-edit nav-icon"></i>
                <p>Edit Account</p>
            </a>
        </li>
        @endif --}}
        {{-- <a href="{{ route('archived') }}" class="nav-link {{ request()->routeIs('archived') ? 'active' : '' }}"> --}}


        {{-- Office List --}}
        @if (($user_role == 'Administrator' || $user_role == 'records_officer') && auth()->user()->id != 1235)
            <li class="nav-item">
                <a href="{{ route('offices') }}" class="nav-link {{ request()->routeIs('offices') ? 'active' : '' }}">
                    <i class="fas fa-building nav-icon"></i>
                    <p>Office List</p>
                </a>
            </li>
        @endif

        {{-- Group List --}}
        @if (($user_role == 'Administrator' || $user_role == 'records_officer') && auth()->user()->id != 1235)
            <li class="nav-item">
                <a href="{{ route('userGroups') }}"
                    class="nav-link {{ request()->routeIs('userGroups') ? 'active' : '' }}">
                    <i class="fas fa-users nav-icon"></i>
                    <p>Group List</p>
                </a>
            </li>
        @endif

        @if ($user_role == 'Administrator')
            <li class="nav-item">
                <a href="#" class="nav-link">

                    <i class="nav-icon fas fa-archive"></i>
                    <p>
                        Routed Files Archive
                    </p>
                </a>
            </li>
        @endif

        <li
            class="nav-item {{ request()->routeIs('viewLogs', 'viewLogs-Tracking') ? 'menu-is-opening menu-open' : '' }}">
            <a href="#" class="nav-link">
                <i class="fa fa-history nav-icon"></i>
                <p>
                    Logs
                    <i class="right fas fa-angle-left"></i>
                </p>
            </a>
            <ul class="nav nav-treeview"
                style="{{ request()->routeIs('viewLogs', 'viewLogs-Tracking') ? 'display: block;' : 'display: none;' }}">
                <li class="nav-item">
                    <a href="{{ route('viewLogs-Tracking') }}"
                        class="nav-link {{ request()->routeIs('viewLogs-Tracking') ? 'active' : '' }}">
                        <i class="fas fa-file-alt nav-icon text-primary"></i>
                        <p>Tracking Logs</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('viewLogs') }}"
                        class="nav-link {{ request()->routeIs('viewLogs') ? 'active' : '' }}">
                        <i class="fas fa-route nav-icon text-success"></i>
                        <p>Route Logs</p>
                    </a>
                </li>
            </ul>
        </li>
    </ul>
</nav>
