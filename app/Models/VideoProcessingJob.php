<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VideoProcessingJob extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'lesson_id',
        'video_upload_id',
        'source_url',
        'status',
        'progress',
        'current_step',
        'duration',
        'width',
        'height',
        'codec',
        'output_qualities',
        'output_url',
        'thumbnail_url',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'output_qualities' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    // Accessors
    public function getResolutionAttribute(): ?string
    {
        if (!$this->width || !$this->height) return null;
        return "{$this->width}x{$this->height}";
    }

    public function getOutputUrlFullAttribute(): ?string
    {
        return $this->output_url ? asset('storage/' . $this->output_url) : null;
    }

    public function getThumbnailUrlFullAttribute(): ?string
    {
        return $this->thumbnail_url ? asset('storage/' . $this->thumbnail_url) : null;
    }

    // Relationships
    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    public function videoUpload()
    {
        return $this->belongsTo(VideoUpload::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    // Helper Methods
    public function updateProgress(int $progress, string $step): void
    {
        $this->update([
            'progress' => $progress,
            'current_step' => $step,
        ]);
    }

    public function markAsCompleted(string $outputUrl, string $thumbnailUrl): void
    {
        $this->update([
            'status' => 'completed',
            'progress' => 100,
            'output_url' => $outputUrl,
            'thumbnail_url' => $thumbnailUrl,
            'completed_at' => now(),
        ]);
        
        // Update lesson video status
        $this->lesson->update(['video_status' => 'ready']);
    }

    public function markAsFailed(string $error): void
    {
        $this->update([
            'status' => 'failed',
            'error_message' => $error,
        ]);
        
        $this->lesson->update(['video_status' => 'failed']);
    }
}
