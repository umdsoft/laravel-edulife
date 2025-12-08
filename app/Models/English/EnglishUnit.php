<?php

namespace App\Models\English;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EnglishUnit extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'english_units';

    protected $fillable = [
        'level_id',
        'slug',
        'unit_number',
        'title',
        'title_uz',
        'description',
        'description_uz',
        'objectives',
        'objectives_uz',
        'grammar_focus',
        'vocabulary_focus',
        'vocabulary_count',
        'xp_required',
        'xp_reward',
        'estimated_minutes',
        'icon',
        'color',
        'thumbnail',
        'banner_image',
        'order_number',
        'is_free',
        'is_active',
    ];

    protected $casts = [
        'objectives' => 'array',
        'objectives_uz' => 'array',
        'grammar_focus' => 'array',
        'vocabulary_focus' => 'array',
        'vocabulary_count' => 'integer',
        'xp_required' => 'integer',
        'xp_reward' => 'integer',
        'estimated_minutes' => 'integer',
        'order_number' => 'integer',
        'is_free' => 'boolean',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function level(): BelongsTo
    {
        return $this->belongsTo(EnglishLevel::class, 'level_id');
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(EnglishLesson::class, 'unit_id')->orderBy('order_number');
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
    public function getLessonsCountAttribute(): int
    {
        return $this->lessons()->count();
    }

    public function getEstimatedHoursAttribute(): float
    {
        return round($this->estimated_minutes / 60, 1);
    }
}
