<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TournamentMatch extends Model
{
    use HasUuids;
    
    protected $fillable = [
        'tournament_id', 'round_number', 'match_number', 'bracket_position',
        'participant1_id', 'participant2_id',
        'participant1_score', 'participant2_score',
        'winner_id', 'is_bye', 'status', 'battle_id',
        'scheduled_at', 'started_at', 'ended_at',
    ];
    
    protected $casts = [
        'round_number' => 'integer',
        'match_number' => 'integer',
        'participant1_score' => 'integer',
        'participant2_score' => 'integer',
        'is_bye' => 'boolean',
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];
    
    public function tournament(): BelongsTo
    {
        return $this->belongsTo(Tournament::class);
    }
    
    public function participant1(): BelongsTo
    {
        return $this->belongsTo(TournamentParticipant::class, 'participant1_id');
    }
    
    public function participant2(): BelongsTo
    {
        return $this->belongsTo(TournamentParticipant::class, 'participant2_id');
    }
    
    public function winner(): BelongsTo
    {
        return $this->belongsTo(TournamentParticipant::class, 'winner_id');
    }
    
    public function battle(): BelongsTo
    {
        return $this->belongsTo(Battle::class);
    }
}
