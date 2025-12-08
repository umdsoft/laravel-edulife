<?php

namespace App\Models\English;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnglishExampleSentence extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'english_example_sentences';

    protected $fillable = [
        'vocabulary_id',
        'sentence',
        'sentence_uz',
        'sentence_ru',
        'highlight_word',
        'word_position',
        'audio_url',
        'context',
        'level_id',
        'order_number',
        'is_primary',
    ];

    protected $casts = [
        'word_position' => 'integer',
        'order_number' => 'integer',
        'is_primary' => 'boolean',
    ];

    // Relationships
    public function vocabulary(): BelongsTo
    {
        return $this->belongsTo(EnglishVocabulary::class, 'vocabulary_id');
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(EnglishLevel::class, 'level_id');
    }

    // Scopes
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order_number');
    }
}
