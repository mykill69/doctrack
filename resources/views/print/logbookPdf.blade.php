<style>
    @page {
        margin: 120px 40px 80px 40px;
        /* top, right, bottom, left */
    }

    body {
        font-family: Arial, Helvetica, sans-serif;
        margin: 0;
        padding: 0;
    }

    /* Header */
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

    /* Footer */
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

    /* Table */
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
        /* consistent row height */
        vertical-align: middle;
    }

    thead {
        display: table-header-group;
        /* repeat on each page */
    }

    tr {
        page-break-inside: avoid;
    }

    th {
        text-align: center;
    }

    .page-break {
        page-break-after: always;
    }
</style>

<body>
    <!-- Header -->
    <header>
        <img src="{{ public_path('template/img/header_new.png') }}" alt="Header Image">
        <h3 style="margin-top: -5px;">DOCUMENT LOGBOOK</h3>
    </header>

    <!-- Footer -->
    <footer>
        <img src="{{ public_path('template/img/footer-logbook.png') }}" alt="Footer Image">
    </footer>

    @php
        $logsPerPage = 15;

        // Keep it as a collection
        $chunks = $logs->values()->chunk($logsPerPage);

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
                    @foreach ($chunk as $log)
                        @php $document = $log->document; @endphp
                        <tr>
                            <td>{{ $log->route_id == 0 ? 'N/A' : $log->route_id }}</td>
                            <td>
                                {{ optional($document->routingSlip)->date_received
                                    ? \Carbon\Carbon::parse($document->routingSlip->date_received)->format('M d, Y')
                                    : ($document->created_at
                                        ? \Carbon\Carbon::parse($document->created_at)->format('M d, Y')
                                        : 'N/A') }}
                            </td>
                            <td>{{ optional($document->routingSlip)->source ?? ($document->department ?? 'N/A') }}</td>
                            @php
                                $subject = optional($document->routingSlip)->subject ?? ($document->subject ?? 'N/A');

                                // Limit to 50 words
                                $words = explode(' ', $subject);
                                if (count($words) > 80) {
                                    $subject = implode(' ', array_slice($words, 0, 80)) . '...';
                                }
                            @endphp

                            <td style="max-width: 300px;font-size:6; overflow: hidden; text-overflow: ellipsis;"
                                title="{{ $subject }}">
                                {{ $subject }}
                            </td>
                            <td>{{ optional($document->routingSlip)->pres_dept ?? 'N/A' }}</td>
                            <td>
                                {{ optional($document->routingSlip)->updated_at ? $document->routingSlip->updated_at->format('M d, Y') : 'N/A' }}
                            </td>
                            <td>
                                @if ($log->routingSlip)
                                    <strong
                                        class="text-danger">{{ ucwords(strtolower($log->routingSlip->r_destination)) }}</strong>
                                @endif
                                @if ($log->assigned_to)
                                    , re-assigned to
                                    <strong class="text-danger">{{ ucwords(strtolower($log->assigned_to)) }}</strong>
                                @endif
                            </td>
                            <td>{{ $document->created_at->format('m-d-Y h:i:s A') }}</td>
                            <td style="font-size:10px;">
                                @if (!empty($document->routingSlip->trans_remarks))
                                    <span>{{ $document->routingSlip->trans_remarks }}</span>
                                @endif
                                @if (!empty($document->routingSlip->other_remarks))
                                    <span>{{ $document->routingSlip->other_remarks }}</span>
                                @endif
                                @php
                                    $comment = $log->comments ?? '';
                                    $wrappedComment = preg_replace('/((?:\S+\s+){4})/', '$1<br>', $comment);
                                @endphp
                                @if ($comment)
                                    <span
                                        style="margin-top:2px; max-width:150px; word-wrap:break-word; white-space:normal;">
                                        {!! $wrappedComment !!}
                                    </span>
                                @endif
                            </td>
                            <td></td>
                        </tr>
                    @endforeach

                    {{-- Fill remaining rows to keep 15 rows per page --}}
                    @for ($i = count($chunk); $i < $logsPerPage; $i++)
                        <tr>
                            <td>&nbsp;</td>
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
                    @endfor
                </tbody>
            </table>
        </div>

        {{-- Page break except for last page --}}
        @if ($pageIndex + 1 < $totalPages)
            <div class="page-break"></div>
        @endif
    @endforeach
</body>
