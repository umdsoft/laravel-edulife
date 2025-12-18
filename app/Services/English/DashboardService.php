<?php

namespace App\Services\English;

use App\Models\User;
use App\Models\English\EnglishLevel;
use Carbon\Carbon;

class DashboardService
{
    public function __construct(
        private LevelService $levelService,
        private LeaderboardService $leaderboardService,
        private AchievementService $achievementService,
        private VocabularyReviewDataService $vocabularyReviewService
    ) {
    }

    /**
     * Get all dashboard data for a user
     */
    public function getDashboardData(User $user): array
    {
        $profile = $this->levelService->getOrCreateProfile($user);
        $profile->load('currentLevel');

        return [
            'profile' => $this->getProfileData($user, $profile),
            'currentLevel' => $this->getCurrentLevelData($profile),
            'dailyChallenge' => $this->getDailyChallengeData($profile),
            'wordsForReview' => $this->getWordsForReviewCount(),
            'weeklyLeaderboard' => $this->getWeeklyLeaderboard(),
            'achievements' => $this->getRecentAchievements($user),
        ];
    }

    /**
     * Get user profile data
     */
    private function getProfileData(User $user, $profile): array
    {
        return [
            'id' => $user->id,
            'name' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: 'Student',
            'avatar' => $user->avatar,
            'total_xp' => $profile->total_xp ?? 0,
            'coins' => $profile->coins ?? 0,
            'gems' => $profile->gems ?? 0,
            'elo_rating' => $profile->elo_rating ?? 1000,
            'current_streak' => $profile->current_streak ?? 0,
            'longest_streak' => $profile->longest_streak ?? 0,
            'words_learned' => $profile->words_learned ?? 0,
            'words_mastered' => $profile->words_mastered ?? 0,
            'lessons_completed' => $profile->lessons_completed ?? 0,
            'games_played' => $profile->games_played ?? 0,
            'battles_played' => $profile->battles_played ?? 0,
            'battles_won' => $profile->battles_won ?? 0,
            'daily_xp' => $profile->today_xp_earned ?? 0,
            'streak_calendar' => $this->getStreakCalendar($profile),
        ];
    }

    /**
     * Get current level data with progress
     */
    private function getCurrentLevelData($profile): array
    {
        $level = $profile->currentLevel;

        if (!$level) {
            $level = EnglishLevel::where('code', 'A1')->first();
        }

        if (!$level) {
            return [
                'id' => null,
                'code' => 'A1',
                'name' => 'Beginner',
                'order_number' => 1,
                'icon' => null,
                'current_xp' => 0,
                'xp_for_next_level' => 500,
                'progress' => 0,
            ];
        }

        $progress = $this->levelService->calculateLevelProgress($level, $profile);

        // Calculate XP needed for next level
        $totalXp = $profile->total_xp ?? 0;
        $xpThresholds = [0, 500, 1500, 3500, 7000, 12000, 20000];
        $currentLevelXp = 0;
        $nextLevelXp = 500;

        foreach ($xpThresholds as $i => $threshold) {
            if ($totalXp >= $threshold) {
                $currentLevelXp = $threshold;
                $nextLevelXp = $xpThresholds[$i + 1] ?? $threshold + 5000;
            }
        }

        return [
            'id' => $level->id,
            'code' => $level->code,
            'name' => $level->name,
            'name_uz' => $level->name_uz ?? $level->name,
            'order_number' => $level->order_number,
            'icon' => $level->icon ?? '📚',
            'current_xp' => $totalXp - $currentLevelXp,
            'xp_for_next_level' => $nextLevelXp - $currentLevelXp,
            'progress' => $progress,
            'current_lesson' => $profile->currentLesson?->title ?? null,
        ];
    }

    /**
     * Get daily challenge data
     */
    private function getDailyChallengeData($profile): array
    {
        $xpGoal = $profile->daily_xp_goal ?? 50;
        $xpProgress = $profile->today_xp_earned ?? 0;

        // Daily tasks
        $tasks = [
            [
                'id' => 1,
                'name' => 'Complete one lesson',
                'completed' => ($profile->lessons_completed ?? 0) > 0 && $this->wasCompletedToday($profile, 'lesson'),
            ],
            [
                'id' => 2,
                'name' => 'Play a game',
                'completed' => ($profile->games_played ?? 0) > 0 && $this->wasCompletedToday($profile, 'game'),
            ],
            [
                'id' => 3,
                'name' => 'Review vocabulary',
                'completed' => $this->wasCompletedToday($profile, 'vocabulary_review'),
            ],
        ];

        $tasksCompleted = count(array_filter($tasks, fn($t) => $t['completed']));

        return [
            'xp_goal' => $xpGoal,
            'xp_progress' => $xpProgress,
            'tasks' => $tasks,
            'tasks_completed' => $tasksCompleted,
            'tasks_total' => count($tasks),
            'is_completed' => $xpProgress >= $xpGoal && $tasksCompleted >= 2,
        ];
    }

    /**
     * Check if activity was completed today
     */
    private function wasCompletedToday($profile, string $type): bool
    {
        $lastStudyDate = $profile->last_study_date;
        if (!$lastStudyDate) {
            return false;
        }

        $isToday = Carbon::parse($lastStudyDate)->isToday();

        if (!$isToday) {
            return false;
        }

        // Check daily challenges completed array
        $dailyChallenges = $profile->daily_challenges_completed ?? [];
        $today = now()->format('Y-m-d');

        return in_array($type, $dailyChallenges[$today] ?? []);
    }

    /**
     * Get words for review count
     */
    private function getWordsForReviewCount(): int
    {
        try {
            $wordsForReview = $this->vocabularyReviewService->getWordsForReview(100);
            return count($wordsForReview);
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Get weekly leaderboard (top 5)
     */
    private function getWeeklyLeaderboard(): array
    {
        try {
            $leaderboard = $this->leaderboardService->getLeaderboard('xp', 'weekly', 5);

            return $leaderboard->map(function ($entry) {
                return [
                    'rank' => $entry->rank,
                    'name' => $entry->user?->name ?? 'Unknown',
                    'avatar' => $entry->user?->avatar ?? null,
                    'score' => $entry->score,
                    'user_id' => $entry->user_id,
                ];
            })->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get recent achievements
     */
    private function getRecentAchievements(User $user): array
    {
        try {
            $achievements = $this->achievementService->getUnlockedAchievements($user);

            return $achievements->take(5)->map(function ($userAchievement) {
                $achievement = $userAchievement->achievement;
                return [
                    'id' => $achievement->id,
                    'name' => $achievement->name,
                    'name_uz' => $achievement->name_uz ?? $achievement->name,
                    'description' => $achievement->description,
                    'description_uz' => $achievement->description_uz ?? $achievement->description,
                    'icon' => $achievement->icon ?? '🏆',
                    'tier' => $achievement->tier ?? 'bronze',
                    'xp_reward' => $achievement->xp_reward,
                    'unlocked_at' => $userAchievement->unlocked_at,
                ];
            })->toArray();
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get streak calendar for last 7 days
     */
    private function getStreakCalendar($profile): array
    {
        $calendar = [];
        $today = Carbon::today();

        // Use safe day abbreviations instead of locale-dependent Carbon methods
        $dayNames = ['Yak', 'Du', 'Se', 'Chor', 'Pay', 'Ju', 'Sha'];
        $dayNamesFull = ['Yakshanba', 'Dushanba', 'Seshanba', 'Chorshanba', 'Payshanba', 'Juma', 'Shanba'];

        // Get streak history from profile or calculate from last_study_date
        $currentStreak = $profile->current_streak ?? 0;
        $streakStartDate = $profile->streak_start_date ? Carbon::parse($profile->streak_start_date) : null;

        for ($i = 6; $i >= 0; $i--) {
            $date = $today->copy()->subDays($i);
            $dayOfWeek = $date->dayOfWeek; // 0 = Sunday, 1 = Monday, etc.

            $hasActivity = false;

            if ($streakStartDate && $currentStreak > 0) {
                // If we have an active streak, mark days from streak start to today
                $hasActivity = $date->gte($streakStartDate) && $date->lte($today);
            }

            // Today is special - check if any activity was done today
            if ($date->isToday()) {
                $lastStudyDate = $profile->last_study_date;
                $hasActivity = $lastStudyDate && Carbon::parse($lastStudyDate)->isToday();
            }

            $calendar[] = [
                'date' => $date->format('Y-m-d'),
                'day' => substr($dayNames[$dayOfWeek], 0, 1),
                'day_full' => $dayNamesFull[$dayOfWeek],
                'has_activity' => $hasActivity,
                'is_today' => $date->isToday(),
            ];
        }

        return $calendar;
    }


    /**
     * Mark daily activity as completed
     */
    public function markDailyActivityCompleted(User $user, string $activityType): void
    {
        $profile = $this->levelService->getOrCreateProfile($user);

        $today = now()->format('Y-m-d');
        $dailyChallenges = $profile->daily_challenges_completed ?? [];

        if (!isset($dailyChallenges[$today])) {
            $dailyChallenges[$today] = [];
        }

        if (!in_array($activityType, $dailyChallenges[$today])) {
            $dailyChallenges[$today][] = $activityType;
        }

        $profile->daily_challenges_completed = $dailyChallenges;
        $profile->last_study_date = now();
        $profile->save();

        // Update streak if needed
        $this->updateStreak($profile);
    }

    /**
     * Update user's streak
     */
    private function updateStreak($profile): void
    {
        $lastStudyDate = $profile->last_study_date;
        $today = Carbon::today();

        if (!$lastStudyDate) {
            $profile->current_streak = 1;
            $profile->streak_start_date = $today;
        } else {
            $lastStudy = Carbon::parse($lastStudyDate);

            if ($lastStudy->isToday()) {
                // Already studied today, no change needed
                return;
            } elseif ($lastStudy->isYesterday()) {
                // Continuing streak
                $profile->current_streak = ($profile->current_streak ?? 0) + 1;
            } else {
                // Streak broken, start new
                $profile->current_streak = 1;
                $profile->streak_start_date = $today;
            }
        }

        // Update longest streak if needed
        if ($profile->current_streak > ($profile->longest_streak ?? 0)) {
            $profile->longest_streak = $profile->current_streak;
        }

        $profile->save();
    }
}
