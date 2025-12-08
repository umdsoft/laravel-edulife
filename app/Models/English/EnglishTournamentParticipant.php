<?php

namespace App\Models\English;

use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EnglishTournamentParticipant extends Model
{
    use HasUuids;

    protected $table = 'english_tournament_participants';

    protected $fillable = [
        'tournament_id',
        'user_id',
        'seed_number',
        'elo_at_registration',
        'registered_at',
        'entry_fee_paid',
        'paid_at',
        'current_round',
        'wins',
        'losses',
        'total_score',
        'matches_played',
        'is_eliminated',
        'eliminated_in_round',
        'eliminated_by_battle_id',
        'final_position',
        'prizes_received',
        'prizes_claimed',
        'status',
    ];

    protected $casts = [
        'seed_number' => 'integer',
        'elo_at_registration' => 'integer',
        'registered_at' => 'datetime',
        'entry_fee_paid' => 'boolean',
        'paid_at' => 'datetime',
        'current_round' => 'integer',
        'wins' => 'integer',
        'losses' => 'integer',
        'total_score' => 'integer',
        'matches_played' => 'integer',
        'is_eliminated' => 'boolean',
        'eliminated_in_round' => 'integer',
        'final_position' => 'integer',
        'prizes_received' => 'array',
        'prizes_claimed' => 'boolean',
    ];

    public function tournament(): BelongsTo
    {
        return $this->belongsTo(EnglishTournament::class, 'tournament_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
