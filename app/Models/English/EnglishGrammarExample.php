<?php

namespace App\Models\English;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnglishGrammarExample extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'english_grammar_examples';

    protected $fillable = [
        'grammar_rule_id',
        'sentence',
        'sentence_uz',
        'highlight_text',
        'highlight_positions',
        'explanation',
        'explanation_uz',
        'example_type',
        'correction',
        'correction_explanation',
        'correction_explanation_uz',
        'audio_url',
        'order_number',
        'is_primary',
    ];

    protected $casts = [
        'highlight_positions' => 'array',
        'order_number' => 'integer',
        'is_primary' => 'boolean',
    ];

    // Relationships
    public function grammarRule(): BelongsTo
    {
        return $this->belongsTo(EnglishGrammarRule::class, 'grammar_rule_id');
    }

    // Scopes
    public function scopeCorrect($query)
    {
        return $query->where('example_type', 'correct');
    }

    public function scopeIncorrect($query)
    {
        return $query->where('example_type', 'incorrect');
    }

    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order_number');
    }
}
