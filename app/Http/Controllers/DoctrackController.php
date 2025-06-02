<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Office;
use App\Models\Document;
use App\Models\RoutingSlip;
use App\Models\Log;
use App\Models\LogsHistory;
use App\Models\AssignLogs;
use App\Models\Doctrack;
use App\Models\DoctrackFile;

class DoctrackController extends Controller
{
    public function storeDoctrack(Request $request)
{
    // Validate the request
    $request->validate([
        'user_id' => 'required|integer',
        'doc_type' => 'required|string',
        'doc_title' => 'required|string',
        'user_name' => 'required|string',
        'file' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png,xlsx,xls|max:20480',
    ]);

    // Generate a unique 9-character alphanumeric docslip_id
    $docslip_id = Str::upper(Str::random(9)); // Converts to uppercase for consistency

    

    // Store the document in the database
    $documentTrack = Doctrack::create([
        'user_id' => $request->user_id,
        'update_by' => NULL,
        'docslip_id' => $docslip_id,
        'doc_type' => $request->doc_type,
        'doc_title' => $request->doc_title,
        'user_name' => $request->user_name,
        'doctrack_stat' => 1,
    ]);


     // Initialize file name as null
     $fileName = null;

    // Handle optional file upload
    if ($request->hasFile('file')) {
        $file = $request->file('file');
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $file->getClientOriginalExtension();
        $fileName = $originalName . '.' . $extension;
    
        $i = 1;
        $storagePath = storage_path('app/doc_track'); // Ensure folder path is correct
    
        // Check for existing file and append " copy X"
        while (file_exists($storagePath . '/' . $fileName)) {
            $fileName = $originalName . ' Copy ' . $i . '.' . $extension;
            $i++;
        }
    
         // Store file
         $file->storeAs('doc_track', $fileName);

         // Save to doctrack_file table
         DoctrackFile::create([
             'doctrack_id' => $documentTrack->id,
    'docslip_id' => $documentTrack->docslip_id, // <-- Add this line
    'file' => $fileName,
         ]);
     }
 
     return redirect()->route('docslipForm', ['id' => $documentTrack->id])
         ->with('success', 'Document successfully submitted!');
 }

public function storeDoctrackUpdate(Request $request)
{
    // Validate the request
    $request->validate([
        'user_id' => 'required|integer',
        'update_by' => 'required|integer',
        'doc_type' => 'required|string',
        'doc_title' => 'required|string',
        'user_name' => 'required|string',
        'file' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png,xlsx,xls|max:20480',
    ]);

    // Store the document in the database
    $documentTrack = Doctrack::create([
        'user_id' => $request->user_id,
        'update_by' => $request->update_by,
        'docslip_id' => $request->docslip_id,
        'doc_type' => $request->doc_type,
        'doc_title' => $request->doc_title,
        'user_name' => $request->user_name,
        'doctrack_stat' => 2,
    ]);

    $storagePath = storage_path('app/doc_track');

    // Check if a new file is uploaded
    if ($request->hasFile('file')) {
        $file = $request->file('file');
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $file->getClientOriginalExtension();
        $fileName = $originalName . '.' . $extension;

        $i = 1;
        while (file_exists($storagePath . '/' . $fileName)) {
            $fileName = $originalName . ' Copy ' . $i . '.' . $extension;
            $i++;
        }

        // Delete old file if it exists
        $existingFile = DoctrackFile::where('docslip_id', $request->docslip_id)->first();
        if ($existingFile && file_exists($storagePath . '/' . $existingFile->file)) {
            unlink($storagePath . '/' . $existingFile->file);
        }

        // Store new file
        $file->storeAs('doc_track', $fileName);

        // Update existing record (no duplicate)
        DoctrackFile::updateOrCreate(
            ['docslip_id' => $request->docslip_id], // match
            [
                'doctrack_id' => $documentTrack->id,
                'file' => $fileName,
            ]
        );
    } else {
        // No new file uploaded: retain existing file reference
        $existingFile = DoctrackFile::where('docslip_id', $request->docslip_id)->first();
        if ($existingFile) {
            $existingFile->update([
                'doctrack_id' => $documentTrack->id,
            ]);
        }
    }

    return redirect()->route('doctrackSlip', ['id' => $documentTrack->id])
        ->with('success', 'New entry with tracking # ' . $documentTrack->docslip_id . ' was added successfully!');
}

public function docslipForm($id)
{
    // Fetch the document tracking record based on the given ID
    $documentTrack = Doctrack::findOrFail($id);

    $recordsOfficerCount = auth()->user()->hasRole('records_officer') ? RoutingSlip::where('route_status', 2)->count() : 0;

    $superUserCount = auth()->user()->hasRole('super_user') ? RoutingSlip::where('route_status', 1)->count() : 0;

    return view('slip.docslipForm', compact('documentTrack','recordsOfficerCount','superUserCount'));
}

// View PDF in the browser
public function pdfDocSlip($id)
{
    $documentTrack = DoctrackFile::findOrFail($id);

    $filePath = storage_path('app/doc_track/' . $documentTrack->file);

    if (file_exists($filePath)) {
        return response()->file($filePath);
    } else {
        return redirect()->back()->with('error', 'File not found.');
    }
}

public function slipMonitoring($docslip_id)
{
    $documentTrackid = Doctrack::with('doctrackFile')
    ->where('docslip_id', $docslip_id)
    ->get();


    $recordsOfficerCount = auth()->user()->hasRole('records_officer') ? RoutingSlip::where('route_status', 2)->count() : 0;

    $superUserCount = auth()->user()->hasRole('super_user') ? RoutingSlip::where('route_status', 1)->count() : 0;
 

    return view('slip.docMonitoring', compact('documentTrackid',  'superUserCount', 'recordsOfficerCount'));
}

public function search(Request $request)
{
    $query = $request->input('query');

    $slip = Doctrack::where('docslip_id', $query)->first();

    if ($slip) {
        return redirect()->route('slipMonitoring', ['docslip_id' => $slip->docslip_id]);
    } else {
        return redirect()->back()->with('error', 'Tracking code not found.');
    }
}

// public function updateSlipStatus(Request $request, $id)
// {
//     $document = Doctrack::findOrFail($id);
//     $document->doctrack_stat = $request->doctrack_stat;
//     $document->save();
    
//     return back()->with('success', 'Status updated successfully!');
// }
public function updateSlipStatus(Request $request, $id)
{
    $document = Doctrack::findOrFail($id);

    // Save status and comment if provided
    if ($request->has('comments')) {
        $document->comments = $request->comments;
    }

    if ($request->has('doctrack_stat')) {
        $document->doctrack_stat = $request->doctrack_stat;
    }

    $document->save();

    return back()->with('success', 'Comment saved successfully!');
}


public function deleteSlip($id)
{
    // Find the user by ID
    $documentTrack = Doctrack::findOrFail($id);

    // Delete the user
    $documentTrack->delete();

    // Redirect back with a success message
    if ($documentTrack) {
        return redirect()->route('slipMonitoring', ['docslip_id' => $documentTrack->docslip_id])
        ->with('success', 'Document deleted successfully!');
    } else {
        return redirect()->back()->with('error', 'Tracking code not found.');
    }
}



}
