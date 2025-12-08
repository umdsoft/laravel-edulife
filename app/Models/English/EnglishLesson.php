<?php

namespace App\Models\English;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class EnglishLesson extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'english_lessons';

    protected $fillable = [
        'unit_id',
        'slug',
        'lesson_number',
        'title',
        'title_uz',
        'description',
        'description_uz',
        'lesson_type',
        'vocabulary_ids',
        'grammar_rule_ids',
        'content',
        'xp_required',
        'xp_reward',
        'coin_reward',
        'estimated_minutes',
        'pass_percentage',
        'icon',
        'thumbnail',
        'order_number',
        'is_free',
        'is_active',
    ];

    protected $casts = [
        'vocabulary_ids' => 'array',
        'grammar_rule_ids' => 'array',
        'content' => 'array',
        'xp_required' => 'integer',
        'xp_reward' => 'integer',
        'coin_reward' => 'integer',
        'estimated_minutes' => 'integer',
        'pass_percentage' => 'integer',
        'order_number' => 'integer',
        'is_free' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function unit(): BelongsTo
    {
        return $this->belongsTo(EnglishUnit::class, 'unit_id');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(EnglishLessonStep::class, 'lesson_id')->orderBy('order_number');
    }

    public function vocabulary(): BelongsToMany
    {
        return $this->belongsToMany(
            EnglishVocabulary::class,
            'english_lesson_vocabulary',
            'lesson_id',
            'vocabulary_id'
        )->withPivot(['order_number', 'is_key_word']);
    }

    public function grammarRules(): BelongsToMany
    {
        return $this->belongsToMany(
            EnglishGrammarRule::class,
            'english_lesson_grammar',
            'lesson_id',
            'grammar_rule_id'
        )->withPivot(['order_number', 'is_main_focus']);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order_number');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('lesson_type', $type);
    }
}
