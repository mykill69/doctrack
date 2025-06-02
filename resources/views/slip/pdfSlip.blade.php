<style>
@font-face {
font-family: 'Embassy BT';
src: url('{{ public_path("font/embassybt_regular.ttf") }}') format('truetype');
}
.text-font {
font-family: 'Embassy BT', Arial, sans-serif;
}
.table-container {
margin: 0 auto;
width: 100%;
text-align: center; /* Centers content inside the container */
}
.header-img {
/*max-width: 100%;*/
width: 700px;
height: auto;
display: block;
/*border: 1px solid black;
*/margin: 0 auto; /* Centers the image */
}
.to-img{
width: 400px;
height: auto;
display: block;
/*border: 1px solid black;
*/margin: 0 auto; /* Centers the image */
margin-left: 25%;
}
/*.reference-slip {
font-family: 'Times New Roman';
letter-spacing: 5px;
}*/
table {
width: 80%;
margin: 0 auto;
border-collapse: collapse; /* Ensures consistent borders */
}
th, td {
text-align: center;
vertical-align: middle;
padding: 10px; /* Adds consistent padding for readability */
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
/* Clear floats after the columns */
.row:after {
content: "";
display: table;
clear: both;
}
.route-number{
margin-top: -8.5%;
margin-left: 10%;
text-align: right;
padding: 0;
width: 70%;
font-size: 24px;
font-family: Verdana, sans-serif;
font-weight: bold;
}
.routed-assign{
font-family: Verdana, sans-serif;
text-align: justify;
margin-left: 14%;
margin-top: -47%;
padding: 0;
line-height: 2.3;
/*border: 1px solid black;*/
}
</style>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" rel="stylesheet">
<div class="content-wrapper">
    <div class="card-body">
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th colspan="4" style="border-style: none;">
                            <div class="center-content">
                                <img src="{{ public_path('template/img/formHeader2.png') }}" class="header-img" alt="Header Image">
                                
                            </div>
                            <div class="route-number">
                                <span>{{ $routingSlip->rslip_id }}</span>
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    
                    <tr style="border-bottom: 1px solid;padding-bottom: 0;font-family: Verdana, sans-serif;">
                        <td colspan="4" style="text-align: left;padding-bottom: 0; bottom: 0;">
                            <p style="font-weight: bold; font-size: 14px;font-style: italic;">From the Office of the University President</p>
                        </td>
                        
                    </tr>
                    
                    <tr>
                        <td colspan="2"></td>
                        <td colspan="2" style="text-align: right;font-family: Verdana, sans-serif;">Date: <u>{{ \Carbon\Carbon::parse($routingSlip->date_received)->format('m / d / Y') }}</u></td>
                    </tr>
                    <tr>
                        
                        <td colspan="2">
                            <img src="{{ public_path('template/img/to.png') }}" class="to-img">
                            <div class="routed-assign">
                                <p>
                                    @if ($routingSlip->assigned_to == NULL)
                                    {{ $routingSlip->r_destination }}
                                    @else
                                    {{ $routingSlip->r_destination }} and was re-routed to {{ $routingSlip->assigned_to }}
                                    @endif
                                </p>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td></td>
                    </tr>
                    <tr>
                        <!-- Left Side -->
                        <td colspan="2" style="text-align: left;font-family: Verdana, sans-serif;">
                            @foreach ([
                            'Appropriate Action',
                            'Calendar',
                            'Comment & Recommendation',
                            'Draft Reply',
                            'Endorsement',
                            ] as $leftItem)
                            @if ($remarks->contains('remarks_dtls', $leftItem))
                            <div style="padding: 4px;">
                                @if ($routingSlip->trans_remarks === $leftItem)
                                <img src="{{ public_path('template/img/square_check.png') }}" alt="Checkmark" style="width:20px;">
                                @else
                                <img src="{{ public_path('template/img/square.png') }}" alt="Unchecked" style="width:20px;">
                                @endif
                                &nbsp; {{ $leftItem }}
                            </div>
                            @endif
                            @endforeach
                        </td>
                        <!-- Right Side -->
                        <td colspan="2" style="text-align: left;font-family: Verdana, sans-serif;">
                            @foreach ([
                            'File',
                            'Information',
                            'Review/Study',
                            'See the Office',
                            'Others',
                            ] as $rightItem)
                            @if ($remarks->contains('remarks_dtls', $rightItem))
                            <div style="padding: 4px;">
                                @if ($routingSlip->trans_remarks === $rightItem)
                                <img src="{{ public_path('template/img/square_check.png') }}" alt="Checkmark" style="width:20px;">
                                @else
                                <img src="{{ public_path('template/img/square.png') }}" alt="Unchecked" style="width:20px;">
                                @endif
                                &nbsp; {{ $rightItem }}
                            </div>
                            @endif
                            @endforeach
                        </td>
                    </tr>
                    
                    <tr>
                        <td colspan="4" style="text-align: left; border-top: 1px solid black; padding: 0; margin: 0;font-family: Verdana, sans-serif;">
                            <p style="margin: 0; padding: 5px;">REMARKS:</p>
                        </td>
                        
                    </tr>
                    
                    <tr>
                        <td colspan="4" style="padding: 0; margin: 0;">
                            
                            @if($routingSlip->esig)
                            <img src="{{ storage_path('app/documents/' . $routingSlip->esig) }}"  
                                     alt="Electronic Signature" 
                                     style="width: 150px; height: auto; margin-bottom: -40px;margin-top:10%;">
                            @endif
                            <p style="font-weight:bold; font-size: 22px;font-family: Verdana, sans-serif;"><u>ALADINO C. MORACA, Ph.D.</u></p>
                            <p style="margin-top: -20px;font-style: italic;font-family: Verdana, sans-serif;">SUC President</p>
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
                </tbody>
            </table>
        </div>
    </div>
</div>