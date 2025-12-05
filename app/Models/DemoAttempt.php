<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DemoAttempt extends Model
{
    use HasFactory, HasUuids;

    // Status constants
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_ABANDONED = 'abandoned';

    protected $fillable = [
        'user_id',
        'olympiad_id',
        'registration_id',
        'attempt_number',
        'started_at',
        'completed_at',
        'duration_seconds',
        'total_score',
        'max_score',
        'score_percent',
        'section_scores',
        'answers_summary',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'attempt_number' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'duration_seconds' => 'integer',
            'total_score' => 'decimal:2',
            'max_score' => 'decimal:2',
            'score_percent' => 'decimal:2',
            'section_scores' => 'array',
            'answers_summary' => 'array',
        ];
    }

    // ==================== RELATIONSHIPS ====================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function olympiad(): BelongsTo
    {
        return $this->belongsTo(Olympiad::class);
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(OlympiadRegistration::class, 'registration_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(DemoAnswer::class, 'attempt_id');
    }

    // ==================== SCOPES ====================

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    // ==================== ACCESSORS ====================

    public function getIsCompletedAttribute(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function getIsInProgressAttribute(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function getFormattedDurationAttribute(): string
    {
        $minutes = floor($this->duration_seconds / 60);
        $seconds = $this->duration_seconds % 60;
        return sprintf('%02d:%02d', $minutes, $seconds);
    }

    public function getPassedAttribute(): bool
    {
        $passingPercent = $this->olympiad->demo_config['passing_percent'] ?? 60;
        return $this->score_percent >= $passingPercent;
    }

    // ==================== METHODS ====================

    /**
     * Start the demo attempt
     */
    public function start(): void
    {
        $this->started_at = now();
        $this->status = self::STATUS_IN_PROGRESS;
        $this->save();
    }

    /**
     * Complete the demo attempt
     */
    public function complete(): void
    {
        $this->completed_at = now();
        $this->duration_seconds = $this->started_at->diffInSeconds($this->completed_at);
        $this->status = self::STATUS_COMPLETED;
        $this->calculateScore();
        $this->save();

        // Update registration best score
        $this->registration->updateDemoBestScore($this->score_percent);
    }

    /**
     * Calculate total score from answers
     */
    public function calculateScore(): void
    {
        $answers = $this->answers;
        $totalScore = 0;
        $maxScore = 0;

        foreach ($answers as $answer) {
            $totalScore += $answer->points_earned;
            $maxScore += $answer->question->base_points ?? 1;
        }

        $this->total_score = $totalScore;
        $this->max_score = $maxScore;
        $this->score_percent = $maxScore > 0 ? ($totalScore / $maxScore) * 100 : 0;

        $this->answers_summary = [
            'total' => $answers->count(),
            'correct' => $answers->where('is_correct', true)->count(),
            'incorrect' => $answers->where('is_correct', false)->count(),
        ];
    }

    /**
     * Check if attempt is expired
     */
    public function checkExpired(): bool
    {
        $demoConfig = $this->olympiad->demo_config ?? [];
        $durationMinutes = $demoConfig['duration_minutes'] ?? 45;

        if ($this->started_at && $this->started_at->addMinutes($durationMinutes)->isPast()) {
            $this->status = self::STATUS_EXPIRED;
            $this->completed_at = $this->started_at->addMinutes($durationMinutes);
            $this->calculateScore();
            $this->save();
            return true;
        }

        return false;
    }
}
