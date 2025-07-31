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
                                <div class="table-responsive">
                                    <table id="example1" class="table table-bordered table-hover"
                                        style="font-size: 0.8rem;">
                                        <thead>
                                            <tr>
                                                <th>CTRL #</th>
                                                <th>SOURCE</th>
                                                <th>SUBJECT MATTER</th>
                                                <th>FILE NAME</th>
                                                <th>ACTION UNIT</th>
                                                <th>DATE RELEASED</th>
                                                {{-- <th>STATUS</th> --}}
                                                <th>ACTION</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($logs as $log)
                                                @php
                                                    $routedUsers = $log->routed_users
                                                        ? array_map('trim', explode(',', $log->routed_users))
                                                        : [];
                                                @endphp


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
                                                        @if ($log->document)
                                                            <a href="{{ route('documents.viewPdf', ['id' => $log->doc_id]) }}"
                                                                target="_blank" style="color: #007bff;">
                                                                <i class="fas fa-file-pdf text-danger"></i>
                                                                {{ \Illuminate\Support\Str::limit($log->document, 22) }}
                                                            </a>
                                                        @else
                                                            N/A
                                                        @endif
                                                    </td>

                                                    <td>
                                                        @foreach ($routedUsers as $user)
                                                            <span
                                                                class="badge bg-warning text-dark">{{ $user }}</span>
                                                        @endforeach
                                                    </td>


                                                    <td>{{ \Carbon\Carbon::parse($log->created_at)->format('m-d-Y h:i:s A') }}
                                                    </td>
                                                    {{-- <td>
                                                        @php
                                                            switch ($log->route_status) {
                                                                case 0:
                                                                    echo '<span class="badge bg-secondary">Pending</span>';
                                                                    break;
                                                                case 1:
                                                                    echo '<span class="badge bg-success">In Progress</span>';
                                                                    break;
                                                                case 2:
                                                                    echo '<span class="badge bg-primary">Completed</span>';
                                                                    break;
                                                                default:
                                                                    echo '<span class="badge bg-dark">Unknown</span>';
                                                            }
                                                        @endphp
                                                    </td> --}}
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
