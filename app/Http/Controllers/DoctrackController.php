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
use App\Models\User;
use App\Mail\DoctrackNotification;
use Illuminate\Support\Facades\Mail;

class DoctrackController extends Controller
{
    public function storeDoctrack(Request $request)
{
    // ✅ Validate the request
    $request->validate([
        'user_id'     => 'required|integer',
        'doc_type'    => 'required|string',
        'doc_title'   => 'required|string',
        'update_by'   => 'required|array',
        'update_by.*' => 'integer|exists:users,id',
        'file'        => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png,xlsm,xlsx,xls',
    ]);

    // ✅ Generate docslip ID
    $docslip_id = Str::upper(Str::random(9));

    // ✅ Handle optional file upload
    $fileName = null;
    if ($request->hasFile('file')) {
        $file = $request->file('file');
        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension = $file->getClientOriginalExtension();
        $fileName = $originalName . '.' . $extension;

        $i = 1;
        $storagePath = storage_path('app/doc_track');

        while (file_exists($storagePath . '/' . $fileName)) {
            $fileName = $originalName . ' Copy ' . $i . '.' . $extension;
            $i++;
        }

        $file->storeAs('doc_track', $fileName);
    }

    // ✅ Create a record for the creator (doctrack_stat = 1)
    $creator = User::find($request->user_id);
    $creatorFullName = $creator->fname . ' ' . $creator->lname;

    $documentTrack = Doctrack::create([
        'user_id'       => $request->user_id,
        'update_by'     => null,
        'docslip_id'    => $docslip_id,
        'doc_type'      => $request->doc_type,
        'doc_title'     => $request->doc_title,
        'user_name'     => $creatorFullName,
        'doctrack_stat' => 1,
    ]);

    // ✅ Prepare email data
    $docInfo = (object)[
        'docslip_id' => $docslip_id,
        'doc_title'  => $request->doc_title,
        'doc_type'   => $request->doc_type,
        'user_name'  => $creatorFullName,
    ];

    // ✅ Send email to the creator
    if (!empty($creator) && !empty($creator->email)) {
        try {
            Mail::to($creator->email)->send(
                new DoctrackNotification($docInfo, $creatorFullName)
            );
        } catch (\Exception $e) {
            Log::error("Email to creator failed: " . $e->getMessage());
        }
    }

    // ✅ Create records and send emails to each recipient (doctrack_stat = 2)
    foreach ($request->update_by as $userId) {
        $recipient = User::find($userId);
        if ($recipient) {
            $recipientFullName = $recipient->fname . ' ' . $recipient->lname;

            Doctrack::create([
                'user_id'       => $request->user_id,
                'update_by'     => $userId,
                'docslip_id'    => $docslip_id,
                'doc_type'      => $request->doc_type,
                'doc_title'     => $request->doc_title,
                'user_name'     => $creatorFullName,
                'doctrack_stat' => 2,
            ]);

            if (!empty($recipient->email)) {
                try {
                    Mail::to($recipient->email)->send(
                        new DoctrackNotification($docInfo, $recipientFullName)
                    );
                } catch (\Exception $e) {
                    Log::error("Email to recipient ID {$userId} failed: " . $e->getMessage());
                }
            }
        }
    }

    // ✅ Save file (linked only to the creator's row)
    if ($fileName) {
        DoctrackFile::create([
            'doctrack_id' => $documentTrack->id,
            'docslip_id'  => $docslip_id,
            'file'        => $fileName,
        ]);
    }

    // ✅ Final JSON response
    return response()->json([
        'success' => true,
        'id'      => $documentTrack->id,
        'message' => 'Document successfully submitted!',
    ]);
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
        'file' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png,xlsm,xlsx,xls|max:20480',
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
    $documentTrack = Doctrack::findOrFail($id);
    $user = auth()->user(); // ✅ fix: get user object, not just the ID

    $recordsOfficerCount = $user->role === 'records_officer' ? RoutingSlip::where('route_status', 2)->count() : 0;
    $superUserCount = $user->role === 'super_user' ? RoutingSlip::where('route_status', 1)->count() : 0;

    return view('slip.docslipForm', compact('documentTrack', 'recordsOfficerCount', 'superUserCount'));
}

public function pdfDocSlip($id)
{
    $documentTrack = DoctrackFile::findOrFail($id);
    $filePath = storage_path('app/doc_track/' . $documentTrack->file);

    if (file_exists($filePath)) {
        // force download with correct extension
        return response()->download($filePath, $documentTrack->file);
    } else {
        return redirect()->back()->with('error', 'File not found.');
    }
}

public function slipMonitoring($docslip_id)
{
    $documentTrackid = Doctrack::with('doctrackFile')
        ->where('docslip_id', $docslip_id)
        ->get();

    $user = auth()->user(); // ✅ get the authenticated user model

    // ✅ Role-based counts
    $recordsOfficerCount = $user->role === 'records_officer' 
        ? RoutingSlip::where('route_status', 2)->count() 
        : 0;

    $superUserCount = $user->role === 'super_user' 
        ? RoutingSlip::where('route_status', 1)->count() 
        : 0;

    return view('slip.docMonitoring', compact('documentTrackid', 'superUserCount', 'recordsOfficerCount'));
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
    // Find the document by ID
    $documentTrack = Doctrack::findOrFail($id);

    // Delete the document
    $documentTrack->delete();

    // Redirect to doctrackslip-list with a success message
    return redirect()->route('doctrackSlip')
        ->with('success', 'Document deleted successfully!');
}



}
