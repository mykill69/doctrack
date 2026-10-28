<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Turnaround Time Report</title>
    <style>
        @page {
            margin: 25px 25px 25px 25px;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9px;
            color: #000;
        }

        h2 {
            margin: 0 0 3px 0;
            font-size: 14px;
            text-align: center;
        }

        .subtitle {
            text-align: center;
            font-size: 10px;
            margin-bottom: 12px;
        }

        .summary {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .summary td {
            border: 1px solid #999;
            padding: 5px;
            text-align: center;
            font-size: 9px;
        }

        .summary .label {
            font-weight: bold;
            display: block;
            margin-bottom: 2px;
        }

        .section-title {
            margin: 12px 0 5px 0;
            font-size: 10px;
            text-transform: uppercase;
        }

        .notice {
            border: 1px solid #ccc;
            background: #f8f9fa;
            padding: 5px;
            margin: 6px 0;
            font-size: 8px;
        }

        table.data {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
        }

        table.data th,
        table.data td {
            border: 1px solid #666;
            padding: 3px;
            vertical-align: middle;
        }

        table.data th {
            background: #e9ecef;
            text-align: center;
            font-weight: bold;
        }

        thead {
            display: table-header-group;
        }

        tr {
            page-break-inside: avoid;
        }

        .text-center {
            text-align: center;
        }

        .footer {
            margin-top: 8px;
            font-size: 8px;
            text-align: right;
        }
    </style>
</head>

<body>
    <h2>CPSU DOCUMENT TRACKING SYSTEM</h2>
    <div class="subtitle">
        TURNAROUND TIME REPORT — {{ strtoupper($typeLabel) }}
    </div>

    <table class="summary">
        <tr>
            <td>
                <span class="label">Completed {{ $typeLabel }}</span>
                {{ number_format($overallStats['total_transactions']) }}
            </td>
            <td>
                <span class="label">Average Turnaround</span>
                {{ $overallStats['avg_display'] }}
            </td>
            <td>
                <span class="label">Fastest</span>
                {{ $overallStats['fastest'] }}
            </td>
            <td>
                <span class="label">Slowest</span>
                {{ $overallStats['slowest'] }}
            </td>
            <td>
                <span class="label">On Time / Not on-time</span>
                {{ number_format($overallStats['on_time']) }} / {{ number_format($overallStats['not_on_time']) }}
            </td>
            <td>
                <span class="label">Compliance Rate</span>
                {{ $overallStats['compliance_rate'] }}%
            </td>
        </tr>
    </table>

    <p style="margin: 0 0 8px 0;">
        <strong>Transaction Type:</strong> {{ $typeLabel }}
        &nbsp; | &nbsp;
        <strong>Period:</strong>
        @if ($startDate && $endDate)
            {{ \Carbon\Carbon::parse($startDate)->format('F j, Y') }} to
            {{ \Carbon\Carbon::parse($endDate)->format('F j, Y') }}
        @elseif ($startDate)
            From {{ \Carbon\Carbon::parse($startDate)->format('F j, Y') }}
        @elseif ($endDate)
            Up to {{ \Carbon\Carbon::parse($endDate)->format('F j, Y') }}
        @else
            All completed transactions
        @endif
        &nbsp; | &nbsp;
        <strong>Generated:</strong> {{ now()->format('F j, Y g:i A') }}
    </p>

    <h3 class="section-title">Turnaround by Office/Department</h3>
    <table class="data">
        <thead>
            <tr>
                <th style="width: 46%;">OFFICE/DEPARTMENT</th>
                <th style="width: 18%;">TRANSACTIONS</th>
                <th style="width: 18%;">TOTAL TURNAROUND</th>
                <th style="width: 18%;">AVERAGE TURNAROUND</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($officeReport as $office)
                <tr>
                    <td>{{ $office['office'] }}</td>
                    <td class="text-center">{{ number_format($office['transactions']) }}</td>
                    <td class="text-center">{{ $office['total_display'] }}</td>
                    <td class="text-center">{{ $office['avg_display'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center">No office/department data found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <h3 class="section-title">Turnaround Details</h3>

    @if (!empty($pdfLimited))
        <p class="notice">
            <strong>Note:</strong> The details table shows the first
            {{ number_format($pdfDetailLimit) }} of
            {{ number_format($pdfTotalRows) }} transactions to keep this export
            manageable. The summary and the office/department breakdown above
            cover all {{ number_format($pdfTotalRows) }} transactions. Use a
            narrower date range to export a complete details list.
        </p>
    @endif

    <table class="data">
        <thead>
            <tr>
                <th style="width: 16%;">PERSONNEL</th>
                <th style="width: 22%;">OFFICE/DEPARTMENT</th>
                <th style="width: 8%;">CONTROL #</th>
                <th style="width: 12%;">DATE RECEIVED</th>
                <th style="width: 12%;">DATE ACKNOWLEDGED</th>
                <th style="width: 16%;">TURNAROUND TIME</th>
                <th style="width: 14%;">REMARKS</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($turnaroundData as $row)
                <tr>
                    <td>{{ $row['personnel'] }}</td>
                    <td>{{ $row['office'] }}</td>
                    <td class="text-center">{{ $row['ctrl_no'] }}</td>
                    <td>{{ $row['date_received'] }}</td>
                    <td>{{ $row['date_completed'] }}</td>
                    <td class="text-center">{{ $row['duration_display'] }}</td>
                    <td class="text-center">{{ $row['remarks'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">
                        No completed {{ strtolower($typeLabel) }} found.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">
        Generated by the CPSU Document Tracking System
    </div>
</body>

</html>
