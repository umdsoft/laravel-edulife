<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OlympiadCodingProblem extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'olympiad_id',
        'section_id',
        'problem_id',
        'order_number',
        'points',
        'time_limit_override_ms',
        'memory_limit_override_mb',
    ];

    protected function casts(): array
    {
        return [
            'order_number' => 'integer',
            'points' => 'integer',
            'time_limit_override_ms' => 'integer',
            'memory_limit_override_mb' => 'integer',
        ];
    }

    // ==================== RELATIONSHIPS ====================

    public function olympiad(): BelongsTo
    {
        return $this->belongsTo(Olympiad::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(OlympiadSection::class, 'section_id');
    }

    public function problem(): BelongsTo
    {
        return $this->belongsTo(CodingProblem::class, 'problem_id');
    }

    // ==================== SCOPES ====================

    public function scopeOrdered($query)
    {
        return $query->orderBy('order_number');
    }

    // ==================== ACCESSORS ====================

    public function getTitleAttribute(): string
    {
        return $this->problem->title ?? '';
    }

    public function getDescriptionAttribute(): string
    {
        return $this->problem->description ?? '';
    }

    public function getDifficultyAttribute(): string
    {
        return $this->problem->difficulty ?? 'medium';
    }

    public function getMaxPointsAttribute(): int
    {
        return $this->points;
    }

    public function getTimeLimitMsAttribute(): int
    {
        return $this->time_limit_override_ms ?? $this->problem->time_limit_ms ?? 2000;
    }

    public function getMemoryLimitMbAttribute(): int
    {
        return $this->memory_limit_override_mb ?? $this->problem->memory_limit_mb ?? 256;
    }

    public function getAllowedLanguagesAttribute(): array
    {
        return $this->problem->allowed_languages ?? ['python', 'javascript', 'cpp', 'java'];
    }

    public function getTestCasesAttribute(): array
    {
        return $this->problem->test_cases ?? [];
    }

    public function getExamplesAttribute(): array
    {
        return $this->problem->examples ?? [];
    }

    public function getProblemLetterAttribute(): string
    {
        // Convert order number to letter: 1 -> A, 2 -> B, etc.
        return chr(64 + $this->order_number);
    }
}
