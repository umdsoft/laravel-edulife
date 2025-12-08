<?php

namespace App\Models\English;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnglishGameContent extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'english_game_content';

    protected $fillable = [
        'game_id',
        'game_level_id',
        'english_level_id',
        'content_type',
        'content',
        'vocabulary_id',
        'grammar_rule_id',
        'difficulty',
        'points',
        'times_shown',
        'times_correct',
        'accuracy_rate',
        'order_number',
        'is_active',
    ];

    protected $casts = [
        'content' => 'array',
        'points' => 'integer',
        'times_shown' => 'integer',
        'times_correct' => 'integer',
        'accuracy_rate' => 'decimal:2',
        'order_number' => 'integer',
        'is_active' => 'boolean',
    ];

    public function game(): BelongsTo
    {
        return $this->belongsTo(EnglishGame::class, 'game_id');
    }

    public function gameLevel(): BelongsTo
    {
        return $this->belongsTo(EnglishGameLevel::class, 'game_level_id');
    }

    public function englishLevel(): BelongsTo
    {
        return $this->belongsTo(EnglishLevel::class, 'english_level_id');
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

    public function updateStatistics(bool $wasCorrect): void
    {
        $this->increment('times_shown');
        if ($wasCorrect) {
            $this->increment('times_correct');
        }
        $this->accuracy_rate = $this->times_shown > 0
            ? round(($this->times_correct / $this->times_shown) * 100, 2)
            : 0;
        $this->save();
    }
}
