<!DOCTYPE html>
<html>

<head>
    <title>{{ $title }}</title>
    <style>
        @page {
            margin: 130px 40px 100px 40px;
        }

        body {
            font-family: Arial, sans-serif;
        }

        header {
            position: fixed;
            top: -120px;
            left: 0;
            right: 0;
            height: 100px;
            text-align: center;
        }

        header img {
            width: 100%;
            height: auto;
        }

        footer {
            position: fixed;
            bottom: -80px;
            left: 0;
            right: 0;
            height: 60px;
            text-align: center;
        }

        footer img {
            width: 100%;
            height: auto;
        }

        h1 {
            text-align: center;
            text-transform: uppercase;
            padding: 0;
            font-size: 16px;
            margin-top: -3%;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
            page-break-inside: avoid;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 8px;
            text-align: center;
            vertical-align: middle;
            height: 32px;
        }

        th {
            background-color: #f8f8f8;
        }

        .page-break {
            page-break-after: always;
        }
    </style>
</head>

<body>

    <!-- Header -->
    <header>
        <img src="{{ public_path('template/img/header_new.png') }}" alt="Header">
    </header>

    <!-- Footer -->
    <footer>
        <img src="{{ public_path('template/img/footer_new.png') }}" alt="Footer">
    </footer>

    <!-- Content -->
    <main>
        <h1>{{ $title }}</h1>

        {{-- @php
            $logsPerPage = 15;
            $chunks = $logs->whereNotNull('user_department')->chunk($logsPerPage);
            $totalPages = ceil(count($logs->whereNotNull('user_department')) / $logsPerPage);
            $rowCount = 1;
        @endphp --}}
        @php
            $logsPerPage = 15;
            $chunks = $logs->chunk($logsPerPage);
            $totalPages = ceil(count($logs) / $logsPerPage);
            $rowCount = 1;
        @endphp

        @foreach ($chunks as $pageIndex => $chunk)
            <table>
                <thead>
                    <tr>
                        <th colspan="6" height="70">{{ $routingSlip->subject }}</th>
                    </tr>
                    <tr>
                        <th>NO.</th>
                        <th>OFFICE OF CUSTODIAN</th>
                        <th>DATE ISSUED</th>
                        <th>DATE RETRIEVED</th>
                        <th>RECEIVED BY</th>
                        <th>SIGNATURE</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($chunk as $log)
                        @if ($log->new_destination)
                            <tr>
                                <td>{{ $rowCount }}</td>
                                <td><small>{{ $log->user_department ?? ' ' }}</small></td>

                                @if (!empty($log->user_department))
                                    <td>{{ \Carbon\Carbon::parse($log->created_at)->format('M d, Y') }}</td>
                                    <td>{{ $log->updated_at ? \Carbon\Carbon::parse($log->updated_at)->format('M d, Y') : '---' }}
                                    </td>
                                    <td>{{ $log->new_destination }}</td>
                                @else
                                    <td>&nbsp;</td>
                                    <td>&nbsp;</td>
                                    <td>&nbsp;</td>
                                @endif

                                <td>
                                    @if ($log->esig_file)
                                        <img src="{{ public_path('storage/esignature/' . $log->esig_file) }}"
                                            alt="Signature" style="width: 80px; height: 50px;">
                                    @endif
                                </td>
                            </tr>
                        @else
                            <tr>
                                <td>{{ $rowCount }}</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                                <td>&nbsp;</td>
                            </tr>
                        @endif
                        @php $rowCount++; @endphp
                    @endforeach

                    {{-- Fill remaining rows to keep total per page at 15 --}}
                    @for ($i = $chunk->count(); $i < $logsPerPage; $i++)
                        <tr>
                            <td>{{ $rowCount }}</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                        </tr>
                        @php $rowCount++; @endphp
                    @endfor
                </tbody>
            </table>

            {{-- Page break except for last page --}}
            @if ($pageIndex + 1 < $totalPages)
                <div class="page-break"></div>
            @endif
        @endforeach

    </main>

</body>

</html>
