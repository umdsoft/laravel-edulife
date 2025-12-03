<?php

namespace App\Services;

use App\Models\User;
use App\Models\DailyMission;
use App\Models\UserMission;
use App\Models\CoinTransaction;
use Carbon\Carbon;

class MissionService
{
    const MISSIONS_PER_DAY = 3;
    
    /**
     * Generate daily missions for a user
     */
    public function generateDailyMissions(User $user): array
    {
        $today = Carbon::today();
        
        // Check if already generated
        $existingMissions = UserMission::where('user_id', $user->id)
            ->where('assigned_date', $today)
            ->count();
        
        if ($existingMissions >= self::MISSIONS_PER_DAY) {
            return UserMission::where('user_id', $user->id)
                ->where('assigned_date', $today)
                ->with('mission')
                ->get()
                ->toArray();
        }
        
        // Get available missions
        $missions = DailyMission::where('is_active', true)
            ->inRandomOrder()
            ->limit(self::MISSIONS_PER_DAY - $existingMissions)
            ->get();
        
        $userMissions = [];
        
        foreach ($missions as $mission) {
            $userMission = UserMission::create([
                'user_id' => $user->id,
                'mission_id' => $mission->id,
                'target_value' => $mission->target_value,
                'assigned_date' => $today,
            ]);
            
            $userMissions[] = $userMission->load('mission');
        }
        
        return $userMissions;
    }
    
    /**
     * Update mission progress
     */
    public function updateProgress(User $user, string $taskType, int $amount = 1): void
    {
        $today = Carbon::today();
        
        // Find matching missions
        $userMissions = UserMission::where('user_id', $user->id)
            ->where('assigned_date', $today)
            ->where('is_completed', false)
            ->whereHas('mission', function ($q) use ($taskType) {
                $q->where('task_type', $taskType);
            })
            ->get();
        
        foreach ($userMissions as $userMission) {
            $userMission->addProgress($amount);
        }
    }
    
    /**
     * Claim mission rewards
     */
    public function claimReward(User $user, UserMission $userMission): array
    {
        if (!$userMission->is_completed) {
            return ['success' => false, 'message' => 'Mission not completed'];
        }
        
        if ($userMission->is_claimed) {
            return ['success' => false, 'message' => 'Already claimed'];
        }
        
        $mission = $userMission->mission;
        $profile = $user->studentProfile;
        
        // Award XP
        if ($mission->xp_reward > 0) {
            $profile->addXp($mission->xp_reward);
        }
        
        // Award coins
        if ($mission->coin_reward > 0) {
            $profile->addCoins($mission->coin_reward);
            
            CoinTransaction::create([
                'user_id' => $user->id,
                'type' => 'earn',
                'amount' => $mission->coin_reward,
                'balance_after' => $profile->coins,
                'source' => 'mission',
                'description' => "Mission: {$mission->title}",
                'transactionable_type' => UserMission::class,
                'transactionable_id' => $userMission->id,
            ]);
        }
        
        $userMission->update([
            'is_claimed' => true,
            'claimed_at' => now(),
        ]);
        
        return [
            'success' => true,
            'xp_reward' => $mission->xp_reward,
            'coin_reward' => $mission->coin_reward,
        ];
    }
    
    /**
     * Check if all missions completed for bonus
     */
    public function checkDailyBonus(User $user): bool
    {
        $today = Carbon::today();
        
        $completedCount = UserMission::where('user_id', $user->id)
            ->where('assigned_date', $today)
            ->where('is_completed', true)
            ->count();
        
        return $completedCount >= self::MISSIONS_PER_DAY;
    }
}
