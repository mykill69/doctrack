<style>
    @page {
        margin: 120px 40px 80px 40px;
    }
    body {
        font-family: Arial, Helvetica, sans-serif;
        margin: 0;
        padding: 0;
    }
    header {
        position: fixed;
        top: -100px;
        left: 0;
        right: 0;
        height: 100px;
        text-align: center;
    }
    header img {
        width: 55%;
        height: auto;
    }
    footer {
        position: fixed;
        bottom: -60px;
        left: 0;
        right: 0;
        height: 50px;
        text-align: center;
    }
    footer img {
        width: 45%;
        height: 30px;
        margin: 0 auto;
        display: block;
    }
    table.dashboardTable {
        width: 100%;
        border: 1px solid black;
        border-collapse: collapse;
        font-size: 9px;
        page-break-inside: auto;
    }
    table.dashboardTable td,
    table.dashboardTable th {
        border: 1px solid black;
        padding: 3px;
        text-align: left;
        height: 27px;
        vertical-align: middle;
    }
    thead { display: table-header-group; }
    tr { page-break-inside: avoid; }
    th { text-align: center; }
    .page-break { page-break-after: always; }
</style>

<body>
    <header>
        <img src="{{ public_path('template/img/header_new.png') }}" alt="Header Image">
        <h3 style="margin-top: -5px;">DOCUMENT LOGBOOK</h3>
    </header>

    <footer>
        <img src="{{ public_path('template/img/footer-logbook.png') }}" alt="Footer Image">
    </footer>

    @php
        $logsByRouteId = $logs->keyBy('route_id');
        
        // ✅ Always start from the first CTRL # in range or 1
        $ctrl_from = request('ctrl_from');
        $minRouteId = $ctrl_from ? (int)$ctrl_from : ($logs->isNotEmpty() ? $logs->min('route_id') : 1);
        
        // ✅ End at the last CTRL # in range or max log
        $ctrl_to = request('ctrl_to');
        $maxRouteId = $ctrl_to ? (int)$ctrl_to : ($logs->isNotEmpty() ? $logs->max('route_id') : 1);
        
        // Build complete sequence
        $orderedLogs = collect();
        for ($i = $minRouteId; $i <= $maxRouteId; $i++) {
            $orderedLogs->push([
                'route_id' => $i,
                'log' => $logsByRouteId[$i] ?? null,
            ]);
        }
        
        $logsPerPage = 15;
        $chunks = $orderedLogs->chunk($logsPerPage);
        $totalPages = $chunks->count();
    @endphp

    @foreach ($chunks as $pageIndex => $chunk)
        <div class="content">
            <table class="dashboardTable">
                <thead>
                    <tr>
                        <th style="width: 2%;">CTRL #</th>
                        <th style="width: 6%;">DATE RECEIVED</th>
                        <th style="width: 14%;">SOURCE</th>
                        <th style="width: 25%;">SUBJECT MATTER</th>
                        <th style="width: 10%;">ACTION UNIT</th>
                        <th style="width: 7%;">RECEIVED BY/DATE</th>
                        <th style="width: 11%;">ACTION TAKEN</th>
                        <th style="width: 8%;">DATE RELEASED</th>
                        <th style="width: 7%;">REMARKS</th>
                        <th style="width: 5%;">STORAGE</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($chunk as $item)
                        @php $log = $item['log']; @endphp
                        
                        @if ($log)
                            @php
                                $exactSlip = $log->exactSlip ?? $log->routingSlip;
                                $exactDoc = $log->exactDoc ?? $log->document;
                            @endphp
                            <tr>
                                <td>{{ $log->route_id }}</td>
                                <td>
                                    {{ $exactSlip && $exactSlip->date_received
                                        ? \Carbon\Carbon::parse($exactSlip->date_received)->format('M d, Y')
                                        : ($exactDoc && $exactDoc->created_at
                                            ? \Carbon\Carbon::parse($exactDoc->created_at)->format('M d, Y')
                                            : 'N/A') }}
                                </td>
                                <td>{{ $exactSlip->source ?? 'N/A' }}</td>
                                @php
                                    $subject = $exactSlip->subject ?? ($exactDoc->subject ?? 'N/A');
                                    $words = explode(' ', $subject);
                                    if (count($words) > 80) {
                                        $subject = implode(' ', array_slice($words, 0, 80)) . '...';
                                    }
                                @endphp
                                <td style="max-width: 300px;font-size:6; overflow: hidden; text-overflow: ellipsis;" title="{{ $subject }}">
                                    {{ $subject }}
                                </td>
                                <td>{{ $exactSlip->pres_dept ?? 'N/A' }}</td>
                                <td>{{ $exactSlip && $exactSlip->updated_at ? $exactSlip->updated_at->format('M d, Y') : 'N/A' }}</td>
                                <td>
                                    @if ($exactSlip && $exactSlip->r_destination)
                                        <strong class="text-danger">{{ ucwords(strtolower($exactSlip->r_destination)) }}</strong>
                                    @endif
                                    @if ($log->assigned_to)
                                        , re-assigned to <strong class="text-danger">{{ ucwords(strtolower($log->assigned_to)) }}</strong>
                                    @endif
                                </td>
                                <td>{{ $exactDoc && $exactDoc->created_at ? $exactDoc->created_at->format('m-d-Y h:i:s A') : 'N/A' }}</td>
                                <td style="font-size:10px;">
                                    @if ($exactSlip && !empty($exactSlip->trans_remarks))
                                        <span>{{ $exactSlip->trans_remarks }}</span>
                                    @endif
                                    @if ($exactSlip && !empty($exactSlip->other_remarks))
                                        <span>{{ $exactSlip->other_remarks }}</span>
                                    @endif
                                    @php $comment = $log->comments ?? ''; $wrappedComment = preg_replace('/((?:\S+\s+){4})/', '$1<br>', $comment); @endphp
                                    @if ($comment)
                                        <span style="margin-top:2px; max-width:150px; word-wrap:break-word; white-space:normal;">{!! $wrappedComment !!}</span>
                                    @endif
                                </td>
                                <td></td>
                            </tr>
                        @else
                            <tr>
                                <td>{{ $item['route_id'] }}</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                            </tr>
                        @endif
                    @endforeach

                    @for ($i = count($chunk); $i < $logsPerPage; $i++)
                        <tr>
                            <td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>
                            <td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td>
                        </tr>
                    @endfor
                </tbody>
            </table>
        </div>

        @if ($pageIndex + 1 < $totalPages)
            <div class="page-break"></div>
        @endif
    @endforeach
</body>