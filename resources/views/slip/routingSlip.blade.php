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
color: lightgrey; /* Default color for disabled */
pointer-events: none; /* Prevent any interaction */
opacity: 0.2; /* Dim the icon to indicate it's disabled */
transition: opacity 0.3s; /* Smooth transition for hover */
}
.disabled-icon:hover {
opacity: 0.4; /* Change opacity on hover to indicate it's disabled */
cursor: not-allowed; /* Change cursor to indicate it's not clickable */
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
                        <div class="table-responsive">
                            <table id="example1" class="table table-bordered table-hover text-sm">
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
                                    @php $no = 1; @endphp
                                    @foreach($routingSlips as $slip)
                                    <tr>
                                        <td>
                                            <a href="{{ route('slipForm', $slip->rslip_id) }}" target="_blank">{{ $slip->rslip_id }}</a>
                                            
                                            
                                            
                                        </td>
                                        <td>{{ \Carbon\Carbon::parse($slip->date_received)->format('F j, Y') }}</td>
                                        <td>{{ $slip->source }}</td>
                                        <td>{{ $slip->subject }}</td>
                                        <td>
                                            <a href="{{ route('viewPdfslip', $slip->id) }}" target="_blank" style="color: #007bff;">
                                                {{  \Illuminate\Support\Str::limit($slip->document, 22) }}
                                            </a>
                                            
                                        </td>
                                        <td>{{ $slip->trans_remarks }}</td>
                                        <td>{{ $slip->oher_remarks }}</td>
                                        <td>
                                            <strong class="text-danger">{{ $slip->r_destination }}</strong>
                                            @if($slip->assigned_to != null)
                                            , was re-assigned to <strong class="text-danger">{{ $slip->assigned_to }}</strong>
                                            @endif
                                        </td>
                                        <td>
                                            @php
                                            $routeId = $slip->rslip_id;
                                            $logStatusUpdates = \App\Models\Log::where('route_id', $routeId)->pluck('status_update');
                                            $allServed = $logStatusUpdates->every(fn($status) => $status == 3);
                                            $logStatusMatches = \App\Models\Log::where('route_id', $slip->rslip_id)
                                            ->where('status_update', $slip->route_status)
                                            ->exists();
                                            $existsInDocuments = \App\Models\Document::where('route_id', $slip->rslip_id)->exists();
                                            @endphp
                                            @switch($slip->route_status)
                                            @case(1)
                                            <p class="badge badge-warning" style="font-size:9px;">Routed to <br>President</p>
                                            @break
                                            @case(2)
                                            <p class="badge badge-info" style="font-size:9px;">Routed back to <br>Records Office</p>
                                            @break
                                            @case(3)
                                            @if($allServed)
                                            <p class="badge badge-success text-center" style="font-size:9px;">Served!</p>
                                            @else
                                            @if($logStatusMatches)
                                            <p class="badge badge-success text-center" style="font-size:9px;">Served!</p>
                                            @else
                                            <p class="badge badge-danger" style="font-size:9px;">En route</p>
                                            @endif
                                            @endif
                                            @break
                                            @default
                                            <p>Unknown Status</p>
                                            @endswitch
                                        </td>
                                        
                                        <td>
                                            @if(!empty($slip->pres_dept))
                                            {{ $slip->pres_dept }} / {{ $slip->updated_at->format('F j, Y') }}
                                            @else
                                            {{ $slip->pres_dept }}
                                            @endif
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                @php
                                                $isRecordsOfficer = auth()->user()->role === 'records_officer';
                                                $isSuperUser = auth()->user()->role === 'super_user';
                                                @endphp
                                                
                                                @if($isRecordsOfficer && $slip->route_status == 2)
                                                @if($slip->assigned_to != null)
                                                <a href="{{ route('editAssign', $slip->id) }}" class="btn btn-info" style="text-decoration: none; color: white;">
                                                    <i class="fas fa-plus"></i>
                                                </a>
                                                @else
                                                <a href="{{ route('editDest', $slip->id) }}" class="btn btn-info" style="text-decoration: none; color: white;">
                                                    <i class="fas fa-plus"></i>
                                                </a>
                                                @endif
                                                @elseif($isSuperUser && $slip->route_status == 1)
                                                <a href="{{ route('editSlip', $slip->id) }}" class="btn btn-info" style="text-decoration: none; color: white;">
                                                    <i class="fas fa-pen"></i>
                                                </a>
                                                @elseif($isSuperUser && $slip->route_status == 3)
                                                <button class="btn btn-secondary" disabled>
                                                <i class="fas fa-pen"></i>
                                                </button>
                                                @elseif($slip->route_status == 3)
                                                @if($existsInDocuments)
                                                <button class="btn btn-secondary" disabled>
                                                <i class="fas fa-plus"></i>
                                                </button>
                                                @else
                                                <a href="{{ route('editDest', $slip->id) }}" class="btn btn-info" style="text-decoration: none; color: white;">
                                                    <i class="fas fa-plus"></i>
                                                </a>
                                                @endif
                                                @elseif($slip->route_status == 2 && $logStatusMatches)
                                                <button class="btn btn-secondary" disabled>
                                                <i class="fas fa-plus"></i>
                                                </button>
                                                @else
                                                <button class="btn btn-secondary" disabled>
                                                <i class="fas fa-pen"></i>
                                                </button>
                                                @endif
                                                
                                                {{-- <form action="{{ route('deletePdf', $slip->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this routing slip?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger no-left-radius"
                                                    @if(($isRecordsOfficer && !empty($slip->trans_remarks) && !empty($slip->r_destination)) || ($isSuperUser && !empty($slip->trans_remarks) && !empty($slip->r_destination)))
                                                    disabled
                                                    @endif>
                                                    <i class="fas fa-trash"></i>
                                                    </button>
                                                </form> --}}
                                                {{-- <a href="" onclick="deleteRoutingSlip('{{ route('routingSlip.destroy', $slip->id) }}')" class="btn btn-danger no-left-radius"
                                                    @if(($isRecordsOfficer && !empty($slip->trans_remarks) && !empty($slip->r_destination)) || ($isSuperUser && !empty($slip->trans_remarks) && !empty($slip->r_destination)))
                                                    disabled
                                                    @endif>
                                                    <i class="fas fa-trash"></i>
                                                </a> --}}
                                                <form action="{{ route('routingSlip.destroy', $slip->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this routing slip?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger no-left-radius"
                                                    @if(($isRecordsOfficer && !empty($slip->trans_remarks) && !empty($slip->r_destination)) || ($isSuperUser && !empty($slip->trans_remarks) && !empty($slip->r_destination)))
                                                    disabled
                                                    @endif>
                                                    <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
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