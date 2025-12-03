<?php

namespace App\Services;

use App\Models\User;
use App\Models\Battle;

class EloRatingService
{
    // K-factor (rating change speed)
    const K_FACTOR_NEW = 40;      // New players (< 30 games)
    const K_FACTOR_NORMAL = 20;   // Normal players
    const K_FACTOR_HIGH = 10;     // High rated players (> 2400)
    
    /**
     * Calculate new ratings after a battle
     */
    public function calculateNewRatings(Battle $battle): array
    {
        $player1 = $battle->player1;
        $player2 = $battle->player2;
        
        $rating1 = $player1->studentProfile->elo_rating;
        $rating2 = $player2->studentProfile->elo_rating;
        
        // Expected scores
        $expected1 = $this->getExpectedScore($rating1, $rating2);
        $expected2 = $this->getExpectedScore($rating2, $rating1);
        
        // Actual scores (1 = win, 0.5 = draw, 0 = loss)
        if ($battle->is_draw) {
            $actual1 = 0.5;
            $actual2 = 0.5;
        } elseif ($battle->winner_id === $player1->id) {
            $actual1 = 1;
            $actual2 = 0;
        } else {
            $actual1 = 0;
            $actual2 = 1;
        }
        
        // K-factors
        $k1 = $this->getKFactor($player1);
        $k2 = $this->getKFactor($player2);
        
        // New ratings
        $newRating1 = $rating1 + round($k1 * ($actual1 - $expected1));
        $newRating2 = $rating2 + round($k2 * ($actual2 - $expected2));
        
        // Minimum rating is 100
        $newRating1 = max(100, $newRating1);
        $newRating2 = max(100, $newRating2);
        
        return [
            'player1' => [
                'old_rating' => $rating1,
                'new_rating' => $newRating1,
                'change' => $newRating1 - $rating1,
            ],
            'player2' => [
                'old_rating' => $rating2,
                'new_rating' => $newRating2,
                'change' => $newRating2 - $rating2,
            ],
        ];
    }
    
    /**
     * Expected score based on ratings
     */
    private function getExpectedScore(int $playerRating, int $opponentRating): float
    {
        return 1 / (1 + pow(10, ($opponentRating - $playerRating) / 400));
    }
    
    /**
     * Get K-factor for a player
     */
    private function getKFactor(User $player): int
    {
        $profile = $player->studentProfile;
        $totalBattles = $profile->battles_won + $profile->battles_lost + $profile->battles_draw;
        
        if ($totalBattles < 30) {
            return self::K_FACTOR_NEW;
        }
        
        if ($profile->elo_rating >= 2400) {
            return self::K_FACTOR_HIGH;
        }
        
        return self::K_FACTOR_NORMAL;
    }
    
    /**
     * Update rank based on ELO
     */
    public function updateRank(User $player): string
    {
        $elo = $player->studentProfile->elo_rating;
        $newRank = $this->calculateRank($elo);
        
        $player->studentProfile->update(['rank' => $newRank]);
        
        return $newRank;
    }
    
    private function calculateRank(int $elo): string
    {
        return match(true) {
            $elo >= 2400 => 'master',
            $elo >= 2000 => 'diamond',
            $elo >= 1600 => 'platinum',
            $elo >= 1200 => 'gold',
            $elo >= 800 => 'silver',
            default => 'bronze',
        };
    }
}
