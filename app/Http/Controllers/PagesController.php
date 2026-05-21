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
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;



class PagesController extends Controller
{
   
    public function outgoingDocs()
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
    // $documentTrack = Doctrack::with(['createdBy', 'doctrackFile'])
    //     ->where(function ($query) use ($userId, $userFullName) {
    //         $query->where('user_id', $userId)
    //               ->orWhere('update_by', $userId)
    //               ->orWhere('user_name', $userFullName);
    //     })
    //     ->orderByDesc('created_at')
    //     ->get();

    $documentTrack = Doctrack::with(['createdBy', 'doctrackFile'])
    ->where('user_id', 56) // Only for this creator
    ->whereNotNull('update_by') // Exclude creator rows (no updates)
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


$documentTrack->transform(function ($item) {
    $query = LogsTracking::where('docslip_id', $item->docslip_id)
        ->whereNotNull('viewed_status')
        ->whereNotNull('viewed_at');

    // Match this specific row's person
    if (!is_null($item->update_by)) {
        // If updated_by exists → show logs for that updater
        $query->where('update_by', $item->update_by);
    } else {
        // If no updater → show logs for the original owner
        $query->where('user_id', $item->user_id);
    }

   $item->views = $query->orderBy('viewed_at', 'asc')->limit(1)->get();


    // Duration calculation
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


$documentTrack->transform(function ($item) {
    $query = LogsTracking::where('docslip_id', $item->docslip_id)
        ->whereNotNull('comments');

    if (!is_null($item->update_by)) {
        $query->where('update_by', $item->update_by);
    } else {
        $query->where('user_id', $item->user_id);
    }

    // Get both comment text and created_at
    $item->all_comments = $query->get(['comments', 'created_at']);

    return $item;
});



    // Count only records with doctrack_stat == 2
    $doctrackCount = $documentTrack->where('doctrack_stat', 2)->count();

    return view('home.outgoingDocs', compact(
        'documentTrack', 'offices',
        'logs', 'routingSlipCount', 'superUserCount', 
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

public function doctrackSlip()
{
    $user = auth()->user();
    $userId = $user->id;
    $userFullName = $user->fname . ' ' . $user->lname;
    $userRole = $user->role;

    // ------------------------------
    // Logs for routing slip counts
    // ------------------------------
    $logs = collect();
    Log::where(function ($query) use ($userId) {
            $query->where('new_user', $userId)
                  ->orWhere('user_id', $userId);
        })
        ->orderBy('created_at', 'desc')
        ->chunk(50, function ($batch) use (&$logs) {
            $logs = $logs->merge($batch);
        });

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

    // ------------------------------
    // Load Doctrack records in batches of 50
    // ------------------------------
$documentTrack = Doctrack::with(['createdBy', 'doctrackFile'])
    ->where(function ($query) use ($userId, $userFullName) {
        $query->where('user_id', $userId)
              ->orWhere('update_by', $userId)
              ->orWhere(function ($q) use ($userFullName, $userId) {
                  $q->where('user_name', $userFullName)
                    ->where('user_id', $userId);
              });
    })
    ->orderByDesc('created_at')
    ->get();  // <-- get ALL data

    // ------------------------------
    // Preload views and comments
    // ------------------------------
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

    // ------------------------------
    // Count only doctrack_stat = 2
    // ------------------------------
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
        'documentTrack', 'offices', 'logs',
        'routingSlipCount', 'superUserCount', 
        'recordsOfficerCount', 'doctrackCount'
    ));
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
                 ->on('logs.user_id', '=', 'routing_slip.user_id'); // ensure correct user
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
        ->orderByDesc('logs.created_at')
        ->get();

    // Step 2: Remove duplicate doc_id
    // $logs = $logs->unique('doc_id')->values();
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

    return view('home.pending', compact(
        'logs', 
        'offices', 
        'recordsOfficerCount', 
        'superUserCount', 
        'doctrackCount',
        'documentTrack'
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
    // Load logs in chunks
    // -----------------------------
    $logs = collect();
    Log::with(['user:id,fname,lname','newUser:id,fname,lname','document','routingSlip'])
        ->whereNotNull('new_user')
        ->where('status_update', 3) // Added this to match sidebar
        ->when($userRole === 'records_officer', function ($query) use ($userId) {
            // Records officer sees served docs they created
            $query->whereHas('routingSlip', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            });
        }, function ($query) use ($userId, $userFullName) {
            // Others: filter by fullname or user_id (no department to match sidebar)
            $query->where(function ($q) use ($userId, $userFullName) {
                $q->where('new_user', $userId)
                  ->orWhere('user_id', $userId)
                  ->orWhere('new_destination', $userFullName);
            });
        })
        ->orderByDesc('created_at')
        ->chunk(100, function ($batch) use (&$logs) {
            $logs = $logs->merge($batch);
        });

    // Keep only the first log for each unique route_id (CTRL #)
    $logs = $logs->unique('route_id')->values();

    // -----------------------------
    // Doctrack records
    // -----------------------------
    $documentTrack = Doctrack::with(['createdBy:id,fname,lname','doctrackFile'])
        ->where(function ($query) use ($userId, $userFullName) {
            $query->where('user_id', $userId)
                  ->orWhere('update_by', $userId)
                  ->orWhere('user_name', $userFullName);
        })
        ->orderByDesc('created_at')
        ->get();

    // Preload views and comments
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
    // Counts - Match sidebar query exactly
    // -----------------------------
    $servedQuery = Log::leftJoin('routing_slip', 'logs.route_id', '=', 'routing_slip.rslip_id')
        ->where('logs.status_update', 3)
        ->whereNotNull('logs.new_user');

    if ($userRole === 'records_officer') {
        $servedQuery->where('routing_slip.user_id', $userId);
    } else {
        $servedQuery->where(function ($q) use ($userId, $userFullName) {
            $q->where('logs.new_user', $userId)
              ->orWhere('logs.user_id', $userId)
              ->orWhere('logs.new_destination', $userFullName);
        });
    }

    $servedCount = $servedQuery->distinct('logs.route_id')->count();

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
        'logs','offices','recordsOfficerCount','superUserCount',
        'doctrackCount','users','documentTrack','servedCount'
    ));
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
    ->leftJoin('routing_slip', 'routing_slip.id', '=', 'logs_history.doc_id') // ✅ Added
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
        'new_users.department as new_user_department',

        'routing_slip.rslip_id' // ✅ Add this to retrieve the slip ID
    )
    ->where(function ($query) use ($userId, $userFullName) {
        $query->where('logs.user_id', $userId)
              ->orWhere('logs.new_user', $userId)
              ->orWhere('logs.new_destination', $userFullName);
    })
    ->orWhereNull('logs.id')
    ->distinct()
    ->orderBy('logs_history.created_at', 'desc')
    ->get();


    $routingSlipCount = RoutingSlip::where('route_status', 3)->count();
    $superUserCount = $userRole === 'super_user' ? RoutingSlip::where('route_status', 1)->count() : 0;
    $recordsOfficerCount = $userRole === 'records_officer' ? RoutingSlip::where('route_status', 2)->count() : 0;

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

    return view('home.viewLogs', compact(
        'logsAll', 'userId', 'userFullName',
        'routingSlipCount', 'superUserCount', 'recordsOfficerCount','doctrackCount'
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
        'esig_file' => 'nullable|file|mimes:jpeg,png,jpg,pdf',
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
        $destinations = \App\Models\Log::where('route_id', $item->rslip_id)
            ->whereNotNull('new_destination')
            ->whereRaw("TRIM(new_destination) != ''")
            ->distinct()
            ->pluck('new_destination');

        // attach destinations for display
        $item->destinations = $destinations;

        return $destinations->count() >= 4;
    });



    $offices = Office::all();

    // ✅ Add doctrackCount similar to viewSlip
    $userFullName = $user->fname . ' ' . $user->lname;

    $documentTrack = Doctrack::where(function ($query) use ($user, $userFullName) {
            $query->where('user_id', $user->id)
                  ->orWhere('update_by', $user->id)
                  ->orWhere('user_name', $userFullName);
        })->get();

    // Count only records with doctrack_stat == 2
    $doctrackCount = $documentTrack->where('doctrack_stat', 2)->count();

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

    $logs = DB::table('logs')
        ->leftJoin('users', 'logs.new_user', '=', 'users.id')
        ->where('logs.route_id', $id)
        ->select('logs.*', 'users.department as user_department')
        ->orderBy('logs.created_at', 'asc')
        ->get()
        ->unique('new_destination') // <-- Removes duplicates
        ->values(); // reindex collection

    // Add e-signature if status_update == 3
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

    // Get the doc_title from the first matching record
    $docTitle = Doctrack::where('docslip_id', $id)->value('doc_title');

    // Eager-load updatedBy and exclude rows where update_by is NULL
$logs = Doctrack::with(['updatedBy', 'receivedBy' => function ($query) {
        $query->select('id', 'fname', 'lname', 'department');
    }])
    ->where('docslip_id', $id)
    ->whereNotNull('update_by')
    ->orderBy('created_at', 'desc')
    ->get()
    ->map(function ($log) {
        // department from user referenced by update_by
        $log->user_department = $log->updatedBy->department ?? null;

        // full name of the update_by user
        $log->update_by_name = $log->updatedBy
            ? trim($log->updatedBy->fname . ' ' . $log->updatedBy->lname)
            : null;

        // full name from new_destination (OFFICE OF CUSTODIAN)
        $log->received_by_name = $log->receivedBy
            ? trim($log->receivedBy->fname . ' ' . $log->receivedBy->lname)
            : null;

        // e-signature when doctrack_stat is 3 or 5
        if (in_array($log->doctrack_stat, [3, 5])) {
            $esig = \App\Models\Esig::where('user_id', $log->update_by)->first();
            $log->esig_file = $esig ? $esig->esig_file : null;
        } else {
            $log->esig_file = null;
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








}
