<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OlympiadAnswer extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'attempt_id',
        'section_attempt_id',
        'olympiad_question_id',
        'question_id',
        'user_answer',
        'text_answer',
        'audio_answer_url',
        'is_correct',
        'is_partially_correct',
        'points_earned',
        'max_points',
        'time_spent_seconds',
        'is_flagged',
        'requires_manual_grading',
        'is_graded',
        'grading_details',
        'grader_feedback',
    ];

    protected function casts(): array
    {
        return [
            'user_answer' => 'array',
            'is_correct' => 'boolean',
            'is_partially_correct' => 'boolean',
            'points_earned' => 'decimal:2',
            'max_points' => 'decimal:2',
            'time_spent_seconds' => 'integer',
            'is_flagged' => 'boolean',
            'requires_manual_grading' => 'boolean',
            'is_graded' => 'boolean',
            'grading_details' => 'array',
        ];
    }

    // ==================== RELATIONSHIPS ====================

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(OlympiadAttempt::class, 'attempt_id');
    }

    public function sectionAttempt(): BelongsTo
    {
        return $this->belongsTo(SectionAttempt::class, 'section_attempt_id');
    }

    public function olympiadQuestion(): BelongsTo
    {
        return $this->belongsTo(OlympiadQuestion::class, 'olympiad_question_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(QuestionBank::class, 'question_id');
    }

    // ==================== SCOPES ====================

    public function scopeNeedsManualGrading($query)
    {
        return $query->where('requires_manual_grading', true)
            ->where('is_graded', false);
    }

    public function scopeGraded($query)
    {
        return $query->where('is_graded', true);
    }

    public function scopeFlagged($query)
    {
        return $query->where('is_flagged', true);
    }

    // ==================== ACCESSORS ====================

    public function getScorePercentAttribute(): float
    {
        return $this->max_points > 0 
            ? ($this->points_earned / $this->max_points) * 100 
            : 0;
    }

    public function getIsEmptyAttribute(): bool
    {
        return $this->user_answer === null 
            && $this->text_answer === null 
            && $this->audio_answer_url === null;
    }

    // ==================== METHODS ====================

    /**
     * Auto-grade the answer
     */
    public function autoGrade(): void
    {
        $question = $this->question;

        if ($question->requires_manual_grading) {
            $this->requires_manual_grading = true;
            $this->save();
            return;
        }

        $this->is_correct = $question->checkAnswer($this->user_answer);
        $this->points_earned = $question->calculatePoints($this->user_answer);
        $this->is_graded = true;
        $this->save();

        // Record usage statistics
        $question->recordUsage($this->is_correct);
    }

    /**
     * Manually grade the answer
     */
    public function manualGrade(float $points, array $gradingDetails = [], ?string $feedback = null): void
    {
        $this->points_earned = min($points, $this->max_points);
        $this->is_correct = $points >= ($this->max_points * 0.6);
        $this->is_partially_correct = $points > 0 && $points < $this->max_points;
        $this->grading_details = $gradingDetails;
        $this->grader_feedback = $feedback;
        $this->is_graded = true;
        $this->save();
    }

    /**
     * Toggle flag status
     */
    public function toggleFlag(): void
    {
        $this->is_flagged = !$this->is_flagged;
        $this->save();
    }
}
