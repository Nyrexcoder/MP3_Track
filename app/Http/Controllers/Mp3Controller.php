<?php

namespace App\Http\Controllers;

use App\Models\Mp3File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class Mp3Controller extends Controller
{
    /**
     * List all MP3 files.
     */
    public function index()
    {
        $files = Mp3File::latest()->get()->map(function ($file) {
            $file->stream_url = Storage::disk('s3')->temporaryUrl($file->file_path, now()->addHours(1));
            return $file;
        });

        return response()->json([
            'success' => true,
            'data' => $files
        ]);
    }

    /**
     * Upload a new MP3 file.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:mp3,wav,ogg|max:51200', // max 50MB
            'title' => 'required|string|max:255',
            'artist' => 'nullable|string|max:255',
            'visibility' => 'required|in:public,private',
            'password' => 'required_if:visibility,private|nullable|string|min:4',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $file = $request->file('file');
        $path = $file->store('mp3s', 's3');

        $mp3File = Mp3File::create([
            'user_id' => auth()->id(),
            'title' => $request->title,
            'artist' => $request->artist,
            'file_path' => $path,
            'size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'visibility' => $request->visibility,
            'password' => $request->visibility === 'private' ? bcrypt($request->password) : null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'MP3 uploaded successfully',
            'data' => $mp3File
        ], 201);
    }

    /**
     * Show details of a single MP3 file.
     */
    public function show($id)
    {
        $file = Mp3File::find($id);

        if (!$file) {
            return response()->json([
                'success' => false,
                'message' => 'File not found'
            ], 404);
        }

        $file->stream_url = Storage::disk('s3')->temporaryUrl($file->file_path, now()->addHours(1));

        return response()->json([
            'success' => true,
            'data' => $file
        ]);
    }

    /**
     * Delete an MP3 file.
     */
    public function destroy($id)
    {
        $file = Mp3File::find($id);

        if (!$file) {
            return response()->json([
                'success' => false,
                'message' => 'File not found'
            ], 404);
        }

        // Delete from S3
        if (Storage::disk('s3')->exists($file->file_path)) {
            Storage::disk('s3')->delete($file->file_path);
        }

        // Delete from database
        $file->delete();

        return response()->json([
            'success' => true,
            'message' => 'File deleted successfully'
        ]);
    }
}
