<?php

namespace App\Models\English;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnglishLessonStep extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'english_lesson_steps';

    protected $fillable = [
        'lesson_id',
        'step_number',
        'title',
        'title_uz',
        'step_type',
        'content_config',
        'xp_reward',
        'estimated_minutes',
        'is_required',
        'is_skippable',
        'order_number',
        'is_active',
    ];

    protected $casts = [
        'content_config' => 'array',
        'step_number' => 'integer',
        'xp_reward' => 'integer',
        'estimated_minutes' => 'integer',
        'is_required' => 'boolean',
        'is_skippable' => 'boolean',
        'order_number' => 'integer',
        'is_active' => 'boolean',
    ];

    // Relationships
    public function lesson(): BelongsTo
    {
        return $this->belongsTo(EnglishLesson::class, 'lesson_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order_number');
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('step_type', $type);
    }
}
