<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class LessonAttachment extends Model
{
    use HasUuids;
    
    protected $fillable = [
        'lesson_id',
        'title',
        'file_name',
        'file_path',
        'file_type',
        'mime_type',
        'file_size',
        'download_count',
        'is_downloadable',
        'sort_order',
    ];
    
    protected $casts = [
        'file_size' => 'integer',
        'download_count' => 'integer',
        'is_downloadable' => 'boolean',
        'sort_order' => 'integer',
    ];
    
    protected $appends = ['file_url', 'formatted_size'];
    
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }
    
    public function getFileUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->file_path);
    }
    
    public function getFormattedSizeAttribute(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
    
    public function incrementDownload(): void
    {
        $this->increment('download_count');
    }
}
