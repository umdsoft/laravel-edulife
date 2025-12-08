<?php

namespace App\Models\English;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnglishCollocation extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'english_collocations';

    protected $fillable = [
        'vocabulary_id',
        'collocation',
        'collocation_type',
        'definition',
        'definition_uz',
        'example_sentence',
        'example_sentence_uz',
        'frequency',
    ];

    // Relationships
    public function vocabulary(): BelongsTo
    {
        return $this->belongsTo(EnglishVocabulary::class, 'vocabulary_id');
    }

    // Scopes
    public function scopeCommon($query)
    {
        return $query->whereIn('frequency', ['very_common', 'common']);
    }
}
