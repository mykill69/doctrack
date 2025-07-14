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
                    (auth()->user()->hasRole('Administrator') ||
                        auth()->user()->hasRole('super_user') ||
                        auth()->user()->hasRole('records_officer')))
                <a href="{{ route('viewSlip') }}"
                    class="nav-link {{ request()->routeIs('viewSlip') || request()->routeIs('editDest') || request()->routeIs('editSlip') ? 'active' : '' }}">
                    <i class="nav-icon fas fa-receipt"></i>
                    <p>Routing Slip
                        @php
                            $user = auth()->user();
                            $userRole = $user->role;

                            $userCount =
                                $userRole === 'super_user'
                                    ? \App\Models\RoutingSlip::where('route_status', 1)->count()
                                    : \App\Models\RoutingSlip::where('route_status', 2)->count();
                        @endphp
                        <span class="badge badge-info ml-2">{{ $userCount }}</span>
                    </p>
                </a>
            @endif
        </li>

        @php

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


        <li class="nav-item">
            <a href="{{ route('served') }}" class="nav-link {{ request()->routeIs('served') ? 'active' : '' }}">
                <i class="nav-icon fas fa-check"></i>
                <p>Served

                    @php

                        $user = auth()->user();
                        $userDepartment = $user->department;
                        $userId = $user->id;
                        $userFullName = $user->fname . ' ' . $user->lname;
                        $userRole = $user->role;

                        $servedQuery = Log::where('status_update', 3)
                            ->whereNotNull('new_user')
                            ->when(
                                $userRole === 'records_officer',
                                function ($query) {
                                    return $query; // No extra filter
                                },
                                function ($query) use ($userId, $userDepartment, $userFullName) {
                                    return $query->where(function ($subQuery) use (
                                        $userId,
                                        $userDepartment,
                                        $userFullName,
                                    ) {
                                        $subQuery
                                            ->where('new_user', $userId)
                                            ->orWhere('user_id', $userId)
                                            ->orWhere('new_destination', $userDepartment)
                                            ->orWhere('new_destination', $userFullName);
                                    });
                                },
                            );

                        $statusUpdateCount = $servedQuery->distinct('route_id')->count();
                    @endphp

                    <span class="badge badge-success ml-2">{{ $statusUpdateCount }}</span>
                </p>
            </a>
        </li>

        @php
            $trackingActive = request()->routeIs('doctrackSlip', 'incoming');
        @endphp

        <li class="nav-item {{ $trackingActive ? 'menu-open menu-is-opening' : '' }}">
            <a href="#" class="nav-link">
                <i class="nav-icon fas fa-route"></i>
                <p>
                    Tracking Documents
                    <i class="right fas fa-angle-left"></i>
                </p>
            </a>
            <ul class="nav nav-treeview" style="{{ $trackingActive ? 'display: block;' : 'display: none;' }}">
                <li class="nav-item">
                    <a href="{{ route('doctrackSlip') }}"
                        class="nav-link {{ request()->routeIs('doctrackSlip') ? 'active' : '' }}">
                        <i class="fas fa-map-marker-alt nav-icon"></i>
                        <p>
                            Tracking Code List
                            <span class="right badge badge-primary">
                                {{ $doctrackCount ?? 0 }}
                            </span>
                        </p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('incoming') }}"
                        class="nav-link {{ request()->routeIs('incoming') ? 'active' : '' }}">
                        <i class="fa fa-search nav-icon"></i>
                        <p>Search Tracking Code</p>
                    </a>
                </li>
            </ul>
        </li>


        <li
            class="nav-item {{ request()->routeIs('viewLogs', 'viewLogsTracking') ? 'menu-is-opening menu-open' : '' }}">
            <a href="#" class="nav-link">
                <i class="fa fa-history nav-icon"></i>
                <p>
                    Logs
                    <i class="right fas fa-angle-left"></i>
                </p>
            </a>
            <ul class="nav nav-treeview"
                style="{{ request()->routeIs('viewLogs', 'viewLogsTracking') ? 'display: block;' : 'display: none;' }}">
                <li class="nav-item">
                    <a href="{{ route('viewLogsTracking') }}"
                        class="nav-link {{ request()->routeIs('viewLogsTracking') ? 'active' : '' }}">
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




        @if ($user_role == 'Administrator' || $user_role == 'records_officer')
            <li class="nav-item">
                <a href="{{ route('userView') }}"
                    class="nav-link {{ request()->routeIs('userView') ? 'active' : '' }}">
                    <i class="fas fa-users nav-icon"></i>
                    <p>Users
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
    </ul>
</nav>
