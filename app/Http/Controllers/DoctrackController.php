<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\UploadedFile;
use App\Models\Office;
use App\Models\Document;
use App\Models\RoutingSlip;
use App\Models\Log;
use App\Models\LogsHistory;
use App\Models\AssignLogs;
use App\Models\LogsTracking;
use App\Models\Doctrack;
use App\Models\DoctrackFile;
use App\Models\User;
use App\Mail\DoctrackNotification;
use Illuminate\Support\Facades\Mail;
use App\Models\Group;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;

class DoctrackController extends Controller
{
//     public function storeDoctrack(Request $request)
// {
//     // ✅ Validate the request
//     $request->validate([
//         'user_id'     => 'required|integer',
//         'doc_type'    => 'required|string',
//         'doc_title'   => 'required|string',
//         'update_by'   => 'required|array',
//         'update_by.*' => 'integer|exists:users,id',
//         'file'        => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png,xlsm,xlsx,xls',
//     ]);

//     // ✅ Generate docslip ID
//     $docslip_id = 'CPSU' . mt_rand(10000000, 99999999);

//     // ✅ Handle optional file upload
//     $fileName = null;
//     if ($request->hasFile('file')) {
//         $file = $request->file('file');
//         $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
//         $extension = $file->getClientOriginalExtension();
//         $fileName = $originalName . '.' . $extension;

//         $i = 1;
//         $storagePath = storage_path('app/doc_track');

//         while (file_exists($storagePath . '/' . $fileName)) {
//             $fileName = $originalName . ' Copy ' . $i . '.' . $extension;
//             $i++;
//         }

//         $file->storeAs('doc_track', $fileName);
//     }

//     // ✅ Create a record for the creator (doctrack_stat = 1)
//     $creator = User::find($request->user_id);
//     $creatorFullName = $creator->fname . ' ' . $creator->lname;

//     $documentTrack = Doctrack::create([
//         'user_id'       => $request->user_id,
//         'update_by'     => null,
//         'docslip_id'    => $docslip_id,
//         'doc_type'      => $request->doc_type,
//         'doc_title'     => $request->doc_title,
//         'user_name'     => $creatorFullName,
//         'doctrack_stat' => 1,
//     ]);

//      // 🔹 Add to logs_tracking for creator
//     LogsTracking::create([
//         'docslip_id' => $docslip_id,
//         'user_id'    => $request->user_id,
//         'update_by'  => null,
//         'doc_title'  => $request->doc_title,
//         'file_logs'  => $fileName,
//         'logs_status'=> 1,
//         'comments'   => null,
//     ]);

//     // ✅ Prepare email data
//     $docInfo = (object)[
//         'docslip_id' => $docslip_id,
//         'doc_title'  => $request->doc_title,
//         'doc_type'   => $request->doc_type,
//         'user_name'  => $creatorFullName,
//     ];

//     // ✅ Send email to the creator
//     if (!empty($creator) && !empty($creator->email)) {
//         try {
//             Mail::to($creator->email)->send(
//                 new DoctrackNotification($docInfo, $creatorFullName)
//             );
//         } catch (\Exception $e) {
//             Log::error("Email to creator failed: " . $e->getMessage());
//         }
//     }

//     // ✅ Create records and send emails to each recipient (doctrack_stat = 2)
//     foreach ($request->update_by as $userId) {
//         $recipient = User::find($userId);
//         if ($recipient) {
//             $recipientFullName = $recipient->fname . ' ' . $recipient->lname;

//             Doctrack::create([
//                 'user_id'       => $request->user_id,
//                 'update_by'     => $userId,
//                 'docslip_id'    => $docslip_id,
//                 'doc_type'      => $request->doc_type,
//                 'doc_title'     => $request->doc_title,
//                 'user_name'     => $creatorFullName,
//                 'doctrack_stat' => 2,
//             ]);

//               LogsTracking::create([
//                 'docslip_id' => $docslip_id,
//                 'user_id'    => $request->user_id,
//                 'update_by'  => $userId,
//                 'doc_title'  => $request->doc_title,
//                 'file_logs'  => $fileName,
//                 'logs_status'=> 2,
//                 'comments'   => null,
//             ]);

//             if (!empty($recipient->email)) {
//                 try {
//                     Mail::to($recipient->email)->send(
//                         new DoctrackNotification($docInfo, $recipientFullName)
//                     );
//                 } catch (\Exception $e) {
//                     Log::error("Email to recipient ID {$userId} failed: " . $e->getMessage());
//                 }
//             }
//         }
//     }

//     // ✅ Save file (linked only to the creator's row)
//     if ($fileName) {
//         DoctrackFile::create([
//             'doctrack_id' => $documentTrack->id,
//             'docslip_id'  => $docslip_id,
//             'file'        => $fileName,
//         ]);
//     }

//     // ✅ Final JSON response
//     return response()->json([
//         'success' => true,
//         'id'      => $documentTrack->id,
//         'message' => 'Document successfully submitted!',
//     ]);
// }


public function storeDoctrack(Request $request)
{
    // ✅ Normalize the file input so a SINGLE file and MULTIPLE files are both accepted
    $this->normalizeFileInput($request);

    $request->validate([
        'user_id'     => 'required|integer',
        'doc_type'    => 'required|string',
        'doc_title'   => 'required|string',
        'ctrl_no'     => 'nullable|integer',
        'update_by'   => 'required|array',
        'update_by.*' => 'required|string',
        'file'        => 'nullable|array',
        'file.*'      => 'file|mimes:pdf,doc,docx,jpg,jpeg,png,xls,xlsx,xlsm|max:20480',
    ]);

    $docslip_id = 'CPSU' . mt_rand(10000000, 99999999);

    // ✅ Store ALL uploaded files (multiple files in a single upload)
    $fileNames = $this->storeUploadedFiles($request->file('file'));
    $fileLogs  = $this->joinFileNames($fileNames);

    $creator = User::findOrFail($request->user_id);
    $creatorFullName = $creator->fname . ' ' . $creator->lname;

    // ✅ Control Code coming from the Document Transmittal form
    $ctrlNo = ($request->ctrl_no !== null && $request->ctrl_no !== '') ? (int) $request->ctrl_no : null;

    $documentTrack = Doctrack::create([
        'user_id'       => $creator->id,
        'update_by'     => null,
        'docslip_id'    => $docslip_id,
        'ctrl_no'       => $ctrlNo,
        'doc_type'      => $request->doc_type,
        'doc_title'     => $request->doc_title,
        'user_name'     => $creatorFullName,
        'doctrack_stat' => 1,
    ]);

    LogsTracking::create([
        'docslip_id' => $docslip_id,
        'user_id'    => $creator->id,
        'update_by'  => null,
        'doc_title'  => $request->doc_title,
        'file_logs'  => $fileLogs,
        'logs_status'=> 1,
        'comments'   => null,
    ]);

    $docInfo = (object)[
        'docslip_id' => $docslip_id,
        'doc_title'  => $request->doc_title,
        'doc_type'   => $request->doc_type,
        'user_name'  => $creatorFullName,
    ];

    // Send email to creator
    if ($creator->email) {
        try {
            Mail::to($creator->email)->send(new DoctrackNotification($docInfo, $creatorFullName));
        } catch (\Exception $e) {
            Log::error("Failed to email creator: " . $e->getMessage());
        }
    }

    // Handle update_by recipients
    $finalRecipients = [];

    foreach ($request->update_by as $entry) {
        $users = collect();

        if (Str::startsWith($entry, 'group:')) {
            $groupName = Str::after($entry, 'group:');
            $group = Group::where('group_name', $groupName)->first();
            if ($group) {
                $users = $group->users;
            }

        } elseif (Str::startsWith($entry, 'position:')) {
            $positionId = (int) Str::after($entry, 'position:');
            $users = User::where('position', $positionId)->get();

        } elseif (is_numeric($entry)) {
            $user = User::find((int) $entry);
            if ($user) $users = collect([$user]);
        }

        foreach ($users as $user) {
            if (!$user || isset($finalRecipients[$user->id])) continue;

            $finalRecipients[$user->id] = $user;
        }
    }

    foreach ($finalRecipients as $recipient) {
        $recipientFullName = $recipient->fname . ' ' . $recipient->lname;

        Doctrack::create([
            'user_id'       => $creator->id,
            'update_by'     => $recipient->id,
            'docslip_id'    => $docslip_id,
            'ctrl_no'       => $ctrlNo,
            'doc_type'      => $request->doc_type,
            'doc_title'     => $request->doc_title,
            'user_name'     => $creatorFullName,
            'doctrack_stat' => 2,
        ]);

        LogsTracking::create([
            'docslip_id' => $docslip_id,
            'user_id'    => $creator->id,
            'update_by'  => $recipient->id,
            'doc_title'  => $request->doc_title,
            'file_logs'  => $fileLogs,
            'logs_status'=> 2,
            'comments'   => null,
        ]);

        if ($recipient->email) {
            try {
                Mail::to($recipient->email)->send(new DoctrackNotification($docInfo, $recipientFullName));
            } catch (\Exception $e) {
                Log::error("Failed to email recipient ({$recipient->id}): " . $e->getMessage());
            }
        }
    }

    // ✅ Link every uploaded file to the transaction (one row per file)
    foreach ($fileNames as $fileName) {
        DoctrackFile::create([
            'docslip_id' => $docslip_id,
            'file'       => $fileName,
        ]);
    }

    return response()->json([
        'success' => true,
        'id'      => $documentTrack->id,
        'message' => 'Document successfully submitted!',
    ]);
}

public function storeDoctrackUpdate(Request $request)
{
    // ✅ Normalize the file input so a SINGLE file and MULTIPLE files are both accepted
    $this->normalizeFileInput($request);

    $request->validate([
        'user_id'     => 'required|integer',
        'update_by'   => 'required|integer',
        'docslip_id'  => 'required|string',
        'doc_type'    => 'required|string',
        'doc_title'   => 'required|string',
        'user_name'   => 'required|string',
        'file'        => 'nullable|array',
        'file.*'      => 'file|mimes:pdf,doc,docx,jpg,jpeg,png,xlsm,xlsx,xls|max:20480',
    ]);

    // ✅ Handle file upload (multiple files allowed)
    $fileNames = $this->storeUploadedFiles($request->file('file'));
    $fileLogs  = $this->joinFileNames($fileNames);

    // Create doctrack entry
    $documentTrack = Doctrack::create([
        'user_id'       => $request->user_id,
        'update_by'     => $request->update_by,
        'docslip_id'    => $request->docslip_id,
        'doc_type'      => $request->doc_type,
        'doc_title'     => $request->doc_title,
        'user_name'     => $request->user_name,
        'doctrack_stat' => 2,
    ]);

    // Save file record (replace the previous attachment with the newly uploaded files)
    if (!empty($fileNames)) {
        $storagePath = storage_path('app/doc_track');

        // Delete old files (if any)
        $existingFiles = DoctrackFile::where('docslip_id', $request->docslip_id)->get();
        foreach ($existingFiles as $existingFile) {
            if ($existingFile->file && file_exists($storagePath . '/' . $existingFile->file)) {
                unlink($storagePath . '/' . $existingFile->file);
            }
            $existingFile->delete();
        }

        // ✅ Store every uploaded file (one row per file)
        foreach ($fileNames as $fileName) {
            DoctrackFile::create([
                'docslip_id' => $request->docslip_id,
                'file'       => $fileName,
            ]);
        }
    }

    // Save log entry
    LogsTracking::create([
        'docslip_id' => $request->docslip_id,
        'user_id'    => $request->user_id,
        'update_by'  => $request->update_by,
        'doc_title'  => $request->doc_title,
        'file_logs'  => $fileLogs,
        'logs_status'=> 2,
        'comments'   => null,
    ]);

    // Send email notification to the receiver (update_by)
    $recipient = User::find($request->update_by);
    $creator = User::find($request->user_id);

    if ($recipient && $recipient->email) {
        $docInfo = (object)[
            'docslip_id' => $request->docslip_id,
            'doc_title'  => $request->doc_title,
            'doc_type'   => $request->doc_type,
            'user_name'  => $request->user_name,
        ];

        try {
            Mail::to($recipient->email)->send(new DoctrackNotification($docInfo, $recipient->fname . ' ' . $recipient->lname));
        } catch (\Exception $e) {
            Log::error("Failed to send email to recipient: " . $e->getMessage());
        }
    }

    return redirect()->route('doctrackSlip', ['id' => $documentTrack->id])
        ->with('success', 'New entry with tracking # ' . $documentTrack->docslip_id . ' was added successfully!');
}

// public function update(Request $request, Doctrack $doctrack)
// {
//     $request->validate([
//         'ctrl_no' => 'nullable|string|max:255',
//     ]);

//     $doctrack->update($request->only(['ctrl_no']));

//     return response()->json(['success' => true, 'message' => 'CTRL # updated successfully.']);
// }

 public function update(Request $request, Doctrack $doctrack)
    {
        try {
            $validated = $request->validate([
                'ctrl_no' => 'nullable|string|max:255',
            ]);

            $doctrack->update($request->only(['ctrl_no']));

            return response()->json([
                'success' => true, 
                'message' => 'CTRL # updated successfully.',
                'data' => [
                    'id' => $doctrack->id,
                    'ctrl_no' => $doctrack->ctrl_no
                ]
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('Doctrack update failed: ' . $e->getMessage(), [
                'doctrack_id' => $doctrack->id ?? null,
                'user_id' => auth()->id(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the record.'
            ], 500);
        }
    }

// public function storeDoctrackUpdate(Request $request)
// {
//     // Validate the request
//     $request->validate([
//         'user_id' => 'required|integer',
//         'update_by' => 'required|integer',
//         'doc_type' => 'required|string',
//         'doc_title' => 'required|string',
//         'user_name' => 'required|string',
//         'file' => 'nullable|file|mimes:pdf,doc,docx,jpg,jpeg,png,xlsm,xlsx,xls|max:20480',
//     ]);

//     // Store the document in the database
//     $documentTrack = Doctrack::create([
//         'user_id' => $request->user_id,
//         'update_by' => $request->update_by,
//         'docslip_id' => $request->docslip_id,
//         'doc_type' => $request->doc_type,
//         'doc_title' => $request->doc_title,
//         'user_name' => $request->user_name,
//         'doctrack_stat' => 2,
//     ]);

//     $storagePath = storage_path('app/doc_track');

//     // Check if a new file is uploaded
//     if ($request->hasFile('file')) {
//         $file = $request->file('file');
//         $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
//         $extension = $file->getClientOriginalExtension();
//         $fileName = $originalName . '.' . $extension;

//         $i = 1;
//         while (file_exists($storagePath . '/' . $fileName)) {
//             $fileName = $originalName . ' Copy ' . $i . '.' . $extension;
//             $i++;
//         }

//         // Delete old file if it exists
//         $existingFile = DoctrackFile::where('docslip_id', $request->docslip_id)->first();
//         if ($existingFile && file_exists($storagePath . '/' . $existingFile->file)) {
//             unlink($storagePath . '/' . $existingFile->file);
//         }

//         // Store new file
//         $file->storeAs('doc_track', $fileName);

//         // Update existing record (no duplicate)
//         DoctrackFile::updateOrCreate(
//             ['docslip_id' => $request->docslip_id], // match
//             [
//                 'doctrack_id' => $documentTrack->id,
//                 'file' => $fileName,
//             ]
//         );
//     } else {
//         // No new file uploaded: retain existing file reference
//         $existingFile = DoctrackFile::where('docslip_id', $request->docslip_id)->first();
//         if ($existingFile) {
//             $existingFile->update([
//                 'doctrack_id' => $documentTrack->id,
//             ]);
//         }
//     }

//     return redirect()->route('doctrackSlip', ['id' => $documentTrack->id])
//         ->with('success', 'New entry with tracking # ' . $documentTrack->docslip_id . ' was added successfully!');
// }



// public function uploadFile(Request $request)
// {
//     try {
//         // Corrected table name from 'doctracks' to 'doctrack_slip'
//         $request->validate([
//             'docslip_id' => 'required|string|exists:doctrack_slip,docslip_id',
//             'file' => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png,xlsm,xlsx,xls|max:20480',
//         ]);

//         $docslip_id = $request->docslip_id;
//         $file = $request->file('file');
//         $storagePath = storage_path('app/doc_track');

//         // Make sure the folder exists
//         if (!file_exists($storagePath)) {
//             mkdir($storagePath, 0755, true);
//         }

//         $originalName = $file->getClientOriginalName();
//         $filePath = $storagePath . '/' . $originalName;

//         // Delete old file from DB and disk
//         $existingFile = DoctrackFile::where('docslip_id', $docslip_id)->first();
//         if ($existingFile && file_exists($storagePath . '/' . $existingFile->file)) {
//             unlink($storagePath . '/' . $existingFile->file);
//         }

//         // Save new file
//         $file->storeAs('doc_track', $originalName);

//         // Insert or update file info in DB
//         DoctrackFile::updateOrCreate(
//             ['docslip_id' => $docslip_id],
//             ['file' => $originalName]
//         );

//         return response()->json(['success' => true]);

//     } catch (\Illuminate\Validation\ValidationException $e) {
//         return response()->json([
//             'success' => false,
//             'message' => $e->validator->errors()->first()
//         ], 422);
//     } catch (\Exception $e) {
//         Log::error('Upload error: ' . $e->getMessage());
//         return response()->json([
//             'success' => false,
//             'message' => 'Server error: ' . $e->getMessage()
//         ], 500);
//     }
// }

public function uploadFile(Request $request)
{
    // ✅ Normalize so a SINGLE file and MULTIPLE files are both accepted
    $this->normalizeFileInput($request);

    $request->validate([
        'file' => 'required|array',
        'file.*' => 'file|max:10240',
        'docslip_id' => 'required'
    ]);

    try {
        if ($request->hasFile('file')) {
            // Ensure the directory exists
            $directory = storage_path('app/doc_track');
            if (!File::exists($directory)) {
                File::makeDirectory($directory, 0755, true);
            }

            $files = $request->file('file');
            $files = is_array($files) ? $files : [$files];

            $saved = 0;

            // ✅ Move every uploaded file to storage/app/doc_track
            foreach ($files as $file) {
                if (!($file instanceof UploadedFile) || !$file->isValid()) {
                    continue;
                }

                $fileName = time() . '_' . $file->getClientOriginalName();
                $file->move($directory, $fileName);

                // Save to database
                DoctrackFile::create([
                    'docslip_id' => $request->docslip_id,
                    'file' => $fileName,
                    // Add any other fields you need
                ]);

                $saved++;
            }

            return response()->json([
                'success' => true, 
                'message' => $saved > 1
                    ? $saved . ' files uploaded successfully'
                    : 'File uploaded successfully'
            ]);
        }
        
        return response()->json([
            'success' => false, 
            'message' => 'No file uploaded'
        ]);
        
    } catch (\Exception $e) {
        return response()->json([
            'success' => false, 
            'message' => 'Error uploading file: ' . $e->getMessage()
        ], 500);
    }

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
    $filePath = storage_path('storage/doc_track/' . $documentTrack->file);

    if (file_exists($filePath)) {
        // force download with correct extension
        return response()->download($filePath, $documentTrack->file);
    } else {
        return redirect()->back()->with('error', 'File not found.');
    }
}

// public function slipMonitoring($docslip_id)
// {
//     $documentTrackid = Doctrack::with('doctrackFile')
//         ->where('docslip_id', $docslip_id)
//         ->get();

//     $user = auth()->user(); // authenticated user

//     // ✅ Get the creator's user_id (first entry assumes the creator)
//     $creatorId = Doctrack::where('docslip_id', $docslip_id)->value('user_id');

//     // ✅ Role-based counts
//     $recordsOfficerCount = $user->role === 'records_officer' 
//         ? RoutingSlip::where('route_status', 2)->count() 
//         : 0;

//     $superUserCount = $user->role === 'super_user' 
//         ? RoutingSlip::where('route_status', 1)->count() 
//         : 0;

//     return view('slip.docMonitoring', compact(
//         'documentTrackid',
//         'superUserCount',
//         'recordsOfficerCount',
//         'docslip_id',
//         'creatorId' // 👈 pass to the blade
//     ));
// }

// updated on 08/11/2025

// public function slipMonitoring($docslip_id)
// {
//     $documentTrackid = Doctrack::with('doctrackFile')
//         ->where('docslip_id', $docslip_id)
//         ->get();

//     $user = auth()->user(); // authenticated user

//     // ✅ Update viewed status if not yet viewed
//     $log = LogsTracking::where('docslip_id', $docslip_id)
//         ->where('update_by', $user->id) // or 'user_id' depending on your use case
//         ->latest()
//         ->first();

//     if ($log && !$log->viewed_status) {
//         $log->timestamps = false; // prevent updated_at from changing
//         $log->update([
//             'viewed_status' => 1,
//             'viewed_at' => now(),
//         ]);
//     }

//     // ✅ Get the creator's user_id
//     $creatorId = Doctrack::where('docslip_id', $docslip_id)->value('user_id');

//     // ✅ Role-based counts
//     $recordsOfficerCount = $user->role === 'records_officer' 
//         ? RoutingSlip::where('route_status', 2)->count() 
//         : 0;

//     $superUserCount = $user->role === 'super_user' 
//         ? RoutingSlip::where('route_status', 1)->count() 
//         : 0;

//     return view('slip.docMonitoring', compact(
//         'documentTrackid',
//         'superUserCount',
//         'recordsOfficerCount',
//         'docslip_id',
//         'creatorId'
//     ));
// }

// public function slipMonitoring($docslip_id)
// {
//     $documentTrackid = Doctrack::with('doctrackFile')
//         ->where('docslip_id', $docslip_id)
//         ->get();

//     $user = auth()->user();

//     // Update viewed status for this user if not yet viewed
//     $log = LogsTracking::where('docslip_id', $docslip_id)
//         ->where('update_by', $user->id)
//         ->latest()
//         ->first();

//     if ($log && !$log->viewed_status) {
//         $log->timestamps = false;
//         $log->update([
//             'viewed_status' => 1,
//             'viewed_at' => now(),
//         ]);
//     }

//     // Get latest viewed date for this slip (by any user)
//     $lastViewed = LogsTracking::where('docslip_id', $docslip_id)
//         ->whereNotNull('viewed_at')
//         ->orderByDesc('viewed_at')
//         ->first();

//     $creatorId = Doctrack::where('docslip_id', $docslip_id)->value('user_id');

//     $recordsOfficerCount = $user->role === 'records_officer' 
//         ? RoutingSlip::where('route_status', 2)->count() 
//         : 0;

//     $superUserCount = $user->role === 'super_user' 
//         ? RoutingSlip::where('route_status', 1)->count() 
//         : 0;

//     return view('slip.docMonitoring', compact(
//         'documentTrackid',
//         'superUserCount',
//         'recordsOfficerCount',
//         'docslip_id',
//         'creatorId',
//         'lastViewed' // ✅ pass to view
//     ));
// }

public function slipMonitoring($docslip_id)
{
      $users = User::orderBy('fname', 'asc')   // ✅ sort ascending
                 ->orderBy('lname', 'asc')
                 ->get();

    $user = auth()->user(); // ✅ get logged-in user

    // Get creator id of the slip
    $creatorId = Doctrack::where('docslip_id', $docslip_id)->value('user_id');

    // Base query
    $documentTrackid = Doctrack::with(['doctrackFile', 'doctrackFiles'])
        ->where('docslip_id', $docslip_id);

    // If current user is NOT the creator, filter by update_by
    if ($user->id !== $creatorId) {
        $documentTrackid->where('update_by', $user->id);
    }

    $documentTrackid = $documentTrackid->get();

    // Update viewed status for this user if not yet viewed
    $log = LogsTracking::where('docslip_id', $docslip_id)
        ->where('update_by', $user->id)
        ->latest()
        ->first();

    if ($log && !$log->viewed_status) {
        $log->timestamps = false;
        $log->update([
            'viewed_status' => 1,
            'viewed_at' => now(),
        ]);
    }

    // Get latest viewed date for this slip (by any user)
    $lastViewed = LogsTracking::where('docslip_id', $docslip_id)
        ->whereNotNull('viewed_at')
        ->orderByDesc('viewed_at')
        ->first();

    $recordsOfficerCount = $user->role === 'records_officer' 
        ? RoutingSlip::where('route_status', 2)->count() 
        : 0;

    $superUserCount = $user->role === 'super_user' 
        ? RoutingSlip::where('route_status', 1)->count() 
        : 0;

    return view('slip.docMonitoring', compact(
        'documentTrackid',
        'superUserCount',
        'recordsOfficerCount',
        'docslip_id',
        'creatorId',
        'lastViewed',
        'users'
    ));
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
// public function updateSlipStatus(Request $request, $id)
// {
//     $document = Doctrack::findOrFail($id);

//     // Save status and comment if provided
//     if ($request->has('comments')) {
//         $document->comments = $request->comments;
//     }

//     if ($request->has('doctrack_stat')) {
//         $document->doctrack_stat = $request->doctrack_stat;
//     }

//     $document->save();

//     return back()->with('success', 'Comment saved successfully!');
// }


public function updateSlipStatus(Request $request, $id)
{
    $document = Doctrack::findOrFail($id);

    // Save old values for logging
    $oldStatus = $document->doctrack_stat;
    $oldComment = $document->comments;

    // Update status and/or comment if present
    if ($request->has('comments')) {
        $document->comments = $request->comments;
    }

    if ($request->has('doctrack_stat')) {
        $document->doctrack_stat = $request->doctrack_stat;
    }

    $document->save();

    // 🔽 Insert into logs_tracking
    LogsTracking::create([
    'docslip_id'  => $document->docslip_id,
    'user_id'     => $document->user_id,         // creator
    'update_by'   => auth()->id(),               // updater (e.g., GMAR PALMA)
    'doc_title'   => $document->doc_title,
    'file_logs'   => $this->joinFileNames($document->doctrackFiles->pluck('file')->all()) ?? optional($document->doctrackFile)->file,
    'logs_status' => $document->doctrack_stat,   // 3 = acknowledged, etc.
    'comments'    => $request->comments ?? null,
]);

    return back()->with('success', 'Status and comment saved successfully!');
}

// public function deleteSlip($id)
// {
//     // Find the document by ID
//     $documentTrack = Doctrack::findOrFail($id);

//     // Delete the document
//     $documentTrack->delete();

//     // Redirect to doctrackslip-list with a success message
//     return redirect()->route('doctrackSlip')
//         ->with('success', 'Document deleted successfully!');
// }


public function viewFile($filename)
{
    // Remove any path traversal attempts
    $filename = basename($filename);
    
    // Define possible file locations
    $possiblePaths = [
        storage_path('app/doc_track/' . $filename),
        storage_path('app/public/doc_track/' . $filename),
        public_path('storage/doc_track/' . $filename),
    ];
    
    $foundPath = null;
    
    foreach ($possiblePaths as $path) {
        if (File::exists($path)) {
            $foundPath = $path;
            break;
        }
    }
    
    if (!$foundPath) {
        abort(404, 'File not found: ' . $filename);
    }
    
    $file = File::get($foundPath);
    $type = File::mimeType($foundPath);
    
    $response = Response::make($file, 200);
    $response->header("Content-Type", $type);
    
    // Set content disposition based on file type
    if (in_array($type, ['application/pdf', 'image/jpeg', 'image/png', 'image/gif'])) {
        $response->header('Content-Disposition', 'inline; filename="' . $filename . '"');
    } else {
        $response->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }
    
    return $response;
}

/**
 * ✅ Normalize the "file" input of the request into an array.
 *
 * This allows a SINGLE file (name="file") and MULTIPLE files
 * (name="file[]") to be handled the same way by the controller.
 */
protected function normalizeFileInput(Request $request, string $key = 'file')
{
    $file = $request->file($key);

    if ($file instanceof UploadedFile) {
        $request->files->set($key, [$file]);
    }

    return $request;
}

/**
 * ✅ Store one or many uploaded files inside storage/app/doc_track.
 *
 * Returns the list of the stored file names.
 */
protected function storeUploadedFiles($files): array
{
    if ($files instanceof UploadedFile) {
        $files = [$files];
    }

    if (!is_array($files)) {
        return [];
    }

    $storagePath = storage_path('app/doc_track');

    if (!File::exists($storagePath)) {
        File::makeDirectory($storagePath, 0755, true);
    }

    $stored = [];

    foreach ($files as $file) {
        if (!($file instanceof UploadedFile) || !$file->isValid()) {
            continue;
        }

        $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $extension    = $file->getClientOriginalExtension();
        $fileName     = $originalName . ($extension ? '.' . $extension : '');

        $i = 1;
        while (file_exists($storagePath . '/' . $fileName)) {
            $fileName = $originalName . ' Copy ' . $i . ($extension ? '.' . $extension : '');
            $i++;
        }

        $file->storeAs('doc_track', $fileName);

        $stored[] = $fileName;
    }

    return $stored;
}

/**
 * ✅ Join the stored file names so they can be saved on the logs tracking table.
 */
protected function joinFileNames(array $fileNames): ?string
{
    if (empty($fileNames)) {
        return null;
    }

    return mb_substr(implode(', ', $fileNames), 0, 255);
}

}
