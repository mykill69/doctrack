@extends('layouts.main')

@section('body')
    <div class="content-wrapper">
        <div class="content" style="padding-top: 1%;">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">ROUTED DISTRIBUTION LIST</h3>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table id="example1" class="table table-bordered table-hover"
                                        style="font-size: 0.8rem;">
                                        <thead>
                                            <tr>
                                                <th>CTRL #</th>
                                                <th>SOURCE</th>
                                                <th>SUBJECT MATTER</th>
                                                <th>ACTION UNIT</th>
                                                <th>DATE RELEASED</th>
                                                <th>ACTION</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($logs as $log)
                                                <tr>
                                                    <td>
                                                        <a href="{{ route('slipForm', $log->rslip_id) }}" target="_blank"
                                                            style="color: #007bff;">
                                                            {{ $log->rslip_id }}
                                                        </a>

                                                    </td>

                                                    <td>{{ $log->source ?? 'N/A' }}</td>
                                                    <td>{{ $log->subject ?? 'N/A' }}</td>
                                                    <td>
                                                        @forelse ($log->destinations as $destination)
                                                            <span
                                                                class="badge bg-warning text-dark">{{ $destination }}</span>
                                                        @empty
                                                            <span class="text-muted">No Destination</span>
                                                        @endforelse
                                                    </td>



                                                    <td>{{ \Carbon\Carbon::parse($log->created_at)->format('m-d-Y h:i:s A') }}
                                                    </td>

                                                    <td class="text-center">
                                                        <a href="{{ route('viewDistributionPdf', ['id' => $log->rslip_id]) }}"
                                                            target="_blank" class="btn btn-outline-danger btn-sm shadow-sm"
                                                            title="View PDF">
                                                            <i class="fas fa-file-pdf me-1"></i> View PDF
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
    </div>
@endsection
