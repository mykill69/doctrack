<style>
    .text-font {
        font-family: 'Embassy BT', Arial, sans-serif;
    }

    .table-container {
        margin: 0 auto;
        width: 100%;
        text-align: center;
        /* Centers content inside the container */
    }

    .header-img {
        /*max-width: 100%;*/
        width: 700px;
        height: auto;
        display: block;
        /*border: 1px solid black;
*/
        margin: 0 auto;
        /* Centers the image */
    }

    .to-img {
        width: 400px;
        height: auto;
        display: block;
        /*border: 1px solid black;
*/
        margin: 0 auto;
        /* Centers the image */
        margin-left: 25%;
    }

    /*.reference-slip {
font-family: 'Times New Roman';
letter-spacing: 5px;
}*/
    table {
        width: 80%;
        margin: 0 auto;
        border-collapse: collapse;
        /* Ensures consistent borders */
    }

    th,
    td {
        text-align: center;
        vertical-align: middle;
        padding: 10px;
        /* Adds consistent padding for readability */
    }

    .center-content {
        text-align: center;
    }

    .text-bold {
        font-weight: bold;
    }

    .column {
        float: left;
        width: 50%;
        font-family: 'Embassy BT', Arial, sans-serif;
    }

    .header-img {
        width: 180%;
        height: auto;
        margin-top: -5%;
    }

    footer {
        position: fixed;
        bottom: -80px;
        left: 0;
        right: 0;
        height: 60px;
        text-align: center;
    }

    /* Clear floats after the columns */
    .row:after {
        content: "";
        display: table;
        clear: both;
    }

    .route-number {
        position: absolute;
        top: 0;
        right: 0;
        margin: 0;
        padding: 0;
        font-size: 20px;
        font-family: Verdana, sans-serif;
        font-weight: bold;
        text-align: right;
        width: auto;
    }

    .routed-assign {
        font-family: Verdana, sans-serif;
        text-align: justify;
        margin-left: 14%;
        margin-top: -47%;
        padding: 0;
        line-height: 1.3;
        /*border: 1px solid black;*/
    }

    h1 {
        text-align: center;
        text-transform: uppercase;
        padding: 0;
        font-size: 24px;
        font-family: Verdana, sans-serif;
    }
</style>
{{-- <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet"> --}}
<div class="content-wrapper">
    <div class="card-body">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th colspan="4" style="border-style: none;">
                            <div class="center-content">
                                <img src="{{ public_path('template/img/header_new.png') }}" class="header-img"
                                    alt="Header Image">
                                <h1>Routing Slip</h1>
                            </div>
                            <div class="route-number">
                                <span>{{ $routingSlip->op_ctrl }}</span>
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Original Slip -->
                    <tr style="border-bottom: 1px solid;padding-bottom: 0;font-family: Verdana, sans-serif;">
                        <td colspan="4" style="text-align: left;padding-bottom: 0; bottom: 0;">
                            <p style="font-weight: bold; font-size: 14px;font-style: italic;">From the Office of the
                                University President</p>
                        </td>
                    </tr>

                    <tr>
                        <td colspan="2"></td>
                        <td colspan="2" style="text-align: right;font-family: Verdana, sans-serif;">Date:
                            <u>{{ \Carbon\Carbon::parse($routingSlip->date_received)->format('F d, Y') }}</u>
                        </td>
                    </tr>

                    <tr>
                        <td colspan="2">
                            <img src="{{ public_path('template/img/to.png') }}" class="to-img">
                            <div class="routed-assign">

                                @php
                                    $destinationUser = $users->firstWhere('id', $routingSlip->r_destination);
                                @endphp

                                <p>
                                    @if ($destinationUser)
                                        {{ ucwords(strtolower($destinationUser->fname)) }}
                                        {{ ucwords(strtolower($destinationUser->lname)) }}
                                    @else
                                        {{ $routingSlip->r_destination }}
                                    @endif
                                </p>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td></td>
                    </tr>

                    <tr>
                        <td colspan="2" style="text-align: left;font-family: Verdana, sans-serif;">
                            @foreach (['Appropriate Action', 'Calendar', 'Comment & Recommendation', 'Draft Reply', 'Endorsement'] as $leftItem)
                                @if ($remarks->contains('remarks_dtls', $leftItem))
                                    <div style="padding: 4px;">
                                        @if ($routingSlip->trans_remarks === $leftItem)
                                            <img src="{{ public_path('template/img/square_check.png') }}"
                                                alt="Checkmark" style="width:20px;">
                                        @else
                                            <img src="{{ public_path('template/img/square.png') }}" alt="Unchecked"
                                                style="width:20px;">
                                        @endif
                                        &nbsp; {{ $leftItem }}
                                    </div>
                                @endif
                            @endforeach
                        </td>

                        <td colspan="2" style="text-align: left;font-family: Verdana, sans-serif;">
                            @foreach (['File', 'Information', 'Review/Study', 'See the Office', 'Others'] as $rightItem)
                                @if ($remarks->contains('remarks_dtls', $rightItem))
                                    <div style="padding: 4px;">
                                        @if ($routingSlip->trans_remarks === $rightItem)
                                            <img src="{{ public_path('template/img/square_check.png') }}"
                                                alt="Checkmark" style="width:20px;">
                                        @else
                                            <img src="{{ public_path('template/img/square.png') }}" alt="Unchecked"
                                                style="width:20px;">
                                        @endif
                                        &nbsp; {{ $rightItem }}
                                    </div>
                                @endif
                            @endforeach
                        </td>
                    </tr>

                    <tr>
                        <td colspan="4"
                            style="text-align: left; border-top: 1px solid black; padding: 0; margin: 0;font-family: Verdana, sans-serif;">
                            <span style="margin: 0; padding: 5px;">REMARKS:</span>{{ $routingSlip->other_remarks }}
                        </td>
                    </tr>

                    <tr>
                        <td colspan="4" style="padding: 0; margin: 0;">
                            @if (isset($esig, $esig->user_id, $esig->esig_file, $routingSlip) && $routingSlip->route_status != 1)
                                <div style="display: flex; align-items: center; margin-top: 40%;">
                                    @if ($esig->user_id == 63 || $esig->user_id == 64)
                                        <span style="font-weight: bold;">for:</span>
                                    @endif
                                    <img src="{{ $esig->esig_path }}" alt="Electronic Signature"
                                        style="width: 200px; height: auto; margin-bottom: -20px;">
                                </div>
                            @endif

                            <p style="font-weight:bold; font-size: 18px;font-family: Verdana, sans-serif;"><u>ALADINO C.
                                    MORACA, Ph.D.</u></p>
                            <p style="margin-top: -20px;font-style: italic;font-family: Verdana, sans-serif;">SUC
                                President</p>
                        </td>
                    </tr>

                    <tr style="font-size:13px;font-family: Verdana, sans-serif;">
                        <td style="text-align: center;width: 40%;">
                            <p>Doc Control Code:CPSU-F-QA-23</p>
                        </td>
                        <td colspan="2" style="text-align: left;width: 35%;">
                            <p>Effective Date:09/12/2018</p>
                        </td>
                        <td style="text-align: left;width: 25%;">
                            <p>Page No.: 1 of 1</p>
                        </td>
                    </tr>

                    <!-- Second Page (if re-routed) -->
                    @if ($routingSlip->assigned_to)
                        <div style="page-break-before: always;"></div>

                        <tr style="border-bottom: 1px solid; padding-bottom: 0; font-family: Verdana, sans-serif;">
                            <td colspan="4" style="text-align: left; padding-bottom: 0; bottom: 0;">
                                {{-- Display the reassignUserDept if available --}}
                                @if (!empty($reassignUserDept))
                                    <p style="font-weight: bold; font-size: 14px; font-style: italic;">
                                        From the Office of the {{ $reassignUserDept }}
                                    </p>
                                @endif
                            </td>
                        </tr>

                        <tr>
                            <td colspan="2"></td>
                            <td colspan="2" style="text-align: right; font-family: Verdana, sans-serif;">
                                Date: <u>{{ \Carbon\Carbon::parse($routingSlip->updated_at)->format('F d, Y') }}</u>
                            </td>
                        </tr>

                        <tr>
                            <td colspan="2">
                                <img src="{{ public_path('template/img/to.png') }}" class="to-img">
                                <div class="routed-assign">
                                    @if ($logs->count())
                                        {{-- Display each log's new_destination --}}
                                        @foreach ($logs as $log)
                                            <p style="font-family: Verdana, sans-serif;">{{ $log->new_destination }}
                                            </p>
                                        @endforeach
                                    @elseif (!empty($groupName))
                                        {{-- If no logs, display groupName --}}
                                        <p style="font-family: Verdana, sans-serif;">{{ $groupName }}</p>
                                    @else
                                        <p>&nbsp;</p>
                                    @endif
                                </div>
                            </td>
                        </tr>

                        <tr>
                            <td></td>
                        </tr>

                        <tr>
                            <td colspan="2" style="text-align: left; font-family: Verdana, sans-serif;">
                                @foreach (['Appropriate Action', 'Calendar', 'Comment & Recommendation', 'Draft Reply', 'Endorsement'] as $leftItem)
                                    @if ($remarks->contains('remarks_dtls', $leftItem))
                                        <div style="padding: 4px;">
                                            @if ($routingSlip->trans_remarks === $leftItem)
                                                <img src="{{ public_path('template/img/square_check.png') }}"
                                                    alt="Checkmark" style="width:20px;">
                                            @else
                                                <img src="{{ public_path('template/img/square.png') }}" alt="Unchecked"
                                                    style="width:20px;">
                                            @endif
                                            &nbsp; {{ $leftItem }}
                                        </div>
                                    @endif
                                @endforeach
                            </td>

                            <td colspan="2" style="text-align: left; font-family: Verdana, sans-serif;">
                                @foreach (['File', 'Information', 'Review/Study', 'See the Office', 'Others'] as $rightItem)
                                    @if ($remarks->contains('remarks_dtls', $rightItem))
                                        <div style="padding: 4px;">
                                            @if ($routingSlip->trans_remarks === $rightItem)
                                                <img src="{{ public_path('template/img/square_check.png') }}"
                                                    alt="Checkmark" style="width:20px;">
                                            @else
                                                <img src="{{ public_path('template/img/square.png') }}" alt="Unchecked"
                                                    style="width:20px;">
                                            @endif
                                            &nbsp; {{ $rightItem }}
                                        </div>
                                    @endif
                                @endforeach
                            </td>
                        </tr>

                        <tr>
                            <td colspan="4"
                                style="text-align: left; border-top: 1px solid black; padding: 0; margin: 0; font-family: Verdana, sans-serif;">
                                <span style="margin: 0; padding: 5px;">REMARKS:</span>
                            </td>
                        </tr>

                        <tr>
                            <td colspan="4" style="padding: 0; margin: 0;">
                                {{-- Show e-signature if available and route_status != 1 --}}
                                @if (isset($reassigningUserEsig, $reassigningUserEsig->esig_file, $routingSlip) && $routingSlip->route_status != 1)
                                    <div style="display: flex; align-items: center; margin-top: 35%;">
                                        <img src="{{ public_path('storage/esignature/' . $reassigningUserEsig->esig_file) }}"
                                            alt="Electronic Signature"
                                            style="width: 120px; height: auto; margin-bottom: -20px;">
                                    </div>
                                @endif

                                {{-- Display assigned user's name --}}
                                <p style="font-weight: bold; font-size: 18px; font-family: Verdana, sans-serif;">
                                    <u>{{ $reassigningUser->fname ?? '' }} {{ $reassigningUser->lname ?? '' }}</u>
                                </p>

                                {{-- Display assigned user's department --}}
                                <p style="margin-top: -20px; font-style: italic; font-family: Verdana, sans-serif;">
                                    {{ $reassignUserDept ?? '' }}
                                </p>
                            </td>
                        </tr>

                        <tr style="font-size:13px; font-family: Verdana, sans-serif;">
                            <td style="text-align: center; width: 40%;">
                                <p>Doc Control Code: CPSU-F-QA-23</p>
                            </td>
                            <td colspan="2" style="text-align: left; width: 35%;">
                                <p>Effective Date: 09/12/2018</p>
                            </td>
                            <td style="text-align: left; width: 25%;">
                                <p>Page No.: 1 of 1</p>
                            </td>
                        </tr>
                    @endif

                </tbody>

            </table>
        </div>
    </div>
</div>
