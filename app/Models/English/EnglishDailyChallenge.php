<?php

namespace App\Models\English;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EnglishDailyChallenge extends Model
{
    use HasUuids;

    protected $table = 'english_daily_challenges';

    protected $fillable = [
        'challenge_date',
        'theme',
        'theme_uz',
        'theme_icon',
        'total_xp_reward',
        'total_coin_reward',
        'bonus_xp',
        'is_special',
        'special_config',
        'participants_count',
        'completions_count',
        'completion_rate',
        'is_active',
    ];

    protected $casts = [
        'challenge_date' => 'date',
        'total_xp_reward' => 'integer',
        'total_coin_reward' => 'integer',
        'bonus_xp' => 'integer',
        'is_special' => 'boolean',
        'special_config' => 'array',
        'participants_count' => 'integer',
        'completions_count' => 'integer',
        'completion_rate' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function tasks(): HasMany
    {
        return $this->hasMany(EnglishDailyChallengeTask::class, 'daily_challenge_id')->orderBy('order_number');
    }

    public function userChallenges(): HasMany
    {
        return $this->hasMany(UserDailyChallenge::class, 'daily_challenge_id');
    }

    public function scopeForToday($query)
    {
        return $query->where('challenge_date', now()->toDateString());
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
