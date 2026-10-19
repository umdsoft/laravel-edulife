<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OlympiadSection extends Model
{
    use HasFactory, HasUuids;

    // Section type constants (math and physics olympiads use test sections only)
    public const TYPE_TEST = 'test';

    protected $fillable = [
        'olympiad_id',
        'section_type',
        'title',
        'description',
        'instructions',
        'duration_minutes',
        'order_number',
        'max_points',
        'weight_percent',
        'passing_percent',
        'section_config',
        'requires_manual_grading',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'section_config' => 'array',
            'duration_minutes' => 'integer',
            'order_number' => 'integer',
            'max_points' => 'integer',
            'weight_percent' => 'decimal:2',
            'passing_percent' => 'decimal:2',
            'requires_manual_grading' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    // ==================== RELATIONSHIPS ====================

    public function olympiad(): BelongsTo
    {
        return $this->belongsTo(Olympiad::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(OlympiadQuestion::class, 'section_id')->orderBy('order_number');
    }

    public function sectionAttempts(): HasMany
    {
        return $this->hasMany(SectionAttempt::class, 'section_id');
    }

    // ==================== SCOPES ====================

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
        return $query->where('section_type', $type);
    }

    public function scopeRequiresManualGrading($query)
    {
        return $query->where('requires_manual_grading', true);
    }

    // ==================== ACCESSORS ====================

    public function getIsTestSectionAttribute(): bool
    {
        return $this->section_type === self::TYPE_TEST;
    }

    public function getIsAutoGradedAttribute(): bool
    {
        return !$this->requires_manual_grading;
    }

    public function getQuestionsCountAttribute(): int
    {
        return $this->section_config['questions_count'] ?? $this->questions()->count();
    }

    public function getDurationInSecondsAttribute(): int
    {
        return $this->duration_minutes * 60;
    }

    public function getGradingRubricAttribute(): array
    {
        return $this->section_config['grading_rubric'] ?? [];
    }

    // ==================== METHODS ====================

    /**
     * Get display name based on section type
     */
    public function getDisplayName(): string
    {
        $names = [
            self::TYPE_TEST => 'Test',
        ];

        return $names[$this->section_type] ?? ucfirst($this->section_type);
    }

    /**
     * Get Uzbek display name
     */
    public function getDisplayNameUz(): string
    {
        $names = [
            self::TYPE_TEST => 'Test',
        ];

        return $names[$this->section_type] ?? ucfirst($this->section_type);
    }

    /**
     * Get icon for section type
     */
    public function getIcon(): string
    {
        $icons = [
            self::TYPE_TEST => 'clipboard-check',
        ];

        return $icons[$this->section_type] ?? 'document';
    }

    /**
     * Check if section is first in order
     */
    public function isFirstSection(): bool
    {
        return $this->order_number === 1;
    }

    /**
     * Check if section is last in order
     */
    public function isLastSection(): bool
    {
        $maxOrder = $this->olympiad->sections()->max('order_number');
        return $this->order_number === $maxOrder;
    }

    /**
     * Get next section
     */
    public function getNextSection(): ?OlympiadSection
    {
        return $this->olympiad->sections()
            ->where('order_number', '>', $this->order_number)
            ->orderBy('order_number')
            ->first();
    }

    /**
     * Get previous section
     */
    public function getPreviousSection(): ?OlympiadSection
    {
        return $this->olympiad->sections()
            ->where('order_number', '<', $this->order_number)
            ->orderByDesc('order_number')
            ->first();
    }

    /**
     * Calculate weighted score
     */
    public function calculateWeightedScore(float $rawScore): float
    {
        if ((float) $this->max_points <= 0) {
            return 0;
        }
        $percentage = ($rawScore / $this->max_points) * 100;
        return ($percentage * $this->weight_percent) / 100;
    }

    /**
     * Check if score passes this section
     */
    public function isPassing(float $score): bool
    {
        $percentage = ($score / $this->max_points) * 100;
        return $percentage >= $this->passing_percent;
    }
}
