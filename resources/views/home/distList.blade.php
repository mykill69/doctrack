@extends('layouts.main')

@section('body')
    <div class="content-wrapper">
        <div class="content" style="padding-top: 1%;">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">DISTRIBUTION LIST</h3>
                            </div>
                            <div class="card-body">
                                <!-- Nav tabs -->
                                <ul class="nav nav-tabs" id="distributionTabs" role="tablist">
                                    <li class="nav-item">
                                        <a class="nav-link active" id="routed-tab" data-toggle="tab" href="#routed" role="tab">
                                            <i class="fas fa-share"></i> ROUTED DISTRIBUTION
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" id="tracking-tab" data-toggle="tab" href="#tracking" role="tab">
                                            <i class="fas fa-map"></i> TRACKING DISTRIBUTION
                                        </a>
                                    </li>
                                </ul>

                                <!-- Tab content -->
                                <div class="tab-content" id="distributionTabsContent">
                                    <!-- Routed Distribution Tab -->
                                    <div class="tab-pane fade show active" id="routed" role="tabpanel">
                                        <div class="table-responsive mt-3">
                                            <table id="example1" class="table table-bordered table-hover" style="font-size: 0.8rem;">
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
                                                    @foreach ($routedLogs as $log)
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
                                                                    <span class="badge bg-warning text-dark">{{ $destination }}</span>
                                                                @empty
                                                                    <span class="text-muted">No Destination</span>
                                                                @endforelse
                                                            </td>
                                                            <td>{{ \Carbon\Carbon::parse($log->created_at)->format('m-d-Y h:i:s A') }}</td>
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

                                    <!-- Tracking Distribution Tab -->
                                    <div class="tab-pane fade" id="tracking" role="tabpanel">
                                        <div class="table-responsive mt-3">
                                            <table id="example2" class="table table-bordered table-hover" style="font-size: 0.8rem;">
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
                                                    @foreach ($trackingLogs as $log)
                                                        <tr>
                                                            <td>{{ $log->docslip_id }}</td>
                                                            <td>{{ $log->doc_type ?? 'N/A' }}</td>
                                                            <td>{{ $log->doc_title ?? 'N/A' }}</td>
                                                            <td>
                                                                @if (!empty($log->combined_names))
                                                                    @foreach (explode(',', $log->combined_names) as $name)
                                                                        <span class="badge bg-warning text-dark">{{ trim($name) }}</span>
                                                                    @endforeach
                                                                @else
                                                                    <span class="text-muted">N/A</span>
                                                                @endif
                                                            </td>
                                                            <td>{{ \Carbon\Carbon::parse($log->created_at)->format('M d, Y h:i:s A') }}</td>
                                                            <td class="text-center">
                                                                <a href="{{ route('viewTrackingDistributionPdf', ['id' => $log->docslip_id]) }}"
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
        </div>
    </div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Initialize tabs
        $('#distributionTabs a').on('click', function (e) {
            e.preventDefault();
            $(this).tab('show');
        });
    });
</script>
@endpush