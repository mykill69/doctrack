<?php

namespace App\Http\Controllers;

use App\Models\ArchiveLog;
use App\Models\User;
use App\Services\ArchiveService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ArchiveController extends Controller
{
    public function __construct(private ArchiveService $archiveService)
    {
    }

    /**
     * Archive Logbook page: select a previous year, preview the records and
     * archive them. Recent archive operations are listed below the form.
     */
    public function index()
    {
        $years = $this->archiveService->availableYears();
        $defaultYear = $years[0] ?? null;

        $recentOperations = ArchiveLog::with('archivedBy:id,fname,lname')
            ->orderByDesc('id')
            ->limit(10)
            ->get();

        return view('archive.index', compact('years', 'defaultYear', 'recentOperations'));
    }

    /**
     * AJAX preview of the number of eligible transactions.
     */
    public function preview(Request $request)
    {
        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:1900'],
        ]);

        $year = (int) $validated['year'];

        if ($year >= (int) now()->year) {
            return response()->json([
                'message' => 'Only previous years can be archived.',
            ], 422);
        }

        $counts = $this->archiveService->preview($year);

        return response()->json([
            'year' => $year,
            'counts' => $counts,
        ]);
    }

    /**
     * Perform the archive operation. Archiving is additive: transactions are
     * flagged and their data/files are left untouched.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:1900'],
            'type' => ['required', 'in:routing_slip,document_tracking,both'],
        ]);

        $year = (int) $validated['year'];

        if ($year >= (int) now()->year) {
            return response()->json([
                'message' => 'Only previous years can be archived.',
            ], 422);
        }

        $results = $this->archiveService->archiveYear(
            $year,
            $validated['type'],
            (int) auth()->id()
        );

        $records = collect($results)->sum('records');
        $failed = collect($results)->contains(fn ($result) => $result['status'] === ArchiveLog::STATUS_FAILED);

        return response()->json([
            'year' => $year,
            'type' => $validated['type'],
            'records' => $records,
            'results' => $results,
            'status' => $failed ? 'failed' : ($records > 0 ? 'completed' : 'skipped'),
            'message' => $failed
                ? 'The archive operation did not complete. Please check the recent operations below.'
                : ($records > 0
                    ? number_format($records) . ' transaction(s) archived successfully.'
                    : 'There are no eligible records left to archive for this year.'),
        ]);
    }

    /**
     * Archived History Logbook page.
     */
    public function history()
    {
        $years = $this->archiveService->archivedYears();

        $actionUnits = DB::table('routing_slip')
            ->where('is_archived', 1)
            ->whereNotNull('pres_dept')
            ->where('pres_dept', '<>', '')
            ->distinct()
            ->orderBy('pres_dept')
            ->limit(200)
            ->pluck('pres_dept');

        return view('archive.history', compact('years', 'actionUnits'));
    }

    /**
     * Server-side DataTable data for archived routing slips.
     */
    public function routingData(Request $request)
    {
        $base = $this->routingHistoryBase();
        $totalRecords = (clone $base)->count();

        $this->applyRoutingHistoryFilters($base, $request);
        $filteredRecords = (clone $base)->count();

        $this->applyHistoryOrder($base, $request, [
            'ctrl_no' => 'rs.rslip_id',
            'date_received' => 'g.original_created_at',
            'source' => 'rs.source',
            'subject' => 'rs.subject',
            'action_unit' => 'rs.pres_dept',
            'received_by_date' => 'g.original_created_at',
            'action_taken' => 'rs.r_destination',
            'date_released' => 'g.last_updated_at',
            'remarks' => 'rs.trans_remarks',
            'file_name' => 'rs.document',
            'updated_by' => 'g.last_updated_at',
        ], 'g.original_created_at', 'rs.id');

        $length = $this->pageLength($request);
        $rows = $base->skip(max(0, (int) $request->input('start', 0)))
            ->take($length)
            ->get();

        $routeIds = $rows->pluck('rslip_id')->unique()->filter()->values()->toArray();

        $documentsByRoute = collect();
        $logsByRoute = collect();
        $usersById = collect();

        if (!empty($routeIds)) {
            $documentsByRoute = DB::table('documents')
                ->whereIn('route_id', $routeIds)
                ->orderBy('id')
                ->get()
                ->groupBy(fn ($document) => (string) $document->route_id);

            $logsByRoute = DB::table('logs')
                ->whereIn('route_id', $routeIds)
                ->orderByDesc('id')
                ->get(['route_id', 'new_destination', 'assigned_to', 'comments', 'created_at', 'updated_at'])
                ->groupBy(fn ($log) => (string) $log->route_id);

            $userIds = $rows->pluck('user_id')
                ->merge($rows->pluck('assigned_to'))
                ->filter(fn ($id) => is_numeric($id))
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->toArray();

            if (!empty($userIds)) {
                $usersById = User::whereIn('id', $userIds)
                    ->select('id', 'fname', 'lname')
                    ->get()
                    ->keyBy('id');
            }
        }

        $data = [];

        foreach ($rows as $row) {
            $routeDocuments = $documentsByRoute->get((string) $row->rslip_id, collect());

            $exactDocument = $routeDocuments->first(function ($document) use ($row) {
                return mb_strtolower((string) $document->file_name) === mb_strtolower((string) $row->document);
            }) ?: $routeDocuments->first();

            $latestLog = $logsByRoute->get((string) $row->rslip_id, collect())->first();
            $routeUser = $usersById->get((int) $row->user_id);

            $remarks = '';
            if (!empty($row->trans_remarks)) {
                $remarks .= '<span class="badge badge-success" style="font-size:10px; display:block;">'
                    . e($row->trans_remarks) . '</span>';
            }
            if (!empty($row->other_remarks)) {
                $remarks .= '<span class="badge badge-danger" style="font-size:10px; display:block;">'
                    . e($row->other_remarks) . '</span>';
            }
            if ($latestLog && !empty($latestLog->comments)) {
                $remarks .= '<span class="badge badge-warning" style="margin-top:2px; font-size:10px; max-width:170px; display:inline-block; word-wrap:break-word; white-space:normal;">'
                    . e($latestLog->comments) . '</span>';
            }

            $fileName = '<span class="text-muted">N/A</span>';
            if ($exactDocument) {
                $fileName = '<a href="' . route('archive.file.documentAttachment', $exactDocument->id)
                    . '" target="_blank" style="color:#007bff;"><i class="fas fa-file-pdf text-danger"></i> '
                    . e(Str::limit($exactDocument->file_name, 24)) . '</a>';
            } elseif (!empty($row->document)) {
                $fileName = '<a href="' . route('archive.file.routing', $row->id)
                    . '" target="_blank" style="color:#007bff;"><i class="fas fa-file-pdf text-danger"></i> '
                    . e(Str::limit($row->document, 24)) . '</a>';
            }

            $actionTaken = '';
            if (!empty($row->r_destination)) {
                $actionTaken .= '<strong class="text-danger">' . e(ucwords(strtolower($row->r_destination))) . '</strong>';
            }
            if (!empty($row->assigned_to)) {
                $actionTaken .= ', was re-assigned to <strong class="text-danger">' . e(ucwords(strtolower($row->assigned_to))) . '</strong>';
            }

            $data[] = [
                'ctrl_no' => '<a href="' . route('archive.history.routingShow', $row->rslip_id)
                    . '" target="_blank" style="color:#007bff;">' . e($row->rslip_id) . '</a>',
                'date_received' => $row->date_received
                    ? Carbon::parse($row->date_received)->format('F d, Y')
                    : ($row->original_created_at ? Carbon::parse($row->original_created_at)->format('F d, Y') : 'N/A'),
                'source' => e($row->source ?: 'N/A'),
                'subject' => e($row->subject ?: 'N/A'),
                'action_unit' => e($row->pres_dept ?: 'N/A'),
                'received_by_date' => e($row->received_name ?: 'N/A')
                    . ($row->date_received ? '<br><small class="text-muted">' . Carbon::parse($row->date_received)->format('M d, Y') . '</small>' : ''),
                'action_taken' => $actionTaken ?: 'N/A',
                'date_released' => $row->last_updated_at
                    ? Carbon::parse($row->last_updated_at)->format('m-d-Y h:i:s A')
                    : 'N/A',
                'remarks' => $remarks,
                'file_name' => $fileName,
                'updated_by' => '<span class="badge badge-secondary">'
                    . e($routeUser ? trim($routeUser->fname . ' ' . $routeUser->lname) : 'N/A')
                    . '</span><br><small class="text-muted">'
                    . ($row->last_updated_at ? Carbon::parse($row->last_updated_at)->format('m-d-Y h:i:s A') : 'N/A')
                    . '</small>',
                'validity' => '<span class="text-muted">N/A</span>',
                'logbook_status' => '<span class="badge badge-dark">Archived</span>',
                'action' => '<a href="' . route('archive.history.routingShow', $row->rslip_id)
                    . '" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a>',
            ];
        }

        return response()->json([
            'draw' => (int) $request->input('draw'),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
    }

    /**
     * Server-side DataTable data for archived document tracking slips.
     */
    public function documentData(Request $request)
    {
        $base = $this->documentHistoryBase();
        $totalRecords = (clone $base)->count();

        $this->applyDocumentHistoryFilters($base, $request);
        $filteredRecords = (clone $base)->count();

        $this->applyHistoryOrder($base, $request, [
            'ctrl_no' => 'g.earliest_ctrl_no',
            'date_received' => 'g.original_created_at',
            'source' => 'dt.user_name',
            'subject' => 'dt.doc_title',
            'received_by_date' => 'g.last_updated_at',
            'action_taken' => 'dt.update_by',
            'date_released' => 'g.last_updated_at',
            'remarks' => 'g.last_updated_at',
            'file_name' => 'dt.docslip_id',
            'updated_by' => 'g.last_updated_at',
        ], 'g.original_created_at', 'dt.id');

        $length = $this->pageLength($request);
        $rows = $base->skip(max(0, (int) $request->input('start', 0)))
            ->take($length)
            ->get();

        $docslipIds = $rows->pluck('docslip_id')->unique()->filter()->values()->toArray();

        $filesBySlip = collect();
        $commentsBySlip = collect();
        $usersById = collect();

        if (!empty($docslipIds)) {
            $filesBySlip = DB::table('doctrack_file')
                ->whereIn('docslip_id', $docslipIds)
                ->orderBy('id')
                ->get()
                ->groupBy(fn ($file) => (string) $file->docslip_id);

            $commentsBySlip = DB::table('logs_tracking')
                ->whereIn('docslip_id', $docslipIds)
                ->whereNotNull('comments')
                ->orderByDesc('created_at')
                ->get(['docslip_id', 'comments', 'logs_status', 'update_by', 'user_id'])
                ->groupBy(fn ($log) => (string) $log->docslip_id);

            $userIds = $rows->pluck('update_by')
                ->merge($rows->pluck('user_id'))
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->toArray();

            if (!empty($userIds)) {
                $usersById = User::whereIn('id', $userIds)
                    ->select('id', 'fname', 'lname')
                    ->get()
                    ->keyBy('id');
            }
        }

        $data = [];

        foreach ($rows as $row) {
            $files = $filesBySlip->get((string) $row->docslip_id, collect());
            $latestComment = $commentsBySlip->get((string) $row->docslip_id, collect())->first();
            $updater = $usersById->get((int) $row->update_by);
            $creator = $usersById->get((int) $row->user_id);
            $displayCtrl = $row->ctrl_no ?: ($row->earliest_ctrl_no ?: $row->docslip_id);

            $fileName = '<span class="text-muted">N/A</span>';
            if ($files->isNotEmpty()) {
                $fileName = $files->take(3)->map(function ($file) {
                    return '<a href="' . route('archive.file.document', $file->id)
                        . '" target="_blank" style="color:#007bff; display:block;"><i class="fas fa-file-pdf text-danger"></i> '
                        . e(Str::limit($file->file, 22)) . '</a>';
                })->implode('');
            }

            $actionTaken = $updater
                ? '<p class="text-red text-bold mb-0">' . e(strtoupper($updater->fname . ' ' . $updater->lname)) . '</p>'
                : '<span class="text-muted">N/A</span>';

            $data[] = [
                'ctrl_no' => '<a href="' . route('archive.history.documentShow', $row->docslip_id)
                    . '" target="_blank" style="color:#007bff;">' . e($displayCtrl) . '</a>',
                'date_received' => $row->original_created_at
                    ? Carbon::parse($row->original_created_at)->format('F d, Y')
                    : 'N/A',
                'source' => e($row->user_name ?: 'N/A'),
                'subject' => e(trim(($row->doc_title ?: 'N/A') . ' - ' . ($row->doc_type ?: 'N/A'))),
                'action_unit' => '<span class="text-muted">--</span>',
                'received_by_date' => ($updater ? e(trim($updater->fname . ' ' . $updater->lname)) : 'N/A')
                    . '<br><small class="text-muted">'
                    . ($row->last_updated_at ? Carbon::parse($row->last_updated_at)->format('M d, Y') : 'N/A')
                    . '</small>',
                'action_taken' => $actionTaken,
                'date_released' => $row->last_updated_at
                    ? Carbon::parse($row->last_updated_at)->format('m-d-Y h:i:s A')
                    : 'N/A',
                'remarks' => $latestComment
                    ? '<span class="badge badge-warning" style="font-size:10px; max-width:170px; display:inline-block; word-wrap:break-word; white-space:normal;">'
                        . e($latestComment->comments) . '</span>'
                    : '<span class="text-muted">No comments</span>',
                'file_name' => $fileName,
                'updated_by' => '<span class="badge badge-secondary">'
                    . e($updater ? trim($updater->fname . ' ' . $updater->lname) : ($creator ? trim($creator->fname . ' ' . $creator->lname) : 'N/A'))
                    . '</span><br><small class="text-muted">'
                    . ($row->last_updated_at ? Carbon::parse($row->last_updated_at)->format('m-d-Y h:i:s A') : 'N/A')
                    . '</small>',
                'validity' => '<span class="text-muted">N/A</span>',
                'logbook_status' => '<span class="badge badge-dark">Archived</span>',
                'action' => '<a href="' . route('archive.history.documentShow', $row->docslip_id)
                    . '" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fas fa-eye"></i></a>',
            ];
        }

        return response()->json([
            'draw' => (int) $request->input('draw'),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $filteredRecords,
            'data' => $data,
        ]);
    }

    /**
     * Read-only archived routing slip detail (all versions + routing history).
     */
    public function showRouting($rslipId)
    {
        abort_unless($this->archiveService->isRoutingSlipArchived($rslipId), 404);

        $slips = DB::table('routing_slip')
            ->where('rslip_id', $rslipId)
            ->where('is_archived', 1)
            ->orderBy('id')
            ->get();

        abort_if($slips->isEmpty(), 404);

        $documents = DB::table('documents')
            ->where('route_id', $rslipId)
            ->orderBy('id')
            ->get();

        $logs = DB::table('logs')
            ->where('route_id', $rslipId)
            ->orderBy('id')
            ->get();

        $userIds = $slips->pluck('user_id')
            ->merge($logs->pluck('user_id'))
            ->merge($logs->pluck('new_user'))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->toArray();

        $usersById = User::whereIn('id', $userIds)
            ->select('id', 'fname', 'lname')
            ->get()
            ->keyBy('id');

        $archivedBy = User::find($slips->last()->archived_by ?? null);
        $title = 'ARCHIVED ROUTING SLIP HISTORY';

        return view('archive.routing_detail', compact(
            'slips',
            'documents',
            'logs',
            'usersById',
            'archivedBy',
            'title',
            'rslipId'
        ));
    }

    /**
     * Read-only archived document tracking detail (all entries, files and
     * tracking history).
     */
    public function showDocument($docslipId)
    {
        abort_unless($this->archiveService->isDocumentTrackingArchived($docslipId), 404);

        $entries = DB::table('doctrack_slip')
            ->where('docslip_id', $docslipId)
            ->where('is_archived', 1)
            ->orderBy('id')
            ->get();

        abort_if($entries->isEmpty(), 404);

        $files = DB::table('doctrack_file')
            ->where('docslip_id', $docslipId)
            ->orderBy('id')
            ->get();

        $trackingLogs = DB::table('logs_tracking')
            ->where('docslip_id', $docslipId)
            ->orderBy('created_at')
            ->get();

        $userIds = $entries->pluck('user_id')
            ->merge($entries->pluck('update_by'))
            ->merge($trackingLogs->pluck('user_id'))
            ->merge($trackingLogs->pluck('update_by'))
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->toArray();

        $usersById = User::whereIn('id', $userIds)
            ->select('id', 'fname', 'lname')
            ->get()
            ->keyBy('id');

        $archivedBy = User::find($entries->last()->archived_by ?? null);

        return view('archive.document_detail', compact(
            'entries',
            'files',
            'trackingLogs',
            'usersById',
            'archivedBy',
            'docslipId'
        ));
    }

    /**
     * Read-only file of an archived routing slip.
     */
    public function routingFile($id)
    {
        $slip = DB::table('routing_slip')
            ->where('id', $id)
            ->where('is_archived', 1)
            ->first();

        abort_if(!$slip || empty($slip->document), 404);

        return $this->serveFile($this->existingPath([
            storage_path('app/documents/' . $slip->document),
            storage_path('app/public/documents/' . $slip->document),
            public_path('storage/documents/' . $slip->document),
        ]), $slip->document);
    }

    /**
     * Read-only file of an archived document tracking transaction.
     */
    public function documentFile($id)
    {
        $file = DB::table('doctrack_file')->where('id', $id)->first();

        abort_if(!$file, 404);
        abort_unless($this->archiveService->isDocumentTrackingArchived($file->docslip_id), 404);

        return $this->serveFile($this->existingPath([
            storage_path('app/doc_track/' . $file->file),
            storage_path('app/public/doc_track/' . $file->file),
            public_path('storage/doc_track/' . $file->file),
        ]), $file->file);
    }

    /**
     * Read-only attachment of an archived routing slip.
     */
    public function documentAttachment($id)
    {
        $document = DB::table('documents')->where('id', $id)->first();

        abort_if(!$document, 404);
        abort_unless($this->archiveService->isRoutingSlipArchived($document->route_id), 404);

        return $this->serveFile($this->existingPath([
            storage_path('app/documents/' . $document->file_name),
            storage_path('app/public/documents/' . $document->file_name),
            public_path('storage/documents/' . $document->file_name),
        ]), $document->file_name);
    }

    private function routingHistoryBase()
    {
        $grouped = DB::table('routing_slip')
            ->selectRaw('MAX(id) as id, rslip_id, MIN(created_at) as original_created_at, MAX(updated_at) as last_updated_at')
            ->where('is_archived', 1)
            ->groupBy('rslip_id');

        return DB::table('routing_slip as rs')
            ->joinSub($grouped, 'g', 'rs.id', '=', 'g.id')
            ->select(
                'rs.id',
                'rs.rslip_id',
                'rs.op_ctrl',
                'rs.date_received',
                'rs.source',
                'rs.subject',
                'rs.pres_dept',
                'rs.received_name',
                'rs.r_destination',
                'rs.assigned_to',
                'rs.trans_remarks',
                'rs.other_remarks',
                'rs.document',
                'rs.user_id',
                'rs.updated_at',
                'rs.created_at',
                'g.original_created_at',
                'g.last_updated_at'
            );
    }

    private function documentHistoryBase()
    {
        $grouped = DB::table('doctrack_slip')
            ->selectRaw('MAX(id) as id, docslip_id, MIN(created_at) as original_created_at, MAX(updated_at) as last_updated_at, MIN(ctrl_no) as earliest_ctrl_no')
            ->where('is_archived', 1)
            ->groupBy('docslip_id');

        return DB::table('doctrack_slip as dt')
            ->joinSub($grouped, 'g', 'dt.id', '=', 'g.id')
            ->select(
                'dt.id',
                'dt.docslip_id',
                'dt.ctrl_no',
                'dt.user_id',
                'dt.update_by',
                'dt.doc_type',
                'dt.doc_title',
                'dt.user_name',
                'dt.doctrack_stat',
                'dt.comments',
                'dt.created_at',
                'dt.updated_at',
                'g.original_created_at',
                'g.last_updated_at',
                'g.earliest_ctrl_no'
            );
    }

    private function applyRoutingHistoryFilters($query, Request $request): void
    {
        $currentYear = (int) now()->year;

        if ($request->filled('year')) {
            $query->whereYear('g.original_created_at', (int) $request->input('year'));
        } else {
            $query->whereYear('g.original_created_at', '<', $currentYear);
        }

        $this->applyDateRange($query, $request, 'g.original_created_at');

        if ($request->filled('ctrl_no')) {
            $like = '%' . $request->input('ctrl_no') . '%';
            $query->where(function ($q) use ($like) {
                $q->where('rs.rslip_id', 'LIKE', $like)
                    ->orWhere('rs.op_ctrl', 'LIKE', $like);
            });
        }

        if ($request->filled('subject')) {
            $query->where('rs.subject', 'LIKE', '%' . $request->input('subject') . '%');
        }

        if ($request->filled('source')) {
            $query->where('rs.source', 'LIKE', '%' . $request->input('source') . '%');
        }

        if ($request->filled('action_unit')) {
            $query->where('rs.pres_dept', $request->input('action_unit'));
        }

        $this->applyGlobalSearch($query, $request, [
            'rs.rslip_id',
            'rs.op_ctrl',
            'rs.subject',
            'rs.source',
            'rs.pres_dept',
            'rs.received_name',
            'rs.r_destination',
            'rs.assigned_to',
            'rs.trans_remarks',
            'rs.other_remarks',
            'rs.document',
        ]);
    }

    private function applyDocumentHistoryFilters($query, Request $request): void
    {
        $currentYear = (int) now()->year;

        if ($request->filled('year')) {
            $query->whereYear('g.original_created_at', (int) $request->input('year'));
        } else {
            $query->whereYear('g.original_created_at', '<', $currentYear);
        }

        $this->applyDateRange($query, $request, 'g.original_created_at');

        if ($request->filled('ctrl_no')) {
            $like = '%' . $request->input('ctrl_no') . '%';
            $query->where(function ($q) use ($like) {
                $q->where('dt.ctrl_no', 'LIKE', $like)
                    ->orWhere('dt.docslip_id', 'LIKE', $like);
            });
        }

        if ($request->filled('subject')) {
            $like = '%' . $request->input('subject') . '%';
            $query->where(function ($q) use ($like) {
                $q->where('dt.doc_title', 'LIKE', $like)
                    ->orWhere('dt.doc_type', 'LIKE', $like);
            });
        }

        if ($request->filled('source')) {
            $query->where('dt.user_name', 'LIKE', '%' . $request->input('source') . '%');
        }

        $this->applyGlobalSearch($query, $request, [
            'dt.docslip_id',
            'dt.ctrl_no',
            'dt.doc_title',
            'dt.doc_type',
            'dt.user_name',
            'dt.comments',
        ]);
    }

    private function applyGlobalSearch($query, Request $request, array $columns): void
    {
        $search = trim((string) $request->input('search.value', ''));

        if ($search === '') {
            return;
        }

        $like = '%' . $search . '%';

        $query->where(function ($q) use ($columns, $like) {
            foreach ($columns as $index => $column) {
                $index === 0
                    ? $q->where($column, 'LIKE', $like)
                    : $q->orWhere($column, 'LIKE', $like);
            }
        });
    }

    private function applyDateRange($query, Request $request, string $column): void
    {
        if ($request->filled('date_from')) {
            $query->whereDate($column, '>=', $request->input('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->whereDate($column, '<=', $request->input('date_to'));
        }
    }

    private function applyHistoryOrder($query, Request $request, array $map, string $defaultColumn, string $idColumn): void
    {
        $columnIndex = (int) $request->input('order.0.column', 0);
        $direction = strtolower((string) $request->input('order.0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';
        $columns = $request->input('columns', []);
        $column = is_array($columns) ? ($columns[$columnIndex] ?? null) : null;
        $requested = is_array($column) ? ($column['data'] ?? ($column['name'] ?? null)) : null;

        $orderBy = $map[$requested] ?? $defaultColumn;

        $query->orderBy($orderBy, $direction);
        $query->orderBy($idColumn, $direction);
    }

    private function pageLength(Request $request): int
    {
        $length = (int) $request->input('length', 25);

        if (!in_array($length, [25, 50, 100], true)) {
            $length = 25;
        }

        return $length;
    }

    private function existingPath(array $candidates): string
    {
        foreach ($candidates as $candidate) {
            if (File::exists($candidate)) {
                return $candidate;
            }
        }

        abort(404, 'File not found.');
    }

    private function serveFile(string $path, string $name)
    {
        return response()->file($path, [
            'Content-Disposition' => 'inline; filename="' . addslashes($name) . '"',
            'Content-Type' => mime_content_type($path) ?: 'application/octet-stream',
        ]);
    }
}
