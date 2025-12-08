<?php

namespace App\Models\English;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnglishBattleQuestion extends Model
{
    use HasUuids;

    protected $table = 'english_battle_questions';

    protected $fillable = [
        'level_id',
        'question_type',
        'question',
        'question_uz',
        'correct_answer',
        'options',
        'explanation',
        'explanation_uz',
        'audio_url',
        'image_url',
        'difficulty',
        'base_points',
        'time_bonus_max',
        'vocabulary_id',
        'grammar_rule_id',
        'times_used',
        'times_correct',
        'accuracy_rate',
        'avg_answer_time',
        'is_active',
    ];

    protected $casts = [
        'options' => 'array',
        'base_points' => 'integer',
        'time_bonus_max' => 'integer',
        'times_used' => 'integer',
        'times_correct' => 'integer',
        'accuracy_rate' => 'decimal:2',
        'avg_answer_time' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function level(): BelongsTo
    {
        return $this->belongsTo(EnglishLevel::class, 'level_id');
    }

    public function vocabulary(): BelongsTo
    {
        return $this->belongsTo(EnglishVocabulary::class, 'vocabulary_id');
    }

    public function grammarRule(): BelongsTo
    {
        return $this->belongsTo(EnglishGrammarRule::class, 'grammar_rule_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByDifficulty($query, string $difficulty)
    {
        return $query->where('difficulty', $difficulty);
    }

    public function updateStatistics(bool $wasCorrect, int $answerTimeMs): void
    {
        $this->times_used++;
        if ($wasCorrect) {
            $this->times_correct++;
        }

        $this->accuracy_rate = round(($this->times_correct / $this->times_used) * 100, 2);
        $this->avg_answer_time = (($this->avg_answer_time * ($this->times_used - 1)) + $answerTimeMs) / $this->times_used;

        $this->save();
    }
}
