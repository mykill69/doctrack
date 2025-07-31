<?php

namespace App\Http\Controllers;


use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\RoutingSlip;
use App\Models\RouteDocument;
use App\Models\Office;
use App\Models\Document;
use App\Models\Log;
use App\Models\LogsHistory;
use App\Models\AssignLogs;
use App\Models\LogsTracking;
use App\Models\User;
use App\Models\Esig;
use App\Models\Doctrack;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;



class PagesController extends Controller
{
   
    public function incoming()
{
    $user = auth()->user();
    $userDepartment = $user->department;
    $userId = $user->id;
    $userFullName = $user->fname . ' ' . $user->lname;
    $userRole = $user->role;

    $logs = Log::where(function ($query) use ($userId, $userDepartment) {
        $query->where('new_user', $userId)
              ->orWhere('user_id', $userId)
              ->orWhere('new_destination', $userDepartment);
    })->get(); 

    $routingSlipCount = ($logs->every(fn($log) => $log->status_update != 3)) ? RoutingSlip::where('route_status', 3)->count() : 0;
    $superUserCount = $userRole === 'super_user' ? RoutingSlip::where('route_status', 1)->count() : 0;
    $recordsOfficerCount = $userRole === 'records_officer' ? RoutingSlip::where('route_status', 2)->count() : 0;

    $offices = Office::all();

    return view('home.incoming', compact('offices', 'logs', 'routingSlipCount', 'superUserCount', 'recordsOfficerCount'));
}

public function doctrackSlip()
{
    $user = auth()->user();
    $userId = $user->id;
    $userFullName = $user->fname . ' ' . $user->lname;
    $userRole = $user->role;

    // Logs for routing slip counts
    $logs = Log::where(function ($query) use ($userId) {
        $query->where('new_user', $userId)
              ->orWhere('user_id', $userId);
    })->get();

    // Routing slip counts
    $routingSlipCount = ($logs->every(fn($log) => $log->status_update != 3)) 
        ? RoutingSlip::where('route_status', 3)->count() 
        : 0;

    $superUserCount = $userRole === 'super_user' 
        ? RoutingSlip::where('route_status', 1)->count() 
        : 0;

    $recordsOfficerCount = $userRole === 'records_officer' 
        ? RoutingSlip::where('route_status', 2)->count() 
        : 0;

    $offices = Office::all();

    // Get all Doctrack records (no grouping)
    $documentTrack = Doctrack::with(['createdBy', 'doctrackFile'])
        ->where(function ($query) use ($userId, $userFullName) {
            $query->where('user_id', $userId)
                  ->orWhere('update_by', $userId)
                  ->orWhere('user_name', $userFullName);
        })
        ->orderByDesc('created_at')
        ->get();

    // Calculate time_diff for each record here
    $documentTrack->transform(function ($item) {
        $start = \Carbon\Carbon::parse($item->created_at);
        $end = \Carbon\Carbon::parse($item->updated_at ?? $item->created_at);
        $diffInMinutes = $end->diffInMinutes($start);

        $item->time_diff = [
            'days' => floor($diffInMinutes / 1440),
            'hours' => floor(($diffInMinutes % 1440) / 60),
            'minutes' => $diffInMinutes % 60,
        ];

        return $item;
    });

    $doctrackCount = $documentTrack->count();

    return view('home.doctrackSlip', compact(
        'documentTrack', 'offices',
        'logs', 'routingSlipCount', 'superUserCount', 
        'recordsOfficerCount', 'doctrackCount'
    ));
}




public function pending()
{
    $user = auth()->user();
    $userFullName = $user->fname . ' ' . $user->lname;
    $userId = $user->id;
    $userRole = $user->role;
    $userDepartment = $user->department;

    $logs = Log::leftJoin('documents', 'logs.doc_id', '=', 'documents.id')
        ->leftJoin('routing_slip', 'logs.route_id', '=', 'routing_slip.rslip_id')
        ->select('logs.*', 'documents.*', 'routing_slip.*')
        ->where('logs.status_update', 2)
        ->where(function ($q) {
            // Exclude rows where both are NOT NULL
            $q->whereNull('logs.new_user')
              ->orWhereNull('logs.assigned_to');
        })
        ->where(function ($q) use ($userFullName, $userId) {
            // Match either destination name or user ID
            $q->where('logs.new_destination', $userFullName)
              ->orWhere('logs.user_id', $userId);
        })
        ->orderBy('logs.created_at', 'desc')
        ->distinct('logs.id') // Optional: prevents duplicates
        ->get();

    $offices = Office::all();
    $recordsOfficerCount = 0;
    $superUserCount = 0;

    $doctrackCount = Doctrack::where(function ($query) use ($userId, $userFullName) {
    $query->where('user_id', $userId)
          ->orWhere('update_by', $userId)
          ->orWhere('user_name', $userFullName);
})->distinct('docslip_id')->count();


    return view('home.pending', compact('logs', 'offices', 'recordsOfficerCount', 'superUserCount','doctrackCount'));
}

   public function served()
{
    $user = auth()->user();
    $userId = $user->id;
    $userDepartment = $user->department;
    $userFullName = $user->fname . ' ' . $user->lname;
    $userRole = $user->role;

    $logs = Log::with('document', 'document.routingSlip')
        ->whereNotNull('new_user')
        ->when($userRole === 'records_officer', function ($query) {
            return $query; // records_officer sees all served logs
        }, function ($query) use ($userId, $userDepartment, $userFullName) {
            return $query->where(function ($q) use ($userId, $userDepartment, $userFullName) {
                $q->where('new_user', $userId)
                  ->orWhere('user_id', $userId)
                  ->orWhere('new_destination', $userDepartment)
                  ->orWhere('new_destination', $userFullName);
            });
        })
        ->get();

        $doctrackCount = Doctrack::where(function ($query) use ($userId, $userFullName) {
    $query->where('user_id', $userId)
          ->orWhere('update_by', $userId)
          ->orWhere('user_name', $userFullName);
})->distinct('docslip_id')->count();

    $offices = Office::all();

    $recordsOfficerCount = $userRole === 'records_officer'
        ? RoutingSlip::where('route_status', 2)->count()
        : 0;

    $superUserCount = $userRole === 'super_user'
        ? RoutingSlip::where('route_status', 1)->count()
        : 0;

    return view('home.served', compact('logs', 'offices', 'recordsOfficerCount', 'superUserCount','doctrackCount'));
    }

public function viewLogs() 
{
    $user = auth()->user();
    $userFullName = $user->fname . ' ' . $user->lname;
    $userId = $user->id;
    $userRole = $user->role;

    $logsAll = DB::table('logs_history')
        ->leftJoin('logs', 'logs.doc_id', '=', 'logs_history.doc_id')
        ->leftJoin('assign_logs', 'assign_logs.new_user', '=', 'logs.new_user')
        ->leftJoin('users as original_users', function ($join) {
            $join->on('logs.user_id', '=', 'original_users.id')
                 ->where('logs_history.status_update', '=', 2);
        })
        ->leftJoin('users as new_users', function ($join) {
            $join->on('logs.new_user', '=', 'new_users.id')
                 ->where('logs_history.status_update', '=', 3);
        })
        ->leftJoin('users as assign_users', 'assign_logs.new_user', '=', 'assign_users.id')
        ->select(
            'logs_history.*',
            'logs.new_destination',
            'logs.new_file',

            'assign_logs.assigned_to as assign_to',
            'assign_users.fname as assign_fname',
            'assign_users.lname as assign_lname',

            'original_users.fname as original_fname',
            'original_users.lname as original_lname',
            'original_users.department as original_user_department',

            'new_users.fname as new_fname',
            'new_users.lname as new_lname',
            'new_users.department as new_user_department'
        )
        ->where(function ($query) use ($userId, $userFullName) {
            $query->where('logs.user_id', $userId)
                  ->orWhere('logs.new_user', $userId)
                  ->orWhere('logs.new_destination', $userFullName);
        })
        ->orWhereNull('logs.id') // Show all logs_history even if logs is missing
        ->distinct()
        ->orderBy('logs_history.created_at', 'desc')
        ->get();

    $routingSlipCount = RoutingSlip::where('route_status', 3)->count();
    $superUserCount = $userRole === 'super_user' ? RoutingSlip::where('route_status', 1)->count() : 0;
    $recordsOfficerCount = $userRole === 'records_officer' ? RoutingSlip::where('route_status', 2)->count() : 0;

    return view('home.viewLogs', compact(
        'logsAll', 'userId', 'userFullName',
        'routingSlipCount', 'superUserCount', 'recordsOfficerCount'
    ));
}


public function viewLogsTracking() 
{
    $user = auth()->user();
    $userId = $user->id;
    $userDepartment = $user->department;
    $userFullName = $user->fname . ' ' . $user->lname;
    $userRole = $user->role;

    // First, get all docslip_ids where the user is involved (either creator or recipient)
    $relatedDocslipIds = LogsTracking::where('user_id', $userId)
        ->orWhere('update_by', $userId)
        ->pluck('docslip_id');

    // Then, fetch all logs for those docslip_ids
    $logsAll = LogsTracking::with(['createdBy', 'updatedBy', 'doctrackFile'])
        ->whereIn('docslip_id', $relatedDocslipIds)
        ->orderByDesc('created_at')
        ->get();
$doctrackCount = Doctrack::where(function ($query) use ($userId, $userFullName) {
    $query->where('user_id', $userId)
          ->orWhere('update_by', $userId)
          ->orWhere('user_name', $userFullName);
})->distinct('docslip_id')->count();
    // Count logic
    $routingSlipCount = RoutingSlip::where('route_status', 3)->count();
    $superUserCount = $userRole === 'super_user' ? RoutingSlip::where('route_status', 1)->count() : 0;
    $recordsOfficerCount = $userRole === 'records_officer' ? RoutingSlip::where('route_status', 2)->count() : 0;

    return view('home.viewLogsTracking', compact(
        'logsAll', 'routingSlipCount', 'superUserCount', 'recordsOfficerCount','doctrackCount'
    ));
}


public function userPassword($id)
{
    if (auth()->user()->role === 'Administrator') {
        return redirect()->back()->with('error', 'Administrators do not have access to this page.');
    }

    $user = auth()->user(); // override User::find($id) to always use logged-in user
    $userEsig = Esig::where('user_id', $user->id)->first(); // get user's esig record

    if (!$user) {
        return redirect()->back()->with('error', 'User not found');
    }

    $recordsOfficerCount = $user->role === 'records_officer'
        ? RoutingSlip::where('route_status', 2)->count()
        : 0;

    $superUserCount = $user->role === 'super_user'
        ? RoutingSlip::where('route_status', 1)->count()
        : 0;

    $offices = Office::all();

    return view('home.changepass', compact(
        'user',
        'offices',
        'recordsOfficerCount',
        'superUserCount',
        'userEsig' // pass it to the view
    ));
}

// public function passChange(Request $request, $id)
// {
//     $validator = Validator::make($request->all(), [
//         'email' => 'nullable|string|max:255|unique:users,email,' . $id,
//         'password' => 'nullable|string|min:8|confirmed',
//         'department' => 'nullable|string|max:255',
//         'esig_file' => 'nullable|file|mimes:jpeg,png,jpg,pdf',
//     ]);

//     if ($validator->fails()) {
//         return redirect()->back()->withErrors($validator)->withInput();
//     }

//     $user = User::find($id);
//     if (!$user) {
//         return redirect()->back()->with('error', 'User not found');
//     }

//     // Update only fields that were filled
//     if ($request->filled('fname')) $user->fname = $request->input('fname');
//     if ($request->filled('mname')) $user->mname = $request->input('mname');
//     if ($request->filled('lname')) $user->lname = $request->input('lname');
//     if ($request->filled('email')) $user->email = $request->input('email');
//     if ($request->filled('department')) $user->department = $request->input('department');
//     if ($request->filled('password')) $user->password = Hash::make($request->input('password'));

//     $user->save();

//     // ✅ Handle E-signature upload
//     if ($request->hasFile('esig_file')) {
//         $file = $request->file('esig_file');
//         $filename = $user->fname . '_' . $file->getClientOriginalName();
//         $destinationPath = public_path('esignature');

//         // Ensure destination folder exists
//         if (!File::exists($destinationPath)) {
//             File::makeDirectory($destinationPath, 0755, true);
//         }

//         // Retrieve existing esig row
//         $existingEsig = Esig::where('user_id', $user->id)->first();

//         // If exists, delete old file before replacing
//         if ($existingEsig && $existingEsig->esig_file) {
//             $oldFilePath = $destinationPath . '/' . $existingEsig->esig_file;
//             if (File::exists($oldFilePath)) {
//                 File::delete($oldFilePath);
//             }
//         }

//         // Move the new file
//         $file->move($destinationPath, $filename);

//         // Update or create the Esig row
//         Esig::updateOrCreate(
//             ['user_id' => $user->id],
//             ['esig_file' => $filename]
//         );
//     }

//     return redirect()->route('userPassword', ['id' => $id])
//         ->with('success', 'User updated successfully.');
// }
public function passChange(Request $request, $id)
{
    $validator = Validator::make($request->all(), [
        'email' => 'nullable|string|max:255|unique:users,email,' . $id,
        'password' => 'nullable|string|min:8|confirmed',
        'department' => 'nullable|string|max:255',
        'esig_file' => 'nullable|file|mimes:jpeg,png,jpg,pdf',
    ]);

    if ($validator->fails()) {
        return redirect()->back()->withErrors($validator)->withInput();
    }

    $user = User::find($id);
    if (!$user) {
        return redirect()->back()->with('error', 'User not found');
    }

    // Update only fields that were filled
    if ($request->filled('fname')) $user->fname = $request->input('fname');
    if ($request->filled('mname')) $user->mname = $request->input('mname');
    if ($request->filled('lname')) $user->lname = $request->input('lname');
    if ($request->filled('email')) $user->email = $request->input('email');
    if ($request->filled('department')) $user->department = $request->input('department');
    if ($request->filled('password')) $user->password = Hash::make($request->input('password'));

    $user->save();

    // ✅ Handle E-signature upload
    if ($request->hasFile('esig_file')) {
        $file = $request->file('esig_file');
        $filename = $user->fname . '_' . $file->getClientOriginalName();

        $storagePath = 'public/esignature'; // store in storage/app/public/esignature   
        Storage::putFileAs($storagePath, $file, $filename);

        // Retrieve existing esig row
        $existingEsig = Esig::where('user_id', $user->id)->first();

        // If exists, delete old file before replacing
        if ($existingEsig && $existingEsig->esig_file) {
            Storage::delete($storagePath . '/' . $existingEsig->esig_file);
        }

        // Save the file inside storage/app/esignature
        Storage::putFileAs($storagePath, $file, $filename);

        // Update or create the Esig row
        Esig::updateOrCreate(
            ['user_id' => $user->id],
            ['esig_file' => $filename]
        );
    }

    return redirect()->route('userPassword', ['id' => $id])
        ->with('success', 'User updated successfully.');
}

public function distributionList()
{
    $user = auth()->user();
    $userRole = $user->role;

    if (!in_array($userRole, ['Administrator', 'records_officer'])) {
        abort(403, 'Unauthorized');
    }

    $logs = RoutingSlip::leftJoin('documents', 'routing_slip.rslip_id', '=', 'documents.route_id')
    ->select('routing_slip.*', 'documents.id as doc_id', 'documents.file_name')
    ->orderBy('routing_slip.created_at', 'desc')
    ->get()
    ->filter(function ($item) {
        $routedUsers = $item->routed_users
            ? array_filter(array_map('trim', explode(',', $item->routed_users)))
            : [];
        return count($routedUsers) >= 2;
    });



    $offices = Office::all();

    $doctrackCount = Doctrack::where(function ($query) use ($user) {
        $query->where('user_id', $user->id)
              ->orWhere('update_by', $user->id)
              ->orWhere('user_name', $user->fname . ' ' . $user->lname);
    })->distinct('docslip_id')->count();

    return view('home.distList', compact('logs', 'offices', 'doctrackCount'));
}

public function viewDistribution($id)
{
    $routingSlip = RoutingSlip::with('document')->findOrFail($id);
    $title = 'DISTRIBUTION/RETRIEVAL LIST';

    // Eager load the newUser relationship
    $logs = Log::with('newUser')->where('route_id', $routingSlip->rslip_id)->get();

    return view('slip.distForm', compact('title', 'routingSlip', 'logs'));
}


public function viewDistributionPdf($id)
{
    $routingSlip = DB::table('routing_slip')->where('rslip_id', $id)->first();

    // Fetch logs with user info
    $logs = DB::table('logs')
        ->leftJoin('users', 'logs.new_user', '=', 'users.id')
        ->where('logs.route_id', $id)
        ->select('logs.*', 'users.department as user_department')
        ->get();

    // Add e-signature (if any) for each log where status_update == 3
    $logs->transform(function ($log) {
        if ($log->status_update == 3) {
            $esig = \App\Models\Esig::where('user_id', $log->new_user)->first();
            $log->esig_file = $esig ? $esig->esig_file : null;
        } else {
            $log->esig_file = null;
        }
        return $log;
    });

    $title = 'DISTRIBUTION/RETRIEVAL LIST';

    return PDF::loadView('slip.distPdf', compact('routingSlip', 'logs', 'title'))
        ->stream('distribution_list.pdf');
}





}
