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

    // Section type constants
    public const TYPE_TEST = 'test';
    public const TYPE_LISTENING = 'listening';
    public const TYPE_READING = 'reading';
    public const TYPE_WRITING = 'writing';
    public const TYPE_SPEAKING = 'speaking';
    public const TYPE_CODING = 'coding';

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

    public function codingProblems(): HasMany
    {
        return $this->hasMany(OlympiadCodingProblem::class, 'section_id')->orderBy('order_number');
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

    public function getIsListeningSectionAttribute(): bool
    {
        return $this->section_type === self::TYPE_LISTENING;
    }

    public function getIsReadingSectionAttribute(): bool
    {
        return $this->section_type === self::TYPE_READING;
    }

    public function getIsWritingSectionAttribute(): bool
    {
        return $this->section_type === self::TYPE_WRITING;
    }

    public function getIsSpeakingSectionAttribute(): bool
    {
        return $this->section_type === self::TYPE_SPEAKING;
    }

    public function getIsCodingSectionAttribute(): bool
    {
        return $this->section_type === self::TYPE_CODING;
    }

    public function getIsAutoGradedAttribute(): bool
    {
        return !$this->requires_manual_grading;
    }

    public function getQuestionsCountAttribute(): int
    {
        return $this->section_config['questions_count'] ?? $this->questions()->count();
    }

    public function getProblemsCountAttribute(): int
    {
        return $this->section_config['problems_count'] ?? $this->codingProblems()->count();
    }

    public function getDurationInSecondsAttribute(): int
    {
        return $this->duration_minutes * 60;
    }

    public function getHasAudioAttribute(): bool
    {
        return $this->section_type === self::TYPE_LISTENING ||
               ($this->section_type === self::TYPE_SPEAKING && ($this->section_config['record_audio'] ?? false));
    }

    public function getAudioPlaysAttribute(): int
    {
        return $this->section_config['audio_plays'] ?? 2;
    }

    public function getGradingRubricAttribute(): array
    {
        return $this->section_config['grading_rubric'] ?? [];
    }

    public function getAllowedLanguagesAttribute(): array
    {
        return $this->section_config['allowed_languages'] ?? ['python', 'javascript', 'cpp', 'java'];
    }

    // ==================== METHODS ====================

    /**
     * Get display name based on section type
     */
    public function getDisplayName(): string
    {
        $names = [
            self::TYPE_TEST => 'Test',
            self::TYPE_LISTENING => 'Listening',
            self::TYPE_READING => 'Reading',
            self::TYPE_WRITING => 'Writing',
            self::TYPE_SPEAKING => 'Speaking',
            self::TYPE_CODING => 'Coding',
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
            self::TYPE_LISTENING => 'Tinglash',
            self::TYPE_READING => "O'qish",
            self::TYPE_WRITING => 'Yozish',
            self::TYPE_SPEAKING => 'Gapirish',
            self::TYPE_CODING => 'Dasturlash',
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
            self::TYPE_LISTENING => 'headphones',
            self::TYPE_READING => 'book-open',
            self::TYPE_WRITING => 'pencil',
            self::TYPE_SPEAKING => 'microphone',
            self::TYPE_CODING => 'code',
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
