<?php

namespace App\Models\English;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnglishGameReward extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'english_game_rewards';

    protected $fillable = [
        'game_id',
        'reward_type',
        'condition',
        'xp_reward',
        'coin_reward',
        'badge_id',
        'achievement_id',
        'title',
        'title_uz',
        'description',
        'icon',
        'is_one_time',
        'is_active',
    ];

    protected $casts = [
        'condition' => 'array',
        'xp_reward' => 'integer',
        'coin_reward' => 'integer',
        'is_one_time' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function game(): BelongsTo
    {
        return $this->belongsTo(EnglishGame::class, 'game_id');
    }
}
