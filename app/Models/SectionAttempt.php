<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SectionAttempt extends Model
{
    use HasFactory, HasUuids;

    // Status constants
    public const STATUS_NOT_STARTED = 'not_started';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_GRADING = 'grading';
    public const STATUS_GRADED = 'graded';

    protected $fillable = [
        'attempt_id',
        'section_id',
        'started_at',
        'completed_at',
        'duration_seconds',
        'remaining_seconds',
        'raw_score',
        'weighted_score',
        'max_score',
        'score_percent',
        'questions_answered',
        'questions_correct',
        'requires_manual_grading',
        'grading_details',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'duration_seconds' => 'integer',
            'remaining_seconds' => 'integer',
            'raw_score' => 'decimal:2',
            'weighted_score' => 'decimal:2',
            'max_score' => 'decimal:2',
            'score_percent' => 'decimal:2',
            'questions_answered' => 'integer',
            'questions_correct' => 'integer',
            'requires_manual_grading' => 'boolean',
            'grading_details' => 'array',
        ];
    }

    // ==================== RELATIONSHIPS ====================

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(OlympiadAttempt::class, 'attempt_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(OlympiadSection::class, 'section_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(OlympiadAnswer::class, 'section_attempt_id');
    }

    // ==================== SCOPES ====================

    public function scopeCompleted($query)
    {
        return $query->whereIn('status', [self::STATUS_COMPLETED, self::STATUS_GRADED]);
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    // ==================== ACCESSORS ====================

    public function getIsCompletedAttribute(): bool
    {
        return in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_GRADED]);
    }

    public function getIsInProgressAttribute(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function getTimeRemainingAttribute(): int
    {
        if (!$this->started_at) {
            return $this->section->duration_minutes * 60;
        }

        $totalDuration = $this->section->duration_minutes * 60;
        $elapsed = $this->started_at->diffInSeconds(now());
        return max(0, $totalDuration - $elapsed);
    }

    // ==================== METHODS ====================

    /**
     * Start section
     */
    public function start(): void
    {
        $this->started_at = now();
        $this->remaining_seconds = $this->section->duration_minutes * 60;
        $this->status = self::STATUS_IN_PROGRESS;
        $this->save();
    }

    /**
     * Complete section
     */
    public function complete(): void
    {
        $this->completed_at = now();
        $this->duration_seconds = $this->started_at->diffInSeconds($this->completed_at);
        $this->status = self::STATUS_COMPLETED;
        $this->calculateScore();
        $this->save();
    }

    /**
     * Calculate section score
     */
    public function calculateScore(): void
    {
        $answers = $this->answers;
        $totalRaw = 0;
        $correctCount = 0;

        foreach ($answers as $answer) {
            $totalRaw += $answer->points_earned;
            if ($answer->is_correct) {
                $correctCount++;
            }
        }

        $this->raw_score = $totalRaw;
        $this->questions_answered = $answers->count();
        $this->questions_correct = $correctCount;
        
        // Calculate weighted score
        $section = $this->section;
        $this->weighted_score = $section->calculateWeightedScore($totalRaw);
        $this->score_percent = $this->max_score > 0 
            ? ($totalRaw / $this->max_score) * 100 
            : 0;
    }

    /**
     * Update remaining time
     */
    public function updateRemainingTime(): void
    {
        $this->remaining_seconds = $this->time_remaining;
        $this->save();
    }
}
