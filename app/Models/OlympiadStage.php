<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OlympiadStage extends Model
{
    use HasFactory, HasUuids;

    // Stage constants
    public const STAGE_SCHOOL = 'school';
    public const STAGE_DISTRICT = 'district';
    public const STAGE_REGION = 'region';
    public const STAGE_NATIONAL = 'national';

    // Order levels
    public const ORDER_SCHOOL = 1;
    public const ORDER_DISTRICT = 2;
    public const ORDER_REGION = 3;
    public const ORDER_NATIONAL = 4;

    protected $fillable = [
        'name',
        'display_name',
        'display_name_uz',
        'order_level',
        'next_stage_id',
        'advancement_config',
        'reward_config',
        'icon',
        'color',
    ];

    protected function casts(): array
    {
        return [
            'advancement_config' => 'array',
            'reward_config' => 'array',
            'order_level' => 'integer',
        ];
    }

    // ==================== RELATIONSHIPS ====================

    public function olympiads(): HasMany
    {
        return $this->hasMany(Olympiad::class, 'stage_id');
    }

    public function nextStage(): BelongsTo
    {
        return $this->belongsTo(OlympiadStage::class, 'next_stage_id');
    }

    public function previousStage(): BelongsTo
    {
        return $this->belongsTo(OlympiadStage::class, 'next_stage_id', 'id')
            ->where('next_stage_id', $this->id);
    }

    // ==================== SCOPES ====================

    public function scopeOrdered($query)
    {
        return $query->orderBy('order_level');
    }

    // ==================== ACCESSORS ====================

    public function getLocalizedDisplayNameAttribute(): string
    {
        $locale = app()->getLocale();
        return $locale === 'uz' ? $this->display_name_uz : $this->display_name;
    }

    public function getIsFirstStageAttribute(): bool
    {
        return $this->order_level === self::ORDER_SCHOOL;
    }

    public function getIsFinalStageAttribute(): bool
    {
        return $this->order_level === self::ORDER_NATIONAL;
    }

    public function getHasNextStageAttribute(): bool
    {
        return !$this->is_final_stage && $this->next_stage_id !== null;
    }

    // ==================== METHODS ====================

    /**
     * Get default advancement config
     */
    public function getDefaultAdvancementConfig(): array
    {
        return [
            'top_n' => null,
            'top_percent' => 20,
            'min_score_percent' => 60,
            'auto_register' => true,
        ];
    }

    /**
     * Get default reward config for this stage
     */
    public function getDefaultRewardConfig(): array
    {
        $baseRewards = [
            '1' => ['discount_percent' => 60, 'bonus_coins' => 500, 'badge' => true],
            '2' => ['discount_percent' => 50, 'bonus_coins' => 300, 'badge' => true],
            '3' => ['discount_percent' => 40, 'bonus_coins' => 200, 'badge' => true],
            '4-10' => ['discount_percent' => 20, 'bonus_coins' => 100],
            '11+' => ['bonus_coins_percent' => 10],
        ];

        // National stage has cash prizes
        if ($this->name === self::STAGE_NATIONAL) {
            $baseRewards['1']['cash'] = 5000000;
            $baseRewards['2']['cash'] = 3000000;
            $baseRewards['3']['cash'] = 2000000;
            $baseRewards['4-10']['cash'] = 500000;
        }

        return $baseRewards;
    }

    /**
     * Get reward for a specific rank
     */
    public function getRewardForRank(int $rank): array
    {
        $config = $this->reward_config ?? $this->getDefaultRewardConfig();

        if (isset($config[(string) $rank])) {
            return $config[(string) $rank];
        }

        foreach ($config as $key => $value) {
            if (str_contains($key, '-')) {
                [$min, $max] = explode('-', $key);
                if ($rank >= (int) $min && $rank <= (int) $max) {
                    return $value;
                }
            } elseif (str_contains($key, '+')) {
                $min = (int) str_replace('+', '', $key);
                if ($rank >= $min) {
                    return $value;
                }
            }
        }

        return [];
    }

    /**
     * Get stage configuration for registration pricing
     */
    public static function getStageConfig(): array
    {
        return [
            self::STAGE_SCHOOL => [
                'price_uzs' => 20000,
                'price_coins' => 200,
                'duration_minutes' => 60,
                'difficulty' => 'beginner',
                'questions_count' => 20,
            ],
            self::STAGE_DISTRICT => [
                'price_uzs' => 35000,
                'price_coins' => 350,
                'duration_minutes' => 90,
                'difficulty' => 'intermediate',
                'questions_count' => 25,
            ],
            self::STAGE_REGION => [
                'price_uzs' => 50000,
                'price_coins' => 500,
                'duration_minutes' => 120,
                'difficulty' => 'advanced',
                'questions_count' => 30,
            ],
            self::STAGE_NATIONAL => [
                'price_uzs' => 100000,
                'price_coins' => 1000,
                'duration_minutes' => 150,
                'difficulty' => 'expert',
                'questions_count' => 30,
            ],
        ];
    }
}
