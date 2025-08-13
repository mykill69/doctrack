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
use App\Models\Esig;
use App\Models\Office;
use App\Models\Document;
use App\Models\Log;
use App\Models\LogsHistory;
use App\Models\AssignLogs;
use App\Models\Remark;
use App\Models\User;
use App\Models\Doctrack;
use App\Mail\DocumentRoutedNotification;
use Illuminate\Support\Facades\Mail;
use App\Models\Group;


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
        // 'file_stamp' => 'required|file|mimes:jpg,jpeg,png',
        'date_received' => 'required|string',
    ]);

    // Store the uploaded PDF
    $pdfFile = $request->file('document');
    $pdfName = $pdfFile->getClientOriginalName();
    $pdfFile->storeAs('documents', $pdfName);

//     // Store the uploaded stamp image
// $stampFile = $request->file('file_stamp');
// $stampName = $stampFile->getClientOriginalName(); // keep the original filename
// $stampFile->storeAs('stamps', $stampName); // this will overwrite if file already exists

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


    
    return redirect()->route('viewSlip')->with('success', 'Routing slip was created successfully.');
}




public function viewSlip()
{

    $user = auth()->user();

    $userRole = $user->id;
    $userDepartment = $user->department;
    $logs = Log::where('user_id', $userRole)->get();

    $routingSlipCount = ($logs->every(fn($log) => $log->status_update != 3)) 
        ? RoutingSlip::where('route_status', 3)->count() 
        : 0;

    $superUserCount = $userRole === 'super_user' 
        ? RoutingSlip::where('route_status', 1)->count() 
        : 0;

    $recordsOfficerCount = $userRole === 'records_officer' 
        ? RoutingSlip::where('route_status', 2)->count() 
        : 0;

    $routingSlips = RoutingSlip::all();
    $offices = Office::all();

    // ✅ Add Doctrack Count
    $userFullName = $user->fname . ' ' . $user->lname;

    $documentTrack = Doctrack::where(function ($query) use ($userRole, $userFullName) {
            $query->where('user_id', $userRole)
                  ->orWhere('update_by', $userRole)
                  ->orWhere('user_name', $userFullName);
        })->get();

    $doctrackCount = $documentTrack->count();

    return view('slip.routingSlip', compact(
        'routingSlips',
        'routingSlipCount',
        'superUserCount',
        'offices',
        'recordsOfficerCount',
        'doctrackCount' // 👈 Pass to view
    ));
}

public function viewPdfslip($id)
{
    $document = RoutingSlip::findOrFail($id);
    $filePath = storage_path('app/documents/' . $document->document);

    if (file_exists($filePath)) {
        return response()->file($filePath, [
            'Content-Disposition' => 'inline; filename="' . $document->document . '"'
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
    $users = User::select('id', 'fname', 'lname')->get();

    $recordsOfficerCount = $user->hasRole('records_officer') ? RoutingSlip::where('route_status', 2)->count() : 0;
    $superUserCount = $user->hasRole('super_user') ? RoutingSlip::where('route_status', 1)->count() : 0;

    return view('slip.slipForm', compact(
        'remarks',
        'routingSlip',
        'relatedDocuments',
        'recordsOfficerCount',
        'superUserCount',
        'users'
    ));
}


// public function pdfSlip($id)
// {
//     $routingSlip = DB::table('routing_slip')->where('rslip_id', $id)->first();
//     $remarks = Remark::all();
//     $relatedDocuments = DB::table('documents')->where('route_id', $id)->get();

//     // 👇 Get e-signature of user_id 38
//     $esig = Esig::where('user_id', 119)->first();

//     // Send all data to view
//     $pdf = Pdf::loadView('slip.pdfSlip', compact('remarks', 'routingSlip', 'relatedDocuments', 'esig'));

//     return $pdf->stream('routing-slip.pdf');
// }



// commented on 08-02-2025

// public function pdfSlip($id)
// {
//     $routingSlip = DB::table('routing_slip')->where('rslip_id', $id)->first();
//     $remarks = Remark::all();
//     $relatedDocuments = DB::table('documents')->where('route_id', $id)->get();

//     // Get e-signature of user_id 119
//     $esig = Esig::where('user_id', 38)->first();

//     // Convert esig_file path to absolute for PDF use
//     if ($esig && $esig->esig_file) {
//         $esig->esig_path = public_path('storage/esignature/' . $esig->esig_file);
//     }

//     $pdf = Pdf::loadView('slip.pdfSlip', compact('remarks', 'routingSlip', 'relatedDocuments', 'esig'));

//     return $pdf->stream('routing-slip.pdf');
// }

public function pdfSlip($id)
{
    $routingSlip = DB::table('routing_slip')->where('rslip_id', $id)->first();
    $remarks = Remark::all();
    $relatedDocuments = DB::table('documents')->where('route_id', $id)->get();

    // Logs with action 're-assigned' or 'Acknowledged' and new_destination not null
    $logs = DB::table('logs')
        ->where('route_id', $id)
        ->whereIn('action', ['re-assigned', 'Acknowledged'])
        ->whereNotNull('new_destination')
        ->get();

    // Get department of user who re-assigned
    $reassignUserDept = DB::table('logs')
        ->join('assign_logs', 'logs.route_id', '=', 'assign_logs.route_id')
        ->join('users', 'assign_logs.new_user', '=', 'users.id')
        ->where('logs.route_id', $id)
        ->whereIn('logs.action', ['re-assigned', 'Acknowledged'])
        ->select('users.department')
        ->orderByDesc('assign_logs.id')
        ->value('department');

    // Get user who re-assigned
    $reassigningUser = DB::table('logs')
        ->join('assign_logs', 'logs.route_id', '=', 'assign_logs.route_id')
        ->join('users', 'assign_logs.new_user', '=', 'users.id')
        ->where('logs.route_id', $id)
        ->whereIn('logs.action', ['re-assigned', 'Acknowledged'])
        ->select('users.id', 'users.fname', 'users.lname', 'users.department')
        ->orderByDesc('assign_logs.id')
        ->first();

    $groupName = null; // default null

    // Only if no logs found, try to get groupName by assigned_to
    if ($logs->isEmpty()) {
        $assignedTo = DB::table('logs')
            ->where('route_id', $id)
            ->whereNotNull('assigned_to')
            ->orderByDesc('id')
            ->value('assigned_to');

        if ($assignedTo) {
            $group = \App\Models\Group::where('group_name', $assignedTo)->first();

            if ($group) {
                $groupName = $group->group_name;

                // --- NEW: Fetch user info & dept and esig related to this group ---

                // Let's assume your Group model has a user_id or some link to user:
                // If not, replace this with the appropriate way to get a user for this group.
                $userForGroup = \App\Models\User::where('department', $groupName)->first();

                if ($userForGroup) {
                    $reassigningUser = $userForGroup;
                    $reassignUserDept = $userForGroup->department;

                    // Get esignature for that user
                    $reassigningUserEsig = Esig::where('user_id', $userForGroup->id)->first();
                } else {
                    // If no user found, nullify to avoid showing stale info
                    $reassigningUser = null;
                    $reassignUserDept = null;
                    $reassigningUserEsig = null;
                }
            }
        }
    } else {
        // If logs exist, still get the esig for current $reassigningUser (existing logic)
        $reassigningUserEsig = Esig::where('user_id', $reassigningUser->id ?? null)->first();
    }

    // Determine e-signature based on president department (existing)
    $esigUserId = null;
    if ($routingSlip->pres_dept === "PRESIDENT'S OFFICE") {
        $esigUserId = 38;
    } elseif ($routingSlip->pres_dept === 'VPAF') {
        $esigUserId = 63;
    } elseif ($routingSlip->pres_dept === 'VPAA') {
        $esigUserId = 64;
    }

    $esig = null;
    if ($esigUserId) {
        $esig = Esig::where('user_id', $esigUserId)->first();
        if ($esig && $esig->esig_file) {
            $esig->esig_path = public_path('storage/esignature/' . $esig->esig_file);
        }
    }

    // Pass data to view including $groupName and user info
    $pdf = Pdf::loadView('slip.pdfSlip', compact(
        'remarks',
        'routingSlip',
        'relatedDocuments',
        'esig',
        'logs',
        'reassignUserDept',
        'reassigningUser',
        'reassigningUserEsig',
        'groupName'
    ));

    return $pdf->stream('routing-slip.pdf');
}


// 08/13/2025

// public function pdfSlip($id)
// {
//     $routingSlip = DB::table('routing_slip')->where('rslip_id', $id)->first();
//     $remarks = Remark::all();
//     $relatedDocuments = DB::table('documents')->where('route_id', $id)->get();

//     // Get logs with action 're-assigned' and new_destination is not null
//     $logs = DB::table('logs')
//     ->where('route_id', $id)
//     ->whereIn('action', ['re-assigned', 'Acknowledged'])
//     ->whereNotNull('new_destination')
//     ->get();

//     // Get the department of the user who re-assigned the form
//     $reassignUserDept = DB::table('logs')
//     ->join('assign_logs', 'logs.route_id', '=', 'assign_logs.route_id')
//     ->join('users', 'assign_logs.new_user', '=', 'users.id')
//     ->where('logs.route_id', $id)
//     ->whereIn('logs.action', ['re-assigned', 'Acknowledged'])
//     ->select('users.department')
//     ->orderByDesc('assign_logs.id') // Get latest assign if multiple
//     ->value('department');


//        $reassigningUser = DB::table('logs')
//     ->join('assign_logs', 'logs.route_id', '=', 'assign_logs.route_id')
//     ->join('users', 'assign_logs.new_user', '=', 'users.id')
//     ->where('logs.route_id', $id)
//     ->whereIn('logs.action', ['re-assigned', 'Acknowledged'])
//     ->select('users.id', 'users.fname', 'users.lname', 'users.department')
//     ->orderByDesc('assign_logs.id')
//     ->first();


//     $reassigningUserEsig = Esig::where('user_id', $reassigningUser->id ?? null)->first();

//     // Determine e-signature based on department
//     $esigUserId = null;
//     if ($routingSlip->pres_dept === "PRESIDENT'S OFFICE") {
//         $esigUserId = 38;
//     } elseif ($routingSlip->pres_dept === 'VPAF') {
//         $esigUserId = 63;
//     } elseif ($routingSlip->pres_dept === 'VPAA') {
//         $esigUserId = 64;
//     }

//     $esig = null;
//     if ($esigUserId) {
//         $esig = Esig::where('user_id', $esigUserId)->first();
//         if ($esig && $esig->esig_file) {
//             $esig->esig_path = public_path('storage/esignature/' . $esig->esig_file);
//         }
//     }

//     // Pass department to view
//     $pdf = Pdf::loadView('slip.pdfSlip', compact(
//         'remarks', 
//         'routingSlip', 
//         'relatedDocuments', 
//         'esig', 
//         'logs', 
//         'reassignUserDept',
//         'reassigningUser',
//         'reassigningUserEsig'
//     ));

//     return $pdf->stream('routing-slip.pdf');
// }




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
    $users = User::select('id', 'fname', 'lname')->get();

    return view('slip.editSlip', compact(
        'routingSlips',
        'routingSlipCount',
        'superUserCount',
        'recordsOfficerCount',
        'users'

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
        'r_destination' => 'nullable|array',
        'r_destination.*' => 'string',
        'route_status'    => 'required|string',
        'received_name'   => 'required|array',
        'received_name.*' => 'required|string',
        'document'        => 'nullable|file|mimes:pdf',
    ]);

    $routingSlip = RoutingSlip::findOrFail($id);

    // Handle document update
    if ($request->hasFile('document')) {
        // Delete old document if exists
        if ($routingSlip->document && Storage::exists('documents/' . $routingSlip->document)) {
            Storage::delete('documents/' . $routingSlip->document);
        }

        $pdfFile = $request->file('document');
        $pdfName = $pdfFile->getClientOriginalName();
        $pdfFile->storeAs('documents', $pdfName);
        $routingSlip->document = $pdfName;
    }

    // Update other fields
    $routingSlip->op_ctrl        = $request->input('op_ctrl');
    $routingSlip->user_id        = $request->input('user_id');
    $routingSlip->pres_dept      = $request->input('pres_dept');
    $routingSlip->subject        = $request->input('subject');
    $routingSlip->trans_remarks  = $request->input('trans_remarks');
    $routingSlip->other_remarks  = $request->input('other_remarks');
    $routingSlip->r_destination = implode(',', $request->input('r_destination', []));
    $routingSlip->route_status   = $request->input('route_status');

    // Merge received names
    $existingNames = $routingSlip->received_name ? explode(',', $routingSlip->received_name) : [];
    $newNames = array_map('trim', $request->input('received_name'));
    $mergedNames = array_unique(array_merge($existingNames, $newNames));
    $routingSlip->received_name = implode(', ', $mergedNames);

    $routingSlip->save();

    return redirect()->route('viewSlip')->with('success', 'Routing Slip CTRL#' . $routingSlip->rslip_id . ' updated successfully.');
}

public function routeBackToPresident($id)
{
    $routingSlip = RoutingSlip::findOrFail($id);

    // Reset fields to NULL
    $routingSlip->op_ctrl        = null;
    $routingSlip->trans_remarks  = null;
    $routingSlip->other_remarks  = null;
    $routingSlip->r_destination  = null;
    // $routingSlip->received_name  = null; // Assuming received_name is a column

    $routingSlip->route_status = 1;
    $routingSlip->save();

    // Assuming you can get the related document ID from routing slip
     $documentId = $routingSlip->id; // Make sure doc_id exists in your routing_slip table

    // Insert into logs_history
    LogsHistory::create([
        'doc_id'        => $documentId,
        'action'        => 'Routed back to Edit',
        'status_update' => 4
    ]);

    return redirect()->route('viewSlip')->with('success', 'Routing Slip routed back to the President.');
}


    public function editDest($id)
{
    $user = auth()->user(); // Full user object
    $userId = $user->id;
    $userDepartment = $user->department;
    $userRole = $user->role;
    $groups = Group::all();

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
    'recordsOfficerCount',
    'groups'
));
}


public function storeRouteDoc(Request $request)
{
    $validatedData = $request->validate([
        'doc_type'        => 'required|string',
        'full_name'       => 'required|string',
        'subject'         => 'required|string',
        'file_name'       => 'required|string',
        'purpose'         => 'nullable|string',
        'department'      => 'required|string',
        'for_to'          => 'required|string',
        'doc_stat'        => 'required|integer',
        'user_id'         => 'required|integer',
        'route_id'        => 'required|integer',
        'routed_users'    => 'required|array',
        'routed_users.*'  => 'required|string',
    ]);

    $document = Document::create([
        'doc_type'   => $validatedData['doc_type'],
        'full_name'  => $validatedData['full_name'],
        'subject'    => $validatedData['subject'],
        'file_name'  => $validatedData['file_name'],
        'purpose'    => $validatedData['purpose'],
        'department' => $validatedData['department'],
        'for_to'     => $validatedData['for_to'],
        'doc_stat'   => $validatedData['doc_stat'],
        'user_id'    => $validatedData['user_id'],
        'route_id'   => $validatedData['route_id'],
    ]);

    $routingSlip = RoutingSlip::where('rslip_id', $validatedData['route_id'])->first();
    $finalDestinations = [];

    foreach ($validatedData['routed_users'] as $destination) {
        if (Str::startsWith($destination, 'position:')) {
            $positionId = (int) Str::after($destination, 'position:');
            $users = User::where('position', $positionId)->get();

        } elseif (Str::startsWith($destination, 'group:')) {
    $groupName = Str::after($destination, 'group:');
    $group = Group::where('group_name', $groupName)->first();

    if (!$group) continue;

    $users = $group->users; // ✅ Fetch users via pivot

        } else {
            // Individual
            $user = User::whereRaw("CONCAT(fname, ' ', lname) = ?", [$destination])->first();
            $users = collect($user ? [$user] : []);
        }

        foreach ($users as $user) {
            if (!$user) continue;

            $fullName = $user->fname . ' ' . $user->lname;

            if (!in_array($fullName, $finalDestinations)) {
                $finalDestinations[] = $fullName;

                $log = Log::create([
                    'user_id'         => auth()->id(),
                    'doc_id'          => $document->id,
                    'route_id'        => $document->route_id,
                    'action'          => 'uploaded',
                    'status_update'   => $document->doc_stat,
                    'prev_file'       => null,
                    'new_file'        => $document->file_name,
                    'new_destination' => $fullName,
                    'created_at'      => now(),
                ]);

                LogsHistory::create([
                    'doc_id'        => $document->id,
                    'action'        => $log->action,
                    'status_update' => $log->status_update
                ]);

                if ($user->email) {
                    Mail::to($user->email)->send(
                        new DocumentRoutedNotification($document, $fullName, $routingSlip?->trans_remarks)
                    );
                }
            }
        }
    }

    if ($routingSlip) {
        $routingSlip->routed_users = implode(', ', $finalDestinations);
        $routingSlip->route_status = 3;
        $routingSlip->save();
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
                'action'        => 're-assigned',
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



// public function updateReroute(Request $request, $id)
// {
//     $routingSlip = RoutingSlip::findOrFail($id);

//     // Get selected user IDs from the form
//     $selectedUserIds = $request->input('new_destination', []);

//     // Update routed_users column with full names for display (optional)
//     $userNames = \App\Models\User::whereIn('id', $selectedUserIds)
//         ->get()
//         ->pluck('full_name') // If accessor exists
//         // ->map(fn($u) => $u->fname . ' ' . $u->lname) // Use this if no accessor
//         ->toArray();

//     $routingSlip->update([
//         'routed_users' => implode(', ', $userNames),
//         'route_status' => 3,
//     ]);

//     // Find the related document
//     $document = Document::where('route_id', $routingSlip->rslip_id)->first();

//     if ($document) {
//         $document->assn_code = null;
//         $document->save();

//         // Log each user routed
//         foreach ($selectedUserIds as $userId) {
//             $user = \App\Models\User::find($userId);
//             if (!$user) continue;

//             $fullName = $user->fname . ' ' . $user->lname;

//             $existingLog = Log::where('route_id', $routingSlip->rslip_id)
//                 ->where('doc_id', $document->id)
//                 ->where('new_destination', $fullName)
//                 ->first();

//             if (!$existingLog) {
//                 // Create log
//                 Log::create([
//                     'user_id'         => auth()->user()->id,
//                     'doc_id'          => $document->id,
//                     'route_id'        => $routingSlip->rslip_id,
//                     'action'          => 're-assigned',
//                     'new_destination' => $fullName,
//                     'status_update'   => 2,
//                     'new_file'        => $document->file_name,
//                     'assigned_to'     => $routingSlip->assigned_to,
//                     'created_at'      => now(),
//                 ]);

//                    // Send email notification if email is available
//                 if (!empty($user->email)) {
//                     Mail::to($user->email)->send(
//                         new DocumentRoutedNotification($document, $fullName, $routingSlip->trans_remarks)
//                     );
//                 }
//             }
//         }
//     }

//     return redirect()->route('viewSlip')->with('success', 'Document rerouted successfully!');
// }


// public function updateReroute(Request $request, $id)
// {
//     $routingSlip = RoutingSlip::findOrFail($id);
//     $selectedDestinations = $request->input('new_destination', []);
//     $finalDestinations = [];

//     $document = Document::where('route_id', $routingSlip->rslip_id)->first();

//     if ($document) {
//         $document->assn_code = null;
//         $document->save();
//     }

//     foreach ($selectedDestinations as $destination) {
//         // Handle by position
//         if (Str::startsWith($destination, 'position:')) {
//             $positionId = (int) Str::after($destination, 'position:');
//             $users = User::where('position', $positionId)->get();

//         // Handle by group
//         } elseif (Str::startsWith($destination, 'group:')) {
//             $groupName = Str::after($destination, 'group:');
//             $group = Group::where('group_name', $groupName)->first();
//             if (!$group) continue;

//             $users = $group->users;

//         // Handle by individual (assumes "Full Name")
//         } else {
//             $user = User::whereRaw("CONCAT(fname, ' ', lname) = ?", [$destination])->first();
//             $users = collect($user ? [$user] : []);
//         }

//         foreach ($users as $user) {
//             if (!$user) continue;

//             $fullName = $user->fname . ' ' . $user->lname;

//             // Prevent duplicate logs/notifications
//             if (in_array($fullName, $finalDestinations)) {
//                 continue;
//             }

//             $finalDestinations[] = $fullName;

//             $existingLog = Log::where('route_id', $routingSlip->rslip_id)
//                 ->where('doc_id', $document?->id)
//                 ->where('new_destination', $fullName)
//                 ->first();

//             if (!$existingLog) {
//                 Log::create([
//                     'user_id'         => auth()->id(),
//                     'doc_id'          => $document?->id,
//                     'route_id'        => $routingSlip->rslip_id,
//                     'action'          => 're-assigned',
//                     'new_destination' => $fullName,
//                     'status_update'   => 2,
//                     'new_file'        => $document?->file_name,
//                     'assigned_to'     => $routingSlip->assigned_to,
//                     'created_at'      => now(),
//                 ]);

//                 // Email Notification
//                 if (!empty($user->email)) {
//                     Mail::to($user->email)->send(
//                         new DocumentRoutedNotification($document, $fullName, $routingSlip->trans_remarks)
//                     );
//                 }
//             }
//         }
//     }

//     // Update routing slip
//     $routingSlip->update([
//         'routed_users' => implode(', ', $finalDestinations),
//         'route_status' => 3,
//     ]);

//     return redirect()->route('viewSlip')->with('success', 'Document rerouted successfully!');
// }

// public function updateReroute(Request $request, $id)
// {
//     $routingSlip = RoutingSlip::findOrFail($id);

//     // Get selected user IDs from the form
//     $selectedUserIds = $request->input('new_destination', []);

//     // Update routed_users column with full names for display (optional)
//     $userNames = \App\Models\User::whereIn('id', $selectedUserIds)
//         ->get()
//         ->pluck('full_name') // If accessor exists
//         // ->map(fn($u) => $u->fname . ' ' . $u->lname) // Use this if no accessor
//         ->toArray();

//     $routingSlip->update([
//         'routed_users' => implode(', ', $userNames),
//         'route_status' => 3,
//     ]);

//     // Find the related document
//     $document = Document::where('route_id', $routingSlip->rslip_id)->first();

//     if ($document) {
//         $document->assn_code = null;
//         $document->save();

//         // Log each user routed
//         foreach ($selectedUserIds as $userId) {
//             $user = \App\Models\User::find($userId);
//             if (!$user) continue;

//             $fullName = $user->fname . ' ' . $user->lname;

//             $existingLog = Log::where('route_id', $routingSlip->rslip_id)
//                 ->where('doc_id', $document->id)
//                 ->where('new_destination', $fullName)
//                 ->first();

//             if (!$existingLog) {
//                 // Create log
//                 Log::create([
//                     'user_id'         => auth()->user()->id,
//                     'doc_id'          => $document->id,
//                     'route_id'        => $routingSlip->rslip_id,
//                     'action'          => 're-assigned',
//                     'new_destination' => $fullName,
//                     'status_update'   => 2,
//                     'new_file'        => $document->file_name,
//                     'assigned_to'     => $routingSlip->assigned_to,
//                     'created_at'      => now(),
//                 ]);

//                    // Send email notification if email is available
//                 if (!empty($user->email)) {
//                     Mail::to($user->email)->send(
//                         new DocumentRoutedNotification($document, $fullName, $routingSlip->trans_remarks)
//                     );
//                 }
//             }
//         }
//     }

//     return redirect()->route('viewSlip')->with('success', 'Document rerouted successfully!');
// }


// 08-11-2025 update

// public function updateReroute(Request $request, $id)
// {
//     $routingSlip = RoutingSlip::findOrFail($id);
//     $selectedDestinations = $request->input('new_destination', []);
//     $finalDestinations = [];

//     $document = Document::where('route_id', $routingSlip->rslip_id)->first();

//     if ($document) {
//         $document->assn_code = null;
//         $document->save();
//     }

//     foreach ($selectedDestinations as $destination) {
//         // Handle by position
//         if (Str::startsWith($destination, 'position:')) {
//             $positionId = (int) Str::after($destination, 'position:');
//             $users = User::where('position', $positionId)->get();

//         // Handle by individual (assumes "Full Name")
//         } else {
//             $user = User::whereRaw("CONCAT(fname, ' ', lname) = ?", [$destination])->first();
//             $users = collect($user ? [$user] : []);
//         }

//         foreach ($users as $user) {
//             if (!$user) continue;

//             $fullName = $user->fname . ' ' . $user->lname;

//             // Prevent duplicate logs/notifications
//             if (in_array($fullName, $finalDestinations)) {
//                 continue;
//             }

//             $finalDestinations[] = $fullName;

//             $existingLog = Log::where('route_id', $routingSlip->rslip_id)
//                 ->where('doc_id', $document?->id)
//                 ->where('new_destination', $fullName)
//                 ->first();

//             if (!$existingLog) {
//                 Log::create([
//                     'user_id'         => auth()->id(),
//                     'doc_id'          => $document?->id,
//                     'route_id'        => $routingSlip->rslip_id,
//                     'action'          => 're-assigned',
//                     'new_destination' => $fullName,
//                     'status_update'   => 2,
//                     'new_file'        => $document?->file_name,
//                     'assigned_to'     => $routingSlip->assigned_to,
//                     'created_at'      => now(),
//                 ]);

//                 // Email Notification
//                 if (!empty($user->email)) {
//                     Mail::to($user->email)->send(
//                         new DocumentRoutedNotification($document, $fullName, $routingSlip->trans_remarks)
//                     );
//                 }
//             }
//         }
//     }

//     // Update routing slip
//     $routingSlip->update([
//         'routed_users' => implode(', ', $finalDestinations),
//         'route_status' => 3,
//     ]);

//     return redirect()->route('viewSlip')->with('success', 'Document rerouted successfully!');
// }


public function updateReroute(Request $request, $id)
{
    $routingSlip = RoutingSlip::findOrFail($id);
    $selectedDestinations = $request->input('new_destination', []);
    $finalDestinations = [];

    $document = Document::where('route_id', $routingSlip->rslip_id)->first();

    if ($document) {
        $document->assn_code = null;
        $document->save();
    }

    foreach ($selectedDestinations as $destination) {
        // Handle by position
        if (Str::startsWith($destination, 'position:')) {
            $positionId = (int) Str::after($destination, 'position:');
            $users = User::where('position', $positionId)->get();

        // Handle by group
        } elseif (Str::startsWith($destination, 'group:')) {
            $groupName = Str::after($destination, 'group:');
            $group = Group::where('group_name', $groupName)->first();

            if (!$group) {
                continue;
            }

            $users = $group->users; // Pivot relationship

        // Handle by individual full name
        } else {
            $user = User::whereRaw("CONCAT(fname, ' ', lname) = ?", [$destination])->first();
            $users = collect($user ? [$user] : []);
        }

        foreach ($users as $user) {
            if (!$user) continue;

            $fullName = $user->fname . ' ' . $user->lname;

            // Prevent duplicate logs/notifications
            if (in_array($fullName, $finalDestinations)) {
                continue;
            }

            $finalDestinations[] = $fullName;

            $existingLog = Log::where('route_id', $routingSlip->rslip_id)
                ->where('doc_id', $document?->id)
                ->where('new_destination', $fullName)
                ->first();

            if (!$existingLog) {
                Log::create([
                    'user_id'         => auth()->id(),
                    'doc_id'          => $document?->id,
                    'route_id'        => $routingSlip->rslip_id,
                    'action'          => 're-assigned',
                    'new_destination' => $fullName,
                    'status_update'   => 2,
                    'new_file'        => $document?->file_name,
                    'assigned_to'     => $routingSlip->assigned_to,
                    'created_at'      => now(),
                ]);

                // Email Notification
                if (!empty($user->email)) {
                    Mail::to($user->email)->send(
                        new DocumentRoutedNotification($document, $fullName, $routingSlip->trans_remarks)
                    );
                }
            }
        }
    }

    // Update routing slip
    $routingSlip->update([
        'routed_users' => implode(', ', $finalDestinations),
        'route_status' => 3,
    ]);

    return redirect()->route('viewSlip')->with('success', 'Document rerouted successfully!');
}

}
