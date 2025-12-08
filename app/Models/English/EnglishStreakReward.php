<?php

namespace App\Models\English;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class EnglishStreakReward extends Model
{
    use HasUuids;

    protected $table = 'english_streak_rewards';

    protected $fillable = [
        'streak_days',
        'title',
        'title_uz',
        'message',
        'message_uz',
        'xp_reward',
        'coin_reward',
        'gem_reward',
        'streak_freeze_reward',
        'badge_id',
        'special_rewards',
        'icon',
        'animation',
        'is_active',
    ];

    protected $casts = [
        'streak_days' => 'integer',
        'xp_reward' => 'integer',
        'coin_reward' => 'integer',
        'gem_reward' => 'integer',
        'streak_freeze_reward' => 'integer',
        'special_rewards' => 'array',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForStreak($query, int $days)
    {
        return $query->where('streak_days', $days);
    }
}
