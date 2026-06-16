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

class DocumentController extends Controller
{
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

        // DOCUMENT TRACK
        $documentTrack = collect();
        
        if ($isSuperUser) {
            Doctrack::with(['createdBy', 'doctrackFile'])
                ->orderByDesc('created_at')
                ->chunk(200, function ($chunk) use (&$documentTrack) {
                    $documentTrack = $documentTrack->merge($chunk);
                });
        } else {
            Doctrack::with(['createdBy', 'doctrackFile'])
                ->where(function ($query) use ($userId, $userFullName) {
                    $query->where('user_id', $userId)
                          ->orWhere('update_by', $userId)
                          ->orWhere('user_name', $userFullName);
                })
                ->orderByDesc('created_at')
                ->chunk(200, function ($chunk) use (&$documentTrack) {
                    $documentTrack = $documentTrack->merge($chunk);
                });
        }

        $documentTrack->transform(function ($item) {
            $start = Carbon::parse($item->created_at);
            $end = Carbon::parse($item->updated_at ?? $item->created_at);
            $diffInMinutes = $end->diffInMinutes($start);

            $item->time_diff = [
                'days' => floor($diffInMinutes / 1440),
                'hours' => floor(($diffInMinutes % 1440) / 60),
                'minutes' => $diffInMinutes % 60,
            ];

            return $item;
        });

        // DOCTRACK COUNT
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

        // Cache key for this user's data
        $cacheKey = 'dashboard_data_' . $userId . '_' . ($isSuperUser ? 'super' : 'normal');
        
        // Get all logs from cache or database
        $allLogs = Cache::remember($cacheKey, 60, function () use ($isSuperUser, $userId, $userFullName) {
            $query = Log::with(['routingSlip', 'document']);
            
            if (!$isSuperUser) {
                $query->where(function ($q) use ($userId) {
                    $q->where('new_user', $userId)
                      ->orWhere('user_id', $userId);
                })
                ->orWhereHas('routingSlip', function ($q) use ($userFullName) {
                    $q->where('pres_dept', $userFullName);
                });
            }

            return $query->orderBy('route_id', 'desc')->get();
        });

        // Process logs to remove duplicates
        $processedLogs = [];
        $logsToShow = collect();
        $currentUserDepartment = $user->department;
        
        foreach ($allLogs as $log) {
            $uniqueIdentifier = $log->route_id . '-' . $log->doc_id . '-' . $log->new_destination;
            
            if (isset($processedLogs[$uniqueIdentifier]) && $processedLogs[$uniqueIdentifier]['hasNewUser']) {
                continue;
            }
            
            if (!is_null($log->new_user)) {
                $processedLogs[$uniqueIdentifier] = ['hasNewUser' => true];
                $logsToShow->push($log);
            } else {
                if (!isset($processedLogs[$uniqueIdentifier])) {
                    $processedLogs[$uniqueIdentifier] = ['hasNewUser' => false];
                    $logsToShow->push($log);
                }
            }
            
            if ($currentUserDepartment === $log->new_destination) {
                if (!$logsToShow->contains('id', $log->id)) {
                    $logsToShow->push($log);
                }
            }
        }

        $totalRecords = $logsToShow->count();
        
        // Apply search if provided
        $searchValue = $request->input('search.value');
        if ($searchValue) {
            $logsToShow = $logsToShow->filter(function ($log) use ($searchValue) {
                $document = $log->document;
                return stripos($log->route_id, $searchValue) !== false ||
                       stripos($document->routingSlip->source ?? '', $searchValue) !== false ||
                       stripos($document->routingSlip->subject ?? '', $searchValue) !== false ||
                       stripos($log->new_destination ?? '', $searchValue) !== false;
            })->values();
            $totalRecords = $logsToShow->count();
        }
        
        // Apply pagination
        $start = $request->input('start', 0);
        $length = $request->input('length', 25);
        
        $paginatedLogs = $logsToShow->slice($start, $length)->values();

        // Build response data
        $data = [];
        foreach ($paginatedLogs as $log) {
            $document = $log->document;
            $routingSlipId = RoutingSlip::where('rslip_id', $log->route_id)
                ->orderBy('id', 'desc')
                ->value('id');
            
            $data[] = [
                'route_id' => $log->route_id,
                'route_display' => $this->formatRouteLink($log, $routingSlipId),
                'date_received' => $this->formatDateReceived($document),
                'source' => optional($document->routingSlip)->source ?? ($document->department ?? 'N/A'),
                'subject' => optional($document->routingSlip)->subject ?? ($document->subject ?? 'N/A'),
                'action_unit' => optional($document->routingSlip)->pres_dept ?? 'N/A',
                'received_by_date' => optional($document->routingSlip)->updated_at ? $document->routingSlip->updated_at->format('F j, Y') : 'N/A',
                'action_taken' => $this->formatActionTaken($log),
                'date_released' => optional($document)->created_at ? $document->created_at->format('m-d-Y h:i:s A') : 'N/A',
                'remarks' => $this->formatRemarks($document, $log),
                'file_name' => $this->formatFileName($document, $log),
                'updated_by' => '<span class="badge badge-secondary">' . ($log->new_destination ?? 'N/A') . '</span><br>' . $log->updated_at->format('m-d-Y h:i:s A'),
                'duration' => $this->formatDuration($document, $log),
            ];
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

    private function formatActionTaken($log)
    {
        $html = '';
        
        if ($log->routingSlip && $log->routingSlip->r_destination) {
            $html .= '<strong class="text-danger">' . ucwords(strtolower($log->routingSlip->r_destination)) . '</strong>';
        }
        
        if ($log->assigned_to != null) {
            $html .= ', was re-assigned to <strong class="text-danger">' . ucwords(strtolower($log->assigned_to)) . '</strong>';
        }
        
        return $html ?: 'N/A';
    }

    private function formatRemarks($document, $log)
    {
        $html = '';
        
        if (!empty($document->routingSlip->trans_remarks)) {
            $html .= '<span class="badge badge-success" style="font-size:10px; display: block;">' . $document->routingSlip->trans_remarks . '</span>';
        }
        
        if (!empty($document->routingSlip->other_remarks)) {
            $html .= '<span class="badge badge-danger" style="font-size:10px; display: block;">' . $document->routingSlip->other_remarks . '</span>';
        }
        
        if (!empty($log->comments)) {
            $wrappedComment = preg_replace('/((?:\S+\s+){4})/', '$1<br>', $log->comments);
            $html .= '<span class="badge badge-warning" style="margin-top: 2px; font-size:10px; max-width: 150px; display: inline-block; word-wrap: break-word; white-space: normal;">' . $wrappedComment . '</span>';
        }
        
        return $html ?: '';
    }

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

        $documents = Document::query()
            ->leftJoin('routing_slip', 'documents.route_id', '=', 'routing_slip.rslip_id')
            ->select(
                'documents.*',
                'routing_slip.id as routing_slip_id',
                'routing_slip.user_id as routing_slip_user_id',
                'routing_slip.routed_users',
                'routing_slip.r_destination',
                'routing_slip.trans_remarks',
                'routing_slip.source'
            )
            ->where('documents.route_id', $routeId)
            ->where('routing_slip.id', $routingSlipId)
            ->get();

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

        $filePath = storage_path('app/documents/' . $document->file_name);

        if (file_exists($filePath)) {
            return response()->file($filePath);
        } else {
            return redirect()->back()->with('error', 'File not found.');
        }
    }
    
   
}