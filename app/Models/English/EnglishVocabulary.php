<?php

namespace App\Models\English;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class EnglishVocabulary extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'english_vocabulary';

    protected $fillable = [
        'word',
        'word_lowercase',
        'phonetic_uk',
        'phonetic_us',
        'audio_uk',
        'audio_us',
        'part_of_speech',
        'definition',
        'definition_uz',
        'definition_ru',
        'definition_simple',
        'level_id',
        'frequency_rank',
        'tags',
        'image',
        'image_thumbnail',
        'times_practiced',
        'times_correct',
        'difficulty_score',
        'is_active',
        'is_common',
    ];

    protected $casts = [
        'tags' => 'array',
        'frequency_rank' => 'integer',
        'times_practiced' => 'integer',
        'times_correct' => 'integer',
        'difficulty_score' => 'decimal:2',
        'is_active' => 'boolean',
        'is_common' => 'boolean',
    ];

    // Auto set lowercase
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->word_lowercase = strtolower($model->word);
        });

        static::updating(function ($model) {
            $model->word_lowercase = strtolower($model->word);
        });
    }

    // Relationships
    public function level(): BelongsTo
    {
        return $this->belongsTo(EnglishLevel::class, 'level_id');
    }

    public function wordFamilies(): HasMany
    {
        return $this->hasMany(EnglishWordFamily::class, 'vocabulary_id');
    }

    public function collocations(): HasMany
    {
        return $this->hasMany(EnglishCollocation::class, 'vocabulary_id');
    }

    public function exampleSentences(): HasMany
    {
        return $this->hasMany(EnglishExampleSentence::class, 'vocabulary_id')->orderBy('order_number');
    }

    public function topics(): BelongsToMany
    {
        return $this->belongsToMany(
            EnglishTopic::class,
            'english_vocabulary_topics',
            'vocabulary_id',
            'topic_id'
        )->withPivot('is_primary');
    }

    public function lessons(): BelongsToMany
    {
        return $this->belongsToMany(
            EnglishLesson::class,
            'english_lesson_vocabulary',
            'vocabulary_id',
            'lesson_id'
        )->withPivot(['order_number', 'is_key_word']);
    }

    public function commonMistakes(): HasMany
    {
        return $this->hasMany(EnglishCommonMistake::class, 'vocabulary_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeCommon($query)
    {
        return $query->where('is_common', true);
    }

    public function scopeByLevel($query, $levelId)
    {
        return $query->where('level_id', $levelId);
    }

    public function scopeByPartOfSpeech($query, string $pos)
    {
        return $query->where('part_of_speech', $pos);
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where('word_lowercase', 'like', strtolower($search) . '%');
    }

    // Helpers
    public function getAccuracyRateAttribute(): float
    {
        if ($this->times_practiced === 0) {
            return 0;
        }
        return round(($this->times_correct / $this->times_practiced) * 100, 1);
    }

    public function getPrimaryExampleAttribute()
    {
        return $this->exampleSentences()->where('is_primary', true)->first();
    }
}
