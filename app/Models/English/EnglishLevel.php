<?php

namespace App\Models\English;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EnglishLevel extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'english_levels';

    protected $fillable = [
        'code',
        'name',
        'name_uz',
        'description',
        'description_uz',
        'cefr_description',
        'can_do_statements',
        'vocabulary_target',
        'grammar_structures',
        'estimated_hours',
        'xp_required',
        'xp_to_complete',
        'ielts_band_min',
        'ielts_band_max',
        'color',
        'icon',
        'badge_image',
        'order_number',
        'is_active',
    ];

    protected $casts = [
        'can_do_statements' => 'array',
        'vocabulary_target' => 'integer',
        'grammar_structures' => 'integer',
        'estimated_hours' => 'integer',
        'xp_required' => 'integer',
        'xp_to_complete' => 'integer',
        'ielts_band_min' => 'decimal:1',
        'ielts_band_max' => 'decimal:1',
        'order_number' => 'integer',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function units(): HasMany
    {
        return $this->hasMany(EnglishUnit::class, 'level_id')->orderBy('order_number');
    }

    public function vocabulary(): HasMany
    {
        return $this->hasMany(EnglishVocabulary::class, 'level_id');
    }

    public function grammarRules(): HasMany
    {
        return $this->hasMany(EnglishGrammarRule::class, 'level_id');
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

    // Helpers
    public function getIeltsBandRangeAttribute(): ?string
    {
        if ($this->ielts_band_min && $this->ielts_band_max) {
            return "{$this->ielts_band_min}-{$this->ielts_band_max}";
        }
        return null;
    }
}
