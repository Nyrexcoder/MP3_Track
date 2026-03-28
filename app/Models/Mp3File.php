<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mp3File extends Model
{
    protected $fillable = [
        'user_id',
        'folder_id',
        'title',
        'artist',
        'file_path',
        'size',
        'duration',
        'mime_type',
        'visibility',
        'password',
    ];

    protected $hidden = [
        'password',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the stream URL for the MP3 file.
     */
    public function getStreamUrlAttribute()
    {
        return \Illuminate\Support\Facades\Storage::disk('s3')->temporaryUrl(
            $this->file_path,
            now()->addHours(1)
        );
    }
}
