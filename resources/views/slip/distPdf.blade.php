<!DOCTYPE html>
<html>

<head>
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 40px;
        }

        h1 {
            text-align: center;
            text-transform: uppercase;
            margin-bottom: 30px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th,
        td {
            border: 1px solid #000;
            padding: 8px;
            text-align: center;
        }

        th {
            background-color: #f2f2f2;
        }
    </style>
</head>

<body>
    <h1>{{ $title }}</h1>

    <table>
        <thead>
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
            @php $count = 1; @endphp
            @foreach ($logs as $log)
                @if (!is_null($log->user_department))
                    {{-- Only display rows with valid department --}}
                    <tr>
                        <td>{{ $count++ }}</td>
                        <td>{{ $log->user_department }}</td>
                        <td>{{ \Carbon\Carbon::parse($log->created_at)->format('M d, Y') }}</td>
                        <td>{{ $log->updated_at ? \Carbon\Carbon::parse($log->updated_at)->format('M d, Y') : '---' }}
                        </td>
                        <td>{{ $log->new_destination ?? '---' }}</td>
                        <td>
                            @if ($log->esig_file)
                                <img src="{{ public_path('storage/esignature/' . $log->esig_file) }}"
                                    alt="Electronic Signature" style="width: 80px; height: auto;">
                            @endif
                        </td>

                    </tr>
                @endif
            @endforeach

            @if ($count === 1)
                <tr>
                    <td colspan="6">No records found.</td>
                </tr>
            @endif
        </tbody>

    </table>
</body>

</html>
