<?php

namespace App\Models\English;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserGameAttempt extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'user_game_attempts';

    protected $fillable = [
        'user_id',
        'game_id',
        'game_level_id',
        'score',
        'max_possible_score',
        'percentage',
        'stars_earned',
        'questions_total',
        'questions_correct',
        'questions_incorrect',
        'questions_skipped',
        'best_streak',
        'hints_used',
        'lives_remaining',
        'time_spent_seconds',
        'time_limit_seconds',
        'average_answer_time',
        'answers',
        'xp_earned',
        'coins_earned',
        'bonuses_earned',
        'status',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'score' => 'integer',
        'max_possible_score' => 'integer',
        'percentage' => 'decimal:2',
        'stars_earned' => 'integer',
        'questions_total' => 'integer',
        'questions_correct' => 'integer',
        'questions_incorrect' => 'integer',
        'questions_skipped' => 'integer',
        'best_streak' => 'integer',
        'hints_used' => 'integer',
        'lives_remaining' => 'integer',
        'time_spent_seconds' => 'integer',
        'time_limit_seconds' => 'integer',
        'average_answer_time' => 'decimal:2',
        'answers' => 'array',
        'xp_earned' => 'integer',
        'coins_earned' => 'integer',
        'bonuses_earned' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function game(): BelongsTo
    {
        return $this->belongsTo(EnglishGame::class, 'game_id');
    }

    public function gameLevel(): BelongsTo
    {
        return $this->belongsTo(EnglishGameLevel::class, 'game_level_id');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
}
