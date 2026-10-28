<div class="table-responsive">
    <table class="table table-bordered table-hover" style="font-size: 0.8rem; width: 100%;">
        <thead>
            <tr>
                <th>{{ $periodLabel }}</th>
                <th>TRANSACTIONS</th>
                <th>TOTAL TURNAROUND</th>
                <th>AVERAGE TURNAROUND</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row['period'] }}</td>
                    <td>{{ number_format($row['transactions']) }}</td>
                    <td>{{ $row['total_display'] }}</td>
                    <td>
                        <span class="badge badge-info">{{ $row['avg_display'] }}</span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center text-muted py-4">
                        No data available for the selected period.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
