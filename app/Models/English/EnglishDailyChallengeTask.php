<?php

namespace App\Models\English;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnglishDailyChallengeTask extends Model
{
    use HasUuids;

    protected $table = 'english_daily_challenge_tasks';

    protected $fillable = [
        'daily_challenge_id',
        'task_type',
        'title',
        'title_uz',
        'description',
        'required_count',
        'requirements',
        'xp_reward',
        'coin_reward',
        'icon',
        'order_number',
        'is_bonus_task',
    ];

    protected $casts = [
        'required_count' => 'integer',
        'requirements' => 'array',
        'xp_reward' => 'integer',
        'coin_reward' => 'integer',
        'order_number' => 'integer',
        'is_bonus_task' => 'boolean',
    ];

    public function dailyChallenge(): BelongsTo
    {
        return $this->belongsTo(EnglishDailyChallenge::class, 'daily_challenge_id');
    }
}
