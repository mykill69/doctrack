@extends('layouts.main')

@section('body')
    <div class="content-wrapper">
        <div class="content" style="padding-top: 1%;">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">TRACKING DISTRIBUTION LIST</h3>
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
                                                {{-- <th>FILE NAME</th> --}}
                                                <th>ACTION UNIT</th>
                                                <th>DATE RELEASED</th>
                                                {{-- <th>STATUS</th> --}}
                                                <th>ACTION</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($logs as $log)
                                                <tr>
                                                    <td>{{ $log->docslip_id }}</td>
                                                    <td>{{ $log->doc_type ?? 'N/A' }}</td>
                                                    <td>{{ $log->doc_title ?? 'N/A' }}</td>
                                                    {{-- <td>{{ $log->doctrackFile->file_name ?? 'N/A' }}</td> --}}
                                                    <td>
                                                        @if (!empty($log->combined_names))
                                                            @foreach (explode(',', $log->combined_names) as $name)
                                                                <span
                                                                    class="badge bg-warning text-dark">{{ trim($name) }}</span>
                                                            @endforeach
                                                        @else
                                                            <span class="text-muted">N/A</span>
                                                        @endif
                                                    </td>


                                                    <td>{{ \Carbon\Carbon::parse($log->created_at)->format('M d, Y h:i:s A') }}
                                                    </td>
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
@endsection
