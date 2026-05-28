<div class="table-responsive mt-3">
    <table class="table table-bordered table-hover" style="font-size: 0.8rem;">
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
            @php
                $filteredSlips = $routingSlips;

                // For non-super_user, filter by tab status
                if ($userRole !== 'super_user') {
                    $statusMap = [
                        'routed2' => 2,
                        'routed1' => 1,
                        'enroute' => [0, null], // assuming enroute is not 1 or 2
                    ];

                    $targetStatus = $statusMap[$tabId] ?? null;

                    if (is_array($targetStatus)) {
                        $filteredSlips = $routingSlips->whereIn('route_status', $targetStatus);
                    } else {
                        $filteredSlips = $routingSlips->where('route_status', $targetStatus);
                    }
                }
            @endphp

            @forelse ($filteredSlips as $slip)
                <tr>
                    @php
                        $routingSlipId = \App\Models\RoutingSlip::where('rslip_id', $slip->rslip_id)
                            ->orderBy('id', 'desc')
                            ->value('id');

                        $isRecordsOfficer = auth()->user()->role === 'records_officer';
                        $isSuperUser = auth()->user()->role === 'super_user';
                        $existsInDocuments = \App\Models\Document::where('route_id', $slip->rslip_id)->exists();
                        $logStatusMatches = \App\Models\Log::where('route_id', $slip->rslip_id)
                            ->where('status_update', $slip->route_status)
                            ->exists();
                    @endphp

                    <!-- All your <td> columns remain the same -->
                    <td>
                        <a href="{{ route('slipForm', ['id' => $slip->rslip_id]) . '?routing_slip_id=' . $routingSlipId }}"
                            target="_blank" style="color:#007bff;">
                            @if ($isSuperUser || auth()->user()->role === 'Administrator')
                                {{ $slip->op_ctrl ?? $slip->rslip_id }}
                            @else
                                {{ $slip->rslip_id }}
                            @endif
                        </a>
                    </td>
                    <td>{{ \Carbon\Carbon::parse($slip->date_received)->format('F j, Y') }}</td>
                    <td>{{ $slip->source }}</td>
                    <td>{{ $slip->subject }}</td>
                    <td>
                        <a href="{{ route('viewPdfslip', $slip->id) }}" target="_blank" style="color:#007bff;">
                            {{ \Illuminate\Support\Str::limit($slip->document, 22) }}
                        </a>
                    </td>
                    <td>{{ $slip->trans_remarks }}</td>
                    <td>{{ $slip->other_remarks }}</td>
                    <td>{{ $slip->r_destination }}</td>
                    <td>
                        @switch($slip->route_status)
                            @case(1)
                                <span class="badge badge-warning" style="font-size:9px;">Routed to President</span>
                            @break

                            @case(2)
                                <span class="badge badge-info" style="font-size:9px;">Routed back to Records</span>
                            @break

                            @case(3)
                                <span class="badge badge-success" style="font-size:9px;">Served!</span>
                            @break
                        @endswitch
                    </td>
                    <td>{{ $slip->pres_dept }} / {{ $slip->updated_at->format('F j, Y') }}</td>
                    <td>
                        <div class="btn-group btn-group-sm">
                            {{-- EDIT BUTTONS --}}
                            @if ($isRecordsOfficer && $slip->route_status == 2)
                                @if ($slip->assigned_to != null)
                                    <a href="{{ route('editAssign', $slip->id) }}" class="btn btn-info">
                                        <i class="fas fa-plus"></i>
                                    </a>
                                @else
                                    <a href="{{ route('editDest', $slip->id) }}" class="btn btn-info">
                                        <i class="fas fa-plus"></i>
                                    </a>
                                @endif
                            @elseif ($isSuperUser && $slip->route_status == 1)
                                <a href="{{ route('editSlip', $slip->id) }}" class="btn btn-info">
                                    <i class="fas fa-pen"></i>
                                </a>
                            @elseif ($isSuperUser && $slip->route_status == 3)
                                <button class="btn btn-secondary" disabled>
                                    <i class="fas fa-pen"></i>
                                </button>
                            @elseif ($slip->route_status == 3)
                                @if ($existsInDocuments)
                                    <button class="btn btn-secondary" disabled>
                                        <i class="fas fa-plus"></i>
                                    </button>
                                @else
                                    <a href="{{ route('editDest', $slip->id) }}" class="btn btn-info">
                                        <i class="fas fa-plus"></i>
                                    </a>
                                @endif
                            @else
                                <button class="btn btn-secondary" disabled>
                                    <i class="fas fa-pen"></i>
                                </button>
                            @endif

                            {{-- DELETE --}}
                            <form action="{{ route('routingSlip.destroy', $slip->id) }}" method="POST"
                                onsubmit="return confirm('Are you sure you want to delete this routing slip?');"
                                class="d-inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger no-left-radius"
                                    @if (
                                        ($isRecordsOfficer && !empty($slip->trans_remarks) && !empty($slip->r_destination)) ||
                                            ($isSuperUser && !empty($slip->trans_remarks) && !empty($slip->r_destination))) disabled @endif>
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>

                            {{-- RECALL --}}
                            @if ($slip->route_status == 3 && ($isRecordsOfficer || auth()->user()->role === 'Administrator'))
                                @php
                                    $hasStatus2Log = \App\Models\Log::where('route_id', $slip->rslip_id)
                                        ->where('status_update', 2)
                                        ->exists();
                                @endphp
                                @if ($hasStatus2Log)
                                    <a href="{{ route('recallSlip', $slip->id) }}"
                                        class="btn btn-primary no-left-radius" title="Recall">
                                        <i class="fas fa-undo"></i>
                                    </a>
                                @endif
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                    <tr>
                        <td colspan="11" class="text-center py-4">No records found</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Pagination - Only show when super_user (since others use collection) -->
        @if ($userRole === 'super_user')
            <div class="d-flex justify-content-between align-items-center mt-4">
                <div>Showing {{ $routingSlips->firstItem() ?? 0 }} to {{ $routingSlips->lastItem() ?? 0 }} of
                    {{ $routingSlips->total() }} results</div>
                <div>{{ $routingSlips->appends(request()->query())->links('pagination::bootstrap-4') }}</div>
            </div>
        @endif
    </div>
