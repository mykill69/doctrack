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
    $userDepartment = auth()->user()->department;
    $userId = auth()->user()->id;

    $logs = Log::where(function ($query) use ($userId, $userDepartment) {
        $query->where('new_user', $userId)
              ->orWhere('user_id', $userId)
              ->orWhere('new_destination', $userDepartment);
    })->get(); 

    $routingSlipCount = ($logs->every(fn($log) => $log->status_update != 3)) ? RoutingSlip::where('route_status', 3)->count() : 0;
    $superUserCount = auth()->user()->hasRole('super_user') ? RoutingSlip::where('route_status', 1)->count() : 0;
    $recordsOfficerCount = auth()->user()->hasRole('records_officer') ? RoutingSlip::where('route_status', 2)->count() : 0; 

    $offices = Office::all();

    return view('home.incoming', compact('offices', 'logs', 'routingSlipCount', 'superUserCount', 'recordsOfficerCount'));
}

public function doctrackSlip()
{
    $userDepartment = auth()->user()->department;
    $userId = auth()->user()->id;

    // Retrieve logs based on the current user or department
    $logs = Log::where(function ($query) use ($userId, $userDepartment) {
        $query->where('new_user', $userId)
              ->orWhere('user_id', $userId)
              ->orWhere('new_destination', $userDepartment);
    })->get(); 

    $routingSlipCount = ($logs->every(fn($log) => $log->status_update != 3)) ? RoutingSlip::where('route_status', 3)->count() : 0;
    $superUserCount = auth()->user()->hasRole('super_user') ? RoutingSlip::where('route_status', 1)->count() : 0;
    $recordsOfficerCount = auth()->user()->hasRole('records_officer') ? RoutingSlip::where('route_status', 2)->count() : 0; 

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
                $item->time_diff = ['days' => 0, 'hours' => 0, 'minutes' => 0]; // No diff for the first item
            } else {
                // For subsequent items, calculate the time difference from the oldest
                $start = \Carbon\Carbon::parse($group->first()->created_at);
                $end = \Carbon\Carbon::parse($item->updated_at);
                $diffInMinutes = $end->diffInMinutes($start);

                // Add time_diff to the item object
                $item->time_diff = [
                    'days' => floor($diffInMinutes / 1440),
                    'hours' => floor(($diffInMinutes % 1440) / 60),
                    'minutes' => $diffInMinutes % 60,
                ];
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



    // public function pending()
    // {

    // $userDepartment = auth()->user()->department;
    // $userId = auth()->user()->id;

    // $logs = Log::where(function ($query) use ($userDepartment, $userId) {
    // $query->where('new_destination', $userDepartment)
    // ->orWhere('user_id', $userId); 
    // })
    // ->with('document') 
    // ->orderBy('created_at', 'desc') 
    // ->get();
    // $offices = Office::all();
    
    // $recordsOfficerCount = auth()->user()->hasRole('records_officer') ? RoutingSlip::where('route_status', 2)->count() : 0;

    // $superUserCount = auth()->user()->hasRole('super_user') ? RoutingSlip::where('route_status', 1)->count() : 0; 

    // return view('home.pending', compact('logs', 'offices', 'recordsOfficerCount','superUserCount'));
    // }
    public function pending()
{
    $userDepartment = auth()->user()->department;
    $userId = auth()->user()->id;

    $logs = Log::leftJoin('documents', 'logs.doc_id', '=', 'documents.id')
        ->leftJoin('routing_slip', 'logs.route_id', '=', 'routing_slip.rslip_id') // Left join with routing_slip
        ->select('logs.*', 'documents.*', 'routing_slip.*') // Select date_received from routing_slip
        ->when(!auth()->user()->hasRole('records_officer'), function ($query) {
            return $query->where('logs.status_update', '!=', 3);
        })
        ->when(auth()->user()->hasRole('records_officer'), function ($query) {
            return $query->where('logs.status_update', 2);
        }, function ($query) use ($userDepartment, $userId) {
            return $query->where(function ($subQuery) use ($userDepartment, $userId) {
                $subQuery->where('logs.new_destination', $userDepartment)
                         ->orWhere('logs.user_id', $userId);
            });
        })
        ->orderBy('logs.created_at', 'desc')
        ->get()
        ->groupBy('logs.doc_id');

    $offices = Office::all();

    $recordsOfficerCount = auth()->user()->hasRole('records_officer') ? RoutingSlip::where('route_status', 2)->count() : 0;
    $superUserCount = auth()->user()->hasRole('super_user') ? RoutingSlip::where('route_status', 1)->count() : 0;

    return view('home.pending', compact('logs', 'offices', 'recordsOfficerCount', 'superUserCount'));
}


    public function served()
    {
    
    $userId = auth()->user()->id;
    
    $logs = Log::where(function($query) use ($userId) {
    $query->where('new_user', $userId)
    ->orWhere('user_id', $userId);
    })->whereNotNull('new_user')->get(); 
    $offices = Office::all();
    
    $recordsOfficerCount = auth()->user()->hasRole('records_officer') ? RoutingSlip::where('route_status', 2)->count() : 0;

    $superUserCount = auth()->user()->hasRole('super_user') ? RoutingSlip::where('route_status', 1)->count() : 0; 

    return view('home.served', compact('logs', 'offices', 'recordsOfficerCount','superUserCount'));
    }

    public function viewLogs()
{
    $userId = auth()->user()->id;
    $userDepartment = auth()->user()->department;

    $logsAll = LogsHistory::leftJoin('logs', 'logs.doc_id', '=', 'logs_history.doc_id')
        ->leftJoin('users as original_users', function ($join) {
            $join->on('logs.user_id', '=', 'original_users.id')
                 ->where('logs_history.status_update', '=', 2);
        })
        ->leftJoin('users as new_users', function ($join) {
            $join->on('logs.new_user', '=', 'new_users.id')
                 ->where('logs_history.status_update', '=', 3);
        })
        ->select(
            'logs_history.*',
            'logs.new_destination',
            'logs.new_file',
            'original_users.fname as original_fname',
            'original_users.lname as original_lname',
            'new_users.fname as new_fname',
            'new_users.lname as new_lname',
            'new_users.department as new_user_department', 
            'original_users.department as original_user_department',
'new_users.department as new_user_department'
        )
        ->distinct()
        ->orderBy('logs_history.updated_at', 'desc')
        ->get();

    // Routing slip counts
    $routingSlipCount = RoutingSlip::where('route_status', 3)->count();
    $superUserCount = auth()->user()->hasRole('super_user') ? RoutingSlip::where('route_status', 1)->count() : 0;
    $recordsOfficerCount = auth()->user()->hasRole('records_officer') ? RoutingSlip::where('route_status', 2)->count() : 0;

    return view('home.viewLogs', compact(
        'logsAll', 'userId', 'userDepartment',
        'routingSlipCount', 'superUserCount', 'recordsOfficerCount'
    ));
}






    public function userPassword($id)
{

    if (auth()->user()->role === 'Administrator') {
        return redirect()->back()->with('error', 'Administrators do not have access to this page.');
    }

    $user = User::find($id);

    if (!$user) {
        return redirect()->back()->with('error', 'User not found');
    }

    $recordsOfficerCount = auth()->user()->hasRole('records_officer') 
        ? RoutingSlip::where('route_status', 2)->count() 
        : 0;

    $offices = Office::all();
    $office = $user->department;
    $superUserCount = auth()->user()->hasRole('super_user') ? RoutingSlip::where('route_status', 1)->count() : 0;

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
