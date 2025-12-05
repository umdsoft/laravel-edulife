<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OlympiadQuestion extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'olympiad_id',
        'section_id',
        'question_id',
        'order_number',
        'points',
        'time_limit_seconds',
        'difficulty_group',
        'concept_group',
    ];

    protected function casts(): array
    {
        return [
            'order_number' => 'integer',
            'points' => 'integer',
            'time_limit_seconds' => 'integer',
        ];
    }

    // ==================== RELATIONSHIPS ====================

    public function olympiad(): BelongsTo
    {
        return $this->belongsTo(Olympiad::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(OlympiadSection::class, 'section_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(QuestionBank::class, 'question_id');
    }

    // ==================== SCOPES ====================

    public function scopeOrdered($query)
    {
        return $query->orderBy('order_number');
    }

    public function scopeByDifficultyGroup($query, string $group)
    {
        return $query->where('difficulty_group', $group);
    }

    public function scopeByConceptGroup($query, string $group)
    {
        return $query->where('concept_group', $group);
    }

    // ==================== ACCESSORS ====================

    public function getQuestionTextAttribute(): string
    {
        return $this->question->question_text ?? '';
    }

    public function getQuestionTypeAttribute(): string
    {
        return $this->question->question_type ?? 'single_choice';
    }

    public function getOptionsAttribute(): ?array
    {
        return $this->question->options;
    }

    public function getCorrectAnswerAttribute(): mixed
    {
        return $this->question->correct_answer;
    }

    public function getExplanationAttribute(): ?string
    {
        return $this->question->explanation;
    }

    public function getRequiresManualGradingAttribute(): bool
    {
        return $this->question->requires_manual_grading ?? false;
    }

    public function getTimeLimitAttribute(): int
    {
        return $this->time_limit_seconds ?? $this->question->estimated_time_seconds ?? 120;
    }
}
