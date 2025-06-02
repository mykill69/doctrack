<?php

namespace App\Http\Controllers;


use Illuminate\Http\Request;
use App\Models\Document;
use App\Models\Office;
use App\Models\Log;
use App\Models\LogsHistory;
use App\Models\RoutingSlip;
use App\Models\RouteDocument;

class DocumentController extends Controller
{
//     public function dashboard()
// {
//     $userDepartment = auth()->user()->department;
//     $userId = auth()->user()->id;

//     $logs = Log::where(function ($query) use ($userId, $userDepartment) {
//         $query->where('new_user', $userId)
//               ->orWhere('user_id', $userId)
//               ->orWhere('new_destination', $userDepartment);
//     })->get(); 

//     $routingSlipCount = ($logs->every(fn($log) => $log->status_update != 3)) ? RoutingSlip::where('route_status', 3)->count() : 0;
//     $superUserCount = auth()->user()->hasRole('super_user') ? RoutingSlip::where('route_status', 1)->count() : 0;
//     $recordsOfficerCount = auth()->user()->hasRole('records_officer') ? RoutingSlip::where('route_status', 2)->count() : 0; 

//     $offices = Office::all();

//     return view('home.dashboard', compact('offices', 'logs', 'routingSlipCount', 'superUserCount', 'recordsOfficerCount'));
// }
public function dashboard()
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
    $dpa = auth()->user()->dpa;

    return view('home.dashboard', compact('offices', 'logs', 'routingSlipCount', 'superUserCount', 'recordsOfficerCount', 'dpa'));
}


public function tracking(Request $request)
{
    $userId = auth()->user()->id;
    $userDepartment = auth()->user()->department;

    $routeId = $request->input('route_id');

    $query = Document::query()
        ->leftJoin('route_documents', 'documents.route_id', '=', 'route_documents.route_id') 
        ->select('documents.*');

    if ($routeId) {
        $query->where('documents.route_id', $routeId); 
    }

    $documents = $query->get();

    $filteredDocuments = $documents->filter(function ($document) use ($userDepartment, $userId) {

        $routeDocument = RouteDocument::where('route_id', $document->route_id)->first();

        if ($routeDocument) {
            $destinations = [
                $routeDocument->destination_1,
                $routeDocument->destination_2,
                $routeDocument->destination_3,
                $routeDocument->destination_4,
                $routeDocument->destination_5,
                $routeDocument->destination_6,
                $routeDocument->destination_7,
                $routeDocument->destination_8,
                $routeDocument->destination_9,
                $routeDocument->destination_10,
            ];

            return in_array($userDepartment, $destinations) || $document->user_id == $userId;
        }

        return false; 
    });

    $logs = Log::where('user_id', $userId)->get();

    // Count routing slips
    $routingSlipCount = ($logs->every(fn($log) => $log->status_update != 3)) ? RoutingSlip::where('route_status', 3)->count() : 0;
    $superUserCount = auth()->user()->hasRole('super_user') ? RoutingSlip::where('route_status', 1)->count() : 0;
    $recordsOfficerCount = auth()->user()->hasRole('records_officer') ? RoutingSlip::where('route_status', 2)->count() : 0; 

    $offices = Office::all();

    return view('track.tracktemp', [
        'documents' => $filteredDocuments,
        'offices' => $offices,
        'docNumber' => $routeId, 
        // 'docCount' => $docCount,
        'routingSlipCount' => $routingSlipCount,
        'superUserCount' => $superUserCount,
        'recordsOfficerCount' => $recordsOfficerCount,
        // 'statusUpdateCount' => $statusUpdateCount
    ]);
}

//     public function store(Request $request)
// {
//     $request->validate([
//         'user_id' => 'required|integer', 
//         'full_name' => 'required|string|max:255',
//         'subject' => 'required|string',
//         'doc_type' => 'required|string',
//         'document' => 'required|file|mimes:pdf', 
//         'purpose' => 'required|string',
//         'department' => 'required|string',
//         'doc_stat' => 'required|integer',
//     ]);

//     $fileName = null;
//     if ($request->hasFile('document')) {

//         do {
//             $randomNumber = mt_rand(10000000, 99999999);
//         } while (Document::where('file_name', 'like', "%$randomNumber%")->exists()); 

//         $originalFileName = $request->file('document')->getClientOriginalName();
//         $fileName = $randomNumber . '_' . $originalFileName;

//         $filePath = $request->file('document')->storeAs('documents', $fileName, 'public');
//     }

//     $document = Document::create([
//         'user_id' => $request->user_id, 
//         'full_name' => $request->full_name,
//         'file_name' => $fileName,
//         'subject' => $request->subject,
//         'purpose' => $request->purpose,
//         'department' => $request->department,
//         'doc_stat' => $request->doc_stat,
//         'doc_type' => $request->doc_type,
//     ]);

//     Log::create([
//         'user_id' => $document->user_id,
//         'new_user' => auth()->user()->id,
//         'doc_id' => $document->id,
//         'action' => 1, 
//         'prev_file' => $document->file_name,
//         'new_file' => null,
//         'new_destination' => $document->destination,
//     ]);

//     return redirect()->back()->with('success', 'Document submitted successfully!');
// }
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

    // Insert into route_documents table
    $routeDocument = new RouteDocument();
    $routeDocument->route_id = $document->route_id; // Assuming document ID is used as route ID

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
        'comments' => 'nullable|string',
        'user_id' => 'required|integer',
        'new_user' => 'required|integer',
    ]);

    $userDepartment = auth()->user()->department;

    $document = Document::find($id);
    if (!$document) {
        return redirect()->back()->with('error', 'Document not found.');
    }

    $routeId = $document->route_id;

    $logToUpdate = Log::where('new_destination', $userDepartment)
                      ->where('route_id', $routeId)
                      ->first();

    if ($logToUpdate) {
        // Update the existing log entry
        $logToUpdate->user_id = $request->input('user_id'); 
        $logToUpdate->new_user = $request->input('new_user');  
        $logToUpdate->action = 'Updated';
        $logToUpdate->status_update = $request->input('status_update');
        $logToUpdate->prev_file = $logToUpdate->new_file;
        $logToUpdate->new_file = $logToUpdate->new_file; 
        $logToUpdate->comments = $request->input('comments', null);
        $logToUpdate->assigned_to = $logToUpdate->assigned_to; 
        $logToUpdate->updated_at = now();
        $logToUpdate->save();

        // If status_update is 3, also create an entry in logs_history
        if ($logToUpdate->status_update == 3) {
            LogsHistory::create([
                'doc_id' => $logToUpdate->doc_id,
                'action' => $logToUpdate->action,
                'status_update' => $logToUpdate->status_update,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return redirect()->back()->with('success', 'The document was acknowledged successfully.');
    } else {
        return redirect()->back()->with('error', 'Log entry not found for the specified route_id.');
    }
}



    // download pdf
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
    // View PDF in the browser
    public function viewPdf($id)
    {
        $document = Document::findOrFail($id);

        $filePath = storage_path('app/documents/' . $document->file_name);

        if (file_exists($filePath)) {
            return response()->file($filePath);
        } else {
            return redirect()->back()->with('error', 'File not found.');
        }
    }

    public function index()
    {
        $documents = index::join('statuses', 'statuses.id', '=', 'documents.doc_type')
            ->select(
                'document.id as document_id',
                'documents.user_id',
                'documents.full_name',
                'documents.file_name',
                'documents.subject',
                'documents.purpose',
                'documents.department',
                'documents.doc_stat',
                'documents.doc_type',
                // 'documents.destination',
                'documents.created_at',
                'documents.updated_at',
                'statuses.status' 
            )
            ->where('documents.doc_stat', '!=', null)
            ->get();

        return view('dashboard', compact('documents','statuses')); 
    }
}