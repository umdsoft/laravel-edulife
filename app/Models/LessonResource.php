<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LessonResource extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'lesson_id',
        'title',
        'type',
        'file_path',
        'file_name',
        'file_size',
        'url',
        'code_content',
        'code_language',
        'sort_order',
        'downloads_count',
    ];

    // Accessors
    public function getFileUrlAttribute(): ?string
    {
        return $this->file_path ? asset('storage/' . $this->file_path) : null;
    }

    public function getFormattedSizeAttribute(): string
    {
        if (!$this->file_size) return '';
        
        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size;
        $unit = 0;
        
        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }
        
        return round($size, 2) . ' ' . $units[$unit];
    }

    // Relationships
    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    // Scopes
    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // Helper Methods
    public function incrementDownloads(): void
    {
        $this->increment('downloads_count');
    }
}
