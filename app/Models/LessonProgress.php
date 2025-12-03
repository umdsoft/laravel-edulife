<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LessonProgress extends Model
{
    use HasUuids;
    
    protected $fillable = [
        'user_id',
        'enrollment_id',
        'course_id',
        'lesson_id',
        'is_completed',
        'completed_at',
        'watch_time',
        'last_position',
        'total_duration',
        'watch_percentage',
        'is_read',
        'read_at',
        'xp_awarded',
        'xp_amount',
    ];
    
    protected $casts = [
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
        'watch_time' => 'integer',
        'last_position' => 'integer',
        'total_duration' => 'integer',
        'watch_percentage' => 'integer',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'xp_awarded' => 'boolean',
        'xp_amount' => 'integer',
    ];
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }
    
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
    
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }
    
    /**
     * Video progress yangilash
     */
    public function updateVideoProgress(int $position, int $duration): void
    {
        $this->last_position = $position;
        $this->total_duration = $duration;
        
        // Watch percentage hisoblash
        if ($duration > 0) {
            $this->watch_percentage = min(100, (int) round(($position / $duration) * 100));
        }
        
        // 90% dan ko'p ko'rilsa - completed
        if ($this->watch_percentage >= 90 && !$this->is_completed) {
            $this->markAsCompleted();
        } else {
            $this->save();
        }
    }
    
    /**
     * Watch time qo'shish
     */
    public function addWatchTime(int $seconds): void
    {
        $this->watch_time += $seconds;
        $this->save();
    }
    
    /**
     * Darsni tugallangan deb belgilash
     */
    public function markAsCompleted(): void
    {
        if ($this->is_completed) {
            return;
        }
        
        $this->is_completed = true;
        $this->completed_at = now();
        $this->save();
        
        // Event dispatch
        event(new \App\Events\LessonCompleted($this));
    }
    
    /**
     * Text darsni o'qilgan deb belgilash
     */
    public function markAsRead(): void
    {
        if ($this->is_read) {
            return;
        }
        
        $this->is_read = true;
        $this->read_at = now();
        $this->save();
        
        // Text lesson ham completed hisoblanadi
        $this->markAsCompleted();
    }
}
