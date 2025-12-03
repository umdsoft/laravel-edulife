<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lesson extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'module_id',
        'course_id',
        'title',
        'description',
        'type',
        'sort_order',
        'video_url',
        'source_url',
        'video_duration',
        'video_qualities',
        'video_status',
        'text_content',
        'reading_time',
        'is_free',
        'is_preview',
    ];

    protected function casts(): array
    {
        return [
            'video_qualities' => 'array',
            'is_free' => 'boolean',
            'is_preview' => 'boolean',
        ];
    }

    // Accessors
    public function getVideoUrlAttribute($value): ?string
    {
        return $value ? asset('storage/' . $value) : null;
    }

    public function getFormattedDurationAttribute(): string
    {
        if (!$this->video_duration) return '0m';
        $hours = floor($this->video_duration / 60);
        $minutes = $this->video_duration % 60;
        return $hours > 0 ? "{$hours}h {$minutes}m" : "{$minutes}m";
    }

    public function getFormattedReadingTimeAttribute(): string
    {
        return $this->reading_time ? "{$this->reading_time} min read" : '';
    }

    // Relationships
    public function module()
    {
        return $this->belongsTo(Module::class);
    }

    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function resources()
    {
        return $this->hasMany(LessonResource::class)->orderBy('sort_order');
    }

    public function tests()
    {
        return $this->morphMany(Test::class, 'testable');
    }

    public function progress()
    {
        return $this->hasMany(LessonProgress::class);
    }

    public function attachments()
    {
        return $this->hasMany(LessonAttachment::class)->orderBy('sort_order');
    }

    // Scopes
    public function scopeFree($query)
    {
        return $query->where('is_free', true);
    }

    public function scopePreview($query)
    {
        return $query->where('is_preview', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeVideoReady($query)
    {
        return $query->where('video_status', 'ready');
    }

    // Helper Methods
    public function isCompletedBy(User $user): bool
    {
        return $this->progress()
            ->where('user_id', $user->id)
            ->where('is_completed', true)
            ->exists();
    }

    public function canBeAccessedBy(User $user): bool
    {
        if ($this->is_free || $this->is_preview) return true;
        return $this->course->isEnrolledBy($user);
    }
}
