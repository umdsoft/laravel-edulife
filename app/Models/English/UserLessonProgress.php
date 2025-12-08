<?php

namespace App\Models\English;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserLessonProgress extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'user_lesson_progress';

    protected $fillable = [
        'user_id',
        'lesson_id',
        'status',
        'current_step',
        'total_steps',
        'completed_steps',
        'best_score',
        'last_score',
        'attempts',
        'stars_earned',
        'xp_earned',
        'coins_earned',
        'time_spent_seconds',
        'started_at',
        'completed_at',
        'last_accessed_at',
        'quiz_results',
    ];

    protected $casts = [
        'current_step' => 'integer',
        'total_steps' => 'integer',
        'completed_steps' => 'array',
        'best_score' => 'integer',
        'last_score' => 'integer',
        'attempts' => 'integer',
        'stars_earned' => 'integer',
        'xp_earned' => 'integer',
        'coins_earned' => 'integer',
        'time_spent_seconds' => 'integer',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'last_accessed_at' => 'datetime',
        'quiz_results' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(EnglishLesson::class, 'lesson_id');
    }

    public function getProgressPercentageAttribute(): float
    {
        if ($this->total_steps === 0)
            return 0;
        return round((count($this->completed_steps ?? []) / $this->total_steps) * 100, 1);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }
}
