<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Enrollment extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'user_id',
        'course_id',
        'access_type',
        'status',
        'progress',
        'completed_lessons',
        'total_lessons',
        'total_watch_time', // renamed from time_spent
        'last_activity_at', // renamed from last_accessed_at
        'completed_at',
        'expires_at',
        'payment_id',
        'is_completed',
        'last_lesson_id',
        'paid_amount',
        'payment_status',
        'certificate_issued',
        'certificate_id',
    ];

    protected $casts = [
        'progress' => 'integer',
        'completed_lessons' => 'integer',
        'total_lessons' => 'integer',
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'expires_at' => 'datetime',
        'total_watch_time' => 'integer',
        'paid_amount' => 'decimal:2',
        'certificate_issued' => 'boolean',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function lastLesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class, 'last_lesson_id');
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function lessonProgresses(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }
    
    // Alias for backward compatibility if needed, but lessonProgresses is better naming
    public function lessonProgress(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }

    public function moduleProgress(): HasMany
    {
        return $this->hasMany(ModuleProgress::class);
    }

    public function certificate(): BelongsTo
    {
        // Note: In migration we added certificate_id foreign key, 
        // but existing model had hasOne relationship. 
        // If we want to link to a specific certificate, belongsTo is correct for certificate_id column.
        // However, usually Certificate belongs to Enrollment.
        // Let's check if we want to keep hasOne or use the new column.
        // The migration added certificate_id to enrollments.
        return $this->belongsTo(Certificate::class);
    }

    public function review()
    {
        return $this->hasOne(Review::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeCompleted($query)
    {
        return $query->where('is_completed', true);
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }

    public function scopeByAccessType($query, string $type)
    {
        return $query->where('access_type', $type);
    }

    // Helper Methods
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Progress yangilash
     */
    public function updateProgress(): void
    {
        $totalLessons = $this->course->lessons()->count();
        $completedLessons = $this->lessonProgresses()
            ->where('is_completed', true)
            ->count();
        
        $this->total_lessons = $totalLessons;
        $this->completed_lessons = $completedLessons;
        $this->progress = $totalLessons > 0 
            ? (int) round(($completedLessons / $totalLessons) * 100) 
            : 0;
        
        // Kurs tugadimi?
        if ($this->progress >= 100 && !$this->is_completed) {
            $this->is_completed = true;
            $this->status = 'completed'; // Sync with status enum
            $this->completed_at = now();
            
            // Student profile yangilash
            if ($this->user->studentProfile) {
                $this->user->studentProfile->increment('courses_completed');
            }
        }
        
        $this->last_activity_at = now();
        $this->save();
    }
}
