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

    public function logbookPdf(Request $request)
{
    $user = auth()->user();
    $userId = $user->id;
    $userDepartment = $user->department;
    $userFullName = $user->fname . ' ' . $user->lname;
    $userRole = $user->role;

    // ✅ Same query as dashboard
    $logs = Log::with(['routingSlip', 'document'])
        ->where(function ($query) use ($userId, $userDepartment) {
            $query->where('new_user', $userId)
                  ->orWhere('user_id', $userId)
                  ->orWhere('new_destination', $userDepartment);
        })
        ->orWhereHas('routingSlip', function ($q) use ($userDepartment) {
            $q->where('pres_dept', $userDepartment);
        });

    // ✅ Apply filters only if user selected
    if ($request->filled('date_from') && $request->filled('date_to')) {
        $logs->whereBetween('created_at', [
            $request->date_from,
            \Carbon\Carbon::parse($request->date_to)->endOfDay()
        ]);
    }

    if ($request->filled('status')) {
        $logs->where('status_update', $request->status);
    }

    $logs = $logs->get();

    // ✅ Build counts (same as dashboard)
    $routingSlipCount = ($logs->every(fn($log) => $log->status_update != 3))
        ? RoutingSlip::where('route_status', 3)->count()
        : 0;

    $superUserCount = $userRole === 'super_user'
        ? RoutingSlip::where('route_status', 1)->count()
        : 0;

    $recordsOfficerCount = $userRole === 'records_officer'
        ? RoutingSlip::where('route_status', 2)->count()
        : 0;

    $groups = User::select('id', 'fname', 'lname', 'department')
        ->orderBy('department')
        ->orderBy('lname')
        ->get()
        ->groupBy('department');

    $offices = Office::all();
    $dpa = $user->dpa;
    $users = User::all();

    // ✅ Doctrack
    $documentTrack = Doctrack::with(['createdBy', 'doctrackFile'])
        ->where(function ($query) use ($userId, $userFullName) {
            $query->where('user_id', $userId)
                  ->orWhere('update_by', $userId)
                  ->orWhere('user_name', $userFullName);
        })
        ->orderByDesc('created_at')
        ->get();

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

    $doctrackCount = $documentTrack->where('doctrack_stat', 2)->count();

    // ✅ For PDF
    if ($request->ajax() || $request->wantsJson() || $request->has('date_from')) {
    $pdf = Pdf::loadView('print.logbookPdf', compact(
        'offices', 'logs', 'routingSlipCount', 'superUserCount',
        'recordsOfficerCount', 'dpa', 'users', 'doctrackCount', 'groups'
    ))->setPaper('legal', 'landscape'); // 👈 Long bondpaper size

    return $pdf->stream('logbook.pdf');
}

    // ✅ For normal blade
    return view('print.printLogbook', compact(
        'offices', 'logs', 'routingSlipCount', 'superUserCount',
        'recordsOfficerCount', 'dpa', 'users', 'doctrackCount', 'groups'
    ));
}

}
