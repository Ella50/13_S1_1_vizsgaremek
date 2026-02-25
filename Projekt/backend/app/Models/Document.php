<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'originalName',
        'fileName',
        'filePath',
        'mimeType',
        'fileSize',
        'type'
    ];

    protected $casts = [
        'fileSize' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Helper metódus a fájl URL-jének lekéréséhez
    public function getUrlAttribute()
    {
        return Storage::disk('public')->url($this->file_path);
    }

    // Helper metódus a fájl méretének formázásához
    public function getFormattedSizeAttribute()
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}