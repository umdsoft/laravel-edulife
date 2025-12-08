<?php

namespace App\Models\English;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EnglishAchievement extends Model
{
    use HasUuids;

    protected $table = 'english_achievements';

    protected $fillable = [
        'category_id',
        'slug',
        'code',
        'name',
        'name_uz',
        'description',
        'description_uz',
        'tier',
        'tier_level',
        'requirement_type',
        'requirement_key',
        'requirement_value',
        'requirement_config',
        'xp_reward',
        'coin_reward',
        'gem_reward',
        'badge_image',
        'badge_color',
        'special_rewards',
        'icon',
        'locked_icon',
        'rarity',
        'unlock_percentage',
        'next_tier_id',
        'previous_tier_id',
        'is_secret',
        'is_active',
        'order_number',
    ];

    protected $casts = [
        'tier_level' => 'integer',
        'requirement_value' => 'integer',
        'requirement_config' => 'array',
        'xp_reward' => 'integer',
        'coin_reward' => 'integer',
        'gem_reward' => 'integer',
        'special_rewards' => 'array',
        'unlock_percentage' => 'decimal:2',
        'is_secret' => 'boolean',
        'is_active' => 'boolean',
        'order_number' => 'integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(EnglishAchievementCategory::class, 'category_id');
    }

    public function userAchievements(): HasMany
    {
        return $this->hasMany(UserAchievement::class, 'achievement_id');
    }

    public function nextTier(): BelongsTo
    {
        return $this->belongsTo(EnglishAchievement::class, 'next_tier_id');
    }

    public function previousTier(): BelongsTo
    {
        return $this->belongsTo(EnglishAchievement::class, 'previous_tier_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeVisible($query)
    {
        return $query->where('is_secret', false);
    }

    public function scopeByTier($query, string $tier)
    {
        return $query->where('tier', $tier);
    }
}
