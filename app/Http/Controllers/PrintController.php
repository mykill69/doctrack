<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Document;
use App\Models\Office;
use App\Models\Log;
use App\Models\User;
use App\Models\Doctrack;
use App\Models\LogsHistory;
use App\Models\RoutingSlip;
use App\Models\RouteDocument;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;


class PrintController extends Controller
{
    public function printLogbook()
    {
            $dpa = auth()->user()->dpa;

        return view('print.printLogbook', (compact('dpa')));
    }

//    public function logbookPdf(Request $request)
// {
//     $user = auth()->user();
//     $userId = $user->id;
//     $userDepartment = $user->department;
//     $userFullName = $user->fname . ' ' . $user->lname;
//     $userRole = $user->role;

//     $logs = Log::with('routingSlip')
//         ->where(function ($query) use ($userId, $userDepartment) {
//             $query->where('new_user', $userId)
//                   ->orWhere('user_id', $userId)
//                   ->orWhere('new_destination', $userDepartment);
//         })
//         ->orWhereHas('routingSlip', function ($q) use ($userDepartment) {
//             $q->where('pres_dept', $userDepartment);
//         });

//     // ✅ Date filter
//     if ($request->filled('date_from') && $request->filled('date_to')) {
//         $logs->whereBetween('created_at', [
//             $request->date_from,
//             \Carbon\Carbon::parse($request->date_to)->endOfDay()
//         ]);
//     }

//     // ✅ Status filter
//     if ($request->filled('status')) {
//         $logs->where('status_update', $request->status);
//     }

//     $logs = $logs->get();

//     // If request comes from iframe, return PDF view
//     if ($request->ajax() || $request->wantsJson() || $request->has('date_from')) {
//         $pdf = Pdf::loadView('print.logbookPdf', compact('logs'));
//         return $pdf->stream('logbook.pdf');
//     }

//     $routingSlipCount = ($logs->every(fn($log) => $log->status_update != 3))
//         ? RoutingSlip::where('route_status', 3)->count()
//         : 0;

//     $superUserCount = auth()->user()->role === 'super_user'
//         ? RoutingSlip::where('route_status', 1)->count()
//         : 0;

//     $recordsOfficerCount = auth()->user()->role === 'records_officer'
//         ? RoutingSlip::where('route_status', 2)->count()
//         : 0;

//  $groups = User::select('id', 'fname', 'lname', 'department')
//         ->orderBy('department')
//         ->orderBy('lname')
//         ->get()
//         ->groupBy('department');
        

//     $offices = Office::all();
//     $dpa = auth()->user()->dpa;

//   // Get all Doctrack records (no grouping)
//     $documentTrack = Doctrack::with(['createdBy', 'doctrackFile'])
//         ->where(function ($query) use ($userId, $userFullName) {
//             $query->where('user_id', $userId)
//                   ->orWhere('update_by', $userId)
//                   ->orWhere('user_name', $userFullName);
//         })
//         ->orderByDesc('created_at')
//         ->get();

//     // Calculate time_diff for each record here
//     $documentTrack->transform(function ($item) {
//         $start = \Carbon\Carbon::parse($item->created_at);
//         $end = \Carbon\Carbon::parse($item->updated_at ?? $item->created_at);
//         $diffInMinutes = $end->diffInMinutes($start);

//         $item->time_diff = [
//             'days' => floor($diffInMinutes / 1440),
//             'hours' => floor(($diffInMinutes % 1440) / 60),
//             'minutes' => $diffInMinutes % 60,
//         ];

//         return $item;
//     });

//     // Count only records with doctrack_stat == 2
//     $doctrackCount = $documentTrack->where('doctrack_stat', 2)->count();


//     return view('print.printLogbook', compact('offices', 'logs', 'routingSlipCount', 'superUserCount', 'recordsOfficerCount', 'dpa','users','doctrackCount' , 'groups'));
// }

// public function logbookPdf(Request $request)
// {
//     $user = auth()->user();
//     $userId = $user->id;
//     $userDepartment = $user->department;
//     $userFullName = $user->fname . ' ' . $user->lname;
//     $userRole = $user->role;

//     $date_from = $request->input('date_from');
//     $date_to   = $request->input('date_to');
//     $status    = $request->input('status');

//     $latestLogs = Log::query()
//     ->select('route_id', DB::raw('MAX(created_at) as latest_created_at'))
//     ->groupBy('route_id');

// if ($date_from && $date_to) {
//     $latestLogs->whereBetween('created_at', [
//         $date_from,
//         \Carbon\Carbon::parse($date_to)->endOfDay()
//     ]);
// }

// $latestLogs = Log::query()
//     ->select('route_id', DB::raw('MAX(created_at) as latest_created_at'))
//     ->groupBy('route_id');

// if ($date_from && $date_to) {
//     $latestLogs->whereBetween('created_at', [
//         $date_from,
//         \Carbon\Carbon::parse($date_to)->endOfDay()
//     ]);
// }

// $logs = Log::query()
//     ->joinSub($latestLogs, 'latest_logs', function ($join) {
//         $join->on('logs.route_id', '=', 'latest_logs.route_id')
//              ->on('logs.created_at', '=', 'latest_logs.latest_created_at');
//     })
//     ->leftJoin('documents', 'logs.doc_id', '=', 'documents.id')
//     ->leftJoin('routing_slip', 'logs.doc_id', '=', 'routing_slip.rslip_id')
//     ->select(
//         'logs.id',
//         'logs.route_id',
//         'logs.doc_id',
//         'logs.new_destination',
//         'logs.new_user',
//         'logs.user_id',
//         'logs.assigned_to',
//         'logs.comments',
//         'logs.status_update',
//         'logs.created_at',
//         'documents.created_at as document_created_at',
//         'documents.department as document_department',
//         'documents.subject as document_subject',
//         'documents.route_id as document_route_id',
//         'routing_slip.date_received',
//         'routing_slip.source',
//         'routing_slip.subject as rs_subject',
//         'routing_slip.pres_dept',
//         'routing_slip.r_destination',
//         'routing_slip.updated_at as rs_updated_at',
//         DB::raw("GROUP_CONCAT(DISTINCT routing_slip.trans_remarks SEPARATOR ', ') as merged_remarks"),
//         'routing_slip.other_remarks'
//     )
//     ->where(function ($query) use ($userId, $userDepartment) {
//         $query->where('logs.new_user', $userId)
//               ->orWhere('logs.user_id', $userId)
//               ->orWhere('logs.new_destination', $userDepartment);
//     })
//     ->orWhere('routing_slip.pres_dept', $userDepartment)
//     ->groupBy(
//         'logs.id',
//         'logs.route_id',
//         'logs.doc_id',
//         'logs.new_destination',
//         'logs.new_user',
//         'logs.user_id',
//         'logs.assigned_to',
//         'logs.comments',
//         'logs.status_update',
//         'logs.created_at',
//         'documents.created_at',
//         'documents.department',
//         'documents.subject',
//         'documents.route_id',
//         'routing_slip.date_received',
//         'routing_slip.source',
//         'routing_slip.subject',
//         'routing_slip.pres_dept',
//         'routing_slip.r_destination',
//         'routing_slip.updated_at',
//         'routing_slip.other_remarks'
//     );

//     if ($status) {
//         $logs->where('logs.status_update', $status);
//     }

//     // ✅ Always order by route_id ascending
//     $logs = $logs->orderBy('logs.route_id', 'asc')->get();



//     // ✅ Build counts (same as dashboard)
//     $routingSlipCount = ($logs->every(fn($log) => $log->status_update != 3))
//         ? RoutingSlip::where('route_status', 3)->count()
//         : 0;

//     $superUserCount = $userRole === 'super_user'
//         ? RoutingSlip::where('route_status', 1)->count()
//         : 0;

//     $recordsOfficerCount = $userRole === 'records_officer'
//         ? RoutingSlip::where('route_status', 2)->count()
//         : 0;

//     $groups = User::select('id', 'fname', 'lname', 'department')
//         ->orderBy('department')
//         ->orderBy('lname')
//         ->get()
//         ->groupBy('department');

//     $offices = Office::all();
//     $dpa = $user->dpa;
//     $users = User::all();

//     // ✅ Doctrack
//     $documentTrack = Doctrack::with(['createdBy', 'doctrackFile'])
//         ->where(function ($query) use ($userId, $userFullName) {
//             $query->where('user_id', $userId)
//                   ->orWhere('update_by', $userId)
//                   ->orWhere('user_name', $userFullName);
//         })
//         ->orderByDesc('created_at')
//         ->get();

//     $documentTrack->transform(function ($item) {
//         $start = \Carbon\Carbon::parse($item->created_at);
//         $end = \Carbon\Carbon::parse($item->updated_at ?? $item->created_at);
//         $diffInMinutes = $end->diffInMinutes($start);

//         $item->time_diff = [
//             'days' => floor($diffInMinutes / 1440),
//             'hours' => floor(($diffInMinutes % 1440) / 60),
//             'minutes' => $diffInMinutes % 60,
//         ];
//         return $item;
//     });

//     $doctrackCount = $documentTrack->where('doctrack_stat', 2)->count();

//     // ✅ For PDF
//     if ($request->ajax() || $request->wantsJson() || $request->has('date_from')) {
//     $pdf = Pdf::loadView('print.logbookPdf', compact(
//         'offices', 'logs', 'routingSlipCount', 'superUserCount',
//         'recordsOfficerCount', 'dpa', 'users', 'doctrackCount', 'groups'
//     ))->setPaper('legal', 'landscape'); // 👈 Long bondpaper size

//     return $pdf->stream('logbook.pdf');
// }

//     // ✅ For normal blade
//     return view('print.printLogbook', compact(
//         'offices', 'logs', 'routingSlipCount', 'superUserCount',
//         'recordsOfficerCount', 'dpa', 'users', 'doctrackCount', 'groups'
//     ));
// }

public function logbookPdf(Request $request)
{
    $user = auth()->user();
    $userId = $user->id;
    $userDepartment = $user->department;
    $userFullName = $user->fname . ' ' . $user->lname;
    $userRole = $user->role;
    $isPresident = ($userId == 38);

    $ctrl_from = $request->input('ctrl_from');
    $ctrl_to   = $request->input('ctrl_to');
    $status    = $request->input('status');

    // Get unique route_ids from logs that match user access
    $logsQuery = Log::query()
        ->select('route_id')
        ->selectRaw('MAX(id) as max_id')
        ->whereNotNull('new_user')
        ->groupBy('route_id');

    // CTRL # range filter
    if ($ctrl_from && $ctrl_to) {
        $logsQuery->whereBetween('route_id', [$ctrl_from, $ctrl_to]);
    }

    // Status filter
    if ($status) {
        $logsQuery->where('status_update', $status);
    }

    // Access control
    if (!$isPresident) {
        if ($userRole === 'records_officer') {
            // Records officer: ONLY their own created logs + routing slips they created
            $logsQuery->where(function ($q) use ($userId) {
                $q->where('user_id', $userId)
                  ->orWhereHas('routingSlip', function ($sq) use ($userId) {
                      $sq->where('user_id', $userId);
                  });
            });
        } else {
            // Regular users / staff
            $logsQuery->where(function ($q) use ($userId, $userDepartment, $userFullName) {
                $q->where('new_user', $userId)
                  ->orWhere('user_id', $userId)
                  ->orWhere('new_destination', $userDepartment)
                  ->orWhere('new_destination', $userFullName);
            });
        }
    }

    $uniqueLogIds = $logsQuery->pluck('max_id');

    // Get the actual logs with their data
    $logs = Log::with(['routingSlip', 'document'])
        ->whereIn('id', $uniqueLogIds)
        ->orderBy('route_id', 'asc')
        ->get();

    // Preload exact routing slips and documents
    $routeIds = $logs->pluck('route_id')->unique();
    $newFiles = $logs->pluck('new_file')->unique();

    $allRoutingSlips = RoutingSlip::whereIn('rslip_id', $routeIds)
        ->whereIn('document', $newFiles)
        ->get();

    $allDocuments = Document::whereIn('route_id', $routeIds)
        ->whereIn('file_name', $newFiles)
        ->get();

    // Attach exact data to each log
    foreach ($logs as $log) {
        $log->exactSlip = $allRoutingSlips
            ->where('rslip_id', $log->route_id)
            ->where('document', $log->new_file)
            ->first();
        
        $log->exactDoc = $allDocuments
            ->where('route_id', $log->route_id)
            ->where('file_name', $log->new_file)
            ->first();
    }

    // Remove duplicates by route_id (keep first)
    $logs = $logs->unique('route_id')->values();

    // Counts
    $routingSlipCount = RoutingSlip::where('route_status', 3)->count();
    $superUserCount = $userRole === 'super_user' ? RoutingSlip::where('route_status', 1)->count() : 0;
    $recordsOfficerCount = $userRole === 'records_officer' ? RoutingSlip::where('route_status', 2)->count() : 0;

    $groups = User::select('id', 'fname', 'lname', 'department')
        ->orderBy('department')->orderBy('lname')->get()->groupBy('department');

    $offices = Office::all();
    $dpa = $user->dpa;
    $users = User::all();
    $doctrackCount = Doctrack::where('doctrack_stat', 2)->count();

    $data = compact(
        'offices', 'logs', 'routingSlipCount', 'superUserCount',
        'recordsOfficerCount', 'dpa', 'users', 'doctrackCount', 'groups', 'isPresident'
    );

    if ($request->ajax() || $request->wantsJson() || $request->has('ctrl_from')) {
        $pdf = Pdf::loadView('print.logbookPdf', $data)->setPaper('legal', 'landscape');
        return $pdf->stream('logbook.pdf');
    }

    return view('print.printLogbook', $data);
}

}
