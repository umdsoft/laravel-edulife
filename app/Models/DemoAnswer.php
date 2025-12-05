<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DemoAnswer extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'attempt_id',
        'question_id',
        'user_answer',
        'is_correct',
        'points_earned',
        'time_spent_seconds',
    ];

    protected function casts(): array
    {
        return [
            'user_answer' => 'array',
            'is_correct' => 'boolean',
            'points_earned' => 'decimal:2',
            'time_spent_seconds' => 'integer',
        ];
    }

    // ==================== RELATIONSHIPS ====================

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(DemoAttempt::class, 'attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(QuestionBank::class, 'question_id');
    }

    // ==================== METHODS ====================

    /**
     * Grade the answer
     */
    public function grade(): void
    {
        $question = $this->question;
        
        if ($question->requires_manual_grading) {
            $this->is_correct = false;
            $this->points_earned = 0;
            $this->save();
            return;
        }

        $this->is_correct = $question->checkAnswer($this->user_answer);
        $this->points_earned = $question->calculatePoints($this->user_answer);
        $this->save();
    }
}
