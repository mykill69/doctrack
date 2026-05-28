@extends('layouts.main')
@section('body')
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
        .no-left-radius { border-top-left-radius: 0; border-bottom-left-radius: 0; }
        .disabled-icon {
            color: lightgrey; pointer-events: none; opacity: 0.2; transition: opacity 0.3s;
        }
        .disabled-icon:hover { opacity: 0.4; cursor: not-allowed; box-shadow: 0 0 5px rgba(0,0,0,0.3); }
        .nav-tabs .nav-link.active {
            background-color: #ffc107 !important;
            color: #212529 !important;
            font-weight: bold;
        }
    </style>

    <div class="content-wrapper">
        <div class="content" style="padding-top: 1%;">
            <div class="container-fluid">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h3 class="card-title">List of Routing Slips</h3>
                        </div>
                        <div class="card-body">
                            <div class="card card-success card-outline">

                                <div class="card-header p-2">
                                    @php $role = auth()->user()->role; @endphp

                                    @if ($role !== 'staff')
                                        <ul class="nav nav-tabs" id="routingTabs" role="tablist">
                                            @if ($role === 'super_user')
                                                <!-- Super User: Only 1 Tab -->
                                                <li class="nav-item">
                                                    <a class="nav-link active" href="#routed1" data-toggle="tab">
                                                        Routed to President
                                                    </a>
                                                </li>
                                            @else
                                                <!-- Other roles: Original tabs -->
                                                @php
                                                    $tabs = [
                                                        'routed2' => 'Routed back to Records',
                                                        'routed1' => 'Routed to President',
                                                        'enroute' => 'Pending',
                                                    ];
                                                @endphp
                                                @foreach ($tabs as $tabId => $label)
                                                    <li class="nav-item">
                                                        <a class="nav-link {{ $loop->first ? 'active' : '' }}" 
                                                           href="#{{ $tabId }}" data-toggle="tab">
                                                            {{ $label }}
                                                        </a>
                                                    </li>
                                                @endforeach
                                            @endif
                                        </ul>
                                    @endif
                                </div>

                                <div class="card-body">
                                    <div class="tab-content">
                                        @if ($role === 'super_user')
                                            <!-- Super User Single Tab -->
                                            <div class="tab-pane fade show active" id="routed1">
                                                @include('partials.routing_slip_table', ['tabId' => 'routed1'])
                                            </div>
                                        @else
                                            <!-- Multiple Tabs for others -->
                                            @foreach (['routed2' => 'Routed back to Records', 'routed1' => 'Routed to President', 'enroute' => 'Pending'] as $tabId => $label)
                                                <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" id="{{ $tabId }}">
                                                    @include('partials.routing_slip_table', ['tabId' => $tabId])
                                                </div>
                                            @endforeach
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    @include('modal.addRoutslip')
    @include('modal.addDestination')




    
@endsection