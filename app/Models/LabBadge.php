<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LabBadge extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'slug',
        'name',
        'name_uz',
        'name_ru',
        'description',
        'description_uz',
        'description_ru',
        'icon',
        'icon_svg',
        'color',
        'background_gradient',
        'rarity',
        'earn_condition',
        'xp_reward',
        'coin_reward',
        'is_active',
        'is_secret',
        'order_number',
    ];

    protected $casts = [
        'earn_condition' => 'array',
        'xp_reward' => 'integer',
        'coin_reward' => 'integer',
        'is_active' => 'boolean',
        'is_secret' => 'boolean',
        'order_number' => 'integer',
    ];

    protected $attributes = [
        'rarity' => 'common',
        'xp_reward' => 50,
        'coin_reward' => 10,
        'is_active' => true,
        'is_secret' => false,
        'order_number' => 0,
    ];

    // ═══════════════════════════════════════════════════════════════════════
    // CONSTANTS
    // ═══════════════════════════════════════════════════════════════════════

    public const RARITY_COLORS = [
        'common' => '#9CA3AF',     // Gray
        'uncommon' => '#10B981',   // Green
        'rare' => '#3B82F6',       // Blue
        'epic' => '#8B5CF6',       // Purple
        'legendary' => '#F59E0B',  // Gold
    ];

    public const CONDITION_TYPES = [
        'complete_experiments',
        'perfect_score',
        'complete_category',
        'streak',
        'speed_complete',
        'first_experiment',
        'total_xp',
        'total_time',
    ];

    // ═══════════════════════════════════════════════════════════════════════
    // SCOPES
    // ═══════════════════════════════════════════════════════════════════════

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeVisible($query)
    {
        return $query->where('is_secret', false);
    }

    public function scopeByRarity($query, string $rarity)
    {
        return $query->where('rarity', $rarity);
    }

    public function scopeOrdered($query)
    {
        return $query->orderByRaw("FIELD(rarity, 'legendary', 'epic', 'rare', 'uncommon', 'common')")
            ->orderBy('order_number');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // ACCESSORS
    // ═══════════════════════════════════════════════════════════════════════

    public function getLocalizedNameAttribute(): string
    {
        $locale = app()->getLocale();
        return $this->{"name_{$locale}"} ?? $this->name_uz ?? $this->name;
    }

    public function getLocalizedDescriptionAttribute(): ?string
    {
        $locale = app()->getLocale();
        return $this->{"description_{$locale}"} ?? $this->description_uz ?? $this->description;
    }

    public function getRarityColorAttribute(): string
    {
        return self::RARITY_COLORS[$this->rarity] ?? self::RARITY_COLORS['common'];
    }

    public function getConditionTypeAttribute(): ?string
    {
        return $this->earn_condition['type'] ?? null;
    }

    public function getConditionDescriptionAttribute(): string
    {
        $condition = $this->earn_condition;
        $type = $condition['type'] ?? '';
        
        return match ($type) {
            'complete_experiments' => sprintf(
                '%d ta tajribani yakunlang%s',
                $condition['count'] ?? 0,
                isset($condition['category']) ? " ({$condition['category']} bo'limida)" : ''
            ),
            'perfect_score' => sprintf('%d ta tajribada 100%% ball oling', $condition['count'] ?? 0),
            'complete_category' => sprintf("'%s' bo'limini to'liq yakunlang", $condition['category'] ?? ''),
            'streak' => sprintf('%d kun ketma-ket lab bajaring', $condition['days'] ?? 0),
            'speed_complete' => sprintf(
                '%s qiyinlikdagi tajribani %d daqiqadan kam vaqtda yakunlang',
                $condition['difficulty'] ?? 'medium',
                $condition['under_minutes'] ?? 0
            ),
            'first_experiment' => 'Birinchi tajribangizni yakunlang',
            'total_xp' => sprintf('%d XP to\'plang', $condition['amount'] ?? 0),
            default => 'Maxsus shart',
        };
    }

    // ═══════════════════════════════════════════════════════════════════════
    // METHODS
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Check if user has earned this badge
     */
    public function checkEarnedBy(User $user): bool
    {
        $condition = $this->earn_condition;
        $type = $condition['type'] ?? '';
        
        $progress = LabUserProgress::where('user_id', $user->id)->first();
        if (!$progress) return false;
        
        return match ($type) {
            'complete_experiments' => $this->checkCompleteExperiments($user, $condition),
            'perfect_score' => ($progress->perfect_scores ?? 0) >= ($condition['count'] ?? 0),
            'complete_category' => $this->checkCompleteCategory($user, $condition),
            'streak' => ($progress->current_streak ?? 0) >= ($condition['days'] ?? 0),
            'speed_complete' => $this->checkSpeedComplete($user, $condition),
            'first_experiment' => ($progress->completed_experiments ?? 0) >= 1,
            'total_xp' => ($progress->total_xp ?? 0) >= ($condition['amount'] ?? 0),
            default => false,
        };
    }

    protected function checkCompleteExperiments(User $user, array $condition): bool
    {
        $query = LabAttempt::where('user_id', $user->id)
            ->where('status', 'completed');
        
        if (isset($condition['category'])) {
            $query->whereHas('experiment', function ($q) use ($condition) {
                $q->whereHas('category', function ($q2) use ($condition) {
                    $q2->where('slug', $condition['category']);
                });
            });
        }
        
        return $query->distinct('experiment_id')->count('experiment_id') >= ($condition['count'] ?? 0);
    }

    protected function checkCompleteCategory(User $user, array $condition): bool
    {
        $category = LabCategory::where('slug', $condition['category'] ?? '')->first();
        if (!$category) return false;
        
        $totalExperiments = $category->experiments()->where('status', 'active')->count();
        if ($totalExperiments === 0) return false;
        
        $completedExperiments = LabAttempt::where('user_id', $user->id)
            ->where('status', 'completed')
            ->whereHas('experiment', function ($q) use ($category) {
                $q->where('category_id', $category->id);
            })
            ->distinct('experiment_id')
            ->count('experiment_id');
        
        return $completedExperiments >= $totalExperiments;
    }

    protected function checkSpeedComplete(User $user, array $condition): bool
    {
        $query = LabAttempt::where('user_id', $user->id)
            ->where('status', 'completed')
            ->whereRaw('time_spent_seconds < ?', [($condition['under_minutes'] ?? 15) * 60]);
        
        if (isset($condition['difficulty'])) {
            $query->whereHas('experiment', function ($q) use ($condition) {
                $q->where('difficulty', $condition['difficulty']);
            });
        }
        
        return $query->exists();
    }

    /**
     * Award badge to user
     */
    public function awardTo(User $user): bool
    {
        $progress = LabUserProgress::firstOrCreate(['user_id' => $user->id]);
        
        $earnedBadges = $progress->badges_earned ?? [];
        
        // Already earned?
        if (in_array($this->id, $earnedBadges)) {
            return false;
        }
        
        // Add badge
        $earnedBadges[] = $this->id;
        $progress->badges_earned = $earnedBadges;
        
        // Award XP and coins
        $progress->total_xp += $this->xp_reward;
        $progress->save();
        
        // Award coins to user
        if ($this->coin_reward > 0) {
            $user->addCoins($this->coin_reward, 'lab_badge', "Badge olindi: {$this->localized_name}");
        }
        
        return true;
    }
}
