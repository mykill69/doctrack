<?php

namespace App\Http\Controllers;

use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfReader;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\RoutingSlip;
use App\Models\RouteDocument;
use App\Models\Office;
use App\Models\Document;
use App\Models\Log;
use App\Models\LogsHistory;
use App\Models\AssignLogs;
use App\Models\Remark;


class RoutingSlipController extends Controller
{
    // public function storeSlip(Request $request)
    // {
    // $isSuperUser = auth()->user()->role === 'super_user';
    // $request->validate([
    // 'ctrl_no' => 'required|integer',  // Control number input from the form
    // 'user_id' => 'required|integer',
    // 'source' => 'required|string',
    // 'subject' => 'required|string',
    // 'trans_remarks' => 'nullable|string',
    // 'other_remarks' => 'nullable|string',
    // 'r_destination' => $isSuperUser ? 'required|string' : 'nullable|string',
    // 'route_status' => 'required|string',
    // 'document' => 'required|file|mimes:pdf,doc,docx,xls,xlsx,jpg,png,jpeg',
    // 'date_received' => 'required|string',
    // ]);
    // if ($request->hasFile('document')) {
    // $documentPath = $request->file('document')->store('documents');
    // } else {
    // return redirect()->back()->withErrors(['document' => 'No document file provided.']);
    // }
    // // Get the control number from the input field
    // $rslip_id = $request->input('ctrl_no');
    // $file = $request->file('document');
    // $documentName = str_replace(' ', '_', $file->getClientOriginalName());
    // $documentPath = $file->storeAs('documents', $documentName);
    // RoutingSlip::create([
    // 'rslip_id' => $rslip_id,  // Use control number as rslip_id
    // 'user_id' => $request->input('user_id'),
    // 'source' => $request->input('source'),
    // 'subject' => $request->input('subject'),
    // 'trans_remarks' => $request->input('trans_remarks'),
    // 'other_remarks' => $request->input('other_remarks'),
    // 'r_destination' => $request->input('r_destination'),
    // 'document' => $documentName,
    // 'route_status' => $request->input('route_status'),
    // 'date_received' => $request->input('date_received'),
    // ]);
    // return redirect()->route('viewSlip')->with('success', 'Routing Slip added successfully.');
    // }
//     public function storeSlip(Request $request)
// {
//     $isSuperUser = auth()->user()->role === 'super_user';
    
//     // Validate the incoming request
//     $request->validate([
//         'ctrl_no' => 'required|integer',
//         'user_id' => 'required|integer',
//         'source' => 'required|string',
//         'subject' => 'required|string',
//         'trans_remarks' => 'nullable|string',
//         'other_remarks' => 'nullable|string',
//         'r_destination' => $isSuperUser ? 'required|string' : 'nullable|string',
//         'route_status' => 'required|string',
//         'received_name' => 'required|string',
//         'document' => 'required|file|mimes:pdf,doc,docx,xls,xlsx,jpg,png,jpeg',
//         'date_received' => 'required|string',
//     ]);

//     // Check if the request has a document file
//     if ($request->hasFile('document')) {
//         // Get the file
//         $file = $request->file('document');
        
//         // Sanitize the file name by replacing spaces with underscores
//         $documentName = str_replace(' ', '_', $file->getClientOriginalName());
        
//         // Store the file with the desired name
//         $documentPath = $file->storeAs('documents', $documentName);
//     } else {
//         return redirect()->back()->withErrors(['document' => 'No document file provided.']);
//     }

//     // Get the control number from the input field
//     $rslip_id = $request->input('ctrl_no');

//     // Create a new routing slip record in the database
//     RoutingSlip::create([
//         'rslip_id' => $rslip_id,  // Use control number as rslip_id
//         'user_id' => $request->input('user_id'),
//         'source' => $request->input('source'),
//         'subject' => $request->input('subject'),
//         'trans_remarks' => $request->input('trans_remarks'),
//         'other_remarks' => $request->input('other_remarks'),
//         'r_destination' => $request->input('r_destination'),
//         'document' => $documentName,  // Save the sanitized document name
//         'received_name' => $request->input('received_name'),
//         'route_status' => $request->input('route_status'),
//         'date_received' => $request->input('date_received'),
//     ]);

//     // Redirect back with success message
//     return redirect()->route('viewSlip')->with('success', 'Routing Slip added successfully.');
// }

public function storeSlip(Request $request)
{
    $isSuperUser = auth()->user()->role === 'super_user';

    $request->validate([
        'ctrl_no' => 'required|integer',
        'user_id' => 'required|integer',
        'source' => 'required|string',
        'subject' => 'required|string',
        'trans_remarks' => 'nullable|string',
        'other_remarks' => 'nullable|string',
        'r_destination' => $isSuperUser ? 'required|string' : 'nullable|string',
        'route_status' => 'required|string',
        'received_name' => 'required|string',
        'document' => 'required|file|mimes:pdf,doc,docx,xls,xlsx,jpg,png,jpeg',
        'date_received' => 'required|string',
    ]);

    if ($request->hasFile('document')) {
        $file = $request->file('document');
        $originalName = str_replace(' ', '_', $file->getClientOriginalName());
        $filename = pathinfo($originalName, PATHINFO_FILENAME);
        $extension = $file->getClientOriginalExtension();
        $documentName = $filename . '.' . $extension;
        $documentPath = 'documents/' . $documentName;
        $counter = 1;

        // Check if file exists and rename if necessary
        while (Storage::exists($documentPath)) {
            $documentName = $filename . " (copy $counter)." . $extension;
            $documentPath = 'documents/' . $documentName;
            $counter++;
        }

        // Save the file
        $file->storeAs('documents', $documentName);

        // === Start Stamping Process ===
        if ($file->getClientOriginalExtension() === 'pdf') {
            $fullDocumentPath = storage_path('app/' . $documentPath); // Path to uploaded PDF
            $stampPath = storage_path('app/stamps/received_records.png'); // Path to stamp image
        
            $pdf = new Fpdi();
            $pageCount = $pdf->setSourceFile($fullDocumentPath);
        
            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $templateId = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($templateId);
        
                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($templateId);
        
                // Add stamp ONLY to the first page
                if ($pageNo == 1) {
                    $stampWidth = 65;  // adjust width
                    $stampHeight = 18; // adjust height
                    $x = 130;          // adjust X position
                    $y = 10;           // adjust Y position
                    $pdf->Image($stampPath, $x, $y, $stampWidth, $stampHeight);


// Format date
$dateReceived = Carbon::parse($request->input('date_received'))->format('M d, Y');
$dateReceived = strtoupper($dateReceived);

$receivedName = $request->input('received_name');
$slipId = $request->input('ctrl_no');

// Set font and darker aqua green color for received name
$pdf->SetFont('Arial', '', 8);
$pdf->SetTextColor(0, 139, 139); // Darker aqua green

$textX = $x + 20;
$textY = $y + 13;

$pdf->Text($textX, $textY, $receivedName);

// Set black font for slip ID
$pdf->SetTextColor(0, 0, 0); // Black

$slipIdX = $textX + 27; // Shift right to avoid sticking
$pdf->Text($slipIdX, $textY, "$slipId");

// Draw the date below
$pdf->SetTextColor(0, 139, 139); // Aqua green again for date
$pdf->Text($textX, $textY + 4, $dateReceived);
                }
            }
        
            // Save the stamped PDF (overwrite original)
            $pdf->Output('F', $fullDocumentPath);
        }
        // === End Stamping Process ===

    } else {
        return redirect()->back()->withErrors(['document' => 'No document file provided.']);
    }

    $rslip_id = $request->input('ctrl_no');

    RoutingSlip::create([

        'rslip_id' => $rslip_id,
        'user_id' => $request->input('user_id'),
        'source' => $request->input('source'),
        'subject' => $request->input('subject'),
        'trans_remarks' => $request->input('trans_remarks'),
        'other_remarks' => $request->input('other_remarks'),
        'r_destination' => $request->input('r_destination'),
        'document' => $documentName,
        'received_name' => $request->input('received_name'),
        'route_status' => $request->input('route_status'),
        'date_received' => $request->input('date_received'),
    ]);

    return redirect()->route('viewSlip')->with('success', 'Routing Slip added and stamped successfully.');
}


    public function viewSlip()
    {
    $userId = auth()->user()->id;
    $userDepartment = auth()->user()->department;
    $logs = Log::where('user_id', $userId)->get();
    // Count routing slips
    $routingSlipCount = ($logs->every(fn($log) => $log->status_update != 3)) ? RoutingSlip::where('route_status', 3)->count() : 0;
    $superUserCount = auth()->user()->hasRole('super_user') ? RoutingSlip::where('route_status', 1)->count() : 0;
    $recordsOfficerCount = auth()->user()->hasRole('records_officer') ? RoutingSlip::where('route_status', 2)->count() : 0;
    $routingSlips = RoutingSlip::all();
    $offices = Office::all();
    return view('slip.routingSlip', compact('routingSlips','routingSlipCount','superUserCount', 'offices','recordsOfficerCount'));
    }

    public function viewPdfslip($id)
    {
    $document = RoutingSlip::findOrFail($id);
    $routingSlips = RoutingSlip::all();
    $filePath = storage_path('app/documents/' . $document->document);
    if (file_exists($filePath)) {
        // Set filename in download
        $filename = $document->document; // This should be the original file name stored

        return response()->file($filePath, [
            'Content-Disposition' => 'inline; filename="' . $filename . '"'
        ]);
    } else {
        return redirect()->back()->with('error', 'File not found.');
    }
}

    public function slipForm($id)
{
    // Fetch the routing slip record based on the given ID
    $routingSlip = DB::table('routing_slip')->where('rslip_id', $id)->first();

    // Fetch all remarks from the remarks table
    $remarks = Remark::all();

    // Fetch other related data if needed (e.g., document info, users, etc.)
    $relatedDocuments = DB::table('documents')->where('route_id', $id)->get();

    $recordsOfficerCount = auth()->user()->hasRole('records_officer') ? RoutingSlip::where('route_status', 2)->count() : 0;

    $superUserCount = auth()->user()->hasRole('super_user') ? RoutingSlip::where('route_status', 1)->count() : 0;

    return view('slip.slipForm', compact('remarks', 'routingSlip', 'relatedDocuments', 'recordsOfficerCount', 'superUserCount'));
}

public function pdfSlip($id)
{
    // Fetch the routing slip record based on the given ID
    $routingSlip = DB::table('routing_slip')->where('rslip_id', $id)->first();

    // Fetch all remarks from the remarks table
    $remarks = Remark::all();

    // Fetch other related data if needed (e.g., document info, users, etc.)
    $relatedDocuments = DB::table('documents')->where('route_id', $id)->get();

    // Render the PDF content using the Blade view
    $pdf = Pdf::loadView('slip.pdfSlip', compact('remarks', 'routingSlip', 'relatedDocuments'));

    // Return the PDF as a stream (to view in the browser)
    return $pdf->stream('routing-slip.pdf');
}

    // public function deletePdf($id)
    // {
    // $document = RoutingSlip::findOrFail($id);
    // $document->delete();
    // return redirect()->back()->with('success', 'File deleted successfully.');
    // }
    public function destroy($id)
{
    // Retrieve the routing slip by ID
    $routingSlips = RoutingSlip::find($id);

    // Check if the routing slip exists
    if (!$routingSlips) {
        return redirect()->back()->with('error', 'Routing slip not found.');
    }

    // Build the full file path
    $filePath = 'documents/' . $routingSlips->document; // Ensure file_name has the correct relative path

    // Delete the file from the storage folder
    if (Storage::exists($filePath)) {
        Storage::delete($filePath);
    }

    // Delete the routing slip record from the database
    $routingSlips->delete();

    return redirect()->back()->with('success', 'Routing slip and its file deleted successfully.');
}


    public function editSlip($id)
    {
    $userId = auth()->user()->id;
    $userDepartment = auth()->user()->department;
    $routingSlips = RoutingSlip::findOrFail($id);
    $logs = Log::where('user_id', $userId)->get();
    $routingSlipCount = ($logs->every(fn($log) => $log->status_update != 3)) ? RoutingSlip::where('route_status', 3)->count() : 0;
    $superUserCount = auth()->user()->hasRole('super_user') ? RoutingSlip::where('route_status', 1)->count() : 0;
    $recordsOfficerCount = auth()->user()->hasRole('records_officer') ? RoutingSlip::where('route_status', 2)->count() : 0;
    return view('slip.editSlip', compact('routingSlips','routingSlipCount','superUserCount','recordsOfficerCount'));
    }

    public function updateSlip(Request $request, $id)
{
    $request->validate([
        'op_ctrl'         => 'required|integer',
        'user_id'         => 'required|integer',
        'pres_dept'       => 'nullable|string',
        'subject'         => 'required|string',
        'trans_remarks'   => 'required|string',
        'other_remarks'   => 'nullable|string',
        'r_destination'   => 'nullable|string',
        'route_status'    => 'required|string',
        'esig'            => 'nullable|file|mimes:pdf,doc,docx,jpeg,png,jpg,gif',
        // 'received_name'   => 'required|string',
        // 'date_received'   => 'required|date',
        // 'ctrl_no'         => 'required|string',
    ]);

    $routingSlip = RoutingSlip::findOrFail($id);

    // === Start Stamping Process ===
    if ($routingSlip->document && str_ends_with(strtolower($routingSlip->document), '.pdf')) {
        $documentPath = 'documents/' . $routingSlip->document;
        $fullDocumentPath = storage_path('app/' . $documentPath); // Full path to PDF

        if (file_exists($fullDocumentPath)) {
            $stampPath = storage_path('app/stamps/PRESIDENT_1.png'); // Stamp path

            $pdf = new Fpdi();
            $pageCount = $pdf->setSourceFile($fullDocumentPath);

            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                $templateId = $pdf->importPage($pageNo);
                $size = $pdf->getTemplateSize($templateId);

                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($templateId);

                if ($pageNo == 1) {
                    $stampWidth = 65;
                    $stampHeight = 18;
                    $x = 130;
                    $y = 10 + 25.4;

                    $pdf->Image($stampPath, $x, $y, $stampWidth, $stampHeight);

                    $dateReceived = strtoupper(Carbon::parse($request->input('date_received'))->format('M d, Y'));
                    $receivedName = $request->input('received_name');
                    $slipId = $request->input('ctrl_no');

                    $pdf->SetFont('Arial', '', 8);
                    $pdf->SetTextColor(0, 139, 139);
                    $textX = $x + 20;
                    $textY = $y + 13;
                    $pdf->Text($textX, $textY, $receivedName);

                    $pdf->SetTextColor(0, 0, 0);
                    $pdf->Text($textX + 27, $textY, "$slipId");

                    $pdf->SetTextColor(0, 139, 139);
                    $pdf->Text($textX, $textY + 4, $dateReceived);
                }
            }

            $pdf->Output('F', $fullDocumentPath); // Overwrite original
        }
    }
    // === End Stamping Process ===

    // Handle e-signature upload
    if ($request->hasFile('esig')) {
        // Delete the old e-signature file if exists
        if ($routingSlip->esig && \Storage::exists('documents/' . $routingSlip->esig)) {
            \Storage::delete('documents/' . $routingSlip->esig);
        }

        // Store the new e-signature file
        $file = $request->file('esig');
        $filename = time() . '_' . $file->getClientOriginalName();
        $file->storeAs('documents', $filename);

        // Update the routing slip with the new e-signature filename
        $routingSlip->esig = $filename;
    }

    // Update routing slip fields
    $routingSlip->op_ctrl        = $request->input('op_ctrl');
    $routingSlip->user_id        = $request->input('user_id');
    $routingSlip->pres_dept      = $request->input('pres_dept');
    $routingSlip->subject        = $request->input('subject');
    $routingSlip->trans_remarks  = $request->input('trans_remarks');
    $routingSlip->other_remarks  = $request->input('other_remarks');
    $routingSlip->r_destination  = $request->input('r_destination');
    $routingSlip->route_status   = $request->input('route_status');
    $routingSlip->save();

    return redirect()->route('viewSlip')->with('success', 'Routing Slip CTRL#' . $routingSlip->rslip_id . ' updated and stamped successfully.');
}

    public function editDest($id)
    {
    $userId = auth()->user()->id;
    $userDepartment = auth()->user()->department;
    $routingSlips = RoutingSlip::findOrFail($id);
    $offices = Office::all();
    $logs = Log::where('user_id', $userId)->get();
    $routingSlipCount = ($logs->every(fn($log) => $log->status_update != 3)) ? RoutingSlip::where('route_status', 3)->count() : 0;
    $superUserCount = auth()->user()->hasRole('super_user') ? RoutingSlip::where('route_status', 1)->count() : 0;
    $recordsOfficerCount = auth()->user()->hasRole('records_officer') ? RoutingSlip::where('route_status', 2)->count() : 0;
    return view('slip.editDest', compact('routingSlips','offices','routingSlipCount','superUserCount','recordsOfficerCount'));
    }

    public function storeRouteDoc(Request $request)
{
    $validatedData = $request->validate([
        'doc_type' => 'required|string',
        'full_name' => 'required|string',
        'subject' => 'required|string',
        'file_name' => 'required|string',
        'purpose' => 'nullable|string',
        'department' => 'required|string',
        'for_to' => 'required|string',
        'doc_stat' => 'required|integer',
        'user_id' => 'required|integer',
        'route_id' => 'required|integer',
    ]);

    $document = new Document();
    $document->doc_type = $validatedData['doc_type'];
    $document->full_name = $validatedData['full_name'];
    $document->subject = $validatedData['subject'];
    $document->file_name = $validatedData['file_name'];
    $document->purpose = $validatedData['purpose'];
    $document->department = $validatedData['department'];
    $document->for_to = $validatedData['for_to'];
    $document->doc_stat = $validatedData['doc_stat'];
    $document->user_id = $validatedData['user_id'];
    $document->route_id = $validatedData['route_id'];
    $document->save();

    $destinationFields = ['destination_1', 'destination_2', 'destination_3', 'destination_4', 'destination_5', 'destination_6', 'destination_7', 'destination_8', 'destination_9', 'destination_10'];
    
    $routeDocument = RouteDocument::where('route_id', $document->route_id)->first();

    if ($routeDocument) {
        foreach ($destinationFields as $field) {
            if (is_null($routeDocument->{$field}) || empty($routeDocument->{$field})) {
                if ($request->has($field)) {
                    foreach ($request->input($field) as $destination) {
                        $routeDocument->{$field} = $destination;

                        $log = Log::create([
                            'user_id' => auth()->user()->id,
                            'doc_id' => $document->id,
                            'route_id' => $document->route_id,
                            'action' => 'Added new destination',
                            'status_update' => $document->doc_stat,
                            'prev_file' => null,
                            'new_file' => $document->file_name,
                            'new_destination' => $destination,
                            'created_at' => now(),
                        ]);

                        // Insert into logs_history
                        LogsHistory::create([
                            
                            'doc_id' => $document->id,
                            'action' => $log->action,
                            'status_update' => $log->status_update
                        ]);
                    }
                    $routeDocument->save();
                    break;
                }
            }
        }
    } else {
        $routeDocument = new RouteDocument();
        $routeDocument->route_id = $document->route_id;

        foreach ($destinationFields as $field) {
            if ($request->has($field)) {
                foreach ($request->input($field) as $destination) {
                    $routeDocument->{$field} = $destination;

                    $log = Log::create([
                        'user_id' => auth()->user()->id,
                        'doc_id' => $document->id,
                        'route_id' => $document->route_id,
                        'action' => 'Added new destination',
                        'status_update' => $document->doc_stat,
                        'prev_file' => null,
                        'new_file' => $document->file_name,
                        'new_destination' => $destination,
                        'created_at' => now(),
                    ]);

                    // Insert into logs_history
                    LogsHistory::create([
                        
                        'doc_id' => $document->id,
                        'action' => $log->action,
                        'status_update' => $log->status_update
                    ]);
                }
            }
        }

        $routeDocument->save();
    }

    $routingSlips = RoutingSlip::where('rslip_id', $validatedData['route_id'])->first();
    if ($routingSlips) {
        $routingSlips->route_status = 3;
        $routingSlips->save();
    }

    return redirect()->route('dashboard')->with('success', 'Document with CTRL#' . $routingSlips->rslip_id . ' was created successfully.');
}


public function updateAssign(Request $request, $routeId)
{
    $request->validate([
        'assigned_to' => 'required|string',
    ]);

    $document = Document::where('route_id', $routeId)->first();

    if ($document) {
        $routingSlip = RoutingSlip::where('rslip_id', $document->route_id)->first();

        if ($routingSlip) {
            $assignedTo = $request->input('assigned_to');
            $assignCom = $request->input('assign_com'); 
            $routingSlip->route_status = 2;
            $routingSlip->assigned_to = $assignedTo; 
            $routingSlip->assign_com = $assignCom;
            $routingSlip->save();
            
            Log::where('route_id', $routingSlip->rslip_id)
                ->where('doc_id', $document->id)
                ->update([
                    'assigned_to' => $assignedTo, 
                    'status_update' => 2, 
                ]);

            Document::where('route_id', $routingSlip->rslip_id)
                ->update(['assn_code' => 1]);

            $newDestination = $document->destination ?? auth()->user()->department;

            Log::where('route_id', $routingSlip->rslip_id)
                ->where('doc_id', $document->id)
                ->update([
                    'new_destination' => $newDestination,
                    'new_user' => auth()->user()->id, 
                ]);

            AssignLogs::updateOrCreate(
                [
                    'doc_id' => $document->id,
                    'route_id' => $routingSlip->rslip_id, 
                ],
                [
                    'new_user' => auth()->user()->id,
                    'assn_code' => 1,
                    'assigned_to' => $assignedTo, 
                ]
            );

            // Insert into logs_history table
            LogsHistory::create([
                'doc_id' => $document->id,
                'action' => 'Added new destination',
                'status_update' => 2,
            ]);

            return redirect()->route('dashboard')->with('success', 'Routing Slip CTRL#' . $routingSlip->rslip_id . ' updated successfully.');
        } else {
            return back()->withErrors(['Routing slip not found']);
        }
    } else {
        return back()->withErrors(['Document not found']);
    }
}


    public function editAssign($id)
{
    $userId = auth()->user()->id;
    $userDepartment = auth()->user()->department;

    // Find the routing slip based on the ID
    $routingSlips = RoutingSlip::findOrFail($id);
    $offices = Office::all();

    // Retrieve the document with left joins
    $document = Document::leftJoin('routing_slip', 'routing_slip.rslip_id', '=', 'documents.route_id')
        ->leftJoin('logs', 'logs.route_id', '=', 'documents.route_id')
        ->where('routing_slip.id', $id)
        ->select('documents.*') // Select only document columns
        ->first();

    $logs = Log::where('user_id', $userId)->get();
    $routingSlipCount = ($logs->every(fn($log) => $log->status_update != 3)) ? RoutingSlip::where('route_status', 3)->count() : 0;
    $superUserCount = auth()->user()->hasRole('super_user') ? RoutingSlip::where('route_status', 1)->count() : 0;
    $recordsOfficerCount = auth()->user()->hasRole('records_officer') ? RoutingSlip::where('route_status', 2)->count() : 0;

    return view('slip.editAssign', compact('routingSlips', 'offices', 'routingSlipCount', 'superUserCount', 'recordsOfficerCount', 'document'));
}


    public function updateReroute(Request $request, $id)
    {

    $routingSlip = RoutingSlip::find($id);

    $destinations = [];

    foreach ($request->all() as $key => $value) {
    if (Str::startsWith($key, 'destination_') && !empty($value)) {
    $destinations[] = $value;
    }
    }

    $routingSlip->update([
    'route_status' => 3,
    ]);

    $document = Document::where('route_id', $routingSlip->rslip_id)->first();
    if ($document) {

    foreach ($destinations as $index => $destination) {

    $document->update([
    'destination_' . ($index + 1) => $destination, 
    ]);
    }

    $document->refresh();
    $document->assn_code = null; 
    $document->save();
    }

    foreach ($destinations as $destination) {

    if (is_array($destination)) {
    $destination = implode(', ', $destination);
    }

    $existingLog = Log::where('route_id', $routingSlip->rslip_id)
    ->where('doc_id', $document->id)
    ->where('new_destination', $destination)
    ->first();

    if (!$existingLog) {
    Log::create([
    'user_id'       => auth()->id(),
    'doc_id'        => $document->id,
    'route_id'      => $routingSlip->rslip_id,
    'action'        => 'Added new destination',
    'new_destination' => $destination, 
    'status_update' => 2,
    'new_file'      => $document->file_name,
    'assigned_to'   => $routingSlip->assigned_to,
    'created_at'    => now(),
    ]);
    }
    }

    $routeDocument = RouteDocument::where('route_id', $routingSlip->rslip_id)->first(); 
    if ($routeDocument) {

    $existingDestinations = [];
    for ($i = 1; $i <= 10; $i++) {
    $existingDestinations[] = $routeDocument->{'destination_' . $i};
    }

    foreach ($destinations as $destination) {

    if (is_array($destination)) {

    $destination = implode(', ', $destination);
    }

    $cleanedDestination = trim(str_replace(['[', ']', '"'], '', $destination));

    if (!in_array($cleanedDestination, $existingDestinations)) {

    for ($i = 1; $i <= 10; $i++) {
    if (empty($routeDocument->{'destination_' . $i})) {
    $routeDocument->{'destination_' . $i} = $cleanedDestination;
    break; 
    }
    }
    }
    }

    $routeDocument->save();
    }

    return redirect()->route('viewSlip')->with('success', 'Document rerouted successfully!');
    }
}
