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
use App\Models\User;
use App\Models\Doctrack;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;




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
    $userDepartment = $user->department;
    $userId = $user->id;
    $userFullName = $user->fname . ' ' . $user->lname;
    $userRole = $user->role;

    // Retrieve logs based on the current user or department
    $logs = Log::where(function ($query) use ($userId, $userDepartment) {
        $query->where('new_user', $userId)
              ->orWhere('user_id', $userId)
              ->orWhere('new_destination', $userDepartment);
    })->get(); 

    $routingSlipCount = ($logs->every(fn($log) => $log->status_update != 3)) ? RoutingSlip::where('route_status', 3)->count() : 0;
    $superUserCount = $userRole === 'super_user' ? RoutingSlip::where('route_status', 1)->count() : 0;
$recordsOfficerCount = $userRole === 'records_officer' ? RoutingSlip::where('route_status', 2)->count() : 0;

    $offices = Office::all();

    // Get document tracking slip with user info
   $documentTrack = Doctrack::with(['createdBy', 'doctrackFile'])->get();

    // Group by docslip_id
    $groupedTrack = $documentTrack->groupBy('docslip_id')->map(function ($group) {
        // Sort by the created_at time to get the oldest document in the group
        $group = $group->sortBy('created_at');

        // Initialize an empty array to hold time differences
        $group->map(function ($item, $index) use ($group) {
            if ($index == 0) {
                // For the first item in the group, consider it the oldest
                $oldest = $item;
               $item->setAttribute('time_diff',['days' => 0, 'hours' => 0, 'minutes' => 0]); // No diff for the first item
            } else {
                // For subsequent items, calculate the time difference from the oldest
                $start = \Carbon\Carbon::parse($group->first()->created_at);
                $end = \Carbon\Carbon::parse($item->updated_at);
                $diffInMinutes = $end->diffInMinutes($start);

                // Add time_diff to the item object
               $item->setAttribute('time_diff', [
                    'days' => floor($diffInMinutes / 1440),
                    'hours' => floor(($diffInMinutes % 1440) / 60),
                    'minutes' => $diffInMinutes % 60,
                ]);
            }
            return $item;
        });
        return $group;
    });

    return view('home.doctrackSlip', compact(
        'documentTrack', 'groupedTrack', 'offices',
        'logs', 'routingSlipCount', 'superUserCount', 'recordsOfficerCount'
    ));
}


public function pending()
{
    $user = auth()->user();
    $userDepartment = $user->department;
    $userId = $user->id;
    $userFullName = $user->fname . ' ' . $user->lname;
    $userRole = $user->role;

    // Base query
    $query = Log::leftJoin('documents', 'logs.doc_id', '=', 'documents.id')
        ->leftJoin('routing_slip', 'logs.route_id', '=', 'routing_slip.rslip_id')
        ->select('logs.*', 'documents.*', 'routing_slip.*')
        ->orderBy('logs.created_at', 'desc');

    // Role-specific filters
    if ($userRole === 'records_officer') {
        $query->where('logs.status_update', 2);
    } else {
        $query->where('logs.status_update', '!=', 3)
              ->where(function ($subQuery) use ($userDepartment, $userId, $userFullName) {
                  $subQuery->where('logs.new_destination', $userDepartment)
                           ->orWhere('logs.user_id', $userId)
                           ->orWhereRaw("FIND_IN_SET(?, routing_slip.routed_users)", [$userFullName]);
              });
    }

    $logs = $query->get()->groupBy('logs.doc_id');

    $offices = Office::all();

    $recordsOfficerCount = ($userRole === 'records_officer') 
        ? RoutingSlip::where('route_status', 2)->count() 
        : 0;

    $superUserCount = ($userRole === 'super_user') 
        ? RoutingSlip::where('route_status', 1)->count() 
        : 0;

    return view('home.pending', compact('logs', 'offices', 'recordsOfficerCount', 'superUserCount'));
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

    $offices = Office::all();

    $recordsOfficerCount = $userRole === 'records_officer'
        ? RoutingSlip::where('route_status', 2)->count()
        : 0;

    $superUserCount = $userRole === 'super_user'
        ? RoutingSlip::where('route_status', 1)->count()
        : 0;

    return view('home.served', compact('logs', 'offices', 'recordsOfficerCount', 'superUserCount'));
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










    public function userPassword($id)
{

    if (auth()->user()->role === 'Administrator') {
        return redirect()->back()->with('error', 'Administrators do not have access to this page.');
    }

    $user = User::find($id);
$user = auth()->user();
    $userDepartment = $user->department;
    $userId = $user->id;
    $userFullName = $user->fname . ' ' . $user->lname;
    $userRole = $user->role;
    if (!$user) {
        return redirect()->back()->with('error', 'User not found');
    }

    $recordsOfficerCount = $userRole->hasRole('records_officer') 
        ? RoutingSlip::where('route_status', 2)->count() 
        : 0;

    $offices = Office::all();
    $office = $user->department;
    $superUserCount = $userRole->hasRole('super_user') ? RoutingSlip::where('route_status', 1)->count() : 0;

    return view('home.changepass', compact('user', 'offices', 'office', 'recordsOfficerCount','superUserCount'));
}

public function passChange(Request $request, $id)
{

    $validator = Validator::make($request->all(), [
        'username' => 'required|string|max:255|unique:users,username,' . $id . ',id',
        'password' => 'nullable|string|min:8|confirmed',
        'department' => 'required|string|max:255',

    ]);

    if ($validator->fails()) {
        return redirect()->back()->withErrors($validator)->withInput();
    }

    $user = User::find($id);
    if (!$user) {
        return redirect()->back()->with('error', 'User not found');
    }

    $user->fname = $request->input('fname');
    $user->mname = $request->input('mname');
    $user->lname = $request->input('lname');
    $user->username = $request->input('username');
    $user->department = $request->input('department');

    if (!empty($request->input('password'))) {
        $user->password = Hash::make($request->input('password'));
    }

    $user->save();

    return redirect()->route('userPassword', ['id' => $id])->with('success', 'User updated successfully.');
}


}
