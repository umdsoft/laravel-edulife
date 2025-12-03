<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentParticipant extends Model
{
    use HasUuids;
    
    protected $fillable = [
        'tournament_id', 'user_id',
        'status', 'current_round',
        'matches_played', 'matches_won', 'matches_lost', 'total_score',
        'seed', 'final_position', 'prize_claimed',
        'registered_at', 'eliminated_at',
    ];
    
    protected $casts = [
        'current_round' => 'integer',
        'matches_played' => 'integer',
        'matches_won' => 'integer',
        'matches_lost' => 'integer',
        'total_score' => 'integer',
        'seed' => 'integer',
        'final_position' => 'integer',
        'prize_claimed' => 'boolean',
        'registered_at' => 'datetime',
        'eliminated_at' => 'datetime',
    ];
    
    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
