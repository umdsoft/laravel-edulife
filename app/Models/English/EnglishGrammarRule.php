<?php

namespace App\Models\English;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class EnglishGrammarRule extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'english_grammar_rules';

    protected $fillable = [
        'category_id',
        'level_id',
        'slug',
        'title',
        'title_uz',
        'explanation',
        'explanation_uz',
        'explanation_simple',
        'formulas',
        'usage_cases',
        'signal_words',
        'tips',
        'tips_uz',
        'video_url',
        'infographic_url',
        'related_rule_ids',
        'prerequisite_rule_ids',
        'order_number',
        'is_active',
    ];

    protected $casts = [
        'formulas' => 'array',
        'usage_cases' => 'array',
        'signal_words' => 'array',
        'tips' => 'array',
        'tips_uz' => 'array',
        'related_rule_ids' => 'array',
        'prerequisite_rule_ids' => 'array',
        'order_number' => 'integer',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function category(): BelongsTo
    {
        return $this->belongsTo(EnglishGrammarCategory::class, 'category_id');
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(EnglishLevel::class, 'level_id');
    }

    public function examples(): HasMany
    {
        return $this->hasMany(EnglishGrammarExample::class, 'grammar_rule_id')->orderBy('order_number');
    }

    public function exercises(): HasMany
    {
        return $this->hasMany(EnglishGrammarExercise::class, 'grammar_rule_id')->orderBy('order_number');
    }

    public function commonMistakes(): HasMany
    {
        return $this->hasMany(EnglishCommonMistake::class, 'grammar_rule_id');
    }

    public function lessons(): BelongsToMany
    {
        return $this->belongsToMany(
            EnglishLesson::class,
            'english_lesson_grammar',
            'grammar_rule_id',
            'lesson_id'
        )->withPivot(['order_number', 'is_main_focus']);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByLevel($query, $levelId)
    {
        return $query->where('level_id', $levelId);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order_number');
    }

    // Helpers
    public function getRelatedRulesAttribute()
    {
        if (empty($this->related_rule_ids)) {
            return collect();
        }
        return self::whereIn('id', $this->related_rule_ids)->get();
    }

    public function getPrerequisiteRulesAttribute()
    {
        if (empty($this->prerequisite_rule_ids)) {
            return collect();
        }
        return self::whereIn('id', $this->prerequisite_rule_ids)->get();
    }
}
