<?php

namespace App\Models\English;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserStudyStreak extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'user_study_streaks';

    protected $fillable = [
        'user_id',
        'date',
        'day_number',
        'xp_earned',
        'minutes_studied',
        'lessons_completed',
        'words_reviewed',
        'games_played',
        'daily_goal_met',
        'challenges_completed',
        'used_freeze',
    ];

    protected $casts = [
        'date' => 'date',
        'day_number' => 'integer',
        'xp_earned' => 'integer',
        'minutes_studied' => 'integer',
        'lessons_completed' => 'integer',
        'words_reviewed' => 'integer',
        'games_played' => 'integer',
        'daily_goal_met' => 'boolean',
        'challenges_completed' => 'array',
        'used_freeze' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
