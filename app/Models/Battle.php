<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Battle extends Model
{
    use HasUuids;
    
    protected $fillable = [
        'player1_id', 'player2_id', 'direction_id',
        'total_rounds', 'time_per_question', 'status', 'current_round',
        'player1_score', 'player2_score', 'winner_id', 'is_draw',
        'player1_elo_change', 'player2_elo_change',
        'xp_reward', 'coin_reward',
        'started_at', 'ended_at', 'expires_at',
    ];
    
    protected $casts = [
        'total_rounds' => 'integer',
        'time_per_question' => 'integer',
        'current_round' => 'integer',
        'player1_score' => 'integer',
        'player2_score' => 'integer',
        'is_draw' => 'boolean',
        'player1_elo_change' => 'integer',
        'player2_elo_change' => 'integer',
        'xp_reward' => 'integer',
        'coin_reward' => 'integer',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'expires_at' => 'datetime',
    ];
    
    public function player1(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player1_id');
    }
    
    public function player2(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player2_id');
    }
    
    public function winner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'winner_id');
    }
    
    public function direction(): BelongsTo
    {
        return $this->belongsTo(Direction::class);
    }
    
    public function rounds(): HasMany
    {
        return $this->hasMany(BattleRound::class)->orderBy('round_number');
    }
    
    /**
     * Get opponent for a user
     */
    public function getOpponent(User $user): ?User
    {
        if ($this->player1_id === $user->id) {
            return $this->player2;
        }
        if ($this->player2_id === $user->id) {
            return $this->player1;
        }
        return null;
    }
    
    /**
     * Get user's score
     */
    public function getUserScore(User $user): int
    {
        if ($this->player1_id === $user->id) {
            return $this->player1_score;
        }
        if ($this->player2_id === $user->id) {
            return $this->player2_score;
        }
        return 0;
    }
    
    /**
     * Is user the winner?
     */
    public function isWinner(User $user): bool
    {
        return $this->winner_id === $user->id;
    }
}
