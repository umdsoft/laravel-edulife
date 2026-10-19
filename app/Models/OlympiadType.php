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

    // Olympiad types: the platform teaches only Mathematics and Physics
    public const TYPE_MATH = 'math';
    public const TYPE_PHYSICS = 'physics';

    public const TYPES = [self::TYPE_MATH, self::TYPE_PHYSICS];

    // Section types (math and physics olympiads use test sections only)
    public const SECTION_TEST = 'test';

    /**
     * The math / physics types and the stages are reference data; a database
     * that never ran OlympiadSeeder had none, so no olympiad could be created.
     * Seeds them (idempotently) when missing.
     */
    public static function ensureDefaults(): void
    {
        if (static::query()->exists() && OlympiadStage::query()->exists()) {
            return;
        }

        // seeders run unguarded (the stage rows carry their own ids)
        Model::unguarded(fn () => (new \Database\Seeders\OlympiadSeeder)->run());
    }

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

    public function scopeMath($query)
    {
        return $query->where('name', self::TYPE_MATH);
    }

    public function scopePhysics($query)
    {
        return $query->where('name', self::TYPE_PHYSICS);
    }

    // ==================== ACCESSORS ====================

    /**
     * The UI is Uzbek: every page read display_name and showed "School Stage" /
     * "Physics Olympiad" (QA2-A-06). Serve the Uzbek name wherever it is loaded.
     */
    public function getDisplayNameAttribute($value): ?string
    {
        $uz = $this->attributes['display_name_uz'] ?? null;

        return app()->getLocale() === 'uz' && filled($uz) ? $uz : $value;
    }

    public function getLocalizedDisplayNameAttribute(): ?string
    {
        return $this->display_name;
    }

    public function getIsMathTypeAttribute(): bool
    {
        return $this->name === self::TYPE_MATH;
    }

    public function getIsPhysicsTypeAttribute(): bool
    {
        return $this->name === self::TYPE_PHYSICS;
    }

    // ==================== METHODS ====================

    /**
     * Get default sections config for this olympiad type
     */
    public function getDefaultSectionsConfig(): array
    {
        $configs = [
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
            self::TYPE_PHYSICS => [
                'test' => [
                    'enabled' => true,
                    'duration_minutes' => 120,
                    'questions_count' => 30,
                    'max_points' => 100,
                    'categories' => [
                        'mechanics' => 8,
                        'thermodynamics' => 5,
                        'electricity_magnetism' => 7,
                        'optics_waves' => 5,
                        'atomic_physics' => 5,
                    ],
                    'order' => 1,
                ],
            ],
        ];

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
