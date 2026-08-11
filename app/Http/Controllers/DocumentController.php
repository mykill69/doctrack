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
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;

class DocumentController extends Controller
{
    // public function dashboard()
    // {
    //     $user = auth()->user();
    //     $userId = $user->id;
    //     $userFullName = $user->fname . ' ' . $user->lname;
    //     $userRole = $user->role;
    //     $isSuperUser = ($userRole === 'super_user');

    //     // Only load counts and metadata (not table data - that loads via AJAX)
        
    //     $routingSlipCount = RoutingSlip::where('route_status', 3)->count();

    //     // COUNTS
    //     if ($isSuperUser) {
    //         $superUserCount = RoutingSlip::where('route_status', 1)->count();
    //         $recordsOfficerCount = RoutingSlip::where('route_status', 2)->count();
    //     } else {
    //         $superUserCount = $userRole === 'super_user'
    //             ? RoutingSlip::where('route_status', 1)->count()
    //             : 0;

    //         $recordsOfficerCount = $userRole === 'records_officer'
    //             ? RoutingSlip::where('route_status', 2)->count()
    //             : 0;
    //     }

    //     $groups = User::select('id', 'fname', 'lname', 'department')
    //         ->orderBy('department')
    //         ->orderBy('lname')
    //         ->get()
    //         ->groupBy('department');

    //     $offices = Office::all();
    //     $dpa = $user->dpa;
    //     $users = User::all();

    //     // DOCUMENT TRACK
    //     $documentTrack = collect();
        
    //     if ($isSuperUser) {
    //         Doctrack::with(['createdBy', 'doctrackFile'])
    //             ->orderByDesc('created_at')
    //             ->chunk(200, function ($chunk) use (&$documentTrack) {
    //                 $documentTrack = $documentTrack->merge($chunk);
    //             });
    //     } else {
    //         Doctrack::with(['createdBy', 'doctrackFile'])
    //             ->where(function ($query) use ($userId, $userFullName) {
    //                 $query->where('user_id', $userId)
    //                       ->orWhere('update_by', $userId)
    //                       ->orWhere('user_name', $userFullName);
    //             })
    //             ->orderByDesc('created_at')
    //             ->chunk(200, function ($chunk) use (&$documentTrack) {
    //                 $documentTrack = $documentTrack->merge($chunk);
    //             });
    //     }

    //     $documentTrack->transform(function ($item) {
    //         $start = Carbon::parse($item->created_at);
    //         $end = Carbon::parse($item->updated_at ?? $item->created_at);
    //         $diffInMinutes = $end->diffInMinutes($start);

    //         $item->time_diff = [
    //             'days' => floor($diffInMinutes / 1440),
    //             'hours' => floor(($diffInMinutes % 1440) / 60),
    //             'minutes' => $diffInMinutes % 60,
    //         ];

    //         return $item;
    //     });

    //     // DOCTRACK COUNT
    //     if ($isSuperUser) {
    //         $doctrackCount = Doctrack::where('doctrack_stat', 2)->count();
    //     } else {
    //         $doctrackCount = Doctrack::where('doctrack_stat', 2)
    //             ->where(function ($query) use ($userId, $userFullName) {
    //                 $query->where('user_id', $userId)
    //                       ->orWhere('update_by', $userId)
    //                       ->orWhere(function ($q) use ($userFullName, $userId) {
    //                           $q->where('user_name', $userFullName)
    //                             ->where('user_id', $userId);
    //                       });
    //             })
    //             ->count();
    //     }

    //     return view('home.dashboard', compact(
    //         'offices',
    //         'routingSlipCount',
    //         'superUserCount',
    //         'recordsOfficerCount',
    //         'dpa',
    //         'users',
    //         'doctrackCount',
    //         'groups'
    //     ));
    // }

    public function dashboard()
{
    $user = auth()->user();
    $userId = $user->id;
    $userFullName = $user->fname . ' ' . $user->lname;
    $userRole = $user->role;
    $isSuperUser = ($userRole === 'super_user');

    // Only load counts and metadata (not table data - that loads via AJAX)
    
    $routingSlipCount = RoutingSlip::where('route_status', 3)->count();

    // COUNTS
    if ($isSuperUser) {
        $superUserCount = RoutingSlip::where('route_status', 1)->count();
        $recordsOfficerCount = RoutingSlip::where('route_status', 2)->count();
    } else {
        $superUserCount = $userRole === 'super_user'
            ? RoutingSlip::where('route_status', 1)->count()
            : 0;

        $recordsOfficerCount = $userRole === 'records_officer'
            ? RoutingSlip::where('route_status', 2)->count()
            : 0;
    }

    $groups = User::select('id', 'fname', 'lname', 'department')
        ->orderBy('department')
        ->orderBy('lname')
        ->get()
        ->groupBy('department');

    $offices = Office::all();
    $dpa = $user->dpa;
    $users = User::all();

    // DOCTRACK COUNT ONLY - No need to load all records
    if ($isSuperUser) {
        $doctrackCount = Doctrack::where('doctrack_stat', 2)->count();
    } else {
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
    }

    return view('home.dashboard', compact(
        'offices',
        'routingSlipCount',
        'superUserCount',
        'recordsOfficerCount',
        'dpa',
        'users',
        'doctrackCount',
        'groups'
    ));
}

    /**
     * Server-side data for DataTables AJAX
     */
    public function getDashboardData(Request $request)
{
    $user = auth()->user();
    $userId = $user->id;
    $userFullName = $user->fname . ' ' . $user->lname;
    $userRole = $user->role;
    $isSuperUser = ($userRole === 'super_user');
    $isAdminOrRecordsOfficer = in_array($userRole, ['records_officer', 'administrator']);

    $searchValue = $request->input('search.value');
    $start = $request->input('start', 0);
    $length = $request->input('length', 25);
    
    // Build base query
    $baseQuery = Log::with(['routingSlip', 'document']);
    
    if (!$isSuperUser) {
        $baseQuery->where(function ($q) use ($userId, $userFullName) {
            $q->where('new_user', $userId)
              ->orWhere('user_id', $userId)
              ->orWhereRaw('LOWER(new_destination) = ?', [strtolower($userFullName)])
              ->orWhereHas('routingSlip', function ($sq) use ($userFullName) {
                  $sq->where('pres_dept', $userFullName);
              });
        });
    }
    
    // Apply search at database level
    if ($searchValue) {
        $baseQuery->where(function ($q) use ($searchValue) {
            $q->where('route_id', 'LIKE', "%{$searchValue}%")
              ->orWhere('new_destination', 'LIKE', "%{$searchValue}%")
              ->orWhereHas('routingSlip', function ($sq) use ($searchValue) {
                  $sq->where('source', 'LIKE', "%{$searchValue}%")
                    ->orWhere('subject', 'LIKE', "%{$searchValue}%");
              });
        });
    }
    
    // Get total count
    $totalRecords = $baseQuery->count();
    
    // Get paginated results directly from database
    $paginatedLogs = $baseQuery->orderBy('route_id', 'desc')
        ->skip($start)
        ->take($length)
        ->get();
    
    // Preload routing slips and documents only for current page
    $routeIds = $paginatedLogs->pluck('route_id')->unique();
    $newFiles = $paginatedLogs->pluck('new_file')->unique();
    
    $allRoutingSlips = RoutingSlip::whereIn('rslip_id', $routeIds)
        ->whereIn('document', $newFiles)
        ->get();
    
    $allDocuments = Document::whereIn('route_id', $routeIds)
        ->whereIn('file_name', $newFiles)
        ->get();

    // Build response data
    $data = [];
    foreach ($paginatedLogs as $log) {
        $exactSlip = $allRoutingSlips
            ->where('rslip_id', $log->route_id)
            ->where('document', $log->new_file)
            ->first();
        
        if (!$exactSlip) {
            $exactSlip = $log->routingSlip;
        }
        
        $exactDoc = $allDocuments
            ->where('route_id', $log->route_id)
            ->where('file_name', $log->new_file)
            ->first();
        
        if (!$exactDoc) {
            $exactDoc = $log->document;
        }
        
        $routingSlipId = $exactSlip ? $exactSlip->id : RoutingSlip::where('rslip_id', $log->route_id)->orderBy('id', 'desc')->value('id');
        
        $row = [
            'route_id' => $log->route_id,
            'route_display' => $this->formatRouteLink($log, $routingSlipId),
            'date_received' => $exactSlip ? Carbon::parse($exactSlip->date_received)->format('F d, Y') : 'N/A',
            'source' => $exactSlip->source ?? 'N/A',
            'subject' => $exactSlip->subject ?? ($exactDoc->subject ?? 'N/A'),
            'action_unit' => $exactSlip->pres_dept ?? 'N/A',
            'received_by_date' => ($exactSlip && $exactSlip->updated_at) ? $exactSlip->updated_at->format('F j, Y') : 'N/A',
            'action_taken' => $this->formatActionTaken($log, $exactSlip),
            'date_released' => optional($exactDoc)->created_at ? $exactDoc->created_at->format('m-d-Y h:i:s A') : 'N/A',
            'remarks' => $this->formatRemarks($exactSlip, $log),
            'file_name' => $this->formatFileName($exactDoc, $log),
            'updated_by' => '<span class="badge badge-secondary">' . ($log->new_destination ?? 'N/A') . '</span><br>' . $log->updated_at->format('m-d-Y h:i:s A'),
            'duration' => $this->formatDuration($exactDoc, $log),
        ];
        
        if ($isAdminOrRecordsOfficer) {
            $row['action'] = $this->formatRecallAction($log);
        }
        
        $data[] = $row;
    }

    return response()->json([
        'draw' => intval($request->input('draw')),
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $totalRecords,
        'data' => $data
    ]);
}
    // ============================================
    // Helper Methods
    // ============================================

    private function formatRouteLink($log, $routingSlipId)
    {
        if ($log->route_id == 0) {
            return 'N/A';
        }
        
        return '<a href="' . route('slipForm', ['id' => $log->route_id]) . '?routing_slip_id=' . $routingSlipId . '" target="_blank" style="color: #007bff;">' . $log->route_id . '</a>';
    }

    private function formatDateReceived($document)
    {
        if (optional($document->routingSlip)->date_received) {
            return Carbon::parse($document->routingSlip->date_received)->format('F d, Y');
        }
        
        if ($document && $document->created_at) {
            return Carbon::parse($document->created_at)->format('F d, Y');
        }
        
        return 'N/A';
    }

    private function formatActionTaken($log, $exactSlip = null)
{
    $html = '';
    $slip = $exactSlip ?? $log->routingSlip;
    
    if ($slip && $slip->r_destination) {
        $html .= '<strong class="text-danger">' . ucwords(strtolower($slip->r_destination)) . '</strong>';
    }
    
    if ($log->assigned_to != null) {
        $html .= ', was re-assigned to <strong class="text-danger">' . ucwords(strtolower($log->assigned_to)) . '</strong>';
    }
    
    return $html ?: 'N/A';
}

    private function formatRemarks($exactSlip, $log)
{
    $html = '';
    $slip = $exactSlip ?? $log->routingSlip;
    
    if ($slip && !empty($slip->trans_remarks)) {
        $html .= '<span class="badge badge-success" style="font-size:10px; display: block;">' . $slip->trans_remarks . '</span>';
    }
    
    if ($slip && !empty($slip->other_remarks)) {
        $html .= '<span class="badge badge-danger" style="font-size:10px; display: block;">' . $slip->other_remarks . '</span>';
    }
    
    if (!empty($log->comments)) {
        $wrappedComment = preg_replace('/((?:\S+\s+){4})/', '$1<br>', $log->comments);
        $html .= '<span class="badge badge-warning" style="margin-top: 2px; font-size:10px; max-width: 150px; display: inline-block; word-wrap: break-word; white-space: normal;">' . $wrappedComment . '</span>';
    }
    
    return $html ?: '';
}

    // private function formatFileName($document, $log)
    // {
    //     if (!$document) {
    //         return 'N/A';
    //     }
        
    //     $html = '<a href="' . route('documents.viewPdf', $document->id) . '" target="_blank" style="color: #007bff;">';
    //     $html .= '<i class="fas fa-file-pdf text-danger"></i> ';
    //     $html .= Str::limit($document->file_name, 22) . '</a>';
        
    //     if ($log->viewed_status) {
    //         $html .= '<p><small class="text-muted">Viewed on <br>' . Carbon::parse($log->viewed_at)->format('M j, Y h:i A') . '</small></p>';
    //     }
        
    //     return $html;
    // }

    private function formatFileName($document, $log)
{
    if (!$document) {
        return 'N/A';
    }
    
    $html = '<a href="' . route('documents.viewPdf', $document->id) . '" target="_blank" style="color: #007bff;">';
    $html .= '<i class="fas fa-file-pdf text-danger"></i> ';
    $html .= Str::limit($document->file_name, 22) . '</a>';
    
    if ($log->viewed_status) {
        $html .= '<p><small class="text-muted">Viewed on <br>' . Carbon::parse($log->viewed_at)->format('M j, Y h:i A') . '</small></p>';
    }
    
    return $html;
}

    private function formatDuration($document, $log)
    {
        if (!$log->updated_at || !$document || !$document->created_at) {
            return 'N/A';
        }
        
        $totalMinutes = Carbon::parse($document->created_at)->diffInMinutes($log->updated_at);
        $days = floor($totalMinutes / 1440);
        $hours = floor(($totalMinutes % 1440) / 60);
        $minutes = $totalMinutes % 60;
        
        $diff = '';
        if ($days > 0) {
            $diff .= $days . ' ' . Str::plural('day', $days) . ', ';
        }
        if ($hours > 0) {
            $diff .= $hours . ' ' . Str::plural('hr', $hours) . ', ';
        }
        $diff .= $minutes . ' ' . Str::plural('min', $minutes);
        
        return $diff;
    }

    private function formatRecallAction($log)
{
    $user = auth()->user();
    
    if (!in_array($user->role, ['records_officer', 'administrator'])) {
        return '';
    }
    
    // Get the routing_slip ID (primary key) from the log's route_id (rslip_id)
    $routingSlipId = RoutingSlip::where('rslip_id', $log->route_id)
        ->orderBy('id', 'desc')
        ->value('id');
    
    if (!$routingSlipId) {
        return '';
    }
    
    return '<div class="buttons">
        <a href="' . route('recallSlip', ['id' => $routingSlipId]) . '" class="btn btn-icon btn-info edit-slip-btn" target="_blank">
            <span>Recall</span> <i class="fas fa-undo-alt"></i>
        </a>
    </div>';
}

    // ============================================
    // Your existing methods below (keep all of them)
    // ============================================

 public function tracking(Request $request)
{
    $user = auth()->user();
    $userId = $user->id;
    $fullName = trim($user->fname . ' ' . $user->lname);

    $routeId = $request->query('route_id');
    $routingSlipId = $request->query('routing_slip_id');

    if ($routeId && !$routingSlipId) {
        $routingSlipId = RoutingSlip::where('rslip_id', $routeId)
            ->orderBy('id', 'desc')
            ->value('id');

        if ($routingSlipId) {
            return redirect()->route('documents.tracking', [
                'route_id' => $routeId,
                'routing_slip_id' => $routingSlipId,
            ]);
        }
    }

    // Get ALL routing slips for this route_id
    $allRoutingSlips = RoutingSlip::where('rslip_id', $routeId)->get();

    // Get documents (without joining to avoid filtering)
    $documents = Document::where('route_id', $routeId)->get();

    // Attach matching routing slip to each document by file_name = document
    foreach ($documents as $doc) {
        $doc->matched_slip = $allRoutingSlips->where('document', $doc->file_name)->first();
        $doc->routing_slip_user_id = $doc->matched_slip->user_id ?? null;
        $doc->routed_users = $doc->matched_slip->routed_users ?? null;
        $doc->r_destination = $doc->matched_slip->r_destination ?? null;
        $doc->trans_remarks = $doc->matched_slip->trans_remarks ?? null;
        $doc->source = $doc->matched_slip->source ?? null;
    }

    $filteredDocuments = $documents->filter(function ($document) use ($fullName, $userId) {
        $routedUsers = array_map('trim', explode(',', $document->routed_users ?? ''));

        return in_array($fullName, $routedUsers)
            || Log::where('doc_id', $document->id)
                ->whereRaw('LOWER(TRIM(new_destination)) = ?', [strtolower($fullName)])
                ->exists()
            || $document->user_id == $userId
            || $document->routing_slip_user_id == $userId;
    });

    $logs = Log::where('user_id', $userId)->get();
    $users = User::all();

    $routingSlipCount = $logs->every(fn ($log) => $log->status_update != 3)
        ? RoutingSlip::where('route_status', 3)->count()
        : 0;

    $superUserCount = $user->role === 'super_user'
        ? RoutingSlip::where('route_status', 1)->count()
        : 0;

    $recordsOfficerCount = $user->role === 'records_officer'
        ? RoutingSlip::where('route_status', 2)->count()
        : 0;

    return view('track.tracktemp', [
        'documents' => $filteredDocuments,
        'users' => $users,
        'offices' => Office::all(),
        'docNumber' => $routeId,
        'routingSlipCount' => $routingSlipCount,
        'superUserCount' => $superUserCount,
        'recordsOfficerCount' => $recordsOfficerCount,
    ]);
}
    public function storeDoc(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer', 
            'full_name' => 'required|string|max:255',
            'route_id' => 'required|integer',
            'subject' => 'required|string',
            'doc_type' => 'required|string',
            'document' => 'required|file|mimes:pdf,doc,docx,xls,xlsx,jpg,png,jpeg', 
            'purpose' => 'required|string',
            'department' => 'required|string',
            'for_to' => 'required|array',
            'for_to.*' => 'string',
            'doc_stat' => 'required|string',
        ]);

        if ($request->hasFile('document')) {
            $file = $request->file('document');
            $originalFileName = str_replace(' ', '_', $file->getClientOriginalName());
            
            do {
                $randomNumber = mt_rand(10000000, 99999999);
                $fileName = $randomNumber . '_' . $originalFileName;
            } while (Document::where('file_name', $fileName)->exists());
            
            $documentPath = $file->storeAs('documents', $fileName, 'public');
        } else {
            return redirect()->back()->withErrors(['document' => 'No document file provided.']);
        }

        $document = Document::create([
            'user_id' => $request->user_id, 
            'full_name' => $request->full_name,
            'route_id' =>  $request->route_id,
            'file_name' => $fileName,
            'doc_type' => $request->doc_type,
            'subject' => $request->subject,
            'purpose' => $request->purpose,
            'department' => $request->department,
            'doc_stat' => $request->doc_stat,
        ]);

        $routeDocument = new RouteDocument();
        $routeDocument->route_id = $document->route_id;

        $destinationFields = ['destination_1', 'destination_2', 'destination_3', 'destination_4', 'destination_5', 'destination_6', 'destination_7', 'destination_8', 'destination_9', 'destination_10'];
        
        foreach ($request->for_to as $index => $destination) {
            if (isset($destinationFields[$index])) {
                $routeDocument->{$destinationFields[$index]} = $destination;

                Log::create([
                    'user_id' => auth()->user()->id,
                    'doc_id' => $document->id,
                    'route_id' => $document->route_id,
                    'action' => 'Added new destination',
                    'status_update' => $document->doc_stat,
                    'prev_file' => null,
                    'new_file' => $document->file_name,
                    'new_destination' => $destination,
                    'created_at' => now(),
                ]);
            }
        }
        $routeDocument->save();

        return redirect()->back()->with('success', 'Document submitted successfully!');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'user_id' => 'required|integer',
            'new_user' => 'required|integer',
            'status_update' => 'required|in:2,3'
        ]);

        $document = Document::find($id);
        if (!$document) {
            return redirect()->back()->with('error', 'Document not found.');
        }

        $routeId = $document->route_id;
        $currentUser = Auth::user();
        $fullName = $currentUser->fname . ' ' . $currentUser->lname;

        if ($request->status_update == 3) {
            $logToUpdate = Log::where('route_id', $routeId)
                ->where('new_destination', 'LIKE', "%$fullName%")
                ->latest('id')
                ->first();

            if (!$logToUpdate) {
                return redirect()->back()->with('error', 'No matching log entry found for acknowledgment.');
            }

            $logToUpdate->user_id = $request->input('user_id');
            $logToUpdate->new_user = $request->input('new_user');
            $logToUpdate->action = 'Acknowledged';
            $logToUpdate->status_update = 3;
            $logToUpdate->prev_file = $logToUpdate->new_file;
            $logToUpdate->comments = $request->input('comments', null);
            $logToUpdate->updated_at = now();
            $logToUpdate->save();

            LogsHistory::create([
                'doc_id' => $logToUpdate->doc_id,
                'action' => $logToUpdate->action,
                'status_update' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return redirect($request->input('redirectUrl'))
                ->with('success', 'The document was acknowledged successfully.');
        }

        if ($request->status_update == 2 && auth()->id() == 56) {
            $logToUpdate = Log::find($request->input('log_id'));

            if (!$logToUpdate) {
                return redirect()->back()->with('error', 'No matching log entry found for re-open.');
            }

            $logToUpdate->status_update = 2;
            $logToUpdate->action = 'Re-opened';
            $logToUpdate->updated_at = now();
            $logToUpdate->save();

            return redirect($request->input('redirectUrl'))
                ->with('success', 'The document was re-opened successfully.');
        }

        return redirect()->back()->with('error', 'Invalid operation.');
    }

    public function download($id)
    {
        $document = Document::findOrFail($id);
        $filePath = storage_path('app/public/documents/' . $document->file_name);

        if (file_exists($filePath)) {
            return response()->download($filePath);
        } else {
            return redirect()->back()->with('error', 'File not found.');
        }
    }

//     public function viewPdf($id)
// {
//     $document = Document::findOrFail($id);

//     $log = Log::where('doc_id', $id)
//         ->where('new_destination', auth()->user()->fname . ' ' . auth()->user()->lname)
//         ->latest()
//         ->first();

//     if ($log && !$log->viewed_status) {
//         $log->timestamps = false;
//         $log->update([
//             'viewed_status' => 1,
//             'viewed_at' => now(),
//         ]);
//     }

//     $filePath = storage_path('app/documents/' . $document->file_name);

    

//     if (file_exists($filePath)) {
//         return response()->file($filePath, [
//             'Content-Disposition' => 'inline; filename="' . $document->file_name . '"',
//             'Content-Type' => 'application/pdf',
//         ]);
//     } else {
//         return redirect()->back()->with('error', 'File not found.');
//     }
// }

//08/11/2026 updated the view of pdf

// public function viewPdf($id)
// {
//     $document = Document::findOrFail($id);

//     $log = Log::where('doc_id', $id)
//         ->where('new_destination', auth()->user()->fname . ' ' . auth()->user()->lname)
//         ->latest()
//         ->first();

//     if ($log && !$log->viewed_status) {
//         $log->timestamps = false;
//         $log->update([
//             'viewed_status' => 1,
//             'viewed_at' => now(),
//         ]);
//     }

//     // ✅ FIXED: Use public disk path
//     $filePath = storage_path('app/public/documents/' . $document->file_name);

//     if (file_exists($filePath)) {
//         // Determine MIME type dynamically instead of hardcoding PDF
//         $mimeType = mime_content_type($filePath);
        
//         return response()->file($filePath, [
//             'Content-Disposition' => 'inline; filename="' . $document->file_name . '"',
//             'Content-Type' => $mimeType,
//         ]);
//     } else {
//         return redirect()->back()->with('error', 'File not found.');
//     }
// }

public function viewPdf($id)
{
    $document = Document::findOrFail($id);

    $log = Log::where('doc_id', $id)
        ->where('new_destination', auth()->user()->fname . ' ' . auth()->user()->lname)
        ->latest()
        ->first();

    if ($log && !$log->viewed_status) {
        $log->timestamps = false;
        $log->update([
            'viewed_status' => 1,
            'viewed_at' => now(),
        ]);
    }

    // Check multiple locations for the file
    $possiblePaths = [
        storage_path('app/documents/' . $document->file_name),
        storage_path('app/public/documents/' . $document->file_name),
        storage_path('app/doc_track/' . $document->file_name),
    ];
    
    $filePath = null;
    
    foreach ($possiblePaths as $path) {
        if (file_exists($path)) {
            $filePath = $path;
            break;
        }
    }

    if ($filePath) {
        // Determine MIME type dynamically instead of hardcoding PDF
        $mimeType = mime_content_type($filePath);
        
        return response()->file($filePath, [
            'Content-Disposition' => 'inline; filename="' . $document->file_name . '"',
            'Content-Type' => $mimeType,
        ]);
    } else {
        return redirect()->back()->with('error', 'File not found.');
    }
}

    // public function viewPdf($id)
    // {
    //     $document = Document::findOrFail($id);

    //     $log = Log::where('doc_id', $id)
    //         ->where('new_destination', auth()->user()->fname . ' ' . auth()->user()->lname)
    //         ->latest()
    //         ->first();

    //     if ($log && !$log->viewed_status) {
    //         $log->timestamps = false;
    //         $log->update([
    //             'viewed_status' => 1,
    //             'viewed_at' => now(),
    //         ]);
    //     }

    //     $filePath = storage_path('app/documents/' . $document->file_name);

    //     if (file_exists($filePath)) {
    //         return response()->file($filePath);
    //     } else {
    //         return redirect()->back()->with('error', 'File not found.');
    //     }
    // }
    
   
}