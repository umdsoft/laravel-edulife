<?php

namespace App\Services;

use App\Models\User;
use App\Models\Achievement;
use App\Models\UserAchievement;
use App\Models\CoinTransaction;
use App\Events\AchievementUnlocked;

class AchievementService
{
    /**
     * Check and unlock achievements for a user
     */
    public function checkAchievements(User $user, string $triggerType, array $data = []): array
    {
        $profile = $user->studentProfile;
        $unlockedAchievements = [];
        
        // Get relevant achievements
        $achievements = Achievement::where('is_active', true)
            ->whereJsonContains('requirements->type', $triggerType)
            ->get();
        
        foreach ($achievements as $achievement) {
            // Skip if already unlocked
            if ($user->achievements()->where('achievement_id', $achievement->id)->exists()) {
                continue;
            }
            
            // Check if requirements are met
            if ($this->checkRequirements($user, $achievement, $data)) {
                $this->unlock($user, $achievement);
                $unlockedAchievements[] = $achievement;
            }
        }
        
        return $unlockedAchievements;
    }
    
    /**
     * Check if achievement requirements are met
     */
    private function checkRequirements(User $user, Achievement $achievement, array $data): bool
    {
        $requirements = $achievement->requirements;
        $profile = $user->studentProfile;
        
        return match($requirements['type']) {
            'lessons_completed' => $profile->lessons_completed >= $requirements['value'],
            'courses_completed' => $profile->courses_completed >= $requirements['value'],
            'tests_passed' => $profile->tests_passed >= $requirements['value'],
            'streak_days' => $profile->streak_days >= $requirements['value'],
            'xp_earned' => $profile->xp >= $requirements['value'],
            'level_reached' => $profile->level >= $requirements['value'],
            'battles_won' => $profile->battles_won >= $requirements['value'],
            'battles_played' => ($profile->battles_won + $profile->battles_lost + $profile->battles_draw) >= $requirements['value'],
            'rank_reached' => $this->isRankHigherOrEqual($profile->rank, $requirements['value']),
            'coins_earned' => $profile->total_coins_earned >= $requirements['value'],
            'watch_time' => $profile->total_watch_time >= $requirements['value'],
            'first_lesson' => $profile->lessons_completed >= 1,
            'first_course' => $profile->courses_completed >= 1,
            'first_battle' => ($profile->battles_won + $profile->battles_lost) >= 1,
            'perfect_test' => isset($data['score']) && $data['score'] == 100,
            'win_streak' => isset($data['win_streak']) && $data['win_streak'] >= $requirements['value'],
            default => false,
        };
    }
    
    /**
     * Unlock achievement for user
     */
    public function unlock(User $user, Achievement $achievement): UserAchievement
    {
        $userAchievement = UserAchievement::create([
            'user_id' => $user->id,
            'achievement_id' => $achievement->id,
            'unlocked_at' => now(),
        ]);
        
        // Broadcast achievement unlocked
        broadcast(new AchievementUnlocked($user, $achievement))->toOthers();
        
        return $userAchievement;
    }
    
    /**
     * Claim achievement rewards
     */
    public function claim(User $user, UserAchievement $userAchievement): array
    {
        if ($userAchievement->is_claimed) {
            return ['success' => false, 'message' => 'Already claimed'];
        }
        
        $achievement = $userAchievement->achievement;
        $profile = $user->studentProfile;
        
        // Award XP
        if ($achievement->xp_reward > 0) {
            $profile->addXp($achievement->xp_reward);
        }
        
        // Award coins
        if ($achievement->coin_reward > 0) {
            $profile->addCoins($achievement->coin_reward);
            
            // Log transaction
            CoinTransaction::create([
                'user_id' => $user->id,
                'type' => 'earn',
                'amount' => $achievement->coin_reward,
                'balance_after' => $profile->coins,
                'source' => 'achievement',
                'description' => "Achievement: {$achievement->title}",
                'transactionable_type' => Achievement::class,
                'transactionable_id' => $achievement->id,
            ]);
        }
        
        $userAchievement->update([
            'is_claimed' => true,
            'claimed_at' => now(),
        ]);
        
        return [
            'success' => true,
            'xp_reward' => $achievement->xp_reward,
            'coin_reward' => $achievement->coin_reward,
        ];
    }
    
    private function isRankHigherOrEqual(string $currentRank, string $requiredRank): bool
    {
        $ranks = ['bronze' => 1, 'silver' => 2, 'gold' => 3, 'platinum' => 4, 'diamond' => 5, 'master' => 6];
        return ($ranks[$currentRank] ?? 0) >= ($ranks[$requiredRank] ?? 0);
    }
}
