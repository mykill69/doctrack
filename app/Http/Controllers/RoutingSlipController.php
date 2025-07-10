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
use App\Models\User;
use App\Mail\DocumentRoutedNotification;
use Illuminate\Support\Facades\Mail;


class RoutingSlipController extends Controller
{
   

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
        'document' => 'required|file|mimes:pdf',
        'file_stamp' => 'required|file|mimes:jpg,jpeg,png',
        'date_received' => 'required|string',
    ]);

    // Store the uploaded PDF
    $pdfFile = $request->file('document');
    $pdfName = $pdfFile->getClientOriginalName();
    $pdfFile->storeAs('documents', $pdfName);

    // Store the uploaded stamp image
$stampFile = $request->file('file_stamp');
$stampName = $stampFile->getClientOriginalName(); // keep the original filename
$stampFile->storeAs('stamps', $stampName); // this will overwrite if file already exists

    // Save to DB without modifying PDF
    RoutingSlip::create([
        'rslip_id' => $request->ctrl_no,
        'user_id' => $request->user_id,
        'source' => $request->source,
        'subject' => $request->subject,
        'trans_remarks' => $request->trans_remarks,
        'other_remarks' => $request->other_remarks,
        'r_destination' => $request->r_destination,
        'document' => $pdfName,
        'received_name' => $request->received_name,
        'route_status' => $request->route_status,
        'date_received' => $request->date_received,
    ]);

    return redirect()->route('viewSlip')->with('success', 'PDF and stamp uploaded successfully.');
}




public function viewSlip()
{
    /** @var \App\Models\User $user */
    $user = auth()->user(); // hint to IDE

    $userId = $user->id;
    $userDepartment = $user->department;
    $logs = Log::where('user_id', $userId)->get();

    // Count routing slips
    $routingSlipCount = ($logs->every(fn($log) => $log->status_update != 3))
        ? RoutingSlip::where('route_status', 3)->count()
        : 0;

    $superUserCount = $user->hasRole('super_user') ? RoutingSlip::where('route_status', 1)->count() : 0;
    $recordsOfficerCount = $user->hasRole('records_officer') ? RoutingSlip::where('route_status', 2)->count() : 0;

    $routingSlips = RoutingSlip::all();
    $offices = Office::all();

    return view('slip.routingSlip', compact(
        'routingSlips',
        'routingSlipCount',
        'superUserCount',
        'offices',
        'recordsOfficerCount'
    ));
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
    /** @var \App\Models\User $user */
    $user = auth()->user();

    // Fetch the routing slip record based on the given ID
    $routingSlip = DB::table('routing_slip')->where('rslip_id', $id)->first();

    // Fetch all remarks from the remarks table
    $remarks = Remark::all();

    // Fetch related documents
    $relatedDocuments = DB::table('documents')->where('route_id', $id)->get();

    $recordsOfficerCount = $user->hasRole('records_officer') ? RoutingSlip::where('route_status', 2)->count() : 0;
    $superUserCount = $user->hasRole('super_user') ? RoutingSlip::where('route_status', 1)->count() : 0;

    return view('slip.slipForm', compact(
        'remarks',
        'routingSlip',
        'relatedDocuments',
        'recordsOfficerCount',
        'superUserCount'
    ));
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
    /** @var \App\Models\User $user */
    $user = auth()->user(); // help Intelephense understand the type

    $userId = $user->id;
    $userDepartment = $user->department;

    $routingSlips = RoutingSlip::findOrFail($id);
    $logs = Log::where('user_id', $userId)->get();

    $routingSlipCount = ($logs->every(fn($log) => $log->status_update != 3))
        ? RoutingSlip::where('route_status', 3)->count()
        : 0;
 $superUserCount = $userId === 'super_user' ? RoutingSlip::where('route_status', 1)->count() : 0;
$recordsOfficerCount = $userId === 'records_officer' ? RoutingSlip::where('route_status', 2)->count() : 0;


    return view('slip.editSlip', compact(
        'routingSlips',
        'routingSlipCount',
        'superUserCount',
        'recordsOfficerCount'
    ));
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
        'file_stamp'      => 'nullable|file|mimes:jpg,jpeg,png',
        'received_name'   => 'required|array',
        'received_name.*' => 'required|string',
    ]);

    $routingSlip = RoutingSlip::findOrFail($id);

    // Handle new e-signature upload
    if ($request->hasFile('esig')) {
        if ($routingSlip->esig && Storage::exists('documents/' . $routingSlip->esig)) {
            Storage::delete('documents/' . $routingSlip->esig);
        }

        $esig = $request->file('esig');
        $esigName = $esig->getClientOriginalName();
        $esig->storeAs('documents', $esigName);
        $routingSlip->esig = $esigName;
    }
// Handle file stamp upload (no renaming, replace if already exists)
if ($request->hasFile('file_stamp')) {
    $stampFile = $request->file('file_stamp');
    $stampName = $stampFile->getClientOriginalName();

    // Delete if it already exists
    if (Storage::exists('stamps/' . $stampName)) {
        Storage::delete('stamps/' . $stampName);
    }

    // Save the new stamp (replace old one with same name)
    $stampFile->storeAs('stamps', $stampName);
}

    // Update other fields
    $routingSlip->op_ctrl        = $request->input('op_ctrl');
    $routingSlip->user_id        = $request->input('user_id');
    $routingSlip->pres_dept      = $request->input('pres_dept');
    $routingSlip->subject        = $request->input('subject');
    $routingSlip->trans_remarks  = $request->input('trans_remarks');
    $routingSlip->other_remarks  = $request->input('other_remarks');
    $routingSlip->r_destination  = $request->input('r_destination');
    $routingSlip->route_status   = $request->input('route_status');

    // Merge received names
    $existingNames = $routingSlip->received_name ? explode(',', $routingSlip->received_name) : [];
    $newNames = array_map('trim', $request->input('received_name'));
    $mergedNames = array_unique(array_merge($existingNames, $newNames));
    $routingSlip->received_name = implode(', ', $mergedNames);

    $routingSlip->save();

    return redirect()->route('viewSlip')->with('success', 'Routing Slip CTRL#' . $routingSlip->rslip_id . ' updated successfully.');
}


    public function editDest($id)
{
    $user = auth()->user(); // Full user object
    $userId = $user->id;
    $userDepartment = $user->department;
    $userRole = $user->role;

    $routingSlips = RoutingSlip::findOrFail($id);
    $users = User::select('id', 'fname', 'lname')->get();
    $logs = Log::where('user_id', $userId)->get();

    $routingSlipCount = ($logs->every(fn($log) => $log->status_update != 3))
        ? RoutingSlip::where('route_status', 3)->count()
        : 0;

    $superUserCount = ($userRole === 'super_user')
        ? RoutingSlip::where('route_status', 1)->count()
        : 0;

    $recordsOfficerCount = ($userRole === 'records_officer')
        ? RoutingSlip::where('route_status', 2)->count()
        : 0;

    return view('slip.editDest', compact(
    'routingSlips',
    'users', // changed from 'offices'
    'routingSlipCount',
    'superUserCount',
    'recordsOfficerCount'
));
}

    public function storeRouteDoc(Request $request)
{
    $validatedData = $request->validate([
        'doc_type'     => 'required|string',
        'full_name'    => 'required|string',
        'subject'      => 'required|string',
        'file_name'    => 'required|string',
        'purpose'      => 'nullable|string',
        'department'   => 'required|string',
        'for_to'       => 'required|string',
        'doc_stat'     => 'required|integer',
        'user_id'      => 'required|integer',
        'route_id'     => 'required|integer',
        'routed_users'    => 'required|array',
        'routed_users.*'  => 'required|string',
    ]);

    // Save to documents table
    $document = new Document();
    $document->doc_type   = $validatedData['doc_type'];
    $document->full_name  = $validatedData['full_name'];
    $document->subject    = $validatedData['subject'];
    $document->file_name  = $validatedData['file_name'];
    $document->purpose    = $validatedData['purpose'];
    $document->department = $validatedData['department'];
    $document->for_to     = $validatedData['for_to'];
    $document->doc_stat   = $validatedData['doc_stat'];
    $document->user_id    = $validatedData['user_id'];
    $document->route_id   = $validatedData['route_id'];
    $document->save();

    // Save routed users as a string (comma-separated) to routing_slip
    $routedUsers = implode(', ', $validatedData['routed_users']);
    $routingSlip = RoutingSlip::where('rslip_id', $validatedData['route_id'])->first();
    if ($routingSlip) {
        $routingSlip->routed_users = $routedUsers;
        $routingSlip->route_status = 3;
        $routingSlip->save();
    }

    // ✅ ADD EMAIL & LOGIC HERE
    foreach ($validatedData['routed_users'] as $destination) {
    // Save log
    $log = Log::create([
        'user_id'         => auth()->user()->id,
        'doc_id'          => $document->id,
        'route_id'        => $document->route_id,
        'action'          => 'Added new destination',
        'status_update'   => $document->doc_stat,
        'prev_file'       => null,
        'new_file'        => $document->file_name,
        'new_destination' => $destination,
        'created_at'      => now(),
    ]);

    LogsHistory::create([
        'doc_id'        => $document->id,
        'action'        => $log->action,
        'status_update' => $log->status_update
    ]);

    // Get the user email by full name
    $userRecipient = \App\Models\User::whereRaw("CONCAT(fname, ' ', lname) = ?", [$destination])->first();

    // Send email notification
    if ($userRecipient && $userRecipient->email) {
        Mail::to($userRecipient->email)->send(
            new DocumentRoutedNotification($document, $destination, $routingSlip->trans_remarks)
        );
    }
}

    return redirect()->route('dashboard')->with('success', 'Document with CTRL#' . $routingSlip->rslip_id . ' was created successfully.');
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

            // Update logs - include assign_com as comments
            Log::where('route_id', $routingSlip->rslip_id)
                ->where('doc_id', $document->id)
                ->update([
                    'assigned_to'   => $assignedTo,
                    'status_update' => 2,
                    'new_user'      => auth()->user()->id,
                    'comments'      => $assignCom, // <-- add this line
                ]);

            // Update document status
            Document::where('route_id', $routingSlip->rslip_id)
                ->update(['assn_code' => 1]);

            // Update or create AssignLogs
            AssignLogs::updateOrCreate(
                [
                    'doc_id'    => $document->id,
                    'route_id'  => $routingSlip->rslip_id,
                ],
                [
                    'new_user'     => auth()->user()->id,
                    'assn_code'    => 1,
                    'assigned_to'  => $assignedTo,
                ]
            );

            // Log history
            LogsHistory::create([
                'doc_id'        => $document->id,
                'action'        => 'Added new destination',
                'status_update' => 2,
            ]);

            $redirectUrl = $request->input('redirectUrl', route('dashboard'));

            return redirect($redirectUrl)->with('success', 'Routing Slip CTRL#' . $routingSlip->rslip_id . ' updated successfully.');
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

    $routingSlips = RoutingSlip::findOrFail($id);
    $offices = Office::all();
    $users = User::all(); // 👈 Add this

    $document = Document::leftJoin('routing_slip', 'routing_slip.rslip_id', '=', 'documents.route_id')
        ->leftJoin('logs', 'logs.route_id', '=', 'documents.route_id')
        ->where('routing_slip.id', $id)
        ->select('documents.*')
        ->first();

    $logs = Log::where('user_id', $userId)->get();
    $routingSlipCount = ($logs->every(fn($log) => $log->status_update != 3)) ? RoutingSlip::where('route_status', 3)->count() : 0;
    $superUserCount = $userId === 'super_user' ? RoutingSlip::where('route_status', 1)->count() : 0;
    $recordsOfficerCount = $userId === 'records_officer' ? RoutingSlip::where('route_status', 2)->count() : 0;

    return view('slip.editAssign', compact(
        'routingSlips',
        'offices',
        'routingSlipCount',
        'superUserCount',
        'recordsOfficerCount',
        'document',
        'users' // 👈 Pass the users to the view
    ));
}



public function updateReroute(Request $request, $id)
{
    $routingSlip = RoutingSlip::findOrFail($id);

    // Get selected user IDs from the form
    $selectedUserIds = $request->input('new_destination', []);

    // Update routed_users column with full names for display (optional)
    $userNames = \App\Models\User::whereIn('id', $selectedUserIds)
                    ->get()
                    ->pluck('full_name') // Assuming you have an accessor. Otherwise, use ->map(fn($u) => $u->fname . ' ' . $u->lname)
                    ->toArray();

    $routingSlip->update([
        'routed_users' => implode(', ', $userNames),
        'route_status' => 3,
    ]);

    // Find the related document
    $document = Document::where('route_id', $routingSlip->rslip_id)->first();

    if ($document) {
        $document->assn_code = null;
        $document->save();

        // Log each user routed
        foreach ($selectedUserIds as $userId) {
            $user = \App\Models\User::find($userId);
            if (!$user) continue;

            $fullName = $user->fname . ' ' . $user->lname;

            $existingLog = Log::where('route_id', $routingSlip->rslip_id)
                ->where('doc_id', $document->id)
                ->where('new_destination', $fullName)
                ->first();

            if (!$existingLog) {
                Log::create([
                    'user_id'         => $user->id,                  // ✅ Save correct user ID
                    'doc_id'          => $document->id,
                    'route_id'        => $routingSlip->rslip_id,
                    'action'          => 'Added new destination',
                    'new_destination' => $fullName,                  // ✅ Save readable name
                    'status_update'   => 2,
                    'new_file'        => $document->file_name,
                    'assigned_to'     => $routingSlip->assigned_to,
                    'created_at'      => now(),
                ]);
            }
        }
    }

    return redirect()->route('viewSlip')->with('success', 'Document rerouted successfully!');
}


}
