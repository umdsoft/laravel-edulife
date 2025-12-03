<?php

namespace App\Services;

use App\Models\Tournament;
use App\Models\TournamentParticipant;
use App\Models\User;

class TournamentService
{
    public function joinTournament(Tournament $tournament, User $user)
    {
        if ($tournament->participants()->where('user_id', $user->id)->exists()) {
            return false; // Already joined
        }

        if ($tournament->participants()->count() >= $tournament->max_participants) {
            return false; // Full
        }

        TournamentParticipant::create([
            'tournament_id' => $tournament->id,
            'user_id' => $user->id,
            'status' => 'active',
        ]);

        return true;
    }

    public function startTournament(Tournament $tournament)
    {
        // Logic to generate brackets and start matches
        // This is a simplified placeholder
        $tournament->update(['status' => 'active']);
    }
}
