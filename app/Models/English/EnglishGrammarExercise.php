<?php

namespace App\Models\English;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnglishGrammarExercise extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'english_grammar_exercises';

    protected $fillable = [
        'grammar_rule_id',
        'level_id',
        'exercise_type',
        'question',
        'question_uz',
        'content',
        'explanation',
        'explanation_uz',
        'difficulty',
        'points',
        'times_attempted',
        'times_correct',
        'order_number',
        'is_active',
    ];

    protected $casts = [
        'content' => 'array',
        'points' => 'integer',
        'times_attempted' => 'integer',
        'times_correct' => 'integer',
        'order_number' => 'integer',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function grammarRule(): BelongsTo
    {
        return $this->belongsTo(EnglishGrammarRule::class, 'grammar_rule_id');
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(EnglishLevel::class, 'level_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByDifficulty($query, string $difficulty)
    {
        return $query->where('difficulty', $difficulty);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('exercise_type', $type);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order_number');
    }

    // Helpers
    public function getAccuracyRateAttribute(): float
    {
        if ($this->times_attempted === 0) {
            return 0;
        }
        return round(($this->times_correct / $this->times_attempted) * 100, 1);
    }

    public function checkAnswer($userAnswer): bool
    {
        $content = $this->content;

        switch ($this->exercise_type) {
            case 'fill_gap':
                $correct = strtolower(trim($content['correct_answer']));
                $user = strtolower(trim($userAnswer));
                if ($correct === $user)
                    return true;
                if (isset($content['accept_alternatives'])) {
                    return in_array($user, array_map('strtolower', $content['accept_alternatives']));
                }
                return false;

            case 'multiple_choice':
                return $userAnswer === $content['correct_index'];

            case 'true_false':
                return $userAnswer === $content['correct_answer'];

            case 'word_order':
                return $userAnswer === $content['correct_order'];

            default:
                return false;
        }
    }
}
