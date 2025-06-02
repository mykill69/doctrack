@extends('layouts.main')
@php
use App\Models\Log; // Ensure you import your Log model here
@endphp
<style>
.select2-container--default .select2-selection--multiple .select2-selection__choice {
background-color: #007bff !important;
border-color: #187744 !important;
color: #fff;
padding: 0 10px;
margin-top: 0.31rem;
}
@font-face {
font-family: 'Embassy BT';
src: url('{{ asset("embassybt_regular.ttf") }}') format('truetype');
}
body {
font-family: 'Embassy BT', Arial, sans-serif;
}
.text-font{
    font-family: 'Embassy BT', Arial, sans-serif;
}

    /* Hide browser footers and page numbers */
    footer, .footer, .page-footer {
        display: none !important;
    }

</style>

@section('body')
<div class="content-wrapper">
    <!-- Main content --><div class="card-body">
    <div class="content" style="padding-top: 1%;">
        <div class="col-md-12">
        <div class="card text-center">
            
            <button onclick="printDiv()" class="btn btn-primary">Print Document</button>
            </div>
        </div>
        
            <div class="col-md-12">
                
                <div class="card" >
                    <div class="card-header text-center">
                    <h1>Document Tracking Slip</h1>
                </div>
                   
                    <div class="card-body d-flex flex-column align-items-center justify-content-center" id="printable-area">
                        <div id="qrcode" class="mb-3"></div>
                        <p><strong>Tracking Code:</strong> <span class="bg-warning">{{ $documentTrack->docslip_id }}</span></p>
                        <p><strong>Personnel:</strong> <span class="bg-warning">{{ $documentTrack->user_name }}</span></p>
                        <p><strong>Type:</strong> <span class="bg-warning">{{ $documentTrack->doc_type }}<span></p>
                        <p><strong>Date Created:</strong> <span class="bg-warning">{{ $documentTrack->created_at }}<span></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
    
                                
<script src="{{ asset('template/plugins/qrcode.min.js') }}"></script>
<script>
    // Ensure the script runs after the page is loaded
    document.addEventListener("DOMContentLoaded", function () {
        // Check if $documentTrack->docslip_id is valid
        if ("{{ $documentTrack->docslip_id }}") {
            var qrcode = new QRCode(document.getElementById("qrcode"), {
                text: "{{ $documentTrack->docslip_id }}", // QR Code content
                width: 200,  // QR Code width
                height: 200  // QR Code height
            });
        } else {
            console.error('Document Slip ID is missing!');
        }
    });

    function printDiv() {
    var printContents = document.getElementById("printable-area").innerHTML;
    var originalContents = document.body.innerHTML;

    var printWindow = window.open('', '', 'height=1000,width=800');
    // printWindow.document.write('<html><head><title>Print Document</title>');
    printWindow.document.write('<style>');
    printWindow.document.write(`
       @media print {
        @page {
            size: auto;
            margin: 0mm; /* Remove margins */
        }
        body {
            visibility: hidden;
        }
        #printable-area, #printable-area * {
            visibility: visible;
        }
        #printable-area {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 0vh; /* Full viewport height */
            position: absolute;
            left: 50%;
            top: 15%;
            transform: translate(-50%, -50%); /* Center content */
            width: 55%;
            max-width: 1000px; /* Optional: limit max width */
            padding: 20px; /* Add some padding around content */
            text-align: center;
            
        }
        #printable-area p {
            padding-left: 10px;
            padding-right: 10px; /* Add padding to both sides of each <p> element */
            margin: 10px 0; /* Optional: Add space between paragraphs */
            font-family: 'Arial', sans-serif; /* Use a standard font for printing */
        }
        footer, .footer, .page-footer {
            display: none !important; /* Ensure footer is hidden */
        }
    }
    `);
    printWindow.document.write('</style></head><body>');
    printWindow.document.write('<div id="printable-area">' + printContents + '</div>');
    printWindow.document.write('</body></html>');

    printWindow.document.close();
    printWindow.focus();
    printWindow.print();
    printWindow.close();
}

</script>
@endsection