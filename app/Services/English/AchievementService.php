<?php

namespace App\Services\English;

use App\Models\English\EnglishAchievement;
use App\Models\English\EnglishAchievementCategory;
use App\Models\English\UserAchievement;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class AchievementService
{
    public function __construct(
        private LevelService $levelService,
        private NotificationService $notificationService
    ) {
    }

    /**
     * Get all achievements with user progress
     */
    public function getAllAchievements(User $user): Collection
    {
        $categories = EnglishAchievementCategory::with([
            'achievements' => fn($q) => $q->where('is_active', true)->orderBy('tier')->orderBy('order_number')
        ])->where('is_active', true)->orderBy('order_number')->get();

        $userAchievements = UserAchievement::where('user_id', $user->id)->get()->keyBy('achievement_id');

        return $categories->map(function ($category) use ($userAchievements) {
            $category->achievements->each(function ($achievement) use ($userAchievements) {
                $achievement->user_progress = $userAchievements->get($achievement->id);
            });
            return $category;
        });
    }

    /**
     * Check and award achievements based on trigger
     */
    public function checkAchievements(User $user, string $trigger, array $context = []): array
    {
        $profile = $this->levelService->getOrCreateProfile($user);
        $awarded = [];

        $achievements = EnglishAchievement::where('trigger_type', $trigger)
            ->where('is_active', true)
            ->get();

        foreach ($achievements as $achievement) {
            $result = $this->evaluateAchievement($user, $achievement, $profile, $context);

            if ($result['awarded']) {
                $awarded[] = $result;
            }
        }

        return $awarded;
    }

    /**
     * Evaluate a single achievement
     */
    private function evaluateAchievement(User $user, EnglishAchievement $achievement, $profile, array $context): array
    {
        $userAchievement = UserAchievement::firstOrCreate(
            ['user_id' => $user->id, 'achievement_id' => $achievement->id],
            [
                'id' => Str::uuid(),
                'current_progress' => 0,
                'is_unlocked' => false,
            ]
        );

        if ($userAchievement->is_unlocked) {
            return ['awarded' => false, 'achievement' => $achievement];
        }

        $newProgress = $this->calculateProgress($achievement, $profile, $context);
        $userAchievement->updateProgress($newProgress);

        if ($userAchievement->is_unlocked) {
            return [
                'awarded' => true,
                'achievement' => $achievement,
                'xp_reward' => $achievement->xp_reward,
                'coin_reward' => $achievement->coin_reward,
            ];
        }

        return ['awarded' => false, 'achievement' => $achievement, 'progress' => $newProgress];
    }

    /**
     * Calculate achievement progress
     */
    private function calculateProgress(EnglishAchievement $achievement, $profile, array $context): int
    {
        $conditions = $achievement->conditions;

        return match ($achievement->trigger_type) {
            'lesson_complete' => $context['total_lessons_completed'] ?? 0,
            'vocabulary_mastered' => $context['total_vocabulary_mastered'] ?? 0,
            'streak_days' => $profile->current_streak ?? 0,
            'battle_win' => $profile->battles_won ?? 0,
            'battle_streak' => $profile->battle_win_streak ?? 0,
            'xp_earned' => $profile->total_xp ?? 0,
            'game_complete' => $context['total_games_completed'] ?? 0,
            'level_complete' => $context['levels_completed'] ?? 0,
            'unit_perfect' => $context['perfect_units'] ?? 0,
            'daily_challenge' => $context['daily_challenges_completed'] ?? 0,
            default => $context['progress'] ?? 0,
        };
    }

    /**
     * Award achievement rewards
     */
    public function awardRewards(User $user, EnglishAchievement $achievement): array
    {
        $profile = $this->levelService->getOrCreateProfile($user);

        $xp = $achievement->xp_reward ?? 0;
        $coins = $achievement->coin_reward ?? 0;
        $gems = $achievement->gem_reward ?? 0;

        if ($xp > 0)
            $profile->addXp($xp);
        if ($coins > 0)
            $profile->addCoins($coins);
        if ($gems > 0) {
            $profile->gems = ($profile->gems ?? 0) + $gems;
            $profile->save();
        }

        $this->notificationService->sendAchievementNotification($user, $achievement);

        return [
            'xp' => $xp,
            'coins' => $coins,
            'gems' => $gems,
            'special_reward' => $achievement->special_reward,
        ];
    }

    /**
     * Get user's unlocked achievements
     */
    public function getUnlockedAchievements(User $user): Collection
    {
        return UserAchievement::with('achievement.category')
            ->where('user_id', $user->id)
            ->where('is_unlocked', true)
            ->orderByDesc('unlocked_at')
            ->get();
    }

    /**
     * Get achievement statistics for user
     */
    public function getAchievementStats(User $user): array
    {
        $total = EnglishAchievement::where('is_active', true)->count();
        $unlocked = UserAchievement::where('user_id', $user->id)->where('is_unlocked', true)->count();

        $byTier = UserAchievement::where('user_id', $user->id)
            ->where('is_unlocked', true)
            ->join('english_achievements', 'user_achievements.achievement_id', '=', 'english_achievements.id')
            ->selectRaw('tier, COUNT(*) as count')
            ->groupBy('tier')
            ->pluck('count', 'tier');

        return [
            'total_achievements' => $total,
            'unlocked_achievements' => $unlocked,
            'completion_percentage' => $total > 0 ? round(($unlocked / $total) * 100, 1) : 0,
            'by_tier' => [
                'bronze' => $byTier['bronze'] ?? 0,
                'silver' => $byTier['silver'] ?? 0,
                'gold' => $byTier['gold'] ?? 0,
                'platinum' => $byTier['platinum'] ?? 0,
            ],
            'total_xp_from_achievements' => UserAchievement::where('user_id', $user->id)
                ->where('is_unlocked', true)
                ->join('english_achievements', 'user_achievements.achievement_id', '=', 'english_achievements.id')
                ->sum('xp_reward'),
        ];
    }
}
