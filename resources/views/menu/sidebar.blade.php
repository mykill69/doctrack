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
                <p>Home</p>
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
                            if ($user->role === 'super_user') {
                                $userCount = \App\Models\RoutingSlip::where('route_status', 1)->count();
                            } elseif ($user->role === 'records_officer') {
                                $userCount = \App\Models\RoutingSlip::where('route_status', 2)
                                    ->where('user_id', $user->id)->count();
                            } else {
                                $userCount = \App\Models\RoutingSlip::where('user_id', $user->id)->count();
                            }
                        @endphp
                        <span class="badge badge-info ml-2">{{ $userCount }}</span>
                    </p>
                </a>
            @endif
        </li>

        {{-- Pending - OPTIMIZED: COUNT in database instead of loading all records --}}
        @php
            $user = auth()->user();
            $userId = $user->id;
            $userFullName = $user->fname . ' ' . $user->lname;
            $userRole = $user->role;

            $pendingQuery = Log::leftJoin('documents', 'logs.doc_id', '=', 'documents.id')
                ->leftJoin('routing_slip', function ($join) {
                    $join->on('logs.route_id', '=', 'routing_slip.rslip_id')
                        ->on('logs.user_id', '=', 'routing_slip.user_id');
                })
                ->where('logs.status_update', 2)
                ->where(function ($q) {
                    $q->whereNull('logs.new_user')->orWhereNull('logs.assigned_to');
                })
                ->whereNotNull('documents.id');

            if ($userRole === 'records_officer') {
                $pendingQuery->where('routing_slip.user_id', $userId);
            } else {
                $pendingQuery->where(function ($q) use ($userId, $userFullName) {
                    $q->where('logs.new_destination', $userFullName)
                      ->orWhere('logs.user_id', $userId);
                });
            }

            $statusUpdateCount1 = $pendingQuery->distinct('documents.id')->count('documents.id');
        @endphp

        <li class="nav-item">
            <a href="{{ route('pending') }}" class="nav-link {{ request()->routeIs('pending') ? 'active' : '' }}">
                <i class="nav-icon fas fa-hourglass"></i>
                <p>Pending
                    <span class="badge badge-warning ml-2">{{ $statusUpdateCount1 }}</span>
                </p>
            </a>
        </li>

        {{-- Completed - OPTIMIZED: COUNT in database instead of loading all records --}}
        @if (auth()->user()->id != 1235)
            @php
                $user = auth()->user();
                $userId = $user->id;
                $userFullName = trim($user->fname . ' ' . ($user->mname ? $user->mname . ' ' : '') . $user->lname);
                $userRole = $user->role;

                $completedQuery = Log::leftJoin('documents', 'logs.doc_id', '=', 'documents.id')
                    ->leftJoin('routing_slip', function ($join) {
                        $join->on('logs.route_id', '=', 'routing_slip.rslip_id')
                            ->on('logs.user_id', '=', 'routing_slip.user_id');
                    })
                    ->where('logs.status_update', 3)
                    ->whereNotNull('logs.new_user')
                    ->whereNotNull('documents.id');

                if ($userRole === 'records_officer') {
                    $completedQuery->where(function ($q) use ($userId, $userFullName) {
                        $q->where('routing_slip.user_id', $userId)
                          ->orWhere('logs.new_destination', $userFullName)
                          ->orWhere('logs.user_id', $userId);
                    });
                } else {
                    $completedQuery->where(function ($q) use ($userId, $userFullName) {
                        $q->where('logs.new_destination', $userFullName)
                          ->orWhere('logs.user_id', $userId);
                    });
                }

                $statusUpdateCount = $completedQuery->distinct('documents.id')->count('documents.id');
            @endphp

            <li class="nav-item">
                <a href="{{ route('served') }}" class="nav-link {{ request()->routeIs('served') ? 'active' : '' }}">
                    <i class="nav-icon fas fa-check"></i>
                    <p>Completed
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

        @if ($user_role == 'Administrator' || $user_role == 'records_officer')
            @php
                $distributionActive = request()->routeIs('distributionList', 'trackingDistributionList');
            @endphp
            <li class="nav-item {{ $distributionActive ? 'menu-open menu-is-opening' : '' }}">
                <a href="#" class="nav-link">
                    <i class="fas fa-list nav-icon"></i>
                    <p>Distribution List<i class="right fas fa-angle-left"></i></p>
                </a>
                <ul class="nav nav-treeview" style="{{ $distributionActive ? 'display: block;' : 'display: none;' }}">
                    <li class="nav-item">
                        <a href="{{ route('distributionList') }}" class="nav-link {{ request()->routeIs('distributionList') ? 'active' : '' }}">
                            <i class="fas fa-share nav-icon"></i>
                            <p>Routed Distribution List</p>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="{{ route('trackingDistributionList') }}" class="nav-link {{ request()->routeIs('trackingDistributionList') ? 'active' : '' }}">
                            <i class="fas fa-map nav-icon"></i>
                            <p>Tracking Distribution List</p>
                        </a>
                    </li>
                </ul>
            </li>
        @endif

        @php
            $showPrintLogbook = in_array($user_role, ['Administrator', 'records_officer', 'president']) || auth()->user()->id == 38;
        @endphp

        @if ($showPrintLogbook)
            <li class="nav-item">
                <a href="{{ route('printLogbook') }}" class="nav-link {{ request()->routeIs('printLogbook') ? 'active' : '' }}">
                    <i class="fas fa-print nav-icon"></i>
                    <p>Print Logbook</p>
                </a>
            </li>
        @endif

        @if ($user_role == 'Administrator' || $user_role == 'records_officer')
            <li class="nav-item">
                <a href="{{ route('userView') }}" class="nav-link {{ request()->routeIs('userView') ? 'active' : '' }}">
                    <i class="fas fa-users-cog nav-icon"></i>
                    <p>User Management
                        @php $userCount = \App\Models\User::count(); @endphp
                        <span class="badge badge-danger ml-2">{{ $userCount ?? 0 }}</span>
                    </p>
                </a>
            </li>
        @endif

        @if (($user_role == 'Administrator' || $user_role == 'records_officer') && auth()->user()->id != 1235)
            <li class="nav-item">
                <a href="{{ route('offices') }}" class="nav-link {{ request()->routeIs('offices') ? 'active' : '' }}">
                    <i class="fas fa-building nav-icon"></i>
                    <p>Office List</p>
                </a>
            </li>
        @endif

        @if (($user_role == 'Administrator' || $user_role == 'records_officer') && auth()->user()->id != 1235)
            <li class="nav-item">
                <a href="{{ route('userGroups') }}" class="nav-link {{ request()->routeIs('userGroups') ? 'active' : '' }}">
                    <i class="fas fa-users nav-icon"></i>
                    <p>Group List</p>
                </a>
            </li>
        @endif

        @if ($user_role == 'Administrator')
            <li class="nav-item">
                <a href="#" class="nav-link">
                    <i class="nav-icon fas fa-archive"></i>
                    <p>Routed Files Archive</p>
                </a>
            </li>
        @endif

        <li class="nav-item {{ request()->routeIs('viewLogs', 'viewLogs-Tracking') ? 'menu-is-opening menu-open' : '' }}">
            <a href="#" class="nav-link">
                <i class="fa fa-history nav-icon"></i>
                <p>Logs<i class="right fas fa-angle-left"></i></p>
            </a>
            <ul class="nav nav-treeview" style="{{ request()->routeIs('viewLogs', 'viewLogs-Tracking') ? 'display: block;' : 'display: none;' }}">
                <li class="nav-item">
                    <a href="{{ route('viewLogs-Tracking') }}" class="nav-link {{ request()->routeIs('viewLogs-Tracking') ? 'active' : '' }}">
                        <i class="fas fa-file-alt nav-icon text-primary"></i>
                        <p>Tracking Logs</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('viewLogs') }}" class="nav-link {{ request()->routeIs('viewLogs') ? 'active' : '' }}">
                        <i class="fas fa-route nav-icon text-success"></i>
                        <p>Route Logs</p>
                    </a>
                </li>
            </ul>
        </li>
    </ul>
</nav>