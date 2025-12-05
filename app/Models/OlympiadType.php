<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OlympiadType extends Model
{
    use HasFactory, HasUuids;

    // Available olympiad type constants
    public const TYPE_LANGUAGE_ENGLISH = 'language_english';
    public const TYPE_LANGUAGE_RUSSIAN = 'language_russian';
    public const TYPE_PROGRAMMING = 'programming';
    public const TYPE_MATH = 'math';

    // Section types
    public const SECTION_TEST = 'test';
    public const SECTION_LISTENING = 'listening';
    public const SECTION_READING = 'reading';
    public const SECTION_WRITING = 'writing';
    public const SECTION_SPEAKING = 'speaking';
    public const SECTION_CODING = 'coding';

    protected $fillable = [
        'direction_id',
        'name',
        'display_name',
        'display_name_uz',
        'description',
        'sections',
        'section_weights',
        'icon',
        'color',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'sections' => 'array',
            'section_weights' => 'array',
            'is_active' => 'boolean',
        ];
    }

    // ==================== RELATIONSHIPS ====================

    public function direction(): BelongsTo
    {
        return $this->belongsTo(Direction::class);
    }

    public function olympiads(): HasMany
    {
        return $this->hasMany(Olympiad::class);
    }

    public function series(): HasMany
    {
        return $this->hasMany(OlympiadSeries::class);
    }

    public function questionBank(): HasMany
    {
        return $this->hasMany(QuestionBank::class);
    }

    // ==================== SCOPES ====================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeLanguage($query)
    {
        return $query->whereIn('name', [self::TYPE_LANGUAGE_ENGLISH, self::TYPE_LANGUAGE_RUSSIAN]);
    }

    public function scopeProgramming($query)
    {
        return $query->where('name', self::TYPE_PROGRAMMING);
    }

    public function scopeMath($query)
    {
        return $query->where('name', self::TYPE_MATH);
    }

    // ==================== ACCESSORS ====================

    public function getLocalizedDisplayNameAttribute(): string
    {
        $locale = app()->getLocale();
        return $locale === 'uz' ? $this->display_name_uz : $this->display_name;
    }

    public function getIsLanguageTypeAttribute(): bool
    {
        return in_array($this->name, [self::TYPE_LANGUAGE_ENGLISH, self::TYPE_LANGUAGE_RUSSIAN]);
    }

    public function getIsProgrammingTypeAttribute(): bool
    {
        return $this->name === self::TYPE_PROGRAMMING;
    }

    public function getIsMathTypeAttribute(): bool
    {
        return $this->name === self::TYPE_MATH;
    }

    public function getRequiresManualGradingAttribute(): bool
    {
        return in_array(self::SECTION_WRITING, $this->sections ?? []) ||
               in_array(self::SECTION_SPEAKING, $this->sections ?? []);
    }

    // ==================== METHODS ====================

    /**
     * Get default sections config for this olympiad type
     */
    public function getDefaultSectionsConfig(): array
    {
        $configs = [
            self::TYPE_LANGUAGE_ENGLISH => [
                'test' => [
                    'enabled' => true,
                    'duration_minutes' => 30,
                    'questions_count' => 30,
                    'max_points' => 100,
                    'passing_percent' => 60,
                    'order' => 1,
                ],
                'listening' => [
                    'enabled' => true,
                    'duration_minutes' => 25,
                    'questions_count' => 20,
                    'max_points' => 100,
                    'audio_plays' => 2,
                    'order' => 2,
                ],
                'reading' => [
                    'enabled' => true,
                    'duration_minutes' => 30,
                    'passages_count' => 3,
                    'questions_count' => 25,
                    'max_points' => 100,
                    'order' => 3,
                ],
                'writing' => [
                    'enabled' => true,
                    'duration_minutes' => 40,
                    'tasks_count' => 2,
                    'max_points' => 100,
                    'requires_manual_grading' => true,
                    'grading_rubric' => [
                        'grammar' => 25,
                        'vocabulary' => 25,
                        'coherence' => 25,
                        'task_response' => 25,
                    ],
                    'order' => 4,
                ],
                'speaking' => [
                    'enabled' => true,
                    'duration_minutes' => 15,
                    'tasks_count' => 3,
                    'max_points' => 100,
                    'requires_manual_grading' => true,
                    'record_audio' => true,
                    'grading_rubric' => [
                        'fluency' => 25,
                        'pronunciation' => 25,
                        'grammar' => 25,
                        'vocabulary' => 25,
                    ],
                    'order' => 5,
                ],
            ],
            self::TYPE_PROGRAMMING => [
                'test' => [
                    'enabled' => true,
                    'duration_minutes' => 45,
                    'questions_count' => 30,
                    'max_points' => 100,
                    'order' => 1,
                ],
                'coding' => [
                    'enabled' => true,
                    'duration_minutes' => 120,
                    'problems_count' => 5,
                    'max_points' => 500,
                    'allowed_languages' => ['python', 'javascript', 'cpp', 'java'],
                    'partial_scoring' => true,
                    'order' => 2,
                ],
            ],
            self::TYPE_MATH => [
                'test' => [
                    'enabled' => true,
                    'duration_minutes' => 120,
                    'questions_count' => 30,
                    'max_points' => 100,
                    'categories' => [
                        'algebra' => 8,
                        'geometry' => 8,
                        'combinatorics' => 5,
                        'number_theory' => 5,
                        'logic' => 4,
                    ],
                    'order' => 1,
                ],
            ],
        ];

        // Russian language uses same config as English
        $configs[self::TYPE_LANGUAGE_RUSSIAN] = $configs[self::TYPE_LANGUAGE_ENGLISH];

        return $configs[$this->name] ?? [];
    }

    /**
     * Calculate total duration for all sections
     */
    public function getTotalDurationMinutes(): int
    {
        $config = $this->getDefaultSectionsConfig();
        return collect($config)->sum('duration_minutes');
    }

    /**
     * Calculate total max points for all sections
     */
    public function getTotalMaxPoints(): int
    {
        $config = $this->getDefaultSectionsConfig();
        return collect($config)->sum('max_points');
    }
}
