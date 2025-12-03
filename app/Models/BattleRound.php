<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BattleRound extends Model
{
    use HasUuids;
    
    protected $fillable = [
        'battle_id', 'question_id', 'round_number',
        'player1_answer', 'player1_correct', 'player1_time',
        'player2_answer', 'player2_correct', 'player2_time',
        'round_winner_id', 'is_draw',
        'player1_points', 'player2_points',
        'started_at', 'ended_at',
    ];
    
    protected $casts = [
        'round_number' => 'integer',
        'player1_answer' => 'array',
        'player1_correct' => 'boolean',
        'player1_time' => 'integer',
        'player2_answer' => 'array',
        'player2_correct' => 'boolean',
        'player2_time' => 'integer',
        'is_draw' => 'boolean',
        'player1_points' => 'integer',
        'player2_points' => 'integer',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];
    
    public function battle(): BelongsTo
    {
        return $this->belongsTo(Battle::class);
    }
    
    public function question(): BelongsTo
    {
        return $this->belongsTo(Question::class);
    }
    
    public function roundWinner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'round_winner_id');
    }
}
