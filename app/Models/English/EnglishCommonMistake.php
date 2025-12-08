<?php

namespace App\Models\English;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnglishCommonMistake extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'english_common_mistakes';

    protected $fillable = [
        'grammar_rule_id',
        'vocabulary_id',
        'level_id',
        'incorrect_form',
        'correct_form',
        'explanation',
        'explanation_uz',
        'mistake_type',
        'common_for_l1',
        'frequency',
    ];

    protected $casts = [
        'common_for_l1' => 'array',
    ];

    // Relationships
    public function grammarRule(): BelongsTo
    {
        return $this->belongsTo(EnglishGrammarRule::class, 'grammar_rule_id');
    }

    public function vocabulary(): BelongsTo
    {
        return $this->belongsTo(EnglishVocabulary::class, 'vocabulary_id');
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(EnglishLevel::class, 'level_id');
    }

    // Scopes
    public function scopeForUzbekSpeakers($query)
    {
        return $query->whereJsonContains('common_for_l1', 'uzbek');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('mistake_type', $type);
    }

    public function scopeCommon($query)
    {
        return $query->whereIn('frequency', ['very_common', 'common']);
    }
}
