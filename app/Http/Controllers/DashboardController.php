<?php

namespace App\Http\Controllers;

use App\Models\Mp3File;
use App\Models\Folder;
use App\Jobs\ProcessMp3Upload;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $query = Folder::where('user_id', auth()->id());
        
        if ($request->has('folder')) {
            $currentFolder = Folder::where('user_id', auth()->id())->findOrFail($request->folder);
            
            // Check folder password if private
            if ($currentFolder->visibility === 'private' && !session()->has("folder_auth_{$currentFolder->id}")) {
                return view('folder-login', compact('currentFolder'));
            }
            
            $files = Mp3File::where('folder_id', $currentFolder->id)->latest()->get();
            $folders = [];
        } else {
            $currentFolder = null;
            $folders = $query->latest()->get();
            $files = Mp3File::where('user_id', auth()->id())->whereNull('folder_id')->latest()->get();
        }

        // Count pending jobs for the user
        $pendingJobs = \DB::table('jobs')
            ->where('queue', 'default')
            ->count();

        return view('dashboard', compact('folders', 'files', 'currentFolder', 'pendingJobs'));
    }

    public function queueStatus(Request $request)
    {
        $count = \DB::table('jobs')
            ->where('queue', 'default')
            ->count();
            
        // Get files that were processed since the last check
        $lastCheck = $request->query('last_check');
        $newFiles = [];
        
        if ($lastCheck && is_numeric($lastCheck)) {
            $newFiles = Mp3File::where('user_id', auth()->id())
                ->where('created_at', '>', date('Y-m-d H:i:s', (int)$lastCheck))
                ->get();
        }
            
        return response()->json([
            'count' => $count,
            'new_files' => $newFiles,
            'timestamp' => time()
        ]);
    }

    public function bulkAction(Request $request)
    {
        $request->validate([
            'file_ids' => 'nullable|array',
            'file_ids.*' => 'exists:mp3_files,id',
            'folder_ids' => 'nullable|array',
            'folder_ids.*' => 'exists:folders,id',
            'action' => 'required|in:delete,move',
            'folder_id' => 'required_if:action,move|nullable|exists:folders,id'
        ]);

        $message = "";

        if ($request->action === 'delete') {
            $deletedFiles = 0;
            $deletedFolders = 0;

            if ($request->has('file_ids')) {
                $files = Mp3File::where('user_id', auth()->id())
                    ->whereIn('id', $request->file_ids)
                    ->get();
                foreach ($files as $file) {
                    Storage::disk('s3')->delete($file->file_path);
                    $file->delete();
                    $deletedFiles++;
                }
            }

            if ($request->has('folder_ids')) {
                $folders = Folder::where('user_id', auth()->id())
                    ->whereIn('id', $request->folder_ids)
                    ->get();
                foreach ($folders as $folder) {
                    foreach ($folder->mp3Files as $file) {
                        Storage::disk('s3')->delete($file->file_path);
                    }
                    $folder->delete();
                    $deletedFolders++;
                }
            }

            $message = "Deleted $deletedFiles files and $deletedFolders folders.";
            return response()->json(['success' => true, 'message' => $message]);
        }

        if ($request->action === 'move') {
            if ($request->has('file_ids')) {
                Mp3File::where('user_id', auth()->id())
                    ->whereIn('id', $request->file_ids)
                    ->update(['folder_id' => $request->folder_id]);
                
                $message = count($request->file_ids) . " files moved successfully.";
            }
            return response()->json(['success' => true, 'message' => $message]);
        }

        return response()->json(['success' => false, 'message' => 'Invalid action.'], 400);
    }

    public function unlockFolder(Request $request, $id)
    {
        $folder = Folder::where('user_id', auth()->id())->findOrFail($id);
        
        if (Hash::check($request->password, $folder->password)) {
            session(["folder_auth_{$folder->id}" => true]);
            return redirect()->route('dashboard', ['folder' => $folder->id]);
        }

        return back()->withErrors(['password' => 'Invalid folder password']);
    }

    public function createFolder(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'visibility' => 'required|in:public,private',
            'password' => 'required_if:visibility,private|nullable|string|min:4',
        ]);

        Folder::create([
            'user_id' => auth()->id(),
            'name' => $request->name,
            'visibility' => $request->visibility,
            'password' => $request->visibility === 'private' ? bcrypt($request->password) : null,
        ]);

        return back()->with('status', 'Folder created successfully!');
    }

    public function store(Request $request)
    {
        // Basic validation for overall structure
        $request->validate([
            'files.*' => 'required|file|max:1024000', // Basic check for file and size
            'visibility' => 'nullable|in:public,private',
            'password' => 'required_if:visibility,private|nullable|string|min:4',
            'folder_id' => 'nullable|exists:folders,id',
        ]);

        $files = $request->file('files');
        $uploadedCount = 0;
        $skippedCount = 0;

        foreach ($files as $file) {
            try {
                // Check if the file is actually an audio file
                // This is better than strict validation for folder uploads which might contain hidden files
                $allowedExtensions = ['mp3', 'wav', 'ogg', 'mpeg'];
                $ext = strtolower($file->getClientOriginalExtension());
                $mime = $file->getMimeType();

                if (!in_array($ext, $allowedExtensions) && !str_starts_with($mime, 'audio/')) {
                    $skippedCount++;
                    continue; // Skip non-audio files
                }

                // Save to local storage first (using 'local' disk which is storage/app)
                $tempName = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();
                $tempPath = $file->storeAs('temp_uploads', $tempName, 'local');

                // Prepare data for the job
                $data = [
                    'user_id' => auth()->id(),
                    'folder_id' => $request->folder_id,
                    'visibility' => $request->visibility ?? 'public',
                    'password' => $request->visibility === 'private' ? bcrypt($request->password) : null,
                ];

                // Dispatch Job
                ProcessMp3Upload::dispatch($tempPath, $data);

                $uploadedCount++;
            } catch (\Exception $e) {
                \Log::error('Upload Error: ' . $e->getMessage());
                // Continue to next file if one fails
                $skippedCount++;
            }
        }

        $message = "{$uploadedCount} files added to queue.";
        if ($skippedCount > 0) {
            $message .= " {$skippedCount} non-audio files skipped.";
        }

        return response()->json([
            'success' => true, 
            'message' => $message
        ]);
    }

    public function stream(Request $request, $id)
    {
        $file = Mp3File::findOrFail($id);

        // Check if file is in a private folder
        if ($file->folder_id) {
            $folder = Folder::findOrFail($file->folder_id);
            if ($folder->visibility === 'private' && !session()->has("folder_auth_{$folder->id}")) {
                return response()->json(['error' => 'Folder access denied'], 403);
            }
        }

        // Individual file password check
        if ($file->visibility === 'private') {
            if (!$request->has('password') || !Hash::check($request->password, $file->password)) {
                return response()->json(['error' => 'Invalid file password'], 403);
            }
        }

        // Return a direct stream response instead of a signed URL
        // This bypasses Nginx/MinIO signature issues and is very secure
        if (!Storage::disk('s3')->exists($file->file_path)) {
            return response()->json(['error' => 'File not found on storage'], 404);
        }

        $headers = [
            'Content-Type' => $file->mime_type ?? 'audio/mpeg',
            'Content-Length' => $file->size,
            'Content-Disposition' => 'inline; filename="' . $file->title . '.mp3"',
            'Accept-Ranges' => 'bytes',
        ];

        return response()->stream(function() use ($file) {
            $stream = Storage::disk('s3')->readStream($file->file_path);
            fpassthru($stream);
            if (is_resource($stream)) {
                fclose($stream);
            }
        }, 200, $headers);
    }

    public function destroy($id)
    {
        $file = Mp3File::where('user_id', auth()->id())->findOrFail($id);
        Storage::disk('s3')->delete($file->file_path);
        $file->delete();

        return back()->with('status', 'File deleted successfully!');
    }

    public function destroyFolder($id)
    {
        $folder = Folder::where('user_id', auth()->id())->findOrFail($id);
        
        // Optionally delete all files in folder from S3
        foreach ($folder->mp3Files as $file) {
            Storage::disk('s3')->delete($file->file_path);
        }
        
        $folder->delete();
        return redirect()->route('dashboard')->with('status', 'Folder and its contents deleted.');
    }
}
