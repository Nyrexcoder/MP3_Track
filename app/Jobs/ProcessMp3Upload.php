<?php

namespace App\Jobs;

use App\Models\Mp3File;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ProcessMp3Upload implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $tempPath;
    protected $data;

    public function __construct($tempPath, $data)
    {
        $this->tempPath = $tempPath;
        $this->data = $data;
    }

    public function handle()
    {
        try {
            // Check if file exists on local disk
            if (!Storage::disk('local')->exists($this->tempPath)) {
                \Log::error("Temp file not found on local disk: " . $this->tempPath);
                return;
            }

            // Get the absolute path for getID3
            $fullTempPath = Storage::disk('local')->path($this->tempPath);

            // Extract Metadata
            $getID3 = new \getID3;
            $fileInfo = $getID3->analyze($fullTempPath);
            
            $title = $fileInfo['tags']['id3v2']['title'][0] 
                     ?? $fileInfo['tags']['id3v1']['title'][0] 
                     ?? basename($this->tempPath)
                     ?? 'Unknown Title';
                     
            $artist = $fileInfo['tags']['id3v2']['artist'][0] 
                      ?? $fileInfo['tags']['id3v1']['artist'][0] 
                      ?? 'Unknown Artist';
            
            $duration = isset($fileInfo['playtime_seconds']) ? (int)$fileInfo['playtime_seconds'] : null;

            // Move to S3
            $s3Path = 'mp3s/' . basename($this->tempPath);
            $stream = fopen($fullTempPath, 'r+');
            
            // Check if S3 disk is working
            Storage::disk('s3')->put($s3Path, $stream);
            
            if (is_resource($stream)) {
                fclose($stream);
            }

            // Save to DB
            Mp3File::create([
                'user_id' => $this->data['user_id'],
                'folder_id' => $this->data['folder_id'] ?? null,
                'title' => $title,
                'artist' => $artist,
                'file_path' => $s3Path,
                'size' => filesize($fullTempPath),
                'duration' => $duration,
                'mime_type' => mime_content_type($fullTempPath) ?: 'audio/mpeg',
                'visibility' => $this->data['visibility'] ?? 'public',
                'password' => $this->data['password'] ?? null,
            ]);

            // Clean up temp file from local disk
            Storage::disk('local')->delete($this->tempPath);
        } catch (\Exception $e) {
            \Log::error("Queue Job Error: " . $e->getMessage());
            throw $e; // Retry job
        }
    }
}
