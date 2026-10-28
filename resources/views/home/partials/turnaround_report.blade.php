{{-- Report content. This partial is rendered on the first page load and is
     also returned by /turnaround/data so the JavaScript filter can replace it
     without changing the address bar. --}}

{{-- Overall statistics --}}
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info">
            <div class="inner">
                <h3 style="font-size: 1.55rem;">{{ number_format($overallStats['total_transactions']) }}</h3>
                <p>Completed {{ $typeLabel }}</p>
            </div>
            <div class="icon">
                <i class="fas fa-file-alt"></i>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-success">
            <div class="inner">
                <h3 style="font-size: 1.55rem;">{{ $overallStats['avg_display'] }}</h3>
                <p>Average Turnaround</p>
            </div>
            <div class="icon">
                <i class="fas fa-clock"></i>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3 style="font-size: 1.55rem;">{{ $overallStats['fastest'] }}</h3>
                <p>Fastest Turnaround</p>
            </div>
            <div class="icon">
                <i class="fas fa-bolt"></i>
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3 style="font-size: 1.55rem;">{{ $overallStats['slowest'] }}</h3>
                <p>Slowest Turnaround</p>
            </div>
            <div class="icon">
                <i class="fas fa-hourglass-end"></i>
            </div>
        </div>
    </div>
</div>

{{-- Report --}}
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    TURNAROUND DETAILS — {{ strtoupper($typeLabel) }}
                </h3>
                <div class="card-tools">
                    <form method="POST" action="{{ route('turnaround.pdf') }}" target="_blank"
                        class="d-inline">
                        @csrf
                        <input type="hidden" name="type" value="{{ $type }}">
                        <input type="hidden" name="start_date" value="{{ $startDate }}">
                        <input type="hidden" name="end_date" value="{{ $endDate }}">
                        <button type="submit" class="btn btn-danger btn-sm">
                            <i class="fas fa-file-pdf mr-1"></i> Export as PDF
                        </button>
                    </form>
                </div>
            </div>
            <div class="card-body">

                <div class="turnaround-summary d-flex flex-wrap mb-3" style="gap: 8px;">
                    <span class="badge badge-success px-3 py-2">
                        On Time: {{ number_format($overallStats['on_time']) }}
                    </span>
                    <span class="badge badge-danger px-3 py-2">
                        Not on-time: {{ number_format($overallStats['not_on_time']) }}
                    </span>
                    <span class="badge badge-info px-3 py-2">
                        Compliance Rate: {{ $overallStats['compliance_rate'] }}%
                    </span>
                    <span class="badge badge-secondary px-3 py-2">
                        Total Turnaround: {{ $overallStats['total_display'] }}
                    </span>
                </div>

                <ul class="nav nav-tabs turnaround-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-toggle="tab" href="#tab-details" role="tab">
                            <i class="fas fa-list mr-1"></i> Details
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#tab-office" role="tab">
                            <i class="fas fa-building mr-1"></i> By Office/Department
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#tab-daily" role="tab">
                            <i class="fas fa-calendar-day mr-1"></i> Daily
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#tab-weekly" role="tab">
                            <i class="fas fa-calendar-week mr-1"></i> Weekly
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#tab-monthly" role="tab">
                            <i class="fas fa-calendar-alt mr-1"></i> Monthly
                        </a>
                    </li>
                </ul>

                <div class="tab-content pt-3">
                    {{-- Details --}}
                    <div class="tab-pane fade show active" id="tab-details" role="tabpanel">
                        <div class="table-responsive">
                            <table id="turnaroundTable" class="table table-bordered table-hover"
                                style="font-size: 0.8rem; width: 100%;">
                                <thead>
                                    <tr>
                                        <th>PERSONNEL</th>
                                        <th>OFFICE/DEPARTMENT</th>
                                        <th>CONTROL #</th>
                                        <th>DATE RECEIVED</th>
                                        <th>DATE ACKNOWLEDGED</th>
                                        <th>TURNAROUND TIME</th>
                                        <th>REMARKS</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($turnaroundData as $row)
                                        <tr>
                                            <td>{{ $row['personnel'] }}</td>
                                            <td>{{ $row['office'] }}</td>
                                            <td>{{ $row['ctrl_no'] }}</td>
                                            <td data-order="{{ $row['started_at']->format('Y-m-d H:i:s') }}">
                                                {{ $row['date_received'] }}
                                            </td>
                                            <td data-order="{{ $row['finished_at']->format('Y-m-d H:i:s') }}">
                                                {{ $row['date_completed'] }}
                                            </td>
                                            <td data-order="{{ $row['duration_minutes'] }}">
                                                {{ $row['duration_display'] }}
                                            </td>
                                            <td data-order="{{ $row['remarks'] === 'On Time' ? 1 : 0 }}">
                                                <span
                                                    class="badge {{ $row['remarks'] === 'On Time' ? 'badge-success' : 'badge-danger' }}">
                                                    {{ $row['remarks'] }}
                                                </span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">
                                                No completed {{ strtolower($typeLabel) }} found for the
                                                selected period.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- By office/department --}}
                    <div class="tab-pane fade" id="tab-office" role="tabpanel">
                        @include('home.partials.turnaround_office_table', [
                            'rows' => $officeReport,
                        ])
                    </div>

                    {{-- Daily --}}
                    <div class="tab-pane fade" id="tab-daily" role="tabpanel">
                        @include('home.partials.turnaround_summary_table', [
                            'rows' => $dailyReport,
                            'periodLabel' => 'Date',
                        ])
                    </div>

                    {{-- Weekly --}}
                    <div class="tab-pane fade" id="tab-weekly" role="tabpanel">
                        @include('home.partials.turnaround_summary_table', [
                            'rows' => $weeklyReport,
                            'periodLabel' => 'Week',
                        ])
                    </div>

                    {{-- Monthly --}}
                    <div class="tab-pane fade" id="tab-monthly" role="tabpanel">
                        @include('home.partials.turnaround_summary_table', [
                            'rows' => $monthlyReport,
                            'periodLabel' => 'Month',
                        ])
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
