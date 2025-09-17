 <style>
     body {
         font-family: Arial, Helvetica, sans-serif;
     }

     table.dashboardTable {
         font-family: Arial, Helvetica, sans-serif;
         font-size: 0.8rem;
         width: 100%;
         border: 1px solid black;
         border-collapse: collapse;
         /* ✅ merge borders */
     }

     table.dashboardTable th,
     table.dashboardTable td {
         border: 1px solid black;
         /* ✅ border for each cell */
         padding: 2px;
         text-align: left;
     }

     table.dashboardTable td,
     table.dashboardTable th {
         padding: 8px;
     }

     .center-content {
         text-align: center;
     }

     .header-img {
         width: 60%;
         height: auto;
         display: inline-block;
         margin-top: -2%;
     }
 </style>


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
                             </div>
                         </th>
                     </tr>
                     <tr>
                         <th colspan="4" style="border-style: none;">DOCUMENT LOGBOOK</th>
                     </tr>
                 </thead>

             </table>

             <table id="dashboardTable" class="dashboardTable" style="font-size: 0.8rem; padding-top: 1%;">
                 <thead>
                     <tr>
                         <th style="width: 5%;">CTRL #</th>
                         <th>DATE RECEIVED</th>
                         <th style="width: 15%;">SOURCE</th>
                         <th style="width: 20%;">SUBJECT MATTER</th>
                         <th>ACTION UNIT</th>
                         <th>RECEIVED BY/DATE</th>
                         <th>ACTION TAKEN</th>
                         <th>DATE RELEASED</th>
                         <th>REMARKS</th>
                         <th>STORAGE</th>
                     </tr>
                 </thead>
                 <tbody>
                     @php
                         $processedLogs = [];
                         $logsToShow = [];
                         $currentUserDepartment = auth()->user()->department;
                         $currentUserId = auth()->user()->id;
                     @endphp

                     {{-- Collect logs --}}
                     @foreach ($logs as $log)
                         @php
                             $document = $log->document;
                             $uniqueIdentifier = $log->route_id . '-' . $log->doc_id . '-' . $log->new_destination;

                             if (
                                 isset($processedLogs[$uniqueIdentifier]) &&
                                 $processedLogs[$uniqueIdentifier]['hasNewUser']
                             ) {
                                 continue;
                             }

                             if (!is_null($log->new_user)) {
                                 $processedLogs[$uniqueIdentifier] = ['hasNewUser' => true];
                                 $logsToShow[$uniqueIdentifier] = $log;
                             } else {
                                 if (!isset($processedLogs[$uniqueIdentifier])) {
                                     $processedLogs[$uniqueIdentifier] = ['hasNewUser' => false];
                                     $logsToShow[$uniqueIdentifier] = $log;
                                 }
                             }

                             if ($currentUserDepartment === $log->new_destination) {
                                 $logsToShow[$uniqueIdentifier] = $log;
                             }
                         @endphp
                     @endforeach

                     {{-- Show actual logs --}}
                     @php $rowCount = 0; @endphp
                     @foreach ($logsToShow as $log)
                         @php
                             $document = $log->document;
                             $rowCount++;
                         @endphp
                         <tr>
                             <td data-order="{{ $log->route_id == 0 ? 0 : $log->route_id }}">
                                 {{ $log->route_id == 0 ? 'N/A' : $log->route_id }}
                             </td>

                             <td>
                                 {{ optional($document->routingSlip)->date_received
                                     ? \Carbon\Carbon::parse($document->routingSlip->date_received)->format('F d, Y')
                                     : ($document->created_at
                                         ? \Carbon\Carbon::parse($document->created_at)->format('F d, Y')
                                         : 'N/A') }}
                             </td>

                             <td>{{ optional($document->routingSlip)->source ?? ($document->department ?? 'N/A') }}</td>
                             <td>{{ optional($document->routingSlip)->subject ?? ($document->subject ?? 'N/A') }}</td>
                             <td>{{ optional($document->routingSlip)->pres_dept ?? 'N/A' }}</td>
                             <td>{{ optional($document->routingSlip)->updated_at ? $document->routingSlip->updated_at->format('F j, Y') : 'N/A' }}
                             </td>

                             <td>
                                 @if ($log->routingSlip)
                                     <strong class="text-danger">
                                         {{ ucwords(strtolower($log->routingSlip->r_destination)) }}
                                     </strong>
                                 @endif
                                 @if ($log->assigned_to != null)
                                     , was re-assigned to
                                     <strong class="text-danger">
                                         {{ ucwords(strtolower($log->assigned_to)) }}
                                     </strong>
                                 @endif
                             </td>

                             <td>{{ $document->created_at->format('m-d-Y h:i:s A') }}</td>

                             <td style="font-size:10px;">
                                 @if (!empty($document->routingSlip->trans_remarks))
                                     <span class="badge badge-success" style="font-size:10px; display: block;">
                                         {{ $document->routingSlip->trans_remarks }}
                                     </span>
                                 @endif

                                 @if (!empty($document->routingSlip->other_remarks))
                                     <span class="badge badge-danger" style="font-size:10px; display: block;">
                                         {{ $document->routingSlip->other_remarks }}
                                     </span>
                                 @endif

                                 @php
                                     $comment = $log->comments ?? '';
                                     $wrappedComment = preg_replace('/((?:\S+\s+){4})/', '$1<br>', $comment);
                                 @endphp
                                 @if (!empty($comment))
                                     <span class="badge badge-warning"
                                         style="margin-top: 2px; font-size:10px; max-width: 150px; display: inline-block; word-wrap: break-word; white-space: normal;">
                                         {!! $wrappedComment !!}
                                     </span>
                                 @endif
                             </td>

                             <td></td>
                         </tr>
                     @endforeach

                     {{-- Add empty rows if less than 15 --}}
                     @for ($i = $rowCount; $i < 15; $i++)
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
     </div>
 </div>
