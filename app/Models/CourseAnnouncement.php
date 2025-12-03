<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CourseAnnouncement extends Model
{
    use HasUuids, SoftDeletes;
    
    protected $fillable = [
        'course_id',
        'teacher_id',
        'title',
        'content',
        'type',
        'is_pinned',
        'send_notification',
        'published_at',
    ];
    
    protected $casts = [
        'is_pinned' => 'boolean',
        'send_notification' => 'boolean',
        'published_at' => 'datetime',
    ];
    
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
    
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }
    
    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at')->where('published_at', '<=', now());
    }
    
    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }
}
