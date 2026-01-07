@extends('layouts.main')

<style>
    .select2-container--default .select2-selection--multiple .select2-selection__choice {
        background-color: #007bff !important;
        border-color: #187744 !important;
        color: #fff;
        padding: 0 10px;
        margin-top: 0.31rem;
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
                                <h3 class="card-title">PENDING DOCUMENTS</h3>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="pendingTable" class="table table-bordered table-hover"
                                        style="font-size: 0.8rem;">
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
                                                {{-- <th>OFFICE</th> --}}
                                                <th>ACTION</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($logs as $log)
                                                <tr>
                                                    <td data-order="{{ $log->route_id ?? 0 }}">
                                                        <a href="{{ route('slipForm', ['id' => $log->route_id]) }}"
                                                            target="_blank" style="color: #007bff;">
                                                            {{ $log->route_id }}
                                                        </a>
                                                    </td>

                                                    <td>{{ $log->date_received ? \Carbon\Carbon::parse($log->date_received)->format('F d, Y') : 'N/A' }}
                                                    </td>
                                                    <td>{{ $log->source ?? 'N/A' }}</td>
                                                    <td>{{ $log->subject ?? 'N/A' }}</td>

                                                    <td>{{ $log->pres_dept ?? 'N/A' }}</td>
                                                    <td>{{ $log->updated_at ? \Carbon\Carbon::parse($log->updated_at)->format('F j, Y') : 'N/A' }}
                                                    </td>
                                                    <td>
                                                        @php
                                                            $destinationUser = $users->firstWhere(
                                                                'id',
                                                                $log->r_destination,
                                                            );
                                                            $assignedUser = $users->firstWhere('id', $log->assigned_to);
                                                        @endphp

                                                        @if ($destinationUser)
                                                            <strong class="text-danger">
                                                                {{ ucwords(strtolower($destinationUser->fname)) }}
                                                                {{ ucwords(strtolower($destinationUser->lname)) }}
                                                            </strong>
                                                        @else
                                                            <strong
                                                                class="text-danger">{{ ucwords(strtolower($log->r_destination)) }}</strong>
                                                        @endif

                                                        @if ($assignedUser)
                                                            , was re-assigned to <strong
                                                                class="text-danger">{{ $assignedUser->fname }}
                                                                {{ $assignedUser->lname }}</strong>
                                                        @endif
                                                    </td>

                                                    <td>{{ $log->created_at->format('m-d-Y h:i:s A') }}</td>
                                                    <td style="font-size:10px;width:5%;">
                                                        @php
                                                            $transRemarks = $log->trans_remarks ?? 'N/A';
                                                            $wrappedTransRemarks = preg_replace(
                                                                '/((?:\S+\s+){4})/',
                                                                '$1<br>',
                                                                $transRemarks,
                                                            );

                                                            $assignCom = $log->assign_com ?? '';
                                                            $wrappedAssignCom = preg_replace(
                                                                '/((?:\S+\s+){4})/',
                                                                '$1<br>',
                                                                $assignCom,
                                                            );
                                                        @endphp

                                                        @if (!empty($transRemarks))
                                                            <span class="badge badge-success"
                                                                style="font-size:10px; display: block;">
                                                                {!! $wrappedTransRemarks !!}
                                                            </span>
                                                        @endif

                                                        @if (!empty($assignCom))
                                                            <span class="badge badge-warning"
                                                                style="font-size:10px; margin-top:2px; max-width: 150px; display: inline-block; word-wrap: break-word; white-space: normal;">
                                                                {!! $wrappedAssignCom !!}
                                                            </span>
                                                        @endif
                                                    </td>

                                                    <td>
                                                        <a href="{{ route('documents.viewPdf', $log->doc_id) }}"
                                                            target="_blank" style="color: #007bff;">
                                                            <i class="fas fa-file-pdf text-danger"></i>
                                                            {{ \Illuminate\Support\Str::limit($log->file_name, 22) }}
                                                        </a>
                                                        <p>
                                                            <small class="text-muted">
                                                                @if ($log->viewed_status)
                                                                    Viewed on <br>
                                                                    {{ \Carbon\Carbon::parse($log->viewed_at)->format('M j, Y h:i A') }}
                                                                @else
                                                                @endif
                                                            </small>
                                                        </p>
                                                    </td>
                                                    {{-- <td>{{ $log->new_destination }}</td> --}}
                                                    {{-- <td>
                                                        <a href="{{ route('tracking', ['route_id' => $log->route_id]) }}"
                                                            class="btn btn-primary" target="_blank">
                                                            <i class="fas fa-pen"></i>
                                                        </a>
                                                    </td> --}}
                                                    {{-- 
                                                    @php
                                                        $routingSlipId = \App\Models\RoutingSlip::where(
                                                            'rslip_id',
                                                            $log->route_id,
                                                        )
                                                            ->orderBy('id', 'desc')
                                                            ->value('id');
                                                    @endphp

                                                    <td>
                                                        <a href="{{ route('tracking', [
                                                            'route_id' => $log->route_id,
                                                            'routing_slip_id' => $log->routing_slip_id,
                                                        ]) }}"
                                                            class="btn btn-primary" target="_blank">
                                                            <i class="fas fa-pen"></i>
                                                        </a>
                                                    </td> --}}
                                                    @php
                                                        $routingSlipId = \App\Models\RoutingSlip::where(
                                                            'rslip_id',
                                                            $log->route_id,
                                                        )
                                                            ->orderBy('id', 'desc')
                                                            ->value('id');
                                                    @endphp

                                                    <td>
                                                        <a href="{{ route('tracking', [
                                                            'route_id' => $log->route_id,
                                                            'routing_slip_id' => $routingSlipId,
                                                        ]) }}"
                                                            class="btn btn-primary" target="_blank">
                                                            <i class="fas fa-pen"></i>
                                                        </a>
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
        <!-- /.content -->
    </div>


    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>


    <script>
        $(document).ready(function() {
            var t = $('#pendingTable').DataTable({
                "order": [
                    [0, "desc"]
                ], // CTRL # descending
                "pageLength": 20,
                "columnDefs": [{
                    "targets": 0,
                    "type": "num",
                    "orderable": true
                }]
            });

            t.order([0, "desc"]).draw();
        });
    </script>






    @include('modal.docAdd')
    @include('modal.docEdit')
    @include('modal.addTrans')
    @include('modal.addRoutslip')
@endsection
