<?php

namespace App\Models\Exam;

use App\Models\QuestionBank;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Answer to one variant question (unique attempt + question).
 * is_correct NULL = not answered / not graded yet.
 */
class ExamAnswer extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'exam_answers';

    protected $fillable = [
        'attempt_id',
        'question_id',
        'position',
        'selected_option_id',
        'selected_option_ids',
        'answer_text',
        'numeric_value',
        'unit',
        'is_correct',
        'unit_correct',
        'points',
        'max_points',
        'is_flagged',
        'time_spent_seconds',
        'answered_at',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'selected_option_ids' => 'array',
            'numeric_value' => 'decimal:10',
            'is_correct' => 'boolean',
            'unit_correct' => 'boolean',
            'points' => 'decimal:2',
            'max_points' => 'decimal:2',
            'is_flagged' => 'boolean',
            'time_spent_seconds' => 'integer',
            'answered_at' => 'datetime',
        ];
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(ExamAttempt::class, 'attempt_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(QuestionBank::class, 'question_id');
    }

    public function selectedOption(): BelongsTo
    {
        return $this->belongsTo(QuestionBankOption::class, 'selected_option_id');
    }

    public function isAnswered(): bool
    {
        return $this->selected_option_id !== null
            || ! empty($this->selected_option_ids)
            || ($this->answer_text !== null && trim($this->answer_text) !== '');
    }
}
