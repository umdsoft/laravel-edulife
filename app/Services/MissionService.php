<?php

namespace App\Services;

use App\Models\User;
use App\Models\DailyMission;
use App\Models\UserMission;
use App\Models\CoinTransaction;
use Carbon\Carbon;

/**
 * Service for managing daily missions and gamification.
 * 
 * This service handles the daily mission system including:
 * - Generating daily missions for users
 * - Tracking mission progress
 * - Claiming rewards (XP and coins)
 * - Daily completion bonuses
 * 
 * @package App\Services
 * @author EDULIFE Team
 */
class MissionService
{
    /**
     * Number of missions generated per day for each user.
     */
    const MISSIONS_PER_DAY = 3;
    
    /**
     * Generate daily missions for a user.
     * 
     * Checks if missions already exist for today, if not,
     * randomly selects missions from the active pool and
     * assigns them to the user.
     * 
     * @param User $user The user to generate missions for
     * 
     * @return array<UserMission> Array of assigned UserMission models with loaded mission relation
     * 
     * @example
     * $missions = $missionService->generateDailyMissions($user);
     * foreach ($missions as $mission) {
     *     echo $mission->mission->title;
     * }
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
                'target_value' => $mission->target ?? $mission->target_value ?? $mission->target_count ?? 1,
                'assigned_date' => $today,
            ]);
            
            $userMissions[] = $userMission->load('mission');
        }
        
        return $userMissions;
    }
    
    /**
     * Update mission progress for a specific task type.
     * 
     * Finds all uncompleted missions matching the task type
     * and increments their progress by the specified amount.
     * Supports both legacy 'type' and new 'task_type' column names.
     * 
     * @param User $user The user whose progress to update
     * @param string $taskType Task identifier (e.g., 'watch_lesson', 'complete_test', 'earn_xp')
     * @param int $amount Progress amount to add (default: 1)
     * 
     * @return void
     * 
     * @example
     * // When user completes a lesson
     * $missionService->updateProgress($user, 'watch_lesson');
     * 
     * // When user earns XP
     * $missionService->updateProgress($user, 'earn_xp', 50);
     */
    public function updateProgress(User $user, string $taskType, int $amount = 1): void
    {
        $today = Carbon::today();
        
        // Find matching missions (support both old 'type' and new 'task_type' columns)
        $userMissions = UserMission::where('user_id', $user->id)
            ->where('assigned_date', $today)
            ->where('is_completed', false)
            ->whereHas('mission', function ($q) use ($taskType) {
                $q->where(function($query) use ($taskType) {
                    $query->where('type', $taskType)
                          ->orWhere('task_type', $taskType);
                });
            })
            ->get();
        
        foreach ($userMissions as $userMission) {
            $userMission->addProgress($amount);
        }
    }
    
    /**
     * Claim rewards for a completed mission.
     * 
     * Validates mission completion, awards XP and coins
     * to the user's profile, and marks the mission as claimed.
     * 
     * @param User $user The user claiming the reward
     * @param UserMission $userMission The mission to claim rewards for
     * 
     * @return array{success: bool, message?: string, xp_reward?: int, coin_reward?: int}
     *         Returns success status with either error message or reward amounts
     * 
     * @throws \InvalidArgumentException If userMission doesn't belong to user
     * 
     * @example
     * $result = $missionService->claimReward($user, $userMission);
     * if ($result['success']) {
     *     return response()->json([
     *         'message' => "Earned {$result['xp_reward']} XP and {$result['coin_reward']} coins!"
     *     ]);
     * }
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
     * Check if user has completed all daily missions for bonus eligibility.
     * 
     * Used to determine if user should receive a daily completion bonus.
     * 
     * @param User $user The user to check
     * 
     * @return bool True if all missions completed, false otherwise
     * 
     * @example
     * if ($missionService->checkDailyBonus($user)) {
     *     // Award daily bonus
     *     $coinService->addCoins($user, 50, 'daily_bonus', 'Completed all daily missions!');
     * }
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
