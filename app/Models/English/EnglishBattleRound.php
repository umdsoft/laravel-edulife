<?php

namespace App\Models\English;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnglishBattleRound extends Model
{
    use HasUuids;

    protected $table = 'english_battle_rounds';

    protected $fillable = [
        'battle_id',
        'round_number',
        'question_id',
        'question_data',
        'player1_answer',
        'player1_correct',
        'player1_time_ms',
        'player1_points',
        'player1_answered_at',
        'player2_answer',
        'player2_correct',
        'player2_time_ms',
        'player2_points',
        'player2_answered_at',
        'round_winner_id',
        'status',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'round_number' => 'integer',
        'question_data' => 'array',
        'player1_correct' => 'boolean',
        'player1_time_ms' => 'integer',
        'player1_points' => 'integer',
        'player1_answered_at' => 'datetime',
        'player2_correct' => 'boolean',
        'player2_time_ms' => 'integer',
        'player2_points' => 'integer',
        'player2_answered_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function battle(): BelongsTo
    {
        return $this->belongsTo(EnglishBattle::class, 'battle_id');
    }

    public function question(): BelongsTo
    {
        return $this->belongsTo(EnglishBattleQuestion::class, 'question_id');
    }

    public function roundWinner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'round_winner_id');
    }

    public function calculatePoints(int $timeMs, bool $correct, int $timeLimit = 15000): int
    {
        if (!$correct)
            return 0;

        $basePoints = 10;
        $timeBonusMax = 5;
        $timeBonus = max(0, $timeBonusMax * (1 - ($timeMs / $timeLimit)));

        return $basePoints + (int) round($timeBonus);
    }
}
