<?php

namespace App\Services;

use App\Models\User;
use App\Models\Battle;
use App\Models\BattleRound;
use App\Models\Question;
use App\Events\BattleMatchFound;
use Illuminate\Support\Facades\Cache;

class MatchmakingService
{
    const SEARCH_TIMEOUT = 60; // seconds
    const ELO_RANGE_INITIAL = 100;
    const ELO_RANGE_INCREMENT = 50;
    const ELO_RANGE_MAX = 500;
    
    /**
     * Add player to matchmaking queue
     */
    public function startSearch(User $user, ?string $directionId = null): Battle
    {
        $profile = $user->studentProfile;
        
        // Check if already in a battle
        $activeBattle = Battle::where(function ($q) use ($user) {
            $q->where('player1_id', $user->id)
              ->orWhere('player2_id', $user->id);
        })->whereIn('status', ['waiting', 'in_progress'])->first();
        
        if ($activeBattle) {
            return $activeBattle;
        }
        
        // Try to find a match
        $match = $this->findMatch($user, $directionId);
        
        if ($match) {
            return $this->joinBattle($match, $user);
        }
        
        // Create waiting battle
        return Battle::create([
            'player1_id' => $user->id,
            'direction_id' => $directionId,
            'status' => 'waiting',
            'expires_at' => now()->addSeconds(self::SEARCH_TIMEOUT),
        ]);
    }
    
    /**
     * Find a suitable match
     */
    private function findMatch(User $user, ?string $directionId): ?Battle
    {
        $profile = $user->studentProfile;
        $userElo = $profile->elo_rating;
        
        // Search with expanding ELO range
        $searchTime = Cache::get("matchmaking_search_{$user->id}", now());
        $elapsedSeconds = now()->diffInSeconds($searchTime);
        
        // Expand range over time
        $eloRange = min(
            self::ELO_RANGE_MAX,
            self::ELO_RANGE_INITIAL + (floor($elapsedSeconds / 10) * self::ELO_RANGE_INCREMENT)
        );
        
        $query = Battle::where('status', 'waiting')
            ->where('player1_id', '!=', $user->id)
            ->whereNull('player2_id')
            ->where('expires_at', '>', now())
            ->whereHas('player1.studentProfile', function ($q) use ($userElo, $eloRange) {
                $q->whereBetween('elo_rating', [$userElo - $eloRange, $userElo + $eloRange]);
            });
        
        // Filter by direction if specified
        if ($directionId) {
            $query->where(function ($q) use ($directionId) {
                $q->where('direction_id', $directionId)
                  ->orWhereNull('direction_id');
            });
        }
        
        return $query->orderBy('created_at')->first();
    }
    
    /**
     * Join an existing battle
     */
    private function joinBattle(Battle $battle, User $player2): Battle
    {
        // Get questions for battle
        $questions = $this->selectQuestions($battle);
        
        $battle->update([
            'player2_id' => $player2->id,
            'status' => 'in_progress',
            'started_at' => now(),
            'current_round' => 1,
        ]);
        
        // Create rounds
        foreach ($questions as $index => $question) {
            BattleRound::create([
                'battle_id' => $battle->id,
                'question_id' => $question->id,
                'round_number' => $index + 1,
            ]);
        }
        
        // Broadcast match found
        broadcast(new BattleMatchFound($battle))->toOthers();
        
        return $battle->fresh(['player1', 'player2', 'rounds']);
    }
    
    /**
     * Select questions for battle
     */
    private function selectQuestions(Battle $battle): \Illuminate\Support\Collection
    {
        $query = Question::where('is_active', true)
            ->whereIn('type', ['single_choice', 'true_false']) // Only fast answer types
            ->inRandomOrder();
        
        if ($battle->direction_id) {
            $query->whereHas('test.course', function ($q) use ($battle) {
                $q->where('direction_id', $battle->direction_id);
            });
        }
        
        return $query->limit($battle->total_rounds)->get();
    }
    
    /**
     * Cancel search
     */
    public function cancelSearch(User $user): bool
    {
        $battle = Battle::where('player1_id', $user->id)
            ->where('status', 'waiting')
            ->first();
        
        if ($battle) {
            $battle->update(['status' => 'cancelled']);
            Cache::forget("matchmaking_search_{$user->id}");
            return true;
        }
        
        return false;
    }
}
