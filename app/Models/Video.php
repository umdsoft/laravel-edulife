<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Video extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'lesson_id',
        'original_path',
        'original_name',
        'hls_path',
        'size',
        'mime_type',
        'duration',
        'qualities',
        'status',
        'transcoding_progress',
        'error_message',
    ];

    protected $casts = [
        'qualities' => 'array',
        'size' => 'integer',
        'duration' => 'integer',
        'transcoding_progress' => 'integer',
    ];

    // Accessors
    public function getHlsUrlAttribute(): ?string
    {
        return $this->hls_path ? Storage::disk('public')->url($this->hls_path) : null;
    }

    public function getOriginalUrlAttribute(): ?string
    {
        return $this->original_path ? Storage::disk('public')->url($this->original_path) : null;
    }

    // Relationships
    public function lesson()
    {
        return $this->belongsTo(Lesson::class);
    }

    public function chapters()
    {
        return $this->hasMany(VideoChapter::class)->orderBy('timestamp');
    }
}
