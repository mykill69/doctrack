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




// public function viewSlip()
// {

//     $user = auth()->user();

//     $userRole = $user->id;
//     $userDepartment = $user->department;
//     $logs = Log::where('user_id', $userRole)->get();

//     $routingSlipCount = ($logs->every(fn($log) => $log->status_update != 3)) 
//         ? RoutingSlip::where('route_status', 3)->count() 
//         : 0;

//     $superUserCount = $userRole === 'super_user' 
//         ? RoutingSlip::where('route_status', 1)->count() 
//         : 0;

//     $recordsOfficerCount = $userRole === 'records_officer' 
//         ? RoutingSlip::where('route_status', 2)->count() 
//         : 0;

//     $routingSlips = RoutingSlip::all();
//     $offices = Office::all();

//     // ✅ Add Doctrack Count
//     $userFullName = $user->fname . ' ' . $user->lname;

//     $documentTrack = Doctrack::where(function ($query) use ($userRole, $userFullName) {
//             $query->where('user_id', $userRole)
//                   ->orWhere('update_by', $userRole)
//                   ->orWhere('user_name', $userFullName);
//         })->get();

//     // Count only records with doctrack_stat == 2
//     $doctrackCount = $documentTrack->where('doctrack_stat', 2)->count();

//     return view('slip.routingSlip', compact(
//         'routingSlips',
//         'routingSlipCount',
//         'superUserCount',
//         'offices',
//         'recordsOfficerCount',
//         'doctrackCount' // 👈 Pass to view
//     ));
// }


//updated January 6, 2025

// public function viewSlip()
// {
//     $user = auth()->user();

//     $userId = $user->id;
//     $userRole = $user->role; // make sure this is role, not id
//     $userDepartment = $user->department;

//     // ✅ Check existence only (NO LOOP, NO GET)
//     $hasStatusThree = Log::where('user_id', $userId)
//         ->where('status_update', 3)
//         ->exists();

//     $routingSlipCount = !$hasStatusThree
//         ? RoutingSlip::where('route_status', 3)->count()
//         : 0;

//     $superUserCount = $userRole === 'super_user'
//         ? RoutingSlip::where('route_status', 1)->count()
//         : 0;

//     $recordsOfficerCount = $userRole === 'records_officer'
//         ? RoutingSlip::where('route_status', 2)->count()
//         : 0;

//     $routingSlips = RoutingSlip::all();
//     $offices = Office::all();

//     // ✅ Doctrack Count (already efficient)
//     $userFullName = $user->fname . ' ' . $user->lname;

//     $doctrackCount = Doctrack::where(function ($query) use ($userId, $userFullName) {
//             $query->where('user_id', $userId)
//                   ->orWhere('update_by', $userId)
//                   ->orWhere('user_name', $userFullName);
//         })
//         ->where('doctrack_stat', 2)
//         ->count();

//     return view('slip.routingSlip', compact(
//         'routingSlips',
//         'routingSlipCount',
//         'superUserCount',
//         'offices',
//         'recordsOfficerCount',
//         'doctrackCount'
//     ));
// }

// 05/28/2026 update the viewslip for president's office

// public function viewSlip()
// {
//     $user = auth()->user();

//     $userId = $user->id;
//     $userRole = $user->role;
//     $userDepartment = $user->department;

//     $hasStatusThree = Log::where('user_id', $userId)
//         ->where('status_update', 3)
//         ->exists();

//     $routingSlipCount = !$hasStatusThree
//         ? RoutingSlip::where('route_status', 3)->count()
//         : 0;

//     $superUserCount = $userRole === 'super_user'
//         ? RoutingSlip::where('route_status', 1)->count()
//         : 0;

//     $recordsOfficerCount = $userRole === 'records_officer'
//         ? RoutingSlip::where('route_status', 2)->count()
//         : 0;

//     // ✅ CONDITIONAL VIEW
//     if ($userId == 38) {
//     // ID 38 can view ALL routing slips
//     $routingSlips = RoutingSlip::all();
//     } else {
//         // Others can view only their own
//         $routingSlips = RoutingSlip::where('user_id', $userId)->get();
//     }

//     $offices = Office::all();

//     $userFullName = $user->fname . ' ' . $user->lname;

//     $doctrackCount = Doctrack::where(function ($query) use ($userId, $userFullName) {
//             $query->where('user_id', $userId)
//                   ->orWhere('update_by', $userId)
//                   ->orWhere('user_name', $userFullName);
//         })
//         ->where('doctrack_stat', 2)
//         ->count();

//     return view('slip.routingSlip', compact(
//         'routingSlips',
//         'routingSlipCount',
//         'superUserCount',
//         'offices',
//         'recordsOfficerCount',
//         'doctrackCount'
//     ));
// }

// public function viewSlip()
// {
//     $user = auth()->user();
//     $userId = $user->id;
//     $userRole = $user->role;

//     $perPage = 15;

//     if ($userRole === 'super_user') {
//         // Super User: Get ALL Routed to President (status 1) - use get() not paginate()
//         $routingSlips = RoutingSlip::where('route_status', 1)
//                         ->orderBy('updated_at', 'desc')
//                         ->get();  // Changed from paginate() to get() to get ALL records
//     } 
//     elseif ($userRole === 'records_officer') {
//         // Records Officer: Get ALL records with route_status == 2
//         $routingSlips = RoutingSlip::where('route_status', 2)
//                         ->orderBy('updated_at', 'desc')
//                         ->get();
//     }
//     else {
//         // Staff and other roles: Get only their own records
//         $routingSlips = RoutingSlip::where('user_id', $userId)
//                         ->orderBy('updated_at', 'desc')
//                         ->get();
//     }

//     // Counts
//     $hasStatusThree = Log::where('user_id', $userId)
//                         ->where('status_update', 3)
//                         ->exists();

//     $routingSlipCount = !$hasStatusThree 
//         ? RoutingSlip::where('route_status', 3)->count() 
//         : 0;

//     $recordsOfficerCount = $userRole === 'records_officer' 
//         ? RoutingSlip::where('route_status', 2)->count() 
//         : 0;

//     $offices = Office::all();
//     $userFullName = $user->fname . ' ' . $user->lname;

//     $doctrackCount = Doctrack::where(function ($query) use ($userId, $userFullName) {
//         $query->where('user_id', $userId)
//               ->orWhere('update_by', $userId)
//               ->orWhere('user_name', $userFullName);
//     })->where('doctrack_stat', 2)->count();

//     return view('slip.routingSlip', compact(
//         'routingSlips',
//         'routingSlipCount',
//         'recordsOfficerCount',
//         'offices',
//         'doctrackCount',
//         'userRole'
//     ));
// }

public function viewSlip()
{
    $user = auth()->user();
    $userId = $user->id;
    $userRole = $user->role;

    $perPage = 15;

    if ($userRole === 'super_user') {
        // Super User: Get ALL Routed to President (status 1)
        $routingSlips = RoutingSlip::where('route_status', 1)
                        ->orderBy('updated_at', 'desc')
                        ->get();
    } 
    elseif ($userRole === 'records_officer') {
        // Records Officer: Get ALL their created routing slips with route_status == 2 AND route_status == 1
        $routingSlips = RoutingSlip::whereIn('route_status', [1, 2])  // Can see both Routed to President and Routed back to Records
                        ->where('user_id', $userId)  // Only show records created by this records_officer
                        ->orderBy('updated_at', 'desc')
                        ->get();
    }
    else {
        // Staff and other roles: Get only their own records
        $routingSlips = RoutingSlip::where('user_id', $userId)
                        ->orderBy('updated_at', 'desc')
                        ->get();
    }

    // Counts
    $hasStatusThree = Log::where('user_id', $userId)
                        ->where('status_update', 3)
                        ->exists();

    $routingSlipCount = !$hasStatusThree 
        ? RoutingSlip::where('route_status', 3)->count() 
        : 0;

    $recordsOfficerCount = $userRole === 'records_officer' 
        ? RoutingSlip::whereIn('route_status', [1, 2])->where('user_id', $userId)->count() 
        : 0;

    $offices = Office::all();
    $userFullName = $user->fname . ' ' . $user->lname;

    $doctrackCount = Doctrack::where(function ($query) use ($userId, $userFullName) {
        $query->where('user_id', $userId)
              ->orWhere('update_by', $userId)
              ->orWhere('user_name', $userFullName);
    })->where('doctrack_stat', 2)->count();

    return view('slip.routingSlip', compact(
        'routingSlips',
        'routingSlipCount',
        'recordsOfficerCount',
        'offices',
        'doctrackCount',
        'userRole'
    ));
}

// public function viewSlip()
// {
//     $user = auth()->user();
//     $userId = $user->id;
//     $userRole = $user->role;

//     $perPage = 15;

//     if ($userRole === 'super_user') {
//         // Super User: Only Routed to President (status 1)
//         $routingSlips = RoutingSlip::where('route_status', 1)
//                         ->orderBy('updated_at', 'desc')
//                         ->paginate($perPage);
//     } 
//     else {
//         // Records Officer + Others: Load ALL their records (pagination will be handled per tab in view)
//         $routingSlips = RoutingSlip::where('user_id', $userId)
//                         ->orderBy('updated_at', 'desc')
//                         ->get();   // Use get() here, we will filter per tab
//     }

//     // Counts
//     $hasStatusThree = Log::where('user_id', $userId)
//                         ->where('status_update', 3)
//                         ->exists();

//     $routingSlipCount = !$hasStatusThree 
//         ? RoutingSlip::where('route_status', 3)->count() 
//         : 0;

//     $recordsOfficerCount = $userRole === 'records_officer' 
//         ? RoutingSlip::where('route_status', 2)->count() 
//         : 0;

//     $offices = Office::all();
//     $userFullName = $user->fname . ' ' . $user->lname;

//     $doctrackCount = Doctrack::where(function ($query) use ($userId, $userFullName) {
//         $query->where('user_id', $userId)
//               ->orWhere('update_by', $userId)
//               ->orWhere('user_name', $userFullName);
//     })->where('doctrack_stat', 2)->count();

//     return view('slip.routingSlip', compact(
//         'routingSlips',
//         'routingSlipCount',
//         'recordsOfficerCount',
//         'offices',
//         'doctrackCount',
//         'userRole'
//     ));
// }


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

public function slipForm(Request $request, $id)
{
    /** @var \App\Models\User $user */
    $user = auth()->user();

    // ✅ Get routing_slip_id
    $routingSlipId = $request->query('routing_slip_id');

    // ✅ Fallback (same logic as tracking/pdf)
    if (!$routingSlipId) {
        $routingSlipId = DB::table('routing_slip')
            ->where('rslip_id', $id)
            ->orderByDesc('id')
            ->value('id');
    }

    // ✅ Get EXACT routing slip version
    $routingSlip = DB::table('routing_slip')
        ->where('rslip_id', $id)
        ->where('id', $routingSlipId)
        ->first();

    $remarks = Remark::all();

    $relatedDocuments = DB::table('documents')
        ->where('route_id', $id)
        ->get();

    $users = User::select('id', 'fname', 'lname')->get();

    $recordsOfficerCount = $user->hasRole('records_officer')
        ? RoutingSlip::where('route_status', 2)->count()
        : 0;

    $superUserCount = $user->hasRole('super_user')
        ? RoutingSlip::where('route_status', 1)->count()
        : 0;

    return view('slip.slipForm', compact(
        'remarks',
        'routingSlip',
        'routingSlipId', // ✅ IMPORTANT
        'id',            // route_id
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

public function pdfSlip(Request $request, $id)
{
    // ✅ Get routing_slip_id
    $routingSlipId = $request->query('routing_slip_id');

    // ✅ Fallback
    if (!$routingSlipId) {
        $routingSlipId = DB::table('routing_slip')
            ->where('rslip_id', $id)
            ->orderByDesc('id')
            ->value('id');
    }

    // ✅ Get EXACT routing slip
    $routingSlip = DB::table('routing_slip')
        ->where('rslip_id', $id)
        ->where('id', $routingSlipId)
        ->first();

    $remarks = Remark::all();

    $relatedDocuments = DB::table('documents')
        ->where('route_id', $id)
        ->get();

    // ✅ Logs (still route-based unless you add routing_slip_id column)
    $logs = DB::table('logs')
        ->where('route_id', $id)
        ->whereIn('action', ['re-assigned', 'Acknowledged'])
        ->whereNotNull('new_destination')
        ->get();

    // ==============================
    // REASSIGNING USER LOGIC
    // ==============================

    $reassigningUser = DB::table('logs')
        ->join('assign_logs', 'logs.route_id', '=', 'assign_logs.route_id')
        ->join('users', 'assign_logs.new_user', '=', 'users.id')
        ->where('logs.route_id', $id)
        ->whereIn('logs.action', ['re-assigned', 'Acknowledged'])
        ->select('users.id', 'users.fname', 'users.lname', 'users.department')
        ->orderByDesc('assign_logs.id')
        ->first();

    $reassignUserDept = $reassigningUser->department ?? null;

    // ✅ Fallback
    if (!$reassigningUser || empty($reassigningUser->id)) {
        $directUser = DB::table('logs')
            ->join('users', 'logs.new_user', '=', 'users.id')
            ->where('logs.route_id', $id)
            ->whereIn('logs.action', ['re-assigned', 'Acknowledged'])
            ->select('users.id', 'users.fname', 'users.lname', 'users.department')
            ->orderByDesc('logs.id')
            ->first();

        if ($directUser) {
            $reassigningUser = $directUser;
            $reassignUserDept = $directUser->department;
        }
    }

    $reassigningUserEsig = null;
    $groupName = null;

    // ==============================
    // GROUP FALLBACK LOGIC
    // ==============================

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

                $logWithUser = DB::table('logs')
                    ->where('route_id', $id)
                    ->whereNotNull('new_user')
                    ->orderByDesc('id')
                    ->first();

                if ($logWithUser && $logWithUser->new_user) {
                    $userForGroup = $group->users()
                        ->where('users.id', $logWithUser->new_user)
                        ->select('users.id', 'users.fname', 'users.lname', 'users.department')
                        ->first();

                    if ($userForGroup) {
                        $reassigningUser = $userForGroup;
                        $reassignUserDept = $userForGroup->department;
                        $reassigningUserEsig = Esig::where('user_id', $userForGroup->id)->first();
                    }
                }
            }
        }
    }

    // ✅ Always load esig
    if ($reassigningUser && !isset($reassigningUserEsig)) {
        $reassigningUserEsig = Esig::where('user_id', $reassigningUser->id)->first();
    }

    // ==============================
    // PRESIDENT ESIGNATURE
    // ==============================

    $esigUserId = match ($routingSlip->pres_dept) {
        "Dr. Aladino C. Moraca" => 38,
        'VPAF' => 63,
        'VPAA' => 64,
        default => null,
    };

    $esig = null;

    if ($esigUserId) {
        $esig = Esig::where('user_id', $esigUserId)->first();

        if ($esig && $esig->esig_file) {
            $esig->esig_path = public_path('storage/esignature/' . $esig->esig_file);
        }
    }

    // ==============================
    // PDF GENERATION
    // ==============================

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

    $pdf->setPaper('letter', 'portrait');
    $pdf->setOption('isRemoteEnabled', true);
    $pdf->setOption('isHtml5ParserEnabled', true);
    $pdf->setOption('margin-top', 0);
    $pdf->setOption('margin-right', 0);
    $pdf->setOption('margin-bottom', 0);
    $pdf->setOption('margin-left', 0);

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
    $routingSlip->r_destination = implode(', ', $request->input('r_destination', []));
    $routingSlip->route_status   = $request->input('route_status');

    // Merge received names
    $existingNames = $routingSlip->received_name ? explode(',', $routingSlip->received_name) : [];
    $newNames = array_map('trim', $request->input('received_name'));
    $mergedNames = array_unique(array_merge($existingNames, $newNames));
    $routingSlip->received_name = implode(', ', $mergedNames);

    $routingSlip->save();

    return redirect()->route('viewSlip')->with('success', 'Routing Slip CTRL#' . $routingSlip->rslip_id . ' updated successfully.');
}


/**
 * Show form to edit subject only (for records_officer in Routed to President tab)
 */
public function editSubject($id)
{
    $user = auth()->user();
    
    // Only records_officer can access this
    if ($user->role !== 'records_officer') {
        return redirect()->route('viewSlip')->with('error', 'Unauthorized access.');
    }
    
    $routingSlips = RoutingSlip::findOrFail($id);
    
    // Only allow editing if route_status is 1 (Routed to President)
    if ($routingSlips->route_status != 1) {
        return redirect()->route('viewSlip')->with('error', 'Can only edit subject for slips routed to President.');
    }
    
    $logs = Log::where('user_id', $user->id)->get();
    
    $routingSlipCount = ($logs->every(fn($log) => $log->status_update != 3))
        ? RoutingSlip::where('route_status', 3)->count()
        : 0;
    $superUserCount = 0;
    $recordsOfficerCount = RoutingSlip::where('route_status', 2)->count();
    $users = User::select('id', 'fname', 'lname')->get();

    return view('slip.editSubject', compact(
        'routingSlips',
        'routingSlipCount',
        'superUserCount',
        'recordsOfficerCount',
        'users'
    ));
}

/**
 * Update subject only
 */
public function updateSubject(Request $request, $id)
{
    $user = auth()->user();
    
    if ($user->role !== 'records_officer') {
        return redirect()->route('viewSlip')->with('error', 'Unauthorized access.');
    }
    
    $request->validate([
        'op_ctrl'       => 'required|integer',
        'source'        => 'required|string|max:255',
        'date_received' => 'required|date',
        'subject'       => 'required|string|max:255',
        'document'      => 'nullable|file|mimes:pdf|max:20480',
    ]);

    $routingSlip = RoutingSlip::findOrFail($id);
    
    if ($routingSlip->route_status != 1) {
        return redirect()->route('viewSlip')->with('error', 'Can only edit slips routed to President.');
    }
    
    // Handle document replacement
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
    
    $routingSlip->op_ctrl       = $request->input('op_ctrl');
    $routingSlip->source        = $request->input('source');
    $routingSlip->date_received = $request->input('date_received');
    $routingSlip->subject       = $request->input('subject');
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


//     public function editDest($id)
// {
//     $user = auth()->user(); // Full user object
//     $userId = $user->id;
//     $userDepartment = $user->department;
//     $userRole = $user->role;
//     $groups = Group::all();

//     $routingSlips = RoutingSlip::findOrFail($id);
//     $users = User::select('id', 'fname', 'lname')->get();
//     $logs = Log::where('user_id', $userId)->get();

//     $routingSlipCount = ($logs->every(fn($log) => $log->status_update != 3))
//         ? RoutingSlip::where('route_status', 3)->count()
//         : 0;

//     $superUserCount = ($userRole === 'super_user')
//         ? RoutingSlip::where('route_status', 1)->count()
//         : 0;

//     $recordsOfficerCount = ($userRole === 'records_officer')
//         ? RoutingSlip::where('route_status', 2)->count()
//         : 0;

//     return view('slip.editDest', compact(
//     'routingSlips',
//     'users', // changed from 'offices'
//     'routingSlipCount',
//     'superUserCount',
//     'recordsOfficerCount',
//     'groups'
// ));
// }

public function editDest($id)
{
    $user = auth()->user(); 
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

    // 🔑 Prepare selected users (only if recall/edit has routed_users)
    $selectedUsers = $routingSlips->routed_users 
        ? array_map('trim', explode(',', $routingSlips->routed_users)) 
        : [];

    return view('slip.editDest', compact(
        'routingSlips',
        'users',
        'routingSlipCount',
        'superUserCount',
        'recordsOfficerCount',
        'groups',
        'selectedUsers' // pass to view
    ));
}


// this was change when I update the recall

public function storeRouteDoc(Request $request)
{
    $validatedData = $request->validate([
        'routing_slip_id' => 'required|integer',
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

    // $routingSlip = RoutingSlip::where('rslip_id', $validatedData['route_id'])->first();
    $routingSlip = RoutingSlip::find($validatedData['routing_slip_id']);
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

public function updateRouteDocRecall(Request $request, $id)
{
    $validatedData = $request->validate([
        'file_name'       => 'nullable|file|mimes:pdf|max:5120',
        'routed_users'    => 'nullable|array',
        'routed_users.*'  => 'required|string',
    ]);

    // 🔹 Find the document by route_id
    $document = Document::where('route_id', $id)->first();
    if (!$document) {
        return redirect()->back()->with('error', 'Document not found.');
    }

    $oldFileName = $document->file_name;
    $newFileName = $oldFileName;

    // 🔹 Check if logs.new_destination exists
    $hasExistingDestination = Log::where('doc_id', $document->id)
                                  ->whereNotNull('new_destination')
                                  ->exists();

    // 🔹 Replace file if uploaded
    if ($request->hasFile('file_name')) {
        if ($oldFileName && Storage::exists('documents/' . $oldFileName)) {
            Storage::delete('documents/' . $oldFileName);
        }

        $pdfFile = $request->file('file_name');
        $pdfName = $pdfFile->getClientOriginalName();
        $pdfFile->storeAs('documents', $pdfName);
        $newFileName = $pdfName;

        // Update routing_slip document
        RoutingSlip::where('rslip_id', $id)->update(['document' => $pdfName]);

        // 🔹 Only log if no existing routed user destinations
        if (!$hasExistingDestination) {
            $lastDest = Log::where('doc_id', $document->id)->latest('id')->value('new_destination');
            $log = Log::create([
                'user_id'         => auth()->id(),
                'doc_id'          => $document->id,
                'route_id'        => $document->route_id,
                'action'          => 'file updated',
                'status_update'   => $document->doc_stat,
                'prev_file'       => $oldFileName,
                'new_file'        => $newFileName,
                'new_destination' => $lastDest ?? 'Unchanged',
                'created_at'      => now(),
            ]);

            LogsHistory::create([
                'doc_id'        => $document->id,
                'action'        => $log->action,
                'status_update' => $log->status_update,
            ]);
        }
    }

    // 🔹 Update document record (only file name changed here, other fields remain)
    $document->update(['file_name' => $newFileName]);

    // 🔹 Routing logic for new routed_users
    if (!empty($validatedData['routed_users'])) {
        $routingSlip = RoutingSlip::where('rslip_id', $id)->first();
        $existingDestinations = $routingSlip && !empty($routingSlip->routed_users)
            ? array_filter(array_map('trim', explode(',', $routingSlip->routed_users)))
            : [];

        $finalDestinations = [];

        foreach ($validatedData['routed_users'] as $destination) {
            $users = collect();

            if (Str::startsWith($destination, 'position:')) {
                $positionId = (int) Str::after($destination, 'position:');
                $users = User::where('position', $positionId)->get();

            } elseif (Str::startsWith($destination, 'group:')) {
                $groupName = Str::after($destination, 'group:');
                $group = Group::where('group_name', $groupName)->first();
                if ($group) $users = $group->users;

            } else {
                $user = User::whereRaw("CONCAT(fname, ' ', lname) = ?", [$destination])->first();
                if ($user) $users = collect([$user]);
            }

            foreach ($users as $user) {
                $fullName = $user->fname . ' ' . $user->lname;

                if (!in_array($fullName, $existingDestinations) && !in_array($fullName, $finalDestinations)) {
                    $finalDestinations[] = $fullName;

                    $log = Log::create([
                        'user_id'         => auth()->id(),
                        'doc_id'          => $document->id,
                        'route_id'        => $document->route_id,
                        'action'          => 'uploaded',
                        'status_update'   => $document->doc_stat,
                        'prev_file'       => $oldFileName,
                        'new_file'        => $document->file_name,
                        'new_destination' => $fullName,
                        'created_at'      => now(),
                    ]);

                    LogsHistory::create([
                        'doc_id'        => $document->id,
                        'action'        => $log->action,
                        'status_update' => $log->status_update,
                    ]);

                    // Send email only to newly added users
                    if ($user->email) {
                        Mail::to($user->email)->send(
                            new DocumentRoutedNotification($document, $fullName, $routingSlip?->trans_remarks)
                        );
                    }
                }
            }
        }

        // Update routing slip with new combined destinations
        if ($routingSlip) {
            $combined = array_values(array_unique(array_merge($existingDestinations, $finalDestinations)));
            $routingSlip->routed_users = implode(', ', $combined);
            $routingSlip->route_status = 3;
            $routingSlip->save();
        }
    }

    return redirect()->route('dashboard')
        ->with('success', 'Document with CTRL#' . $document->route_id . ' was updated successfully.');
}





public function recallSlip($id)
{
    $user = auth()->user(); 
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

    // 🔑 Prepare selected users (only if recall/edit has routed_users)
    $selectedUsers = $routingSlips->routed_users 
        ? array_map('trim', explode(',', $routingSlips->routed_users)) 
        : [];

    return view('slip.recallSlip', compact(
        'routingSlips',
        'users',
        'routingSlipCount',
        'superUserCount',
        'recordsOfficerCount',
        'groups',
        'selectedUsers' // pass to view
    ));
}


// public function updateAssign(Request $request, $routeId)
// {
//     $request->validate([
//         'assigned_to' => 'required|string',
//     ]);

//     $document = Document::where('route_id', $routeId)->first();

//     if ($document) {
//         $routingSlip = RoutingSlip::where('rslip_id', $document->route_id)->first();

//         if ($routingSlip) {
//             $assignedTo = $request->input('assigned_to');
//             $assignCom = $request->input('assign_com');

//             $routingSlip->route_status = 2;
//             $routingSlip->assigned_to = $assignedTo;
//             $routingSlip->assign_com = $assignCom;
//             $routingSlip->save();

//             // Update logs - include assign_com as comments
//             Log::where('route_id', $routingSlip->rslip_id)
//                 ->where('doc_id', $document->id)
//                 ->update([
//                     'assigned_to'   => $assignedTo,
//                     'status_update' => 2,
//                     'new_user'      => auth()->user()->id,
//                     'comments'      => $assignCom, // <-- add this line
//                 ]);

//             // Update document status
//             Document::where('route_id', $routingSlip->rslip_id)
//                 ->update(['assn_code' => 1]);

//             // Update or create AssignLogs
//             AssignLogs::updateOrCreate(
//                 [
//                     'doc_id'    => $document->id,
//                     'route_id'  => $routingSlip->rslip_id,
//                 ],
//                 [
//                     'new_user'     => auth()->user()->id,
//                     'assn_code'    => 1,
//                     'assigned_to'  => $assignedTo,
//                 ]
//             );

//             // Log history
//             LogsHistory::create([
//                 'doc_id'        => $document->id,
//                 'action'        => 're-assigned',
//                 'status_update' => 2,
//             ]);

//             $redirectUrl = $request->input('redirectUrl', route('dashboard'));

//             return redirect($redirectUrl)->with('success', 'Routing Slip CTRL#' . $routingSlip->rslip_id . ' updated successfully.');
//         } else {
//             return back()->withErrors(['Routing slip not found']);
//         }
//     } else {
//         return back()->withErrors(['Document not found']);
//     }
// }

public function updateAssign(Request $request, $routeId)
{
    $request->validate([
        'assigned_to'    => 'required|string',
        'assign_com'     => 'required|string',
        'routing_slip_id' => 'nullable|integer',
    ]);

    $routingSlipId = $request->input('routing_slip_id');
    
    // Get the EXACT routing slip by its primary key
    if ($routingSlipId) {
        $routingSlip = RoutingSlip::find($routingSlipId);
    } else {
        // Fallback: get the first one with this rslip_id
        $routingSlip = RoutingSlip::where('rslip_id', $routeId)->first();
    }

    if (!$routingSlip) {
        return back()->withErrors(['Routing slip not found']);
    }

    // Find document linked to this specific routing slip by file_name
    $document = Document::where('route_id', $routingSlip->rslip_id)
        ->where('file_name', $routingSlip->document)
        ->first();

    if (!$document) {
        // Fallback: get first document with this route_id
        $document = Document::where('route_id', $routingSlip->rslip_id)->first();
    }

    if (!$document) {
        return back()->withErrors(['Document not found']);
    }

    $assignedTo = $request->input('assigned_to');
    $assignCom  = $request->input('assign_com');
    $fullName   = auth()->user()->fname . ' ' . auth()->user()->lname;

    // Update routing slip
    $routingSlip->route_status = 2;
    $routingSlip->assigned_to  = $assignedTo;
    $routingSlip->assign_com   = $assignCom;
    $routingSlip->save();

    // Update the log
    Log::where('route_id', $routingSlip->rslip_id)
        ->where('doc_id', $document->id)
        ->where('new_destination', $fullName)
        ->update([
            'assigned_to'   => $assignedTo,
            'status_update' => 3,
            'new_user'      => auth()->user()->id,
            'action'        => 'Acknowledged',
            'comments'      => $assignCom,
        ]);

    Document::where('route_id', $routingSlip->rslip_id)
        ->update(['assn_code' => 1]);

    // Update or create AssignLogs
    $assignLog = AssignLogs::where('doc_id', $document->id)
        ->where('route_id', $routingSlip->rslip_id)
        ->first();

    if ($assignLog) {
        $existing = $assignLog->assigned_to ?? '';
        $existingArray = $existing ? array_map('trim', explode(',', $existing)) : [];
        if (!in_array($assignedTo, $existingArray)) {
            $existingArray[] = $assignedTo;
        }
        $updatedAssignedTo = implode(', ', $existingArray);
        $assignLog->update([
            'new_user'    => auth()->user()->id,
            'assn_code'   => 1,
            'assigned_to' => $updatedAssignedTo,
        ]);
    } else {
        AssignLogs::create([
            'doc_id'      => $document->id,
            'route_id'    => $routingSlip->rslip_id,
            'new_user'    => auth()->user()->id,
            'assn_code'   => 1,
            'assigned_to' => $assignedTo,
        ]);
    }

    LogsHistory::create([
        'doc_id'        => $document->id,
        'action'        => 're-assigned',
        'status_update' => 2,
    ]);

    $redirectUrl = $request->input('redirectUrl', route('dashboard'));

    return redirect($redirectUrl)
        ->with('success', 'Routing Slip CTRL#' . $routingSlip->rslip_id . ' updated successfully.');
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
