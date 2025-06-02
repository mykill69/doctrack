@php
    $current_route=request()->route()->getName();
    $user_role = auth()->user()->role;
    use App\Models\Log;
@endphp

<nav class="mt-2">
    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
        <li class="nav-item">
            <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="nav-icon fas fa-home"></i>
                <p>Home
                    @php
                    $userId = auth()->user()->id;
                    $userDepartment = auth()->user()->department;
                    $docCountDash = \App\Models\Document::where('user_id', $userId)
                    ->orWhereHas('logs', function($query) use ($userDepartment) {
                    $query->where('new_destination', $userDepartment);
                    })
                    ->count();
                    @endphp
                    
                    @if($docCountDash > 0)
                    <span class="badge badge-danger ml-2">{{ $docCountDash }}</span>
                    @else
                    <span class="badge badge-danger ml-2">0</span>
                    @endif
                </p>
            </a>
        </li>
        <li class="nav-item menu-open">
            <a href="#" class="nav-link">
              <i class="nav-icon fas fa-route"></i>
              <p>
                Tracking
                <i class="right fas fa-angle-left"></i>
              </p>
            </a>
            <ul class="nav nav-treeview" style="display: none;">
              <li class="nav-item">
                <a href="{{ route('doctrackSlip') }}" class="nav-link {{ request()->routeIs('doctrackSlip') ? 'active' : ''  }}">
                  <i class="fas fa-map-marker-alt nav-icon"></i>
                  <p>Tracking List</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="{{ route('incoming') }}" class="nav-link {{ request()->routeIs('incoming') ? 'active' : ''  }}">
                  <i class="fa fa-search nav-icon"></i>
                  <p>Search Tracking Code</p>
                </a>
              </li>
              
            </ul>
          </li>
        {{-- <li class="nav-item">
           
            <a href="{{ route('doctrackSlip') }}" class="nav-link {{ request()->routeIs('doctrackSlip') ? 'active' : ''  }}">
                <i class="nav-icon fa fa-map-marker"></i>
                <p>Tracking List
                    
                    <span class="badge badge-info ml-2"></span>
                    
                    <span class="badge badge-info ml-2"></span> 
                  
                </p>
            </a>
           
        </li> --}}
        {{-- <li class="nav-item">
           
            <a href="{{ route('incoming') }}" class="nav-link {{ request()->routeIs('incoming') ? 'active' : ''  }}">
                <i class="nav-icon fa fa-search"></i>
                <p>Tracking Code
                    
                    <span class="badge badge-info ml-2"></span>
                    
                    <span class="badge badge-info ml-2"></span> 
                  
                </p>
            </a>
           
        </li> --}}
        <li class="nav-item">
            @if (auth()->check() && (auth()->user()->hasRole('Administrator') || auth()->user()->hasRole('super_user') || auth()->user()->hasRole('records_officer')))
            <a href="{{ route('viewSlip') }}" class="nav-link {{ request()->routeIs('viewSlip') || request()->routeIs('editDest') || request()->routeIs('editSlip') ? 'active' : '' }}">
                <i class="nav-icon fas fa-receipt"></i>
                <p>Routing Slip
                    @if (auth()->user()->hasRole('records_officer'))
                    <span class="badge badge-info ml-2">{{ $recordsOfficerCount }}</span>
                    @elseif (auth()->user()->hasRole('super_user'))
                    <span class="badge badge-info ml-2">{{ $superUserCount }}</span> 
                    @endif
                </p>
            </a>
            @endif
        </li>
        <li class="nav-item">
            <a href="{{ route('pending') }}" class="nav-link {{ request()->routeIs('pending') ? 'active' : '' }}">
                <i class="nav-icon fas fa-exclamation"></i>
                <p>Pending
                    
                    @php
                    use Illuminate\Support\Facades\DB;
                    $userDepartment = auth()->user()->department;
                    $userId = auth()->user()->id;
                    $query = Log::where('status_update', 2)
                    ->where(function ($query) use ($userDepartment, $userId) {
                    $query->where('new_destination', $userDepartment)
                    ->orWhere('user_id', $userId);
                    });
                    if ($userId) {
                    $query->whereNull('new_user')
                    ->whereNotExists(function($subQuery) use ($userId) {
                    $subQuery->select(DB::raw(1))
                    ->from('logs as sublog')
                    ->whereRaw('sublog.route_id = logs.route_id')
                    ->whereRaw('sublog.doc_id = logs.doc_id')
                    ->whereRaw('sublog.new_destination = logs.new_destination')
                    ->whereNotNull('sublog.new_user');
                    });
                    }
                    $statusUpdateCount1 = $query->distinct('doc_id')->count();
                    @endphp
                    
                    <span class="badge badge-warning ml-2">{{ $statusUpdateCount1 > 0 ? $statusUpdateCount1 : '0' }}</span>
                </p>
            </a>
        </li>

 
        <li class="nav-item">
            <a href="{{ route('served') }}" class="nav-link {{ request()->routeIs('served') ? 'active' : '' }}">
                <i class="nav-icon fas fa-check"></i>
                <p>Served
                    
                    @php
                    $userDepartment = auth()->user()->department;
                    $userId = auth()->user()->id;
                    $statusUpdateCount = \App\Models\Log::where('status_update', 3)
                    ->where(function ($query) use ($userDepartment, $userId) {
                    $query->where('new_destination', $userDepartment)
                    ->orWhere('user_id', $userId);
                    })
                    ->distinct('route_id')
                    ->count();
                    @endphp
                    @if($statusUpdateCount > 0)
                    <span class="badge badge-success ml-2">{{ $statusUpdateCount }}</span>
                    @else
                    <span class="badge badge-success ml-2">0</span>
                    @endif
                </p>
            </a>
        </li>
        <li class="nav-item">
            <a href="{{ route('viewLogs') }}" class="nav-link {{ request()->routeIs('viewLogs') ? 'active' : '' }}">
                <i class="fa fa-history nav-icon"></i>
                <p>Logs</p>
            </a>
        </li>
        @if ($user_role == 'Administrator')
        <li class="nav-item">
            <a href="{{ route('userView') }}" class="nav-link {{ request()->routeIs('userView') ? 'active' : '' }}">
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