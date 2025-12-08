<?php

namespace App\Models\English;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnglishWordFamily extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'english_word_families';

    protected $fillable = [
        'vocabulary_id',
        'word',
        'part_of_speech',
        'relation_type',
        'phonetic',
        'definition',
        'definition_uz',
        'example_sentence',
    ];

    // Relationships
    public function vocabulary(): BelongsTo
    {
        return $this->belongsTo(EnglishVocabulary::class, 'vocabulary_id');
    }
}
