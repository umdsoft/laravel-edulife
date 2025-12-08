<?php

namespace App\Models\English;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EnglishPronunciationWord extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'english_pronunciation_words';

    protected $fillable = [
        'vocabulary_id',
        'level_id',
        'text',
        'type',
        'phonetic_uk',
        'phonetic_us',
        'audio_uk',
        'audio_us',
        'audio_slow',
        'syllables',
        'syllable_count',
        'stress_pattern',
        'difficulty',
        'common_mistakes',
        'focus_sounds',
        'times_practiced',
        'average_score',
        'is_active',
        'order_number',
    ];

    protected $casts = [
        'syllables' => 'array',
        'syllable_count' => 'integer',
        'stress_pattern' => 'array',
        'common_mistakes' => 'array',
        'focus_sounds' => 'array',
        'times_practiced' => 'integer',
        'average_score' => 'decimal:2',
        'is_active' => 'boolean',
        'order_number' => 'integer',
    ];

    public function vocabulary(): BelongsTo
    {
        return $this->belongsTo(EnglishVocabulary::class, 'vocabulary_id');
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(EnglishLevel::class, 'level_id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(UserPronunciationAttempt::class, 'pronunciation_word_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
