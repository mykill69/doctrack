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
</style>
@section('body')
<div class="content-wrapper">
    
    <!-- Main content -->
    <div class="content" style="padding-top: 1%;">
        <div class="container-fluid">
            <div class="row">
                <div class="card-body">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">ROUTING SLIP FORM</h3>
                            </div>
                            <div class="card-body" style="font-family:">
                               
                           <iframe src="{{ route('pdfSlip', ['id' => $routingSlip->rslip_id]) }}" width="100%" height="850" style="border: none;"></iframe>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection