<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonNote extends Model
{
    use HasUuids;
    
    protected $fillable = [
        'user_id',
        'course_id',
        'lesson_id',
        'content',
        'video_timestamp',
        'color',
        'is_pinned',
    ];
    
    protected $casts = [
        'video_timestamp' => 'integer',
        'is_pinned' => 'boolean',
    ];
    
    protected $appends = ['formatted_timestamp'];
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
    
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }
    
    public function getFormattedTimestampAttribute(): ?string
    {
        if (!$this->video_timestamp) {
            return null;
        }
        
        $minutes = floor($this->video_timestamp / 60);
        $seconds = $this->video_timestamp % 60;
        return sprintf('%02d:%02d', $minutes, $seconds);
    }
}
