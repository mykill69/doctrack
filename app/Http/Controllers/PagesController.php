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
use App\Models\DoctrackFile;
use App\Services\ArchiveService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

use Illuminate\Support\Facades\Response;
use Barryvdh\DomPDF\Facade\Pdf;



class PagesController extends Controller
{
   
//     public function outgoingDocs()
// {
//     $user = auth()->user();
//     $userId = $user->id;
//     $userFullName = $user->fname . ' ' . $user->lname;
//     $userRole = $user->role;

//     // Logs for routing slip counts
//     $logs = Log::where(function ($query) use ($userId) {
//         $query->where('new_user', $userId)
//               ->orWhere('user_id', $userId);
//     })->get();

//     // Routing slip counts
//     $routingSlipCount = ($logs->every(fn($log) => $log->status_update != 3)) 
//         ? RoutingSlip::where('route_status', 3)->count() 
//         : 0;

//     $superUserCount = $userRole === 'super_user' 
//         ? RoutingSlip::where('route_status', 1)->count() 
//         : 0;

//     $recordsOfficerCount = $userRole === 'records_officer' 
//         ? RoutingSlip::where('route_status', 2)->count() 
//         : 0;

//     $offices = Office::all();

//     // Get all Doctrack records (no grouping)
//     // $documentTrack = Doctrack::with(['createdBy', 'doctrackFile'])
//     //     ->where(function ($query) use ($userId, $userFullName) {
//     //         $query->where('user_id', $userId)
//     //               ->orWhere('update_by', $userId)
//     //               ->orWhere('user_name', $userFullName);
//     //     })
//     //     ->orderByDesc('created_at')
//     //     ->get();

//     $documentTrack = Doctrack::with(['createdBy', 'doctrackFile'])
//     ->where('user_id', 56) // Only for this creator
//     ->whereNotNull('update_by') // Exclude creator rows (no updates)
//     ->where(function ($query) use ($userId, $userFullName) {
//         $query->where('user_id', $userId)
//               ->orWhere('update_by', $userId)
//               ->orWhere('user_name', $userFullName);
//     })
//     ->orderByDesc('created_at')
//     ->get();


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


// $documentTrack->transform(function ($item) {
//     $query = LogsTracking::where('docslip_id', $item->docslip_id)
//         ->whereNotNull('viewed_status')
//         ->whereNotNull('viewed_at');

//     // Match this specific row's person
//     if (!is_null($item->update_by)) {
//         // If updated_by exists → show logs for that updater
//         $query->where('update_by', $item->update_by);
//     } else {
//         // If no updater → show logs for the original owner
//         $query->where('user_id', $item->user_id);
//     }

//    $item->views = $query->orderBy('viewed_at', 'asc')->limit(1)->get();


//     // Duration calculation
//     $start = \Carbon\Carbon::parse($item->created_at);
//     $end = \Carbon\Carbon::parse($item->updated_at ?? $item->created_at);
//     $diffInMinutes = $end->diffInMinutes($start);

//     $item->time_diff = [
//         'days' => floor($diffInMinutes / 1440),
//         'hours' => floor(($diffInMinutes % 1440) / 60),
//         'minutes' => $diffInMinutes % 60,
//     ];

//     return $item;
// });


// $documentTrack->transform(function ($item) {
//     $query = LogsTracking::where('docslip_id', $item->docslip_id)
//         ->whereNotNull('comments');

//     if (!is_null($item->update_by)) {
//         $query->where('update_by', $item->update_by);
//     } else {
//         $query->where('user_id', $item->user_id);
//     }

//     // Get both comment text and created_at
//     $item->all_comments = $query->get(['comments', 'created_at']);

//     return $item;
// });



//     // Count only records with doctrack_stat == 2
//     $doctrackCount = $documentTrack->where('doctrack_stat', 2)->count();

//     return view('home.outgoingDocs', compact(
//         'documentTrack', 'offices',
//         'logs', 'routingSlipCount', 'superUserCount', 
//         'recordsOfficerCount', 'doctrackCount'
//     ));
// }

public function outgoingDocs()
{
    $user = auth()->user();
    $userId = $user->id;
    $userFullName = $user->fname . ' ' . $user->lname;
    $userRole = $user->role;

    // ✅ Keep this exactly as is
    $logs = Log::where(function ($query) use ($userId) {
        $query->where('new_user', $userId)
              ->orWhere('user_id', $userId);
    })->get();

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

    // ✅ Same logic, just use paginate instead of get
    $documentTrack = Doctrack::with(['createdBy', 'doctrackFile'])
        ->where('user_id', 56)
        ->whereNotNull('update_by')
        ->where(function ($query) use ($userId, $userFullName) {
            $query->where('user_id', $userId)
                  ->orWhere('update_by', $userId)
                  ->orWhere('user_name', $userFullName);
        })
        ->orderByDesc('created_at')
        ->paginate(50);  // ← ONLY changed get() to paginate(50)

    // ✅ Keep ALL transformations on the paginated collection
    $documentTrack->getCollection()->transform(function ($item) {
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

    // ✅ Keep the views transformation
    $documentTrack->getCollection()->transform(function ($item) {
        $query = LogsTracking::where('docslip_id', $item->docslip_id)
            ->whereNotNull('viewed_status')
            ->whereNotNull('viewed_at');
        if (!is_null($item->update_by)) {
            $query->where('update_by', $item->update_by);
        } else {
            $query->where('user_id', $item->user_id);
        }
        $item->views = $query->orderBy('viewed_at', 'asc')->limit(1)->get();
        return $item;
    });

    // ✅ Keep the comments transformation
    $documentTrack->getCollection()->transform(function ($item) {
        $query = LogsTracking::where('docslip_id', $item->docslip_id)
            ->whereNotNull('comments');
        if (!is_null($item->update_by)) {
            $query->where('update_by', $item->update_by);
        } else {
            $query->where('user_id', $item->user_id);
        }
        $item->all_comments = $query->get(['comments', 'created_at']);
        return $item;
    });

    $doctrackCount = $documentTrack->total(); // Use total count from paginator

    return view('home.outgoingDocs', compact(
        'documentTrack', 'offices',
        'routingSlipCount', 'superUserCount', 
        'recordsOfficerCount', 'doctrackCount'
    ));
}

// public function doctrackSlip()
// {
//     $user = auth()->user();
//     $userId = $user->id;
//     $userFullName = $user->fname . ' ' . $user->lname;
//     $userRole = $user->role;

//     // Logs for routing slip counts
//     $logs = Log::where(function ($query) use ($userId) {
//         $query->where('new_user', $userId)
//               ->orWhere('user_id', $userId);
//     })->get();

//     // Routing slip counts
//     $routingSlipCount = ($logs->every(fn($log) => $log->status_update != 3)) 
//         ? RoutingSlip::where('route_status', 3)->count() 
//         : 0;

//     $superUserCount = $userRole === 'super_user' 
//         ? RoutingSlip::where('route_status', 1)->count() 
//         : 0;

//     $recordsOfficerCount = $userRole === 'records_officer' 
//         ? RoutingSlip::where('route_status', 2)->count() 
//         : 0;

//     $offices = Office::all();

//     // Get all Doctrack records (no grouping)
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


// $documentTrack->transform(function ($item) {
//     $query = LogsTracking::where('docslip_id', $item->docslip_id)
//         ->whereNotNull('viewed_status')
//         ->whereNotNull('viewed_at');

//     // Match this specific row's person
//     if (!is_null($item->update_by)) {
//         // If updated_by exists → show logs for that updater
//         $query->where('update_by', $item->update_by);
//     } else {
//         // If no updater → show logs for the original owner
//         $query->where('user_id', $item->user_id);
//     }

//    $item->views = $query->orderBy('viewed_at', 'asc')->limit(1)->get();


//     // Duration calculation
//     $start = \Carbon\Carbon::parse($item->created_at);
//     $end = \Carbon\Carbon::parse($item->updated_at ?? $item->created_at);
//     $diffInMinutes = $end->diffInMinutes($start);

//     $item->time_diff = [
//         'days' => floor($diffInMinutes / 1440),
//         'hours' => floor(($diffInMinutes % 1440) / 60),
//         'minutes' => $diffInMinutes % 60,
//     ];

//     return $item;
// });


// $documentTrack->transform(function ($item) {
//     $query = LogsTracking::where('docslip_id', $item->docslip_id)
//         ->whereNotNull('comments');

//     if (!is_null($item->update_by)) {
//         $query->where('update_by', $item->update_by);
//     } else {
//         $query->where('user_id', $item->user_id);
//     }

//     // Get both comment text and created_at
//     $item->all_comments = $query->get(['comments', 'created_at']);

//     return $item;
// });



//     $doctrackCount = $documentTrack->where('doctrack_stat', 2)->count();

//     return view('home.doctrackSlip', compact(
//         'documentTrack', 'offices',
//         'logs', 'routingSlipCount', 'superUserCount', 
//         'recordsOfficerCount', 'doctrackCount'
//     ));
// }


// 11-28-2025 adding batch loading
// public function doctrackSlip()
// {
//     $user = auth()->user();
//     $userId = $user->id;
//     $userFullName = $user->fname . ' ' . $user->lname;
//     $userRole = $user->role;

//     // Logs for routing slip counts
//     $logs = collect(); // container for batched results

//     Log::where(function ($query) use ($userId) {
//             $query->where('new_user', $userId)
//                   ->orWhere('user_id', $userId);
//         })
//         ->chunk(50, function ($batch) use (&$logs) {
//             $logs = $logs->merge($batch);
//         });

//     // Routing slip counts
//     $routingSlipCount = ($logs->every(fn($log) => $log->status_update != 3)) 
//         ? RoutingSlip::where('route_status', 3)->count() 
//         : 0;

//     $superUserCount = $userRole === 'super_user' 
//         ? RoutingSlip::where('route_status', 1)->count() 
//         : 0;

//     $recordsOfficerCount = $userRole === 'records_officer' 
//         ? RoutingSlip::where('route_status', 2)->count() 
//         : 0;

//     $offices = Office::all();

//     // ------------------------------
//     // 🔹 Batch load Doctrack records
//     // ------------------------------
//     $documentTrack = collect();

//     Doctrack::with(['createdBy', 'doctrackFile'])
//         ->where(function ($query) use ($userId, $userFullName) {
//             $query->where('user_id', $userId)
//                   ->orWhere('update_by', $userId)
//                   ->orWhere(function ($q) use ($userFullName, $userId) {
//                       $q->where('user_name', $userFullName)
//                         ->where('user_id', $userId); // ✅ restrict by logged-in user_id
//                   });
//         })
//         ->orderByDesc('created_at')
//         ->chunk(100, function ($batch) use (&$documentTrack) {  // fetch in batches of 100
//             $batch->transform(function ($item) {
//                 // Views
//                 $query = LogsTracking::where('docslip_id', $item->docslip_id)
//                     ->whereNotNull('viewed_status')
//                     ->whereNotNull('viewed_at');

//                 $item->views = !is_null($item->update_by)
//                     ? $query->where('update_by', $item->update_by)->orderBy('viewed_at')->limit(1)->get()
//                     : $query->where('user_id', $item->user_id)->orderBy('viewed_at')->limit(1)->get();

//                 // Comments
//                 $queryComments = LogsTracking::where('docslip_id', $item->docslip_id)
//                     ->whereNotNull('comments');

//                 $item->all_comments = !is_null($item->update_by)
//                     ? $queryComments->where('update_by', $item->update_by)->get(['comments', 'created_at'])
//                     : $queryComments->where('user_id', $item->user_id)->get(['comments', 'created_at']);

//                 // Duration calculation
//                 $start = \Carbon\Carbon::parse($item->created_at);
//                 $end = \Carbon\Carbon::parse($item->updated_at ?? $item->created_at);
//                 $diffInMinutes = $end->diffInMinutes($start);

//                 $item->time_diff = [
//                     'days' => floor($diffInMinutes / 1440),
//                     'hours' => floor(($diffInMinutes % 1440) / 60),
//                     'minutes' => $diffInMinutes % 60,
//                 ];

//                 return $item;
//             });

//             $documentTrack = $documentTrack->merge($batch);
//         });

//     // ------------------------------
//     // 🔹 Count only doctrack_stat = 2
//     // ------------------------------
//     $doctrackCount = Doctrack::where('doctrack_stat', 2)
//         ->where(function ($query) use ($userId, $userFullName) {
//             $query->where('user_id', $userId)
//                   ->orWhere('update_by', $userId)
//                   ->orWhere(function ($q) use ($userFullName, $userId) {
//                       $q->where('user_name', $userFullName)
//                         ->where('user_id', $userId);
//                   });
//         })
//         ->count();

//     return view('home.doctrackSlip', compact(
//         'documentTrack', 'offices',
//         'logs', 'routingSlipCount', 'superUserCount', 
//         'recordsOfficerCount', 'doctrackCount'
//     ));
// }

// Optimized for better usage 08/06/2026

// public function doctrackSlip()
// {
//     $user = auth()->user();
//     $userId = $user->id;
//     $userFullName = $user->fname . ' ' . $user->lname;
//     $userRole = $user->role;

//     // ------------------------------
//     // Logs for routing slip counts
//     // ------------------------------
//     $logs = collect();
//     Log::where(function ($query) use ($userId) {
//             $query->where('new_user', $userId)
//                   ->orWhere('user_id', $userId);
//         })
//         ->orderBy('created_at', 'desc')
//         ->chunk(50, function ($batch) use (&$logs) {
//             $logs = $logs->merge($batch);
//         });

//     $routingSlipCount = ($logs->every(fn($log) => $log->status_update != 3)) 
//         ? RoutingSlip::where('route_status', 3)->count() 
//         : 0;

//     $superUserCount = $userRole === 'super_user' 
//         ? RoutingSlip::where('route_status', 1)->count() 
//         : 0;

//     $recordsOfficerCount = $userRole === 'records_officer' 
//         ? RoutingSlip::where('route_status', 2)->count() 
//         : 0;

//     $offices = Office::all();

//     // ------------------------------
//     // Load Doctrack records in batches of 50
//     // ------------------------------
// $documentTrack = Doctrack::with(['createdBy', 'doctrackFile'])
//     ->where(function ($query) use ($userId, $userFullName) {
//         $query->where('user_id', $userId)
//               ->orWhere('update_by', $userId)
//               ->orWhere(function ($q) use ($userFullName, $userId) {
//                   $q->where('user_name', $userFullName)
//                     ->where('user_id', $userId);
//               });
//     })
//     ->orderByDesc('created_at')
//     ->get();  // <-- get ALL data

//     // ------------------------------
//     // Preload views and comments
//     // ------------------------------
//     $docslipIds = $documentTrack->pluck('docslip_id')->toArray();

//     $views = LogsTracking::whereIn('docslip_id', $docslipIds)
//         ->whereNotNull('viewed_status')
//         ->whereNotNull('viewed_at')
//         ->get()
//         ->groupBy('docslip_id');

//     $comments = LogsTracking::whereIn('docslip_id', $docslipIds)
//         ->whereNotNull('comments')
//         ->get()
//         ->groupBy('docslip_id');

//     $documentTrack->transform(function ($item) use ($views, $comments) {
//         $item->views = $views[$item->docslip_id] ?? collect();
//         $item->all_comments = $comments[$item->docslip_id] ?? collect();

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

//     // ------------------------------
//     // Count only doctrack_stat = 2
//     // ------------------------------
//     $doctrackCount = Doctrack::where('doctrack_stat', 2)
//         ->where(function ($query) use ($userId, $userFullName) {
//             $query->where('user_id', $userId)
//                   ->orWhere('update_by', $userId)
//                   ->orWhere(function ($q) use ($userFullName, $userId) {
//                       $q->where('user_name', $userFullName)
//                         ->where('user_id', $userId);
//                   });
//         })
//         ->count();

//     return view('home.doctrackSlip', compact(
//         'documentTrack', 'offices', 'logs',
//         'routingSlipCount', 'superUserCount', 
//         'recordsOfficerCount', 'doctrackCount'
//     ));
// }


// 08/11/2026 updated for optimized view and ajax loading

// public function doctrackSlip()
// {
//     $user = auth()->user();
//     $userId = $user->id;
//     $userFullName = $user->fname . ' ' . $user->lname;
//     $userRole = $user->role;

//     // Only load counts - table data loads via AJAX
//     $logs = Log::where(function ($query) use ($userId) {
//             $query->where('new_user', $userId)
//                   ->orWhere('user_id', $userId);
//         })->get();

//     $routingSlipCount = ($logs->every(fn($log) => $log->status_update != 3)) 
//         ? RoutingSlip::where('route_status', 3)->count() 
//         : 0;

//     $superUserCount = $userRole === 'super_user' 
//         ? RoutingSlip::where('route_status', 1)->count() 
//         : 0;

//     $recordsOfficerCount = $userRole === 'records_officer' 
//         ? RoutingSlip::where('route_status', 2)->count() 
//         : 0;

//     $offices = Office::all();

//     $doctrackCount = Doctrack::where('doctrack_stat', 2)
//         ->where(function ($query) use ($userId, $userFullName) {
//             $query->where('user_id', $userId)
//                   ->orWhere('update_by', $userId)
//                   ->orWhere(function ($q) use ($userFullName, $userId) {
//                       $q->where('user_name', $userFullName)
//                         ->where('user_id', $userId);
//                   });
//         })
//         ->count();

//     return view('home.doctrackSlip', compact(
//         'offices', 'routingSlipCount', 'superUserCount', 
//         'recordsOfficerCount', 'doctrackCount'
//     ));
// }

public function doctrackSlip()
{
    $user = auth()->user();
    $userId = $user->id;
    $userFullName = $user->fname . ' ' . $user->lname;
    $userRole = $user->role;

    // ✅ Keep this logic - it's just a check
    $logs = Log::where(function ($query) use ($userId) {
            $query->where('new_user', $userId)
                  ->orWhere('user_id', $userId);
        })->get();

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

    $doctrackCount = Doctrack::where('doctrack_stat', 2)
        ->where(function ($query) use ($userId, $userFullName) {
            $query->where('user_id', $userId)
                  ->orWhere('update_by', $userId)
                  ->orWhere(function ($q) use ($userFullName, $userId) {
                      $q->where('user_name', $userFullName)
                        ->where('user_id', $userId);
                  });
        })
        ->count();

    return view('home.doctrackSlip', compact(
        'offices', 'routingSlipCount', 'superUserCount', 
        'recordsOfficerCount', 'doctrackCount'
    ));
}

/**
 * Server-side data for Doctrack DataTables AJAX
 */
// public function getDoctrackData(Request $request)
// {
//     $user = auth()->user();
//     $userId = $user->id;
//     $userFullName = $user->fname . ' ' . $user->lname;
//     $isUser1235 = ($userId == 1235);

//     $searchValue = $request->input('search.value');
//     $start = $request->input('start', 0);
//     $length = $request->input('length', 50);

//     $query = Doctrack::with(['createdBy', 'doctrackFile'])
//         ->where(function ($q) use ($userId, $userFullName) {
//             $q->where('user_id', $userId)
//               ->orWhere('update_by', $userId)
//               ->orWhere(function ($sq) use ($userFullName, $userId) {
//                   $sq->where('user_name', $userFullName)
//                     ->where('user_id', $userId);
//               });
//         })
//         ->orderByDesc('created_at');

//     // Search
//     if ($searchValue) {
//         $query->where(function ($q) use ($searchValue) {
//             $q->where('docslip_id', 'LIKE', "%{$searchValue}%")
//               ->orWhere('user_name', 'LIKE', "%{$searchValue}%")
//               ->orWhere('doc_title', 'LIKE', "%{$searchValue}%")
//               ->orWhere('doc_type', 'LIKE', "%{$searchValue}%")
//               ->orWhere('ctrl_no', 'LIKE', "%{$searchValue}%");
//         });
//     }

//     $totalRecords = $query->count();
//     $doctracks = $query->skip($start)->take($length)->get();

//     $data = [];
//     foreach ($doctracks as $record) {
//         $comments = LogsTracking::where('docslip_id', $record->docslip_id)
//             ->whereNotNull('comments')
//             ->orderByDesc('created_at')
//             ->get();

//         $commentsHtml = '';
//         foreach ($comments as $comment) {
//             if (!is_null($record->update_by)) {
//                 $wrappedComment = nl2br(e($comment->comments));
//                 $commentsHtml .= '<div style="margin-bottom: 3px;">';
//                 $commentsHtml .= '<span class="badge badge-warning" style="font-size:10px; max-width: 150px; display: inline-block; word-wrap: break-word; white-space: normal;">' . $wrappedComment . '</span>';
//                 $commentsHtml .= '<br><small class="text-muted">' . \Carbon\Carbon::parse($comment->created_at)->format('M-d-Y h:i A') . '</small>';
//                 $commentsHtml .= '</div>';
//             }
//         }
//         if (empty($commentsHtml) && !is_null($record->update_by)) {
//             $commentsHtml = '<span class="text-muted">No comments</span>';
//         }

//         $user = $record->update_by
//             ? \App\Models\User::find($record->update_by)
//             : \App\Models\User::find($record->user_id);
        
//         $actionTaken = $user 
//             ? '<p class="text-red text-bold">' . ucwords(strtolower($user->fname)) . ' ' . ucwords(strtolower($user->lname)) . '</p>'
//             : '<p class="text-muted"><i>User not found</i></p>';

//         $startTime = \Carbon\Carbon::parse($record->created_at);
//         $endTime = \Carbon\Carbon::parse($record->updated_at ?? $record->created_at);
//         $diffInMinutes = $endTime->diffInMinutes($startTime);
//         $days = floor($diffInMinutes / 1440);
//         $hours = floor(($diffInMinutes % 1440) / 60);
//         $minutes = $diffInMinutes % 60;

//         $duration = '';
//         if ($days > 0) $duration .= $days . ' ' . \Illuminate\Support\Str::plural('day', $days) . ' ';
//         if ($hours > 0) $duration .= $hours . ' ' . \Illuminate\Support\Str::plural('hr', $hours) . ' ';
//         if ($minutes > 0 || ($days == 0 && $hours == 0)) $duration .= $minutes . ' ' . \Illuminate\Support\Str::plural('min', $minutes);

//         $statusBadge = '';
//         switch ($record->doctrack_stat) {
//             case 1: $statusBadge = '<span class="badge badge-primary">Created</span>'; break;
//             case 2: $statusBadge = '<span class="badge badge-warning">Pending</span>'; break;
//             case 3: $statusBadge = '<span class="badge badge-success">Signed</span>'; break;
//             case 5: $statusBadge = '<span class="badge badge-info">Checked</span>'; break;
//             case 6: $statusBadge = '<span class="badge badge-success">Acknowledged</span>'; break;
//             default: $statusBadge = '<span class="badge badge-danger">Returned with comments</span>';
//         }

//         $actionDropdown = '<div class="btn-group">';
//         $actionDropdown .= '<button type="button" class="btn btn-primary btn-sm dropdown-toggle" data-toggle="dropdown">Click here</button>';
//         $actionDropdown .= '<div class="dropdown-menu">';
//         if ($record->doctrack_stat == 1) {
//             $actionDropdown .= '<form action="' . route('deleteSlip', ['docslip_id' => $record->docslip_id]) . '" method="POST" onsubmit="return confirm(\'Are you sure?\')">';
//             $actionDropdown .= '<input type="hidden" name="_token" value="' . csrf_token() . '">';
//             $actionDropdown .= '<input type="hidden" name="_method" value="DELETE">';
//             $actionDropdown .= '<button type="submit" class="dropdown-item"><i class="fas fa-trash-alt"></i> Delete</button>';
//             $actionDropdown .= '</form>';
//         }
//         $actionDropdown .= '<a class="dropdown-item" href="' . route('slipMonitoring', ['docslip_id' => $record->docslip_id]) . '" target="_blank"><i class="fas fa-eye"></i> View Details</a>';
//         $actionDropdown .= '</div></div>';

//         $row = [
//             'ctrl_no' => $record->ctrl_no,
//             'ctrl_input' => $isUser1235 ? '<input type="text" class="form-control doctrack-input form-control-sm" data-id="' . $record->id . '" data-field="ctrl_no" value="' . ($record->ctrl_no ?? '') . '" style="width: 80px;">' : '',
//             'date_received' => $record->created_at->format('M j, Y'),
//             'source' => $record->user_name ?? 'N/A',
//             'subject' => ($record->doc_title ?? 'N/A') . ' - ' . ($record->doc_type ?? 'N/A'),
//             'action_unit' => '--',
//             'received_by_date' => '--',
//             'action_taken' => $actionTaken,
//             'date_released' => $record->updated_at->format('M d, Y'),
//             'remarks' => $commentsHtml,
//             'status' => $statusBadge,
//             'tracking_code' => '<a href="' . route('slipMonitoring', ['docslip_id' => $record->docslip_id]) . '" target="_blank" style="color: #007bff;">' . $record->docslip_id . '</a>',
//             'duration' => trim($duration) ?: '0 minutes',
//             'action' => $actionDropdown,
//         ];

//         $data[] = $row;
//     }

//     return response()->json([
//         'draw' => intval($request->input('draw')),
//         'recordsTotal' => $totalRecords,
//         'recordsFiltered' => $totalRecords,
//         'data' => $data
//     ]);
// }

public function getDoctrackData(Request $request)
{
    $user = auth()->user();
    $userId = $user->id;
    $userFullName = $user->fname . ' ' . $user->lname;
    // ✅ Users 1235 and 56 are allowed to see (and edit) the CTRL # column
    $canViewCtrlNo = in_array((int) $userId, [1235, 56]);

    $searchValue = trim((string) $request->input('search.value'));
    $start = (int) $request->input('start', 0);
    $length = (int) $request->input('length', 50);
    
    // Get order column and direction from DataTables
    $orderColumnIndex = (int) $request->input('order.0.column', 0);
    $orderDirection = strtolower((string) $request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';
    
    // ✅ The "name" property of the DataTable column holds the real database column
    //    (the "data" property is only the key of the JSON payload)
    $columns = $request->input('columns', []);
    $requestedColumn = $columns[$orderColumnIndex]['name'] ?? ($columns[$orderColumnIndex]['data'] ?? null);

    // ✅ Map every sortable column to its SQL expression
    $sortMap = [
        'ctrl_no'       => 'doctrack_slip.ctrl_no',
        'created_at'    => 'doctrack_slip.created_at',
        'user_name'     => 'doctrack_slip.user_name',
        'doc_title'     => 'doctrack_slip.doc_title',
        'updated_at'    => 'doctrack_slip.updated_at',
        'doctrack_stat' => 'doctrack_slip.doctrack_stat',
        // ✅ ACTION TAKEN shows the name of the person who took the action, so it is
        //    resolved through the users table (the id is the primary key, so the
        //    lookup stays fast). UPPER() is used because that is exactly how the
        //    name is displayed on the table.
        'action_taken'  => "UPPER(COALESCE((select concat(u.fname, ' ', u.lname) from users u where u.id = doctrack_slip.update_by), (select concat(u.fname, ' ', u.lname) from users u where u.id = doctrack_slip.user_id)))",
    ];

    $orderColumn = $sortMap[$requestedColumn] ?? 'doctrack_slip.created_at';

    $query = Doctrack::with(['createdBy', 'doctrackFile'])
        ->where(function ($q) use ($userId, $userFullName) {
            $q->where('user_id', $userId)
              ->orWhere('update_by', $userId)
              ->orWhere(function ($sq) use ($userFullName, $userId) {
                  $sq->where('user_name', $userFullName)
                    ->where('user_id', $userId);
              });
        });

    // ✅ TOTAL records (before the search filter)
    $totalRecords = (clone $query)->count();

    // Search
    if ($searchValue) {
        $query->where(function ($q) use ($searchValue) {
            $q->where('docslip_id', 'LIKE', "%{$searchValue}%")
              ->orWhere('user_name', 'LIKE', "%{$searchValue}%")
              ->orWhere('doc_title', 'LIKE', "%{$searchValue}%")
              ->orWhere('doc_type', 'LIKE', "%{$searchValue}%")
              ->orWhere('ctrl_no', 'LIKE', "%{$searchValue}%")
              // Handle status text search
              ->orWhere(function ($sq) use ($searchValue) {
                  $statusMap = [
                      'created' => 1,
                      'pending' => 2,
                      'signed' => 3,
                      'checked' => 5,
                      'acknowledged' => 6,
                      'returned' => 4
                  ];
                  
                  $searchLower = strtolower($searchValue);
                  $statusIds = [];
                  
                  foreach ($statusMap as $key => $value) {
                      if (strpos($key, $searchLower) !== false || strpos($searchLower, $key) !== false) {
                          $statusIds[] = $value;
                      }
                  }
                  
                  if (!empty($statusIds)) {
                      $sq->whereIn('doctrack_stat', $statusIds);
                  }
              });
        });
    }

    $filteredRecords = (clone $query)->count();
    
    // Apply ordering
    $query->orderByRaw($orderColumn . ' ' . $orderDirection);
    
    // ✅ A stable second sort keeps the pagination consistent
    $query->orderBy('doctrack_slip.id', $orderDirection);
    
    $doctracks = $query->skip($start)->take($length)->get();

    // ✅ Preload the comments of the whole page in a SINGLE query (removes the n+1)
    $slipIds = $doctracks->pluck('docslip_id')->unique()->filter()->toArray();

    $commentsBySlip = collect();
    if (!empty($slipIds)) {
        $commentsBySlip = LogsTracking::whereIn('docslip_id', $slipIds)
            ->whereNotNull('comments')
            ->orderByDesc('created_at')
            ->get()
            ->groupBy('docslip_id');
    }

    // ✅ Preload the users of the whole page in a SINGLE query (removes the n+1)
    $userIds = $doctracks->pluck('update_by')
        ->merge($doctracks->pluck('user_id'))
        ->filter()
        ->unique()
        ->values()
        ->toArray();

    $usersById = User::whereIn('id', $userIds)->get()->keyBy('id');

    $data = [];
    foreach ($doctracks as $record) {
        $comments = $commentsBySlip->get($record->docslip_id, collect());

        $commentsHtml = '';
        foreach ($comments as $comment) {
            if (!is_null($record->update_by)) {
                $wrappedComment = nl2br(e($comment->comments));
                $commentsHtml .= '<div style="margin-bottom: 3px;">';
                $commentsHtml .= '<span class="badge badge-warning" style="font-size:10px; max-width: 150px; display: inline-block; word-wrap: break-word; white-space: normal;">' . $wrappedComment . '</span>';
                $commentsHtml .= '<br><small class="text-muted">' . \Carbon\Carbon::parse($comment->created_at)->format('M-d-Y h:i A') . '</small>';
                $commentsHtml .= '</div>';
            }
        }
        if (empty($commentsHtml) && !is_null($record->update_by)) {
            $commentsHtml = '<span class="text-muted">No comments</span>';
        }

        $user = $usersById->get($record->update_by ?: $record->user_id);
        
        // FIX: ACTION TAKEN - Convert names to ALL CAPS
        $actionTaken = $user 
            ? '<p class="text-red text-bold">' . strtoupper($user->fname) . ' ' . strtoupper($user->lname) . '</p>'
            : '<p class="text-muted"><i>User not found</i></p>';

        $startTime = \Carbon\Carbon::parse($record->created_at);
        $endTime = \Carbon\Carbon::parse($record->updated_at ?? $record->created_at);
        $diffInMinutes = $endTime->diffInMinutes($startTime);
        $days = floor($diffInMinutes / 1440);
        $hours = floor(($diffInMinutes % 1440) / 60);
        $minutes = $diffInMinutes % 60;

        $duration = '';
        if ($days > 0) $duration .= $days . ' ' . \Illuminate\Support\Str::plural('day', $days) . ' ';
        if ($hours > 0) $duration .= $hours . ' ' . \Illuminate\Support\Str::plural('hr', $hours) . ' ';
        if ($minutes > 0 || ($days == 0 && $hours == 0)) $duration .= $minutes . ' ' . \Illuminate\Support\Str::plural('min', $minutes);

        $statusBadge = '';
        switch ($record->doctrack_stat) {
            case 1: $statusBadge = '<span class="badge badge-primary">Created</span>'; break;
            case 2: $statusBadge = '<span class="badge badge-warning">Pending</span>'; break;
            case 3: $statusBadge = '<span class="badge badge-success">Signed</span>'; break;
            case 5: $statusBadge = '<span class="badge badge-info">Checked</span>'; break;
            case 6: $statusBadge = '<span class="badge badge-success">Acknowledged</span>'; break;
            default: $statusBadge = '<span class="badge badge-danger">Returned with comments</span>';
        }

        $isCreator = ((int) $record->user_id === (int) $userId);

        $actionDropdown = '<div class="btn-group">';
        $actionDropdown .= '<button type="button" class="btn btn-primary btn-sm dropdown-toggle" data-toggle="dropdown">Click here</button>';
        $actionDropdown .= '<div class="dropdown-menu">';
        if ($record->doctrack_stat == 1 && $isCreator) {
            $actionDropdown .= '<form action="' . route('deleteSlip', ['docslip_id' => $record->docslip_id]) . '" method="POST" onsubmit="return confirm(\'Are you sure? This will delete the whole transaction.\')">';
            $actionDropdown .= '<input type="hidden" name="_token" value="' . csrf_token() . '">';
            $actionDropdown .= '<input type="hidden" name="_method" value="DELETE">';
            $actionDropdown .= '<button type="submit" class="dropdown-item text-danger"><i class="fas fa-trash-alt"></i> Delete Transaction</button>';
            $actionDropdown .= '</form>';
        }
        if ($isCreator) {
            // ✅ Delete a single row (useful when there is duplicate data)
            $actionDropdown .= '<form action="' . route('deleteSlipRow', ['id' => $record->id]) . '" method="POST" onsubmit="return confirm(\'Are you sure? This will delete this row only.\')">';
            $actionDropdown .= '<input type="hidden" name="_token" value="' . csrf_token() . '">';
            $actionDropdown .= '<input type="hidden" name="_method" value="DELETE">';
            $actionDropdown .= '<button type="submit" class="dropdown-item text-warning"><i class="fas fa-minus-circle"></i> Delete Row</button>';
            $actionDropdown .= '</form>';
        }
        $actionDropdown .= '<a class="dropdown-item" href="' . route('slipMonitoring', ['docslip_id' => $record->docslip_id]) . '" target="_blank"><i class="fas fa-eye"></i> View Details</a>';
        $actionDropdown .= '</div></div>';

        $row = [
            'ctrl_no' => $record->ctrl_no,
            'ctrl_input' => $canViewCtrlNo ? '<input type="text" class="form-control doctrack-input form-control-sm" data-id="' . $record->id . '" data-field="ctrl_no" value="' . ($record->ctrl_no ?? '') . '" style="width: 80px;">' : '',
            'date_received' => $record->created_at->format('M j, Y'),
            'source' => $record->user_name ?? 'N/A',
            'subject' => ($record->doc_title ?? 'N/A') . ' - ' . ($record->doc_type ?? 'N/A'),
            'action_unit' => '--',
            'received_by_date' => '--',
            'action_taken' => $actionTaken, // Now in ALL CAPS
            'date_released' => $record->updated_at->format('M d, Y'),
            'remarks' => $commentsHtml,
            'status' => $statusBadge,
            'tracking_code' => '<a href="' . route('slipMonitoring', ['docslip_id' => $record->docslip_id]) . '" target="_blank" style="color: #007bff;">' . $record->docslip_id . '</a>',
            'duration' => trim($duration) ?: '0 minutes',
            'action' => $actionDropdown,
        ];

        $data[] = $row;
    }

    return response()->json([
        'draw' => intval($request->input('draw')),
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $filteredRecords,
        'data' => $data
    ]);
}



// public function pending()
// {
//     $user = auth()->user();
//     $userFullName = $user->fname . ' ' . $user->lname;
//     $userId = $user->id;
//     $userRole = $user->role;
//     $userDepartment = $user->department;

//     $logs = Log::with('routingSlip') // <-- eager load routingSlip
//     ->leftJoin('documents', 'logs.doc_id', '=', 'documents.id')
//     ->leftJoin('routing_slip', 'logs.route_id', '=', 'routing_slip.rslip_id')
//     ->select('logs.*', 'documents.*', 'routing_slip.*')
//     ->where('logs.status_update', 2)
//     ->where(function ($q) {
//         $q->whereNull('logs.new_user')
//           ->orWhereNull('logs.assigned_to');
//     })
//     ->where(function ($q) use ($userFullName, $userId) {
//         $q->where('logs.new_destination', $userFullName)
//           ->orWhere('logs.user_id', $userId);
//     })
//     ->orderBy('logs.created_at', 'desc')
//     ->distinct('logs.id')
//     ->get();


//     $offices = Office::all();
//     $recordsOfficerCount = 0;
//     $superUserCount = 0;

//     // Get all Doctrack records (no grouping)
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

//     return view('home.pending', compact('logs', 'offices', 'recordsOfficerCount', 'superUserCount', 'doctrackCount'));
// }


// september 1, 2026. updated the sorting

// public function pending()
// {
//     $user = auth()->user();
//     $userFullName = $user->fname . ' ' . $user->lname;
//     $userId = $user->id;
//     $userRole = $user->role;
//     $userDepartment = $user->department;

//     // Step 1: Get logs joined with documents and routing_slip
//     $logs = Log::with('routingSlip')
//         ->leftJoin('documents', 'logs.doc_id', '=', 'documents.id')
//         ->leftJoin('routing_slip', function ($join) {
//             $join->on('logs.route_id', '=', 'routing_slip.rslip_id')
//                  ->on('logs.user_id', '=', 'routing_slip.user_id'); // ensure correct user
//         })
//         ->select(
//             'logs.*',
//             'documents.id as doc_id',
//             'documents.file_name',
//             'routing_slip.id as routing_slip_id',
//             'routing_slip.date_received',
//             'routing_slip.source',
//             'routing_slip.subject',
//             'routing_slip.pres_dept',
//             'routing_slip.trans_remarks',
//             'routing_slip.r_destination as routing_destination'
//         )
//         ->where('logs.status_update', 2)
//         ->where(function ($q) {
//             $q->whereNull('logs.new_user')
//               ->orWhereNull('logs.assigned_to');
//         })
//         ->where(function ($q) use ($userFullName, $userId) {
//             $q->where('logs.new_destination', $userFullName)
//               ->orWhere('logs.user_id', $userId);
//         })
//         ->orderByDesc('logs.created_at')
//         ->get();

//     // Step 2: Remove duplicate doc_id
//     // $logs = $logs->unique('doc_id')->values();
//     $logs = $logs->whereNotNull('doc_id')
//              ->unique('doc_id')
//              ->values();

//     // Step 3: Other data
//     $offices = Office::all();
//     $recordsOfficerCount = 0;
//     $superUserCount = 0;

//     // Get all Doctrack records (no grouping)
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

//     return view('home.pending', compact(
//         'logs', 
//         'offices', 
//         'recordsOfficerCount', 
//         'superUserCount', 
//         'doctrackCount',
//         'documentTrack'
//     ));
// }

public function pending()
{
    $user = auth()->user();
    $userFullName = $user->fname . ' ' . $user->lname;
    $userId = $user->id;
    $userRole = $user->role;
    $userDepartment = $user->department;

    // Step 1: Get logs joined with documents and routing_slip
    $logs = Log::with('routingSlip')
        ->leftJoin('documents', 'logs.doc_id', '=', 'documents.id')
        ->leftJoin('routing_slip', function ($join) {
            $join->on('logs.route_id', '=', 'routing_slip.rslip_id')
                 ->on('logs.user_id', '=', 'routing_slip.user_id');
        })
        ->select(
            'logs.*',
            'documents.id as doc_id',
            'documents.file_name',
            'routing_slip.id as routing_slip_id',
            'routing_slip.date_received',
            'routing_slip.source',
            'routing_slip.subject',
            'routing_slip.pres_dept',
            'routing_slip.trans_remarks',
            'routing_slip.r_destination as routing_destination'
        )
        ->where('logs.status_update', 2)
        ->where(function ($q) {
            $q->whereNull('logs.new_user')
              ->orWhereNull('logs.assigned_to');
        })
        ->where(function ($q) use ($userFullName, $userId) {
            $q->where('logs.new_destination', $userFullName)
              ->orWhere('logs.user_id', $userId);
        })
        ->orderByDesc('logs.updated_at') // Changed from created_at to updated_at
        ->get();

    // Step 2: Remove duplicate doc_id
    $logs = $logs->whereNotNull('doc_id')
             ->unique('doc_id')
             ->values();

    // Step 3: Other data
    $offices = Office::all();
    $recordsOfficerCount = 0;
    $superUserCount = 0;

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

    // Count only records with doctrack_stat == 2
    $doctrackCount = $documentTrack->where('doctrack_stat', 2)->count();

    // Get users for display
    $users = \App\Models\User::all();

    return view('home.pending', compact(
        'logs', 
        'offices', 
        'recordsOfficerCount', 
        'superUserCount', 
        'doctrackCount',
        'documentTrack',
        'users' // Pass users to view
    ));
}

// 12 - 1 - 2025 optimized into chunks of 50

//    public function served()
// {
//     $user = auth()->user();
//     $userId = $user->id;
//     $userDepartment = $user->department;
//     $userFullName = $user->fname . ' ' . $user->lname;
//     $userRole = $user->role;
// $users = User::all();

//     $logs = Log::with('document', 'document.routingSlip')
//         ->whereNotNull('new_user')
//         ->when($userRole === 'records_officer', function ($query) {
//             return $query; // records_officer sees all served logs
//         }, function ($query) use ($userId, $userDepartment, $userFullName) {
//             return $query->where(function ($q) use ($userId, $userDepartment, $userFullName) {
//                 $q->where('new_user', $userId)
//                   ->orWhere('user_id', $userId)
//                   ->orWhere('new_destination', $userDepartment)
//                   ->orWhere('new_destination', $userFullName);
//             });
//         })
//         ->get();

//          // Get all Doctrack records (no grouping)
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

//     $offices = Office::all();

//     $recordsOfficerCount = $userRole === 'records_officer'
//         ? RoutingSlip::where('route_status', 2)->count()
//         : 0;

//     $superUserCount = $userRole === 'super_user'
//         ? RoutingSlip::where('route_status', 1)->count()
//         : 0;

//     return view('home.served', compact('logs', 'offices', 'recordsOfficerCount', 'superUserCount','doctrackCount', 'users'));
//     }
// public function served()
// {
//     $user = auth()->user();
//     $userId = $user->id;
//     $userDepartment = trim($user->department);
//     $userFullName = trim($user->fname . ' ' . $user->lname);
//     $userRole = $user->role;

//     $users = User::select('id','fname','lname')->get();
//     $offices = Office::all();

//     // -----------------------------
//     // Load logs in chunks
//     // -----------------------------
//     $logs = collect();
//     Log::with(['user:id,fname,lname','newUser:id,fname,lname','document','routingSlip'])
//         ->whereNotNull('new_user')
//         ->when($userRole !== 'records_officer', function ($query) use ($userId, $userDepartment, $userFullName) {
//             $query->where(function ($q) use ($userId, $userDepartment, $userFullName) {
//                 $q->where('new_user', $userId)
//                   ->orWhere('user_id', $userId)
//                   ->orWhere('new_destination', $userDepartment)
//                   ->orWhere('new_destination', $userFullName);
//             });
//         })
//         ->orderByDesc('created_at')
//         ->chunk(100, function ($batch) use (&$logs) {
//             $logs = $logs->merge($batch);
//         });

//     // Keep only the first log for each unique route_id (CTRL #)
//     $logs = $logs->unique('route_id')->values();

//     // -----------------------------
//     // Doctrack records
//     // -----------------------------
//     $documentTrack = Doctrack::with(['createdBy:id,fname,lname','doctrackFile'])
//         ->where(function ($query) use ($userId, $userFullName) {
//             $query->where('user_id', $userId)
//                   ->orWhere('update_by', $userId)
//                   ->orWhere('user_name', $userFullName);
//         })
//         ->orderByDesc('created_at')
//         ->get();

//     // Preload views and comments
//     $docslipIds = $documentTrack->pluck('docslip_id')->toArray();

//     $views = LogsTracking::whereIn('docslip_id', $docslipIds)
//         ->whereNotNull('viewed_status')
//         ->whereNotNull('viewed_at')
//         ->get()
//         ->groupBy('docslip_id');

//     $comments = LogsTracking::whereIn('docslip_id', $docslipIds)
//         ->whereNotNull('comments')
//         ->get()
//         ->groupBy('docslip_id');

//     $documentTrack->transform(function ($item) use ($views, $comments) {
//         $item->views = $views[$item->docslip_id] ?? collect();
//         $item->all_comments = $comments[$item->docslip_id] ?? collect();

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

//     // -----------------------------
//     // Counts
//     // -----------------------------
//     $doctrackCount = Doctrack::where('doctrack_stat', 2)
//         ->where(function ($query) use ($userId, $userFullName) {
//             $query->where('user_id', $userId)
//                   ->orWhere('update_by', $userId)
//                   ->orWhere(function ($q) use ($userFullName, $userId) {
//                       $q->where('user_name', $userFullName)
//                         ->where('user_id', $userId);
//                   });
//         })
//         ->count();

//     $recordsOfficerCount = $userRole === 'records_officer'
//         ? RoutingSlip::where('route_status', 2)->count()
//         : 0;

//     $superUserCount = $userRole === 'super_user'
//         ? RoutingSlip::where('route_status', 1)->count()
//         : 0;

//     return view('home.served', compact(
//         'logs','offices','recordsOfficerCount','superUserCount',
//         'doctrackCount','users','documentTrack'
//     ));
// }

// June 16, 2026 optimized with batch loading selection

// public function served()
// {
//     $user = auth()->user();
//     $userId = $user->id;
//     $userDepartment = trim($user->department);
//     $userFullName = trim($user->fname . ' ' . $user->lname);
//     $userRole = $user->role;

//     $users = User::select('id','fname','lname')->get();
//     $offices = Office::all();

//     // -----------------------------
//     // Load logs efficiently with chunking
//     // -----------------------------
//     $logsQuery = Log::with(['user:id,fname,lname','newUser:id,fname,lname','document.routingSlip','routingSlip'])
//         ->whereNotNull('new_user')
//         ->when($userRole !== 'records_officer', function ($query) use ($userId, $userDepartment, $userFullName) {
//             $query->where(function ($q) use ($userId, $userDepartment, $userFullName) {
//                 $q->where('new_user', $userId)
//                   ->orWhere('user_id', $userId)
//                   ->orWhere('new_destination', $userDepartment)
//                   ->orWhere('new_destination', $userFullName);
//             });
//         })
//         ->orderByDesc('created_at');

//     // Get unique route_ids first with a subquery
//     $uniqueRouteIds = (clone $logsQuery)
//         ->select('route_id')
//         ->selectRaw('MIN(id) as min_id')
//         ->groupBy('route_id')
//         ->pluck('min_id');

//     // Load all unique logs using chunking to avoid memory issues
//     $logs = collect();
//     Log::with(['user:id,fname,lname','newUser:id,fname,lname','document.routingSlip','routingSlip'])
//         ->whereIn('id', $uniqueRouteIds)
//         ->orderByDesc('created_at')
//         ->chunk(50, function ($chunk) use (&$logs) {
//             $logs = $logs->merge($chunk);
//         });

//     // -----------------------------
//     // Doctrack records
//     // -----------------------------
//     $documentTrack = Doctrack::with(['createdBy:id,fname,lname','doctrackFile'])
//         ->where(function ($query) use ($userId, $userFullName) {
//             $query->where('user_id', $userId)
//                   ->orWhere('update_by', $userId)
//                   ->orWhere('user_name', $userFullName);
//         })
//         ->orderByDesc('created_at')
//         ->get();

//     // Preload views and comments
//     $docslipIds = $documentTrack->pluck('docslip_id')->toArray();

//     $views = LogsTracking::whereIn('docslip_id', $docslipIds)
//         ->whereNotNull('viewed_status')
//         ->whereNotNull('viewed_at')
//         ->get()
//         ->groupBy('docslip_id');

//     $comments = LogsTracking::whereIn('docslip_id', $docslipIds)
//         ->whereNotNull('comments')
//         ->get()
//         ->groupBy('docslip_id');

//     $documentTrack->transform(function ($item) use ($views, $comments) {
//         $item->views = $views[$item->docslip_id] ?? collect();
//         $item->all_comments = $comments[$item->docslip_id] ?? collect();

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

//     // -----------------------------
//     // Counts
//     // -----------------------------
//     $doctrackCount = Doctrack::where('doctrack_stat', 2)
//         ->where(function ($query) use ($userId, $userFullName) {
//             $query->where('user_id', $userId)
//                   ->orWhere('update_by', $userId)
//                   ->orWhere(function ($q) use ($userFullName, $userId) {
//                       $q->where('user_name', $userFullName)
//                         ->where('user_id', $userId);
//                   });
//         })
//         ->count();

//     $recordsOfficerCount = $userRole === 'records_officer'
//         ? RoutingSlip::where('route_status', 2)->count()
//         : 0;

//     $superUserCount = $userRole === 'super_user'
//         ? RoutingSlip::where('route_status', 1)->count()
//         : 0;

//     return view('home.served', compact(
//         'logs','offices','recordsOfficerCount','superUserCount',
//         'doctrackCount','users','documentTrack'
//     ));
// }


public function served()
{
    $user = auth()->user();
    $userId = $user->id;
    $userDepartment = trim($user->department);
    $userFullName = trim($user->fname . ' ' . $user->lname);
    $userRole = $user->role;

    $users = User::select('id','fname','lname')->get();
    $offices = Office::all();

    // -----------------------------
    // Doctrack records
    // -----------------------------
    // $documentTrack = Doctrack::with(['createdBy:id,fname,lname','doctrackFile'])
    //     ->where(function ($query) use ($userId, $userFullName) {
    //         $query->where('user_id', $userId)
    //               ->orWhere('update_by', $userId)
    //               ->orWhere('user_name', $userFullName);
    //     })
    //     ->orderByDesc('created_at')
    //     ->get();

    $documentTrack = Doctrack::with(['createdBy:id,fname,lname','doctrackFile'])
    ->where(function ($query) use ($userId, $userFullName) {
        $query->where('user_id', $userId)
              ->orWhere('update_by', $userId)
              ->orWhere('user_name', $userFullName);
    })
    ->orderByDesc('created_at')
    ->limit(200)  // ← ADD LIMIT
    ->get();

    $docslipIds = $documentTrack->pluck('docslip_id')->toArray();

    $views = LogsTracking::whereIn('docslip_id', $docslipIds)
        ->whereNotNull('viewed_status')
        ->whereNotNull('viewed_at')
        ->get()
        ->groupBy('docslip_id');

    $comments = LogsTracking::whereIn('docslip_id', $docslipIds)
        ->whereNotNull('comments')
        ->get()
        ->groupBy('docslip_id');

    $documentTrack->transform(function ($item) use ($views, $comments) {
        $item->views = $views[$item->docslip_id] ?? collect();
        $item->all_comments = $comments[$item->docslip_id] ?? collect();

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

    // -----------------------------
    // Counts
    // -----------------------------
    $doctrackCount = Doctrack::where('doctrack_stat', 2)
        ->where(function ($query) use ($userId, $userFullName) {
            $query->where('user_id', $userId)
                  ->orWhere('update_by', $userId)
                  ->orWhere(function ($q) use ($userFullName, $userId) {
                      $q->where('user_name', $userFullName)
                        ->where('user_id', $userId);
                  });
        })
        ->count();

    $recordsOfficerCount = $userRole === 'records_officer'
        ? RoutingSlip::where('route_status', 2)->count()
        : 0;

    $superUserCount = $userRole === 'super_user'
        ? RoutingSlip::where('route_status', 1)->count()
        : 0;

    return view('home.served', compact(
        'offices','recordsOfficerCount','superUserCount',
        'doctrackCount','users','documentTrack'
    ));
}

/**
 * Server-side data for Served DataTables AJAX
 */
    public function getServedData(Request $request)
    {
        $user = auth()->user();
        $userId = $user->id;
        $userDepartment = trim($user->department);
        $userFullName = trim($user->fname . ' ' . $user->lname);
        $userRole = $user->role;

        $searchValue = trim((string) $request->input('search.value'));
        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 25);

        // The documents the logged in user is allowed to see
        $applyScope = function ($query) use ($userId, $userDepartment, $userFullName, $userRole) {
            $query->whereNotNull('new_user');

            if ($userRole !== 'records_officer') {
                $query->where(function ($q) use ($userId, $userDepartment, $userFullName) {
                    $q->where('new_user', $userId)
                      ->orWhere('user_id', $userId)
                      ->orWhere('new_destination', $userDepartment)
                      ->orWhere('new_destination', $userFullName);
                });
            }

            return $query;
        };

        // The search is done by the database (no row is loaded into PHP)
        $applySearch = function ($query) use ($searchValue) {
            if ($searchValue === '') {
                return $query;
            }

            $like = '%' . $searchValue . '%';

            return $query->where(function ($q) use ($like) {
                $q->where('route_id', 'LIKE', $like)
                  ->orWhere('new_destination', 'LIKE', $like)
                  ->orWhere('new_file', 'LIKE', $like)
                  ->orWhere('assigned_to', 'LIKE', $like)
                  ->orWhere('comments', 'LIKE', $like)
                  // Routing slip fields (source / subject / remarks)
                  ->orWhereIn('route_id', function ($sub) use ($like) {
                      $sub->selectRaw('CAST(rslip_id AS UNSIGNED)')
                          ->from('routing_slip')
                          ->where(function ($w) use ($like) {
                              $w->where('source', 'LIKE', $like)
                                ->orWhere('subject', 'LIKE', $like)
                                ->orWhere('trans_remarks', 'LIKE', $like)
                                ->orWhere('other_remarks', 'LIKE', $like);
                          });
                  })
                  // Document fields (file name / subject / purpose)
                  ->orWhereIn('route_id', function ($sub) use ($like) {
                      $sub->select('route_id')
                          ->from('documents')
                          ->where(function ($w) use ($like) {
                              $w->where('file_name', 'LIKE', $like)
                                ->orWhere('subject', 'LIKE', $like)
                                ->orWhere('purpose', 'LIKE', $like);
                          });
                  });
            });
        };

        // ONE row per route (the first log of the route, exactly like before)
        $representativeIds = Log::query()
            ->selectRaw('MIN(id)')
            ->groupBy('route_id');

        $applyScope($representativeIds);
        $applySearch($representativeIds);

        // TOTAL routes (before the search filter)
        $totalRecords = $applyScope(Log::query())->distinct()->count('route_id');

        // Routes that matched the search
        $filteredRecords = $applySearch($applyScope(Log::query()))->distinct()->count('route_id');

        // Build the base query - only the columns we need are selected
        $baseQuery = Log::select('id', 'route_id', 'doc_id', 'new_file', 'new_destination',
            'new_user', 'user_id', 'assigned_to', 'comments', 'viewed_status',
            'viewed_at', 'status_update', 'created_at', 'updated_at')
            ->whereIn('id', $representativeIds);

        // Apply the sorting requested by the DataTable
        $baseQuery = $this->applyServedOrder($baseQuery, $request);

        // Paginated results - only the current page is fetched from the database
        $paginatedLogs = $baseQuery->skip($start)->take($length)->get();

        // Preload routing slips and documents for the current page - NO n+1 queries
        $routeIds = $paginatedLogs->pluck('route_id')->unique()->filter()->toArray();

        $slipsByRoute = collect();
        $docsByRoute = collect();

        if (!empty($routeIds)) {
            $slipsByRoute = RoutingSlip::whereIn('rslip_id', $routeIds)
                ->orderBy('id')
                ->get()
                ->groupBy(function ($slip) {
                    return (int) $slip->rslip_id;
                });

            $docsByRoute = Document::whereIn('route_id', $routeIds)
                ->orderBy('id')
                ->get()
                ->groupBy(function ($doc) {
                    return (int) $doc->route_id;
                });
        }

        // ✅ Load only the users that are needed for the current page (O(1) lookups)
        $userIds = [];

        foreach ($slipsByRoute as $slips) {
            foreach ($slips as $slip) {
                if (is_numeric($slip->r_destination)) {
                    $userIds[] = (int) $slip->r_destination;
                }
                if (is_numeric($slip->assigned_to)) {
                    $userIds[] = (int) $slip->assigned_to;
                }
            }
        }

        $usersById = User::whereIn('id', array_values(array_unique($userIds)))
            ->select('id', 'fname', 'lname')
            ->get()
            ->keyBy('id');

        // Build data array
        $data = [];
        foreach ($paginatedLogs as $log) {
            $routeSlips = $slipsByRoute->get((int) $log->route_id, collect());

            // The exact slip is the one matching the file of the log
            $exactSlip = $routeSlips->first(function ($slip) use ($log) {
                return mb_strtolower((string) $slip->document) === mb_strtolower((string) $log->new_file);
            });

            // Fallback used when the log has no matching document on the slip
            $exactSlip = $exactSlip ?: $routeSlips->first();
            $log->setRelation('routingSlip', $exactSlip);

            $routeDocs = $docsByRoute->get((int) $log->route_id, collect());

            // Match the EXACT document by file_name = new_file
            $exactDoc = $routeDocs->first(function ($doc) use ($log) {
                return mb_strtolower((string) $doc->file_name) === mb_strtolower((string) $log->new_file);
            });

            // Fallback used when the log has no matching document on the route
            $exactDoc = $exactDoc ?: $routeDocs->first();

            // The routing slip id used on the links (latest one when there is no exact match)
            $routingSlipId = $exactSlip ? $exactSlip->id : ($routeSlips->last()->id ?? null);

            // Action taken
            $actionTaken = '<strong class="text-danger">' . ucwords(strtolower($exactDoc->for_to ?? '')) . '</strong>';
            $destinationUser = $usersById->get($exactSlip->r_destination ?? null);
            $assignedUser = $usersById->get($exactSlip->assigned_to ?? null);

            if ($exactSlip && $destinationUser) {
                $actionTaken .= ' <strong class="text-danger">' . ucwords(strtolower($destinationUser->fname)) . ' ' . ucwords(strtolower($destinationUser->lname)) . '</strong>';
            } elseif ($exactSlip && $exactSlip->r_destination) {
                $actionTaken .= ' <strong class="text-danger">' . ucwords(strtolower($exactSlip->r_destination)) . '</strong>';
            }
            if ($exactSlip && $assignedUser) {
                $actionTaken .= ', was re-assigned to <strong class="text-danger">' . ucwords(strtolower($assignedUser->fname)) . ' ' . ucwords(strtolower($assignedUser->lname)) . '</strong>';
            } elseif ($exactSlip && $exactSlip->assigned_to) {
                $actionTaken .= ', was re-assigned to <strong class="text-danger">' . ucwords(strtolower($exactSlip->assigned_to)) . '</strong>';
            }

            // Remarks
            $remarks = '';
            if ($exactSlip && !empty($exactSlip->trans_remarks)) {
                $remarks .= '<span class="badge badge-success" style="font-size:10px; display: block;">' . $exactSlip->trans_remarks . '</span>';
            }
            if ($exactSlip && !empty($exactSlip->other_remarks)) {
                $remarks .= '<span class="badge badge-danger" style="font-size:10px; display: block;">' . $exactSlip->other_remarks . '</span>';
            }
            if (!empty($log->comments)) {
                $wrappedComment = preg_replace('/((?:\S+\s+){4})/', '$1<br>', $log->comments);
                $remarks .= '<span class="badge badge-warning" style="margin-top: 2px; font-size:10px; max-width: 150px; display: inline-block; word-wrap: break-word; white-space: normal;">' . $wrappedComment . '</span>';
            }

            // File name - using exact document
            $fileName = 'N/A';
            if ($exactDoc) {
                $fileName = '<a href="' . route('documents.viewPdf', $exactDoc->id) . '" style="color: #007bff;" target="_blank"><i class="fas fa-file-pdf text-danger"></i> ' . Str::limit($exactDoc->file_name, 22) . '</a>';
                if ($log->viewed_status) {
                    $fileName .= '<p><small class="text-muted">Viewed on <br>' . Carbon::parse($log->viewed_at)->format('M j, Y h:i A') . '</small></p>';
                }
            }

            // Duration - using exact document
            $created = optional($exactDoc)->created_at;
            $updated = $log->updated_at;
            $diff = $created && $updated ? $created->diff($updated) : null;
            $duration = $diff ? "{$diff->days} days, {$diff->h} hours, {$diff->i} minutes" : 'N/A';

            $data[] = [
                'route_id' => $log->route_id ?? 0,
                'route_display' => $log->route_id == 0 ? 'N/A' : '<a href="' . route('slipForm', ['id' => $log->route_id]) . '?routing_slip_id=' . $routingSlipId . '" target="_blank" style="color: #007bff;">' . $log->route_id . '</a>',
                'date_received' => $exactSlip ? Carbon::parse($exactSlip->date_received)->format('F d, Y') : 'N/A',
                'source' => $exactSlip->source ?? 'N/A',
                'subject' => $exactSlip->subject ?? ($exactDoc->subject ?? 'N/A'),
                'action_unit' => $exactSlip->pres_dept ?? 'N/A',
                'received_by_date' => ($exactSlip && $exactSlip->updated_at) ? $exactSlip->updated_at->format('F j, Y') : 'N/A',
                'action_taken' => $actionTaken,
                'date_released' => optional($exactDoc)->created_at ? $exactDoc->created_at->format('m-d-Y h:i:s A') : 'N/A',
                'remarks' => $remarks,
                'file_name' => $fileName,
                'updated_at' => $log->updated_at ? $log->updated_at->format('m-d-Y h:i:s A') : 'N/A',
                'duration' => $duration,
            ];
        }

        return response()->json([
            'draw' => intval($request->input('draw', 1)),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data
        ]);
    }

    /**
     * Apply the DataTable sorting to the served query.
     *
     * The routing slip / document columns are resolved with indexed correlated
     * subqueries, so sorting stays fast even on big tables.
     */
    private function applyServedOrder($query, Request $request)
    {
        $orderColumnIndex = (int) $request->input('order.0.column', 0);
        $orderDirection = strtolower((string) $request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $columns = $request->input('columns', []);
        $requestedColumn = $columns[$orderColumnIndex]['name'] ?? ($columns[$orderColumnIndex]['data'] ?? null);

        // Correlated subquery on the routing slip (the CAST keeps the rslip_id index usable)
        $slipColumn = function ($column) {
            return "(select rs.$column from routing_slip rs
                     where rs.rslip_id = cast(logs.route_id as char) collate utf8mb4_general_ci
                       and rs.document = logs.new_file collate utf8mb4_general_ci
                     order by rs.id limit 1)";
        };

        // Correlated subquery on the documents table
        $docColumn = function ($column) {
            return "(select d.$column from documents d
                     where d.route_id = logs.route_id
                       and d.file_name = logs.new_file collate utf8mb4_unicode_ci
                     order by d.id limit 1)";
        };

        $sortMap = [
            'ctrl_no' => 'logs.ctrl_no',
            'route_display' => 'logs.route_id',
            'route_id' => 'logs.route_id',
            'date_received' => $slipColumn('date_received'),
            'source' => $slipColumn('source'),
            'subject' => $slipColumn('subject'),
            'action_unit' => $slipColumn('pres_dept'),
            'received_by_date' => $slipColumn('updated_at'),
            'action_taken' => 'logs.assigned_to',
            'date_released' => $docColumn('created_at'),
            'remarks' => 'logs.updated_at',
            'file_name' => $docColumn('file_name'),
            'updated_at' => 'logs.updated_at',
            'duration' => 'logs.updated_at',
        ];

        $orderBy = $sortMap[$requestedColumn] ?? 'logs.route_id';

        // A stable second sort keeps the pagination consistent
        return $query->orderByRaw($orderBy . ' ' . $orderDirection)
            ->orderBy('logs.id', $orderDirection);
    }

    /**
     * Server-side data for Pending DataTables AJAX
     */
/**
     * Server-side data for the Pending DataTable
     *
     * The rows are the same ones the old blade rendered (one row per
     * document), but the search, the sorting and the pagination are now
     * done by the database.
     */
    public function getPendingData(Request $request)
    {
        $user = auth()->user();
        $userId = $user->id;
        $userFullName = trim($user->fname . ' ' . $user->lname);

        $searchValue = trim((string) $request->input('search.value'));
        $start = (int) $request->input('start', 0);
        $length = (int) $request->input('length', 20);

        // The pending documents of the logged in user
        $applyScope = function ($query) use ($userId, $userFullName) {
            return $query->where('logs.status_update', 2)
                ->where(function ($q) {
                    $q->whereNull('logs.new_user')
                      ->orWhereNull('logs.assigned_to');
                })
                ->where(function ($q) use ($userId, $userFullName) {
                    $q->where('logs.new_destination', $userFullName)
                      ->orWhere('logs.user_id', $userId);
                });
        };

        // The search is done by the database (no row is loaded into PHP)
        $applySearch = function ($query) use ($searchValue) {
            if ($searchValue === '') {
                return $query;
            }

            $like = '%' . $searchValue . '%';

            return $query->where(function ($q) use ($like) {
                $q->where('logs.route_id', 'LIKE', $like)
                  ->orWhere('logs.new_destination', 'LIKE', $like)
                  ->orWhere('logs.new_file', 'LIKE', $like)
                  ->orWhere('logs.assigned_to', 'LIKE', $like)
                  ->orWhere('logs.comments', 'LIKE', $like)
                  // Routing slip fields (source / subject / remarks)
                  ->orWhereIn('logs.route_id', function ($sub) use ($like) {
                      $sub->selectRaw('CAST(rslip_id AS UNSIGNED)')
                          ->from('routing_slip')
                          ->where(function ($w) use ($like) {
                              $w->where('source', 'LIKE', $like)
                                ->orWhere('subject', 'LIKE', $like)
                                ->orWhere('trans_remarks', 'LIKE', $like)
                                ->orWhere('assign_com', 'LIKE', $like)
                                ->orWhere('other_remarks', 'LIKE', $like);
                          });
                  })
                  // Document fields (file name / subject / purpose)
                  ->orWhereIn('logs.route_id', function ($sub) use ($like) {
                      $sub->select('route_id')
                          ->from('documents')
                          ->where(function ($w) use ($like) {
                              $w->where('file_name', 'LIKE', $like)
                                ->orWhere('subject', 'LIKE', $like)
                                ->orWhere('purpose', 'LIKE', $like);
                          });
                  });
            });
        };

        // Only the documents that really exist (same as the old left join)
        $hasDocument = function ($query) {
            return $query->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('documents')
                    ->whereColumn('documents.id', 'logs.doc_id');
            });
        };

        // ONE row per document (the newest log of the document, like before)
        $rankedLogs = Log::query()
            ->select(
                'logs.id', 'logs.route_id', 'logs.new_file', 'logs.new_destination',
                'logs.new_user', 'logs.user_id', 'logs.assigned_to', 'logs.comments',
                'logs.viewed_status', 'logs.viewed_at', 'logs.status_update',
                'logs.created_at', 'logs.updated_at',
                'documents.id as doc_id', 'documents.file_name',
                'routing_slip.id as routing_slip_id', 'routing_slip.date_received',
                'routing_slip.source', 'routing_slip.subject', 'routing_slip.pres_dept',
                'routing_slip.trans_remarks',
                'routing_slip.r_destination as routing_destination'
                
            )
            ->selectRaw('row_number() over (partition by documents.id order by logs.updated_at desc, logs.id desc, routing_slip.id asc) as rn')
            ->leftJoin('documents', 'logs.doc_id', '=', 'documents.id')
            ->leftJoin('routing_slip', function ($join) {
                $join->on('logs.route_id', '=', 'routing_slip.rslip_id')
                     ->on('logs.user_id', '=', 'routing_slip.user_id');
            });

        $applyScope($rankedLogs);
        $applySearch($rankedLogs);
        $hasDocument($rankedLogs);

        // TOTAL documents (before the search filter)
        $totalRecords = $hasDocument($applyScope(Log::query()))
            ->distinct()
            ->count('doc_id');

        // Documents that matched the search
        $filteredRecords = $hasDocument($applySearch($applyScope(Log::query())))
            ->distinct()
            ->count('doc_id');

        // Build the base query
        $baseQuery = Log::query()
            ->fromSub($rankedLogs, 'logs')
            ->select('logs.*')
            ->where('logs.rn', 1);

        // Apply the sorting requested by the DataTable
        $baseQuery = $this->applyPendingOrder($baseQuery, $request);

        // Paginated results - only the current page is fetched from the database
        $paginatedLogs = $baseQuery->skip($start)->take($length)->get();

        // Preload the routing slips of the current page - NO n+1 queries
        $routeIds = $paginatedLogs->pluck('route_id')->unique()->filter()->toArray();

        $slipsByRoute = collect();

        if (!empty($routeIds)) {
            $slipsByRoute = RoutingSlip::whereIn('rslip_id', $routeIds)
                ->orderBy('id')
                ->get()
                ->groupBy(function ($slip) {
                    return (int) $slip->rslip_id;
                });
        }

        // Build data array
        $data = [];
        foreach ($paginatedLogs as $log) {
            $routeSlips = $slipsByRoute->get((int) $log->route_id, collect());

            // The routing slip of the log (the one matching the file of the log)
            $routingSlip = $routeSlips->first(function ($slip) use ($log) {
                return mb_strtolower((string) $slip->document) === mb_strtolower((string) $log->new_file);
            });

            // Fallback used when the log has no matching document on the slip
            $routingSlip = $routingSlip ?: $routeSlips->first();

            // The latest routing slip of the route (used on the links)
            $routingSlipId = $routeSlips->last()->id ?? null;

            // Action taken
            $actionTaken = '';
            if ($routingSlip && $routingSlip->r_destination) {
                $actionTaken .= '<strong class="text-danger">' . ucwords(strtolower($routingSlip->r_destination)) . '</strong>';
            }
            if ($routingSlip && $routingSlip->assigned_to) {
                $actionTaken .= ', was re-assigned to <strong class="text-danger">' . ucwords(strtolower($routingSlip->assigned_to)) . '</strong>';
            }

            // Remarks
            $remarks = '';
            if (!empty($log->trans_remarks)) {
                $wrappedTransRemarks = preg_replace('/((?:\S+\s+){4})/', '$1<br>', $log->trans_remarks);
                $remarks .= '<span class="badge badge-success" style="font-size:10px; display: block;">' . $wrappedTransRemarks . '</span>';
            }

            // File name
            if ($log->doc_id) {
                $fileName = '<a href="' . route('documents.viewPdf', ['id' => $log->doc_id]) . '" style="color: #007bff;" target="_blank"><i class="fas fa-file-pdf text-danger"></i> ' . Str::limit($log->file_name, 22) . '</a>';
            } else {
                $fileName = '<span class="text-muted"><i class="fas fa-file-times text-secondary"></i> No PDF Available</span>';
            }

            // Tracking action
            $action = '<a href="' . route('tracking', ['route_id' => $log->route_id, 'routing_slip_id' => $routingSlipId]) . '" class="btn btn-primary" target="_blank"><i class="fas fa-pen"></i></a>';

            $data[] = [
                'route_id' => $log->route_id ?? 0,
                'route_display' => '<a href="' . route('slipForm', ['id' => $log->route_id]) . '?routing_slip_id=' . $routingSlipId . '" target="_blank" style="color: #007bff;">' . $log->route_id . '</a>',
                'date_received' => $log->date_received ? Carbon::parse($log->date_received)->format('F d, Y') : 'N/A',
                'source' => $log->source ?? 'N/A',
                'subject' => $log->subject ?? 'N/A',
                'action_unit' => $log->pres_dept ?? 'N/A',
                'received_by_date' => $log->updated_at ? Carbon::parse($log->updated_at)->format('F j, Y') : 'N/A',
                'action_taken' => $actionTaken,
                'date_released' => $log->created_at ? $log->created_at->format('m-d-Y h:i:s A') : 'N/A',
                'remarks' => $remarks,
                'file_name' => $fileName,
                'action' => $action,
            ];
        }

        return response()->json([
            'draw' => intval($request->input('draw', 1)),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data
        ]);
    }

    /**
     * Apply the DataTable sorting to the pending query.
     *
     * The columns of the routing slip and of the document are already part of
     * the query, so the sorting is a plain index friendly order by.
     */
    private function applyPendingOrder($query, Request $request)
    {
        $orderColumnIndex = (int) $request->input('order.0.column', 0);
        $orderDirection = strtolower((string) $request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';

        $columns = $request->input('columns', []);
        $requestedColumn = $columns[$orderColumnIndex]['name'] ?? ($columns[$orderColumnIndex]['data'] ?? null);

        $sortMap = [
            'ctrl_no' => 'logs.route_id',
            'route_display' => 'logs.route_id',
            'route_id' => 'logs.route_id',
            'date_received' => 'logs.date_received',
            'source' => 'logs.source',
            'subject' => 'logs.subject',
            'action_unit' => 'logs.pres_dept',
            'received_by_date' => 'logs.updated_at',
            'action_taken' => 'logs.updated_at',
            'date_released' => 'logs.created_at',
            'remarks' => 'logs.updated_at',
            'file_name' => 'logs.file_name',
            'action' => 'logs.updated_at',
        ];

        $orderBy = $sortMap[$requestedColumn] ?? 'logs.updated_at';

        // A stable second sort keeps the pagination consistent
        return $query->orderByRaw($orderBy . ' ' . $orderDirection)
            ->orderBy('logs.id', $orderDirection);
    }
// Updated to optimized the loading of each page 08/06/2026


// public function viewLogs() 
// {
//     $user = auth()->user();
//     $userFullName = $user->fname . ' ' . $user->lname;
//     $userId = $user->id;
//     $userRole = $user->role;

//     $logsAll = DB::table('logs_history')
//     ->leftJoin('logs', 'logs.doc_id', '=', 'logs_history.doc_id')
//     ->leftJoin('assign_logs', 'assign_logs.new_user', '=', 'logs.new_user')
//     ->leftJoin('routing_slip', 'routing_slip.id', '=', 'logs_history.doc_id') // ✅ Added
//     ->leftJoin('users as original_users', function ($join) {
//         $join->on('logs.user_id', '=', 'original_users.id')
//              ->where('logs_history.status_update', '=', 2);
//     })
//     ->leftJoin('users as new_users', function ($join) {
//         $join->on('logs.new_user', '=', 'new_users.id')
//              ->where('logs_history.status_update', '=', 3);
//     })
//     ->leftJoin('users as assign_users', 'assign_logs.new_user', '=', 'assign_users.id')
//     ->select(
//         'logs_history.*',
//         'logs.new_destination',
//         'logs.new_file',

//         'assign_logs.assigned_to as assign_to',
//         'assign_users.fname as assign_fname',
//         'assign_users.lname as assign_lname',

//         'original_users.fname as original_fname',
//         'original_users.lname as original_lname',
//         'original_users.department as original_user_department',

//         'new_users.fname as new_fname',
//         'new_users.lname as new_lname',
//         'new_users.department as new_user_department',

//         'routing_slip.rslip_id' // ✅ Add this to retrieve the slip ID
//     )
//     ->where(function ($query) use ($userId, $userFullName) {
//         $query->where('logs.user_id', $userId)
//               ->orWhere('logs.new_user', $userId)
//               ->orWhere('logs.new_destination', $userFullName);
//     })
//     ->orWhereNull('logs.id')
//     ->distinct()
//     ->orderBy('logs_history.created_at', 'desc')
//     ->get();


//     $routingSlipCount = RoutingSlip::where('route_status', 3)->count();
//     $superUserCount = $userRole === 'super_user' ? RoutingSlip::where('route_status', 1)->count() : 0;
//     $recordsOfficerCount = $userRole === 'records_officer' ? RoutingSlip::where('route_status', 2)->count() : 0;

//      // Get all Doctrack records (no grouping)
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

//     return view('home.viewLogs', compact(
//         'logsAll', 'userId', 'userFullName',
//         'routingSlipCount', 'superUserCount', 'recordsOfficerCount','doctrackCount'
//     ));
// }


// public function viewLogsTracking() 
// {
//     $user = auth()->user();
//     $userId = $user->id;
//     $userDepartment = $user->department;
//     $userFullName = $user->fname . ' ' . $user->lname;
//     $userRole = $user->role;

//     // First, get all docslip_ids where the user is involved (either creator or recipient)
//     $relatedDocslipIds = LogsTracking::where('user_id', $userId)
//         ->orWhere('update_by', $userId)
//         ->pluck('docslip_id');

//     // Then, fetch all logs for those docslip_ids
//     $logsAll = LogsTracking::with(['createdBy', 'updatedBy', 'doctrackFile'])
//         ->whereIn('docslip_id', $relatedDocslipIds)
//         ->orderByDesc('created_at')
//         ->get();
//  // Get all Doctrack records (no grouping)
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

//     // Count logic
//     $routingSlipCount = RoutingSlip::where('route_status', 3)->count();
//     $superUserCount = $userRole === 'super_user' ? RoutingSlip::where('route_status', 1)->count() : 0;
//     $recordsOfficerCount = $userRole === 'records_officer' ? RoutingSlip::where('route_status', 2)->count() : 0;

//     return view('home.viewLogsTracking', compact(
//         'logsAll', 'routingSlipCount', 'superUserCount', 'recordsOfficerCount','doctrackCount'
//     ));
// }

public function viewLogs() 
{
    $user = auth()->user();
    $userRole = $user->role;

    $routingSlipCount = RoutingSlip::where('route_status', 3)->count();
    $superUserCount = $userRole === 'super_user' ? RoutingSlip::where('route_status', 1)->count() : 0;
    $recordsOfficerCount = $userRole === 'records_officer' ? RoutingSlip::where('route_status', 2)->count() : 0;
    $doctrackCount = Doctrack::where('doctrack_stat', 2)->count();

    return view('home.viewLogs', compact(
        'routingSlipCount', 'superUserCount', 'recordsOfficerCount', 'doctrackCount'
    ));
}

public function getViewLogsData(Request $request)
{
    $user = auth()->user();
    $userFullName = $user->fname . ' ' . $user->lname;
    $userId = $user->id;

    $searchValue = $request->input('search.value');
    $start = $request->input('start', 0);
    $length = $request->input('length', 50);

    // Step 1: Simple query to get IDs
    $idQuery = DB::table('logs_history')
        ->leftJoin('logs', 'logs.doc_id', '=', 'logs_history.doc_id')
        ->where(function ($q) use ($userId, $userFullName) {
            $q->where('logs.user_id', $userId)
              ->orWhere('logs.new_user', $userId)
              ->orWhere('logs.new_destination', $userFullName);
        })
        ->whereNotExists(function ($q) {
            $q->select(DB::raw(1))
                ->from('routing_slip')
                ->whereColumn('routing_slip.rslip_id', 'logs.route_id')
                ->where('routing_slip.is_archived', 1);
        })
        ->whereNotExists(function ($q) {
            $q->select(DB::raw(1))
                ->from('doctrack_slip')
                ->whereColumn('doctrack_slip.docslip_id', 'logs.route_id')
                ->where('doctrack_slip.is_archived', 1);
        });

    if ($searchValue) {
        $idQuery->where(function ($q) use ($searchValue) {
            $q->where('logs.new_file', 'LIKE', "%{$searchValue}%")
              ->orWhere('logs.new_destination', 'LIKE', "%{$searchValue}%");
        });
    }

    $totalRecords = $idQuery->count();

    $pageIds = (clone $idQuery)
        ->select('logs_history.id')
        ->orderBy('logs_history.created_at', 'desc')
        ->skip($start)
        ->take($length)
        ->pluck('id');

    if ($pageIds->isEmpty()) {
        return response()->json([
            'draw' => intval($request->input('draw')),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecords,
            'data' => []
        ]);
    }

    // Step 2: Full joins only for the 50 IDs
    $logsAll = DB::table('logs_history')
        ->leftJoin('logs', 'logs.doc_id', '=', 'logs_history.doc_id')
        ->leftJoin('assign_logs', 'assign_logs.new_user', '=', 'logs.new_user')
        ->leftJoin('routing_slip', 'routing_slip.id', '=', 'logs_history.doc_id')
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
            'logs_history.id',
            'logs_history.status_update',
            'logs_history.created_at',
            'logs.new_destination',
            'logs.new_file',
            'assign_logs.assigned_to as assign_to',
            'assign_users.fname as assign_fname',
            'assign_users.lname as assign_lname',
            'original_users.fname as original_fname',
            'original_users.lname as original_lname',
            'new_users.fname as new_fname',
            'new_users.lname as new_lname',
            'routing_slip.rslip_id'
        )
        ->whereIn('logs_history.id', $pageIds)
        ->orderBy('logs_history.created_at', 'desc')
        ->get();

    $data = [];
    foreach ($logsAll as $log) {
        $isAssignLog = $log->status_update == 2 && $log->assign_to;
        $displayName = $isAssignLog
            ? trim(($log->assign_fname ?? '') . ' ' . ($log->assign_lname ?? ''))
            : ($log->status_update == 2
                ? trim(($log->original_fname ?? '') . ' ' . ($log->original_lname ?? ''))
                : trim(($log->new_fname ?? '') . ' ' . ($log->new_lname ?? '')));

        $badgeClass = match ((int)$log->status_update) {
            2 => 'badge-warning', 3 => 'badge-success', 4 => 'badge-danger',
            default => 'badge-secondary',
        };
        $badgeLabel = match ((int)$log->status_update) {
            2 => 'uploaded', 3 => 'acknowledged', 4 => 'returned to the OP with correction',
            default => 'action',
        };
        $routedTo = $isAssignLog ? $log->assign_to : $log->new_destination;

        $logEntry = '<span style="font-weight:bold;">' . ($displayName ?: ' ') . '</span> ';
        $logEntry .= $isAssignLog 
            ? '<span class="badge bg-info text-dark">re-assigned</span> ' 
            : '<span class="badge ' . $badgeClass . '">' . $badgeLabel . '</span> ';
        $logEntry .= 'the file <span class="text-primary" style="font-weight:bold;">' . ($log->new_file ?? 'N/A') . '</span>';
        if ($routedTo) {
            $logEntry .= ' and routed it to <span style="font-weight:bold;">' . $routedTo . '</span>';
        }

        $data[] = [
            'log_entry' => $logEntry,
            'date' => \Carbon\Carbon::parse($log->created_at)->format('M j, Y h:i:s A'),
        ];
    }

    return response()->json([
        'draw' => intval($request->input('draw')),
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $totalRecords,
        'data' => $data
    ]);
}

public function viewLogsTracking() 
{
    $user = auth()->user();
    $userRole = $user->role;

    $routingSlipCount = RoutingSlip::where('route_status', 3)->count();
    $superUserCount = $userRole === 'super_user' ? RoutingSlip::where('route_status', 1)->count() : 0;
    $recordsOfficerCount = $userRole === 'records_officer' ? RoutingSlip::where('route_status', 2)->count() : 0;
    $doctrackCount = Doctrack::where('doctrack_stat', 2)->count();

    return view('home.viewLogsTracking', compact(
        'routingSlipCount', 'superUserCount', 'recordsOfficerCount', 'doctrackCount'
    ));
}

public function getViewLogsTrackingData(Request $request)
{
    $user = auth()->user();
    $userId = $user->id;

    $searchValue = $request->input('search.value');
    $start = $request->input('start', 0);
    $length = $request->input('length', 50);

    $query = LogsTracking::where(function ($q) use ($userId) {
            $q->where('user_id', $userId)
              ->orWhere('update_by', $userId);
        })
        ->whereNotExists(function ($q) {
            $q->select(DB::raw(1))
                ->from('doctrack_slip')
                ->whereColumn('doctrack_slip.docslip_id', 'logs_tracking.docslip_id')
                ->where('doctrack_slip.is_archived', 1);
        });

    if ($searchValue) {
        $query->where(function ($q) use ($searchValue) {
            $q->where('docslip_id', 'LIKE', "%{$searchValue}%")
              ->orWhere('comments', 'LIKE', "%{$searchValue}%")
              ->orWhere('file_logs', 'LIKE', "%{$searchValue}%");
        });
    }

    $totalRecords = $query->count();

    $logsAll = $query->with(['createdBy', 'updatedBy', 'doctrackFile'])
        ->orderByDesc('created_at')
        ->skip($start)
        ->take($length)
        ->get();

    $data = [];
    foreach ($logsAll as $log) {
        $actor = in_array($log->logs_status, [3, 5, 6]) ? $log->updatedBy : $log->createdBy;
        $target = in_array($log->logs_status, [1, 2]) ? $log->updatedBy : null;
        $fileName = $log->file_logs ?? 'N/A';

        $statusBadge = match ((int)$log->logs_status) {
            1 => ['label' => 'Created', 'class' => 'badge-primary'],
            2 => ['label' => 'Forwarded', 'class' => 'badge-warning'],
            3 => ['label' => 'Signed', 'class' => 'badge-success'],
            4 => ['label' => 'Returned', 'class' => 'badge-danger'],
            5 => ['label' => 'Checked', 'class' => 'badge-info'],
            6 => ['label' => 'Acknowledged', 'class' => 'badge-success'],
            7 => ['label' => 'Deleted', 'class' => 'badge-dark'],
            default => ['label' => 'Updated', 'class' => 'badge-secondary'],
        };

        $entry = $log->docslip_id . ' - <strong>' . ($actor->fname ?? 'N/A') . ' ' . ($actor->lname ?? '') . '</strong> ';
        $entry .= '<span class="badge ' . $statusBadge['class'] . '">' . $statusBadge['label'] . '</span> ';
        if ($log->logs_status != 7) {
            $entry .= 'file: <span class="text-primary font-weight-bold">' . $fileName . '</span> ';
        }
        if ($target && !in_array($log->logs_status, [3, 4, 5, 6, 7])) {
            $entry .= '→ Routed to <strong>' . ($target->fname ?? '') . ' ' . ($target->lname ?? '') . '</strong>';
        }
        if ($log->comments) {
            $entry .= '<br><em class="text-muted">Comment: "' . $log->comments . '"</em>';
        }

        $data[] = [
            'log_entry' => $entry,
            'date' => \Carbon\Carbon::parse($log->created_at)->format('M j, Y h:i A'),
        ];
    }

    return response()->json([
        'draw' => intval($request->input('draw')),
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $totalRecords,
        'data' => $data
    ]);
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

//     // ✅ Handle E-signature upload to storage/app/esignature
//     if ($request->hasFile('esig_file')) {
//         $file = $request->file('esig_file');
//         $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
//         $extension = $file->getClientOriginalExtension();
//         $filename = $user->fname . '_' . $originalName . '.' . $extension;

//         $i = 1;
//         $storagePath = storage_path('app/esignature');

//         // Ensure filename is unique in esignature folder
//         while (file_exists($storagePath . '/' . $filename)) {
//             $filename = $user->fname . '_' . $originalName . ' Copy ' . $i . '.' . $extension;
//             $i++;
//         }

//         // Delete old file if exists
//         $existingEsig = Esig::where('user_id', $user->id)->first();
//         if ($existingEsig && $existingEsig->esig_file) {
//             $oldPath = $storagePath . '/' . $existingEsig->esig_file;
//             if (file_exists($oldPath)) {
//                 unlink($oldPath);
//             }
//         }

//         // Save new file
//         $file->storeAs('esignature', $filename); // this stores to storage/app/esignature

//         // Save or update Esig record
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
        'esig_file' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:25600',
    ]);

    if ($validator->fails()) {
        return redirect()->back()->withErrors($validator)->withInput();
    }

    $user = User::find($id);
    if (!$user) {
        return redirect()->back()->with('error', 'User not found');
    }

    // ✅ Update password if provided
    if ($request->filled('password')) {
        $user->password = Hash::make($request->password);
    }

    // ✅ Update department if provided
    if ($request->filled('department')) {
        $user->department = $request->department;
    }

    // ✅ Handle E-signature upload
    if ($request->hasFile('esig_file')) {
        $file = $request->file('esig_file');
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $file->getClientOriginalExtension();
        $filename = $user->fname . '_' . $originalName . '.' . $extension;

        $i = 1;
        $storagePath = storage_path('app/esignature');

        while (file_exists($storagePath . '/' . $filename)) {
            $filename = $user->fname . '_' . $originalName . ' Copy ' . $i . '.' . $extension;
            $i++;
        }

        // Delete old file if exists
        $existingEsig = Esig::where('user_id', $user->id)->first();
        if ($existingEsig && $existingEsig->esig_file) {
            $oldPath = $storagePath . '/' . $existingEsig->esig_file;
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
        }

        // Save new file
        $file->storeAs('esignature', $filename);

        Esig::updateOrCreate(
            ['user_id' => $user->id],
            ['esig_file' => $filename]
        );
    }

    // ✅ Save changes to user
    $user->save();

    return redirect()->route('userPassword', ['id' => $id])
        ->with('success', 'User updated successfully.');
}


// public function distributionList()
// {
//     $user = auth()->user();
//     $userRole = $user->role;

//     if (!in_array($userRole, ['Administrator', 'records_officer'])) {
//         abort(403, 'Unauthorized');
//     }

//   $logs = RoutingSlip::leftJoin('documents', 'routing_slip.rslip_id', '=', 'documents.route_id')
//     ->select('routing_slip.*', 'documents.id as doc_id', 'documents.file_name')
//     ->orderBy('routing_slip.created_at', 'desc')
//     ->get()
//     ->filter(function ($item) {
//         $destinations = \App\Models\Log::where('route_id', $item->rslip_id)
//             ->whereNotNull('new_destination')
//             ->whereRaw("TRIM(new_destination) != ''")
//             ->distinct()
//             ->pluck('new_destination');

//         // attach destinations for display
//         $item->destinations = $destinations;

//         return $destinations->count() >= 4;
//     });



//     $offices = Office::all();

//     // ✅ Add doctrackCount similar to viewSlip
//     $userFullName = $user->fname . ' ' . $user->lname;

//     $documentTrack = Doctrack::where(function ($query) use ($user, $userFullName) {
//             $query->where('user_id', $user->id)
//                   ->orWhere('update_by', $user->id)
//                   ->orWhere('user_name', $userFullName);
//         })->get();

//     // Count only records with doctrack_stat == 2
//     $doctrackCount = $documentTrack->where('doctrack_stat', 2)->count();

//     return view('home.distList', compact('logs', 'offices', 'doctrackCount'));
// }

public function distributionList()
{
    $user = auth()->user();
    $userRole = $user->role;

    if (!in_array($userRole, ['Administrator', 'records_officer'])) {
        abort(403, 'Unauthorized');
    }

    // Get Routed Distribution List (Original distributionList logic)
    $routedLogs = RoutingSlip::leftJoin('documents', 'routing_slip.rslip_id', '=', 'documents.route_id')
        ->select('routing_slip.*', 'documents.id as doc_id', 'documents.file_name')
        ->orderBy('routing_slip.created_at', 'desc')
        ->get()
        ->filter(function ($item) {
            $destinations = \App\Models\Log::where('route_id', $item->rslip_id)
                ->whereNotNull('new_destination')
                ->whereRaw("TRIM(new_destination) != ''")
                ->distinct()
                ->pluck('new_destination');

            // attach destinations for display
            $item->destinations = $destinations;

            return $destinations->count() >= 4;
        });

    // Get Tracking Distribution List (Original trackingDistributionList logic)
    $trackingLogs = Doctrack::with(['updatedBy', 'doctrackFile'])
        ->orderBy('created_at', 'desc')
        ->whereNotNull('update_by')
        ->where('user_id', 56) // 👈 filter here
        ->get()
        ->groupBy('docslip_id')
        ->filter(function ($group) {
            // Only keep groups with 4 or more entries
            return $group->count() >= 4;
        })
        ->map(function ($group) {
            // Get the first record to use as the "main" row
            $first = $group->first();

            // Combine all update_by names
            $names = $group->map(function ($item) {
                return $item->updatedBy ? $item->updatedBy->fname . ' ' . $item->updatedBy->lname : null;
            })
            ->filter() // Remove nulls
            ->unique() // Remove duplicates
            ->implode(', '); // Combine into one string

            // Attach the combined names as a new property
            $first->combined_names = $names;

            return $first;
        })
        ->values(); // Reset array keys

    $offices = Office::all();

    // Common doctrackCount logic
    $userFullName = $user->fname . ' ' . $user->lname;

    $documentTrack = Doctrack::where(function ($query) use ($user, $userFullName) {
            $query->where('user_id', $user->id)
                  ->orWhere('update_by', $user->id)
                  ->orWhere('user_name', $userFullName);
        })->get();

    // Count only records with doctrack_stat == 2
    $doctrackCount = $documentTrack->where('doctrack_stat', 2)->count();

    return view('home.distList', compact('routedLogs', 'trackingLogs', 'offices', 'doctrackCount'));
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
    abort_if(app(ArchiveService::class)->isRouteArchived($id), 404, 'This transaction has been archived.');

    $routingSlip = DB::table('routing_slip')->where('rslip_id', $id)->first();

    $logs = DB::table('logs')
        ->leftJoin('users', 'logs.new_user', '=', 'users.id')
        ->where('logs.route_id', $id)
        ->select('logs.*', 'users.department as user_department')
        ->orderBy('logs.created_at', 'asc')
        ->get()
        ->unique('new_destination')
        ->values();

    // Add e-signature if status_update == 3
    $logs->transform(function ($log) {
        if ($log->status_update == 3) {
            $esig = \App\Models\Esig::where('user_id', $log->new_user)->first();
            if ($esig && $esig->esig_file) {
                $esigPath = storage_path('app/esignature/' . $esig->esig_file);
                if (file_exists($esigPath)) {
                    $imageData = file_get_contents($esigPath);
                    $mimeType = mime_content_type($esigPath);
                    $log->esig_src = 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
                } else {
                    $log->esig_src = null;
                }
            } else {
                $log->esig_src = null;
            }
        } else {
            $log->esig_src = null;
        }
        return $log;
    });

    $title = 'DISTRIBUTION/RETRIEVAL LIST';

    return PDF::loadView('slip.distPdf', compact('routingSlip', 'logs', 'title'))
        ->stream('distribution_list.pdf');
}


public function trackingDistributionList()
{
    $user = auth()->user();
    $userRole = $user->role;

    if (!in_array($userRole, ['Administrator', 'records_officer'])) {
        abort(403, 'Unauthorized');
    }

    // ✅ Fetch Doctrack with relationships for better performance
   $logs = Doctrack::with(['updatedBy', 'doctrackFile'])
    ->orderBy('created_at', 'desc')
    ->whereNotNull('update_by')
    ->where('user_id', 56) // 👈 filter here
    ->get()
    ->groupBy('docslip_id')// Group by docslip_id
        ->filter(function ($group) {
            // Only keep groups with 4 or more entries
            return $group->count() >= 4;
        })
        ->map(function ($group) {
            // Get the first record to use as the "main" row
            $first = $group->first();

            // Combine all update_by names
            $names = $group->map(function ($item) {
                return $item->updatedBy ? $item->updatedBy->fname . ' ' . $item->updatedBy->lname : null;
            })
            ->filter() // Remove nulls
            ->unique() // Remove duplicates
            ->implode(', '); // Combine into one string

            // Attach the combined names as a new property
            $first->combined_names = $names;

            return $first;
        })
        ->values(); // Reset array keys

    $offices = Office::all();

    $userFullName = $user->fname . ' ' . $user->lname;

    $documentTrack = Doctrack::where(function ($query) use ($user, $userFullName) {
        $query->where('user_id', $user->id)
              ->orWhere('update_by', $user->id)
              ->orWhere('user_name', $userFullName);
    })->get();

    // Count only records with doctrack_stat == 2
    $doctrackCount = $documentTrack->where('doctrack_stat', 2)->count();

    return view('home.trackingDistList', compact('logs', 'offices', 'doctrackCount'));
}

public function viewTrackingDistributionPdf($id)
{
    $title = 'DISTRIBUTION/RETRIEVAL LIST';

    $docTitle = Doctrack::where('docslip_id', $id)->value('doc_title');

    $logs = Doctrack::with(['updatedBy', 'receivedBy' => function ($query) {
            $query->select('id', 'fname', 'lname', 'department');
        }])
        ->where('docslip_id', $id)
        ->whereNotNull('update_by')
        ->orderBy('created_at', 'desc')
        ->get()
        ->map(function ($log) {
            $log->user_department = $log->updatedBy->department ?? null;
            $log->update_by_name = $log->updatedBy
                ? trim($log->updatedBy->fname . ' ' . $log->updatedBy->lname)
                : null;
            $log->received_by_name = $log->receivedBy
                ? trim($log->receivedBy->fname . ' ' . $log->receivedBy->lname)
                : null;

            if (in_array($log->doctrack_stat, [3, 5])) {
                $esig = \App\Models\Esig::where('user_id', $log->update_by)->first();
                if ($esig && $esig->esig_file) {
                    $esigPath = storage_path('app/esignature/' . $esig->esig_file);
                    if (file_exists($esigPath)) {
                        $imageData = file_get_contents($esigPath);
                        $mimeType = mime_content_type($esigPath);
                        $log->esig_src = 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
                    } else {
                        $log->esig_src = null;
                    }
                } else {
                    $log->esig_src = null;
                }
            } else {
                $log->esig_src = null;
            }

            return $log;
        });

    return PDF::loadView('slip.distTrackPdf', compact('logs', 'title', 'docTitle'))
        ->stream('distribution_list.pdf');
}


public function deleteSlip($docslip_id)
{
    // Find the document by ID
    $documentTrack = Doctrack::where('docslip_id', $docslip_id)->firstOrFail();

    // ✅ Only the creator of the transaction may delete the whole transaction
    if ((int) $documentTrack->user_id !== (int) auth()->id()) {
        return redirect()->route('doctrackSlip')
            ->with('error', 'Only the creator of the transaction can delete it.');
    }

    // Capture necessary info for logging before deletion
    $docslip_id = $documentTrack->docslip_id;
    $user_id    = $documentTrack->user_id;
    $update_by  = $documentTrack->update_by;
    $doc_title  = $documentTrack->doc_title;
    
    // Log the deletion (status 0 = deleted, or use a specific code if preferred)
    LogsTracking::create([
        'docslip_id' => $docslip_id,
        'user_id'    => $user_id,
        'update_by'  => $update_by,
        'doc_title'  => $doc_title,
        'file_logs'  => null,
        'logs_status'=> 7, // You can define 0 as "Deleted" in your UI logic
        'comments'   => 'Document deleted.',
    ]);

    // Delete all associated files if necessary
    $files = DoctrackFile::where('docslip_id', $docslip_id)->get();
    foreach ($files as $file) {
        Storage::delete('doc_track/' . $file->file);
        $file->delete();
    }

    // Delete all related document records with the same docslip_id
    Doctrack::where('docslip_id', $docslip_id)->delete();

    // Redirect with message
    return redirect()->route('doctrackSlip')
        ->with('success', 'Document deleted and logged successfully!');
}

/**
 * ✅ Delete a SINGLE row (entry) of a transaction.
 *
 * The creator of the transaction may remove a row when there is duplicate
 * data, without deleting the whole transaction.
 */
public function deleteSlipRow(Request $request, $id)
{
    $row = Doctrack::findOrFail($id);

    // ✅ Only the creator of the transaction may delete a row
    if ((int) $row->user_id !== (int) auth()->id()) {
        return back()->with('error', 'Only the creator of the transaction can delete a row.');
    }

    $docslip_id = $row->docslip_id;
    $user_id    = $row->user_id;
    $update_by  = $row->update_by;
    $doc_title  = $row->doc_title;

    // Log the row deletion (no comment is added, the remarks are left as they are)
    LogsTracking::create([
        'docslip_id' => $docslip_id,
        'user_id'    => $user_id,
        'update_by'  => $update_by,
        'doc_title'  => $doc_title,
        'file_logs'  => null,
        'logs_status'=> 7,
        'comments'   => null,
    ]);

    // ✅ Remove the tracking logs that belong to the deleted row (duplicate data)
    $rowLogs = LogsTracking::where('docslip_id', $docslip_id)
        ->where('user_id', $user_id)
        ->where('logs_status', '!=', 7);

    if (is_null($update_by)) {
        $rowLogs->whereNull('update_by');
    } else {
        $rowLogs->where('update_by', $update_by);
    }

    $rowLogs->delete();

    // Remove the row itself
    $row->delete();

    // ✅ Clean up the transaction when no rows are left
    if (Doctrack::where('docslip_id', $docslip_id)->count() === 0) {
        $storagePath = storage_path('app/doc_track');

        $files = DoctrackFile::where('docslip_id', $docslip_id)->get();
        foreach ($files as $file) {
            if ($file->file && file_exists($storagePath . '/' . $file->file)) {
                @unlink($storagePath . '/' . $file->file);
            }
            $file->delete();
        }

        LogsTracking::where('docslip_id', $docslip_id)->delete();
    }

    return back()->with('success', 'Row deleted successfully!');
}

public function offices()
{
    $user = auth()->user();
    $userRole = $user->role;

    // Only allow certain roles to access this page
    if (!in_array($userRole, ['Administrator', 'records_officer', 'super_user'])) {
        abort(403, 'Unauthorized');
    }

    $offices = Office::orderBy('office_name')->get();

    // For sidebar counts
    $userId = $user->id;
    $userFullName = $user->fname . ' ' . $user->lname;

    $documentTrack = Doctrack::where(function ($query) use ($user, $userFullName) {
        $query->where('user_id', $user->id)
              ->orWhere('update_by', $user->id)
              ->orWhere('user_name', $userFullName);
    })->get();

   // Count only records with doctrack_stat == 2
    $doctrackCount = $documentTrack->where('doctrack_stat', 2)->count();

    $recordsOfficerCount = $userRole === 'records_officer'
        ? RoutingSlip::where('route_status', 2)->count()
        : 0;

    $superUserCount = $userRole === 'super_user'
        ? RoutingSlip::where('route_status', 1)->count()
        : 0;

    return view('home.offices', compact('offices', 'doctrackCount', 'recordsOfficerCount', 'superUserCount'));
}


public function update(Request $request, Office $office)
{
    $request->validate([
        'office_name' => 'string|max:255',
        'office_abbr' => 'string|max:50',
    ]);

    $office->update($request->only(['office_name', 'office_abbr']));

    return response()->json(['success' => true, 'message' => 'Office updated successfully.']);
}

public function destroy(Office $office)
{
    $office->delete();

    return response()->json(['success' => 'Office deleted successfully.']);
}

public function store(Request $request)
{
    $request->validate([
        'office_name' => 'required|string|max:255',
        'office_abbr' => 'required|string|max:50',
    ]);

    Office::create($request->only('office_name', 'office_abbr'));

    return response()->json(['success' => 'Office added successfully.']);
}

public function userGroups()
{
    $user = auth()->user();
    $userRole = $user->role;

    // Access control similar to offices
    if (!in_array($userRole, ['Administrator', 'records_officer', 'super_user'])) {
        abort(403, 'Unauthorized');
    }

    // Fetch groups ordered by group_name
    $groups = \App\Models\Group::orderBy('group_name')->get();

    // Sidebar counts or other data as needed
    $userId = $user->id;
    $userFullName = $user->fname . ' ' . $user->lname;

    $documentTrack = Doctrack::where(function ($query) use ($user, $userFullName) {
        $query->where('user_id', $user->id)
              ->orWhere('update_by', $user->id)
              ->orWhere('user_name', $userFullName);
    })->get();

    // Count only records with doctrack_stat == 2
    $doctrackCount = $documentTrack->where('doctrack_stat', 2)->count();

    $recordsOfficerCount = $userRole === 'records_officer'
        ? RoutingSlip::where('route_status', 2)->count()
        : 0;

    $superUserCount = $userRole === 'super_user'
        ? RoutingSlip::where('route_status', 1)->count()
        : 0;

    return view('home.userGroups', compact(
        'groups', 'doctrackCount', 'recordsOfficerCount', 'superUserCount'
    ));
}

// Update group
public function updateGroup(Request $request, \App\Models\Group $group)
{
    $request->validate([
        'group_name' => 'required|string|max:255',
    ]);

    $group->update($request->only('group_name'));

    return response()->json(['success' => true, 'message' => 'Group updated successfully.']);
}

// Delete group
public function destroyGroup(\App\Models\Group $group)
{
    $group->delete();

    return response()->json(['success' => 'Group deleted successfully.']);
}

// Store new group
public function storeGroup(Request $request)
{
    $request->validate([
        'group_name' => 'required|string|max:255',
    ]);

    \App\Models\Group::create($request->only('group_name'));

    return response()->json(['success' => 'Group added successfully.']);
}

/**
 * TURNAROUND TIME REPORT
 *
 * Two separate transaction types are supported:
 *  - Tracking documents (doctrack_slip): one transaction is one docslip_id.
 *    It is completed when every recipient row has been acknowledged
 *    (doctrack_stat = 6).
 *  - Routing slips (routing_slip): a slip is completed when route_status = 3.
 *
 * The turnaround time is measured from the date received (the first log for
 * tracking documents and the created_at value for routing slips) up to the
 * final acknowledgement (or final routing action for routing slips).
 */
public function turnaround(Request $request)
{
    $data = $this->buildTurnaroundReport($request);

    return view('home.turnaround', $data);
}

/**
 * Return the report HTML for the JavaScript filter. This keeps the address
 * bar on /turnaround while the transaction type and date range change.
 */
public function turnaroundData(Request $request)
{
    $data = $this->buildTurnaroundReport($request);

    return response()->json([
        'html' => view('home.partials.turnaround_report', $data)->render(),
        'type' => $data['type'],
        'typeLabel' => strtoupper($data['typeLabel']),
    ]);
}

public function turnaroundPdf(Request $request)
{
    $data = $this->buildTurnaroundReport($request);

    // A PDF containing thousands of rows is very heavy for DomPDF and can
    // exhaust the server's memory. The summary above still covers every
    // transaction; only the detail rows are capped.
    $pdfDetailLimit = $data['type'] === 'routing' ? 500 : 1000;
    $data['pdfTotalRows'] = $data['turnaroundData']->count();
    $data['pdfDetailLimit'] = $pdfDetailLimit;
    $data['pdfLimited'] = $data['pdfTotalRows'] > $pdfDetailLimit;
    $data['turnaroundData'] = $data['turnaroundData']->take($pdfDetailLimit)->values();

    $pdf = Pdf::loadView('pdf.turnaround', $data);
    $pdf->setPaper('A4', 'landscape');

    return $pdf->download(
        'turnaround-' . $data['type'] . '-' . now()->format('Y-m-d') . '.pdf'
    );
}

/**
 * Build all the data used by both the page and the PDF export.
 */
private function buildTurnaroundReport(Request $request): array
{
    // Routing slips are the primary display unless tracking documents are
    // explicitly requested.
    $type = $request->input('type') === 'tracking' ? 'tracking' : 'routing';

    $startDate = $request->filled('start_date')
        ? Carbon::parse($request->start_date)->startOfDay()
        : null;

    $endDate = $request->filled('end_date')
        ? Carbon::parse($request->end_date)->endOfDay()
        : null;

    $user = auth()->user();
    $canViewAll = $this->canViewAllTurnaround($user);
    $userOffice = $user->department ?: '';

    if ($type === 'routing') {
        $turnaroundData = $this->buildRoutingTurnaroundRows($request, $startDate, $endDate);
        $typeLabel = 'Routing Slips';
        $typeLabelSingular = 'Routing Slip';
    } else {
        $turnaroundData = $this->buildTrackingTurnaroundRows($request, $startDate, $endDate);
        $typeLabel = 'Tracking Documents';
        $typeLabelSingular = 'Tracking Document';
    }

    // Newest transactions first.
    $turnaroundData = $turnaroundData->sortByDesc('started_at')->values();

    [$dailyReport, $weeklyReport, $monthlyReport, $officeReport] =
        $this->buildTurnaroundGroupReports($turnaroundData);

    $overallStats = $this->buildTurnaroundStats($turnaroundData);

    return [
        'type' => $type,
        'typeLabel' => $typeLabel,
        'typeLabelSingular' => $typeLabelSingular,
        'canViewAll' => $canViewAll,
        'userOffice' => $userOffice,
        'turnaroundData' => $turnaroundData,
        'officeReport' => $officeReport,
        'dailyReport' => $dailyReport,
        'weeklyReport' => $weeklyReport,
        'monthlyReport' => $monthlyReport,
        'overallStats' => $overallStats,
        'startDate' => $request->input('start_date'),
        'endDate' => $request->input('end_date'),
    ];
}

/**
 * User id 56 has been given full access to the year-end turnaround report.
 * Records officers and administrators also see every transaction.
 * Every other role only sees their own completed transactions.
 */
private function canViewAllTurnaround($user): bool
{
    if (!$user) {
        return false;
    }

    return (int) $user->id === 56
        || in_array($user->role, ['Administrator', 'records_officer'], true);
}

/**
 * Normalize an office/department name so differently-cased or spaced values
 * still match the same office.
 */
private function normalizeOffice($value): string
{
    $value = preg_replace('/\s+/', ' ', trim((string) $value));

    return mb_strtoupper($value);
}

/**
 * Turnaround rows of the document tracking slips (doctrack_slip table).
 *
 * A "transaction" is one docslip_id. It is counted as completed when every
 * recipient row has already been acknowledged (doctrack_stat = 6).
 */
private function buildTrackingTurnaroundRows(Request $request, $startDate, $endDate)
{
    $user = auth()->user();
    $userId = $user->id;

    // Only user id 56, records officers and administrators can see every
    // transaction. Standard users only see their own completed transactions.
    $canViewAll = $this->canViewAllTurnaround($user);

    // Only fully acknowledged transactions (all recipient rows are status 6).
    $completedIds = Doctrack::query()
        ->select('docslip_id')
        ->whereNotNull('update_by')
        ->groupBy('docslip_id')
        ->havingRaw('SUM(CASE WHEN doctrack_stat <> 6 THEN 1 ELSE 0 END) = 0')
        ->pluck('docslip_id');

    // The first log is the date received and the last acknowledgement log is
    // the date acknowledged.
    $logSummary = LogsTracking::query()
        ->selectRaw('docslip_id, MIN(created_at) AS started_at, MAX(CASE WHEN logs_status = 6 THEN created_at END) AS finished_at')
        ->whereIn('docslip_id', $completedIds)
        ->groupBy('docslip_id')
        ->get()
        ->filter(function ($row) use ($startDate, $endDate) {
            if (!$row->started_at || !$row->finished_at) {
                return false;
            }

            $started = Carbon::parse($row->started_at);

            if ($startDate && $started->lt($startDate)) {
                return false;
            }

            if ($endDate && $started->gt($endDate)) {
                return false;
            }

            return true;
        });

    $docslipIds = $logSummary->pluck('docslip_id')->unique()->values();

    // One row of information per transaction.
    $details = Doctrack::query()
        ->selectRaw('docslip_id, MIN(user_id) AS creator_id, MAX(ctrl_no) AS ctrl_no, MIN(user_name) AS user_name')
        ->whereIn('docslip_id', $docslipIds)
        ->groupBy('docslip_id')
        ->get()
        ->keyBy('docslip_id');

    // The person who gave the final acknowledgement is the office that
    // actually completed the transaction.
    $lastAckLogs = LogsTracking::query()
        ->whereIn('docslip_id', $docslipIds)
        ->where('logs_status', 6)
        ->orderBy('created_at')
        ->get(['docslip_id', 'update_by', 'created_at'])
        ->groupBy('docslip_id')
        ->map(function ($logs) {
            return $logs->last();
        });

    $creatorIds = $details->pluck('creator_id')->filter()->unique();
    $ackUserIds = $lastAckLogs->pluck('update_by')->filter()->unique();

    $users = User::whereIn('id', $creatorIds->merge($ackUserIds)->unique()->values())
        ->get()
        ->keyBy('id');

    $threshold = 24 * 60;
    $rows = collect();

    foreach ($logSummary as $log) {
        $detail = $details->get($log->docslip_id);

        if (!$detail) {
            continue;
        }

        $creator = $users->get($detail->creator_id);
        $ackLog = $lastAckLogs->get($log->docslip_id);
        $ackUser = $ackLog ? $users->get($ackLog->update_by) : null;

        $source = $creator
            ? trim($creator->fname . ' ' . $creator->lname)
            : ($detail->user_name ?: 'N/A');

        $personnel = $ackUser
            ? trim($ackUser->fname . ' ' . $ackUser->lname)
            : $source;

        $office = $ackUser
            ? ($ackUser->department ?: ($ackUser->position ?: 'N/A'))
            : ($creator ? ($creator->department ?: ($creator->position ?: 'N/A')) : 'N/A');

        $started = Carbon::parse($log->started_at);
        $finished = Carbon::parse($log->finished_at);
        $diffInMinutes = (int) round($started->diffInMinutes($finished));

        $rows->push([
            'personnel' => $personnel,
            'office' => $office,
            'source' => $source,
            'ctrl_no' => $detail->ctrl_no ?: 'N/A',
            'reference' => $log->docslip_id,
            'actor_id' => $ackUser ? $ackUser->id : null,
            'date_received' => $started->format('m/d/Y H:i'),
            'date_completed' => $finished->format('m/d/Y H:i'),
            'started_at' => $started,
            'finished_at' => $finished,
            'duration_minutes' => $diffInMinutes,
            'duration_display' => $this->formatTurnaroundDuration($diffInMinutes),
            'remarks' => $diffInMinutes > $threshold ? 'Not on-time' : 'On Time',
        ]);
    }

    // Standard users only see transactions that they themselves completed
    // (the last acknowledgement was made by their account).
    if (!$canViewAll) {
        $rows = $rows->filter(function ($row) use ($userId) {
            return (int) $row['actor_id'] === (int) $userId;
        })->values();
    }

    return $rows;
}

/**
 * Turnaround rows of the routing slips (routing_slip table).
 *
 * A routing slip is counted as completed when route_status = 3. The
 * turnaround time is measured from the date it was received up to its last
 * acknowledgement (or its last routing action when there is no acknowledgement).
 */
private function buildRoutingTurnaroundRows(Request $request, $startDate, $endDate)
{
    $user = auth()->user();
    $userId = $user->id;
    $userOffice = $this->normalizeOffice($user->department ?? '');

    // Only user id 56, records officers and administrators can see every
    // routing slip. Standard users only see their own completed slips.
    $canViewAll = $this->canViewAllTurnaround($user);

    $query = RoutingSlip::query()->where('route_status', 3);

    if ($startDate) {
        $query->whereDate('date_received', '>=', $startDate->toDateString());
    }

    if ($endDate) {
        $query->whereDate('date_received', '<=', $endDate->toDateString());
    }

    $slips = $query->get();
    $rslipIds = $slips->pluck('rslip_id')->filter()->unique()->values();

    $logsByRoute = Log::query()
        ->whereIn('route_id', $rslipIds)
        ->get(['route_id', 'new_destination', 'status_update', 'created_at'])
        ->groupBy(function ($log) {
            return (string) $log->route_id;
        });

    // Map "Firstname Lastname" => department so a destination can be shown
    // as an office/department.
    $destinationUsers = User::query()
        ->select('id', 'fname', 'lname', 'department', 'position')
        ->get()
        ->mapWithKeys(function ($destinationUser) {
            $key = mb_strtolower(trim($destinationUser->fname . ' ' . $destinationUser->lname));

            return [$key => [
                'id' => $destinationUser->id,
                'name' => trim($destinationUser->fname . ' ' . $destinationUser->lname),
                'department' => $destinationUser->department ?: ($destinationUser->position ?: 'N/A'),
            ]];
        });

    $threshold = 24 * 60;
    $rows = collect();

    foreach ($slips as $slip) {
        $slipLogs = $logsByRoute->get((string) $slip->rslip_id, collect());

        if ($slipLogs->isEmpty()) {
            continue;
        }

        $firstLog = $slipLogs->sortBy(function ($log) {
            return $log->created_at;
        })->first();

        // The last acknowledgement is the actual completion; when there is no
        // acknowledgement log, the last routing log is used instead.
        $ackLog = $slipLogs->where('status_update', 3)
            ->sortByDesc(function ($log) {
                return $log->created_at;
            })
            ->first();

        $completionLog = $ackLog ?: $slipLogs->sortByDesc(function ($log) {
            return $log->created_at;
        })->first();

        $started = $slip->created_at
            ? Carbon::parse($slip->created_at)
            : Carbon::parse($firstLog->created_at);

        $finished = Carbon::parse($completionLog->created_at);

        if ($finished->lt($started)) {
            $started = Carbon::parse($firstLog->created_at);
        }

        $destination = trim((string) ($completionLog->new_destination ?? ''));

        $destinationUser = $destination !== ''
            ? $destinationUsers->get(mb_strtolower(preg_replace('/\s+/', ' ', $destination)))
            : null;

        $personnel = $destinationUser['name'] ?? ($destination !== '' ? $destination : 'N/A');
        $office = $destinationUser['department'] ?? ($destination !== '' ? $destination : 'N/A');

        $diffInMinutes = (int) round($started->diffInMinutes($finished));

        $rows->push([
            'personnel' => $personnel,
            'office' => $office,
            'source' => $slip->source ?: 'N/A',
            'ctrl_no' => $slip->op_ctrl ?: $slip->rslip_id,
            'reference' => $slip->rslip_id,
            'actor_id' => $destinationUser['id'] ?? null,
            'date_received' => $started->format('m/d/Y H:i'),
            'date_completed' => $finished->format('m/d/Y H:i'),
            'started_at' => $started,
            'finished_at' => $finished,
            'duration_minutes' => $diffInMinutes,
            'duration_display' => $this->formatTurnaroundDuration($diffInMinutes),
            'remarks' => $diffInMinutes > $threshold ? 'Not on-time' : 'On Time',
        ]);
    }

    // Standard users only see slips whose final destination is their own
    // account. Generic office destinations (not tied to a user account) are
    // allowed when they match the user's own office.
    if (!$canViewAll) {
        $rows = $rows->filter(function ($row) use ($userId, $userOffice) {
            if ((int) $row['actor_id'] === (int) $userId) {
                return true;
            }

            return $row['actor_id'] === null
                && $userOffice !== ''
                && $this->normalizeOffice($row['office']) === $userOffice;
        })->values();
    }

    return $rows;
}

/**
 * Build the daily, weekly, monthly and per-office turnaround summaries.
 */
private function buildTurnaroundGroupReports($turnaroundData): array
{
    $dailyData = [];
    $weeklyData = [];
    $monthlyData = [];
    $officeData = [];

    foreach ($turnaroundData as $row) {
        $started = $row['started_at'];

        $dayKey = $started->format('Y-m-d');
        if (!isset($dailyData[$dayKey])) {
            $dailyData[$dayKey] = [
                'period' => $started->format('F j, Y'),
                'sort' => $dayKey,
                'total_minutes' => 0,
                'count' => 0,
            ];
        }
        $dailyData[$dayKey]['total_minutes'] += $row['duration_minutes'];
        $dailyData[$dayKey]['count']++;

        $weekKey = $started->format('o-\WW');
        if (!isset($weeklyData[$weekKey])) {
            $weeklyData[$weekKey] = [
                'period' => $started->copy()->startOfWeek()->format('M d') . ' - ' . $started->copy()->endOfWeek()->format('M d, Y'),
                'sort' => $weekKey,
                'total_minutes' => 0,
                'count' => 0,
            ];
        }
        $weeklyData[$weekKey]['total_minutes'] += $row['duration_minutes'];
        $weeklyData[$weekKey]['count']++;

        $monthKey = $started->format('Y-m');
        if (!isset($monthlyData[$monthKey])) {
            $monthlyData[$monthKey] = [
                'period' => $started->format('F Y'),
                'sort' => $monthKey,
                'total_minutes' => 0,
                'count' => 0,
            ];
        }
        $monthlyData[$monthKey]['total_minutes'] += $row['duration_minutes'];
        $monthlyData[$monthKey]['count']++;

        $officeName = $this->normalizeOffice($row['office'] ?: 'N/A');

        if (!isset($officeData[$officeName])) {
            $officeData[$officeName] = [
                'office' => $officeName,
                'total_minutes' => 0,
                'count' => 0,
            ];
        }
        $officeData[$officeName]['total_minutes'] += $row['duration_minutes'];
        $officeData[$officeName]['count']++;
    }

    $officeReport = collect($officeData)
        ->sortBy('office')
        ->map(function ($row) {
            $average = $row['count'] > 0 ? $row['total_minutes'] / $row['count'] : 0;

            return [
                'office' => $row['office'],
                'transactions' => $row['count'],
                'total_display' => $this->formatTurnaroundDuration($row['total_minutes']),
                'avg_display' => $this->formatTurnaroundDuration($average),
            ];
        })
        ->values();

    return [
        $this->buildTurnaroundSummary($dailyData),
        $this->buildTurnaroundSummary($weeklyData),
        $this->buildTurnaroundSummary($monthlyData),
        $officeReport,
    ];
}

/**
 * Build the overall turnaround statistics of a transaction type.
 */
private function buildTurnaroundStats($turnaroundData): array
{
    $totalTransactions = $turnaroundData->count();
    $totalMinutes = $turnaroundData->sum('duration_minutes');
    $overallAverageMinutes = $totalTransactions > 0 ? $totalMinutes / $totalTransactions : 0;

    $fastest = 'N/A';
    $slowest = 'N/A';

    if ($totalTransactions > 0) {
        $sorted = $turnaroundData->sortBy('duration_minutes')->values();
        $fastest = $sorted->first()['duration_display'];
        $slowest = $sorted->last()['duration_display'];
    }

    $onTimeCount = $turnaroundData->where('remarks', 'On Time')->count();
    $notOnTimeCount = $totalTransactions - $onTimeCount;

    return [
        'total_transactions' => $totalTransactions,
        'total_display' => $this->formatTurnaroundDuration($totalMinutes),
        'avg_display' => $this->formatTurnaroundDuration($overallAverageMinutes),
        'fastest' => $fastest,
        'slowest' => $slowest,
        'on_time' => $onTimeCount,
        'not_on_time' => $notOnTimeCount,
        'compliance_rate' => $totalTransactions > 0 ? round(($onTimeCount / $totalTransactions) * 100, 1) : 0,
    ];
}

/**
 * Turn the grouped daily/weekly/monthly rows into display-ready collections.
 */
private function buildTurnaroundSummary(array $data)
{
    return collect($data)
        ->sortByDesc('sort')
        ->map(function ($row) {
            $average = $row['count'] > 0 ? $row['total_minutes'] / $row['count'] : 0;

            return [
                'period' => $row['period'],
                'transactions' => $row['count'],
                'total_display' => $this->formatTurnaroundDuration($row['total_minutes']),
                'avg_display' => $this->formatTurnaroundDuration($average),
            ];
        })
        ->values();
}

/**
 * Format a number of minutes the same way the rest of the system does.
 */
private function formatTurnaroundDuration($totalMinutes): string
{
    $totalMinutes = max(0, (int) round($totalMinutes));

    $days = intdiv($totalMinutes, 1440);
    $hours = intdiv($totalMinutes % 1440, 60);
    $minutes = $totalMinutes % 60;

    $parts = [];

    if ($days > 0) {
        $parts[] = $days . ' ' . Str::plural('day', $days);
    }

    if ($hours > 0) {
        $parts[] = $hours . ' ' . Str::plural('hr', $hours);
    }

    $parts[] = $minutes . ' ' . Str::plural('min', $minutes);

    return implode(', ', $parts);
}








}
