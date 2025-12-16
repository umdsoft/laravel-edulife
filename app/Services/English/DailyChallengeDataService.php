<?php

namespace App\Services\English;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class DailyChallengeDataService
{
    protected string $basePath;
    protected string $progressPath;

    public function __construct()
    {
        $this->basePath = base_path('data/english/games/daily-challenge');
        $this->progressPath = storage_path('app/game-progress/daily-challenge');

        if (!File::exists($this->progressPath)) {
            File::makeDirectory($this->progressPath, 0755, true);
        }
    }

    /**
     * Get game configuration
     */
    public function getConfig(): array
    {
        return Cache::remember('daily_challenge_config', 3600, function () {
            $configPath = $this->basePath . '/config.json';
            if (File::exists($configPath)) {
                return json_decode(File::get($configPath), true);
            }
            return [];
        });
    }

    /**
     * Get all levels with user progress status
     */
    public function getLevelsWithStatus(): array
    {
        $levels = $this->getLevels();
        $progress = $this->getUserProgress();

        foreach ($levels as &$level) {
            $levelProgress = $progress['levels'][$level['id']] ?? null;

            $level['completed'] = $levelProgress['completed'] ?? false;
            $level['stars'] = $levelProgress['stars'] ?? 0;
            $level['best_score'] = $levelProgress['best_score'] ?? 0;
            $level['best_accuracy'] = $levelProgress['best_accuracy'] ?? 0;
            $level['attempts'] = $levelProgress['attempts'] ?? 0;
            $level['unlocked'] = $this->isLevelUnlocked($level['level_number'], $progress);
        }

        return $levels;
    }

    /**
     * Get all levels
     */
    public function getLevels(): array
    {
        return Cache::remember('daily_challenge_levels', 3600, function () {
            $levelsPath = $this->basePath . '/levels.json';
            if (File::exists($levelsPath)) {
                return json_decode(File::get($levelsPath), true);
            }
            return [];
        });
    }

    /**
     * Get a specific level
     */
    public function getLevel(int $levelNumber): ?array
    {
        $levels = $this->getLevels();

        foreach ($levels as $level) {
            if ($level['level_number'] === $levelNumber) {
                $level['questions'] = $this->getQuestionsForLevel($level);
                return $level;
            }
        }

        return null;
    }

    /**
     * Get questions for a level based on its categories
     */
    public function getQuestionsForLevel(array $level): array
    {
        $questions = [];
        $categories = $level['categories'] ?? ['vocabulary'];
        $difficulty = $level['difficulty'] ?? 'easy';

        foreach ($categories as $category) {
            $categoryQuestions = $this->getQuestionsByCategory($category);

            // Filter by difficulty
            $filteredQuestions = array_filter($categoryQuestions, function ($q) use ($difficulty) {
                $qDifficulty = $q['difficulty'] ?? 'easy';
                return $this->difficultyMatches($qDifficulty, $difficulty);
            });

            $questions = array_merge($questions, $filteredQuestions);
        }

        shuffle($questions);

        $questionCount = $level['questions_count'] ?? 10;
        return array_slice($questions, 0, $questionCount);
    }

    /**
     * Check if question difficulty matches level difficulty
     */
    protected function difficultyMatches(string $questionDifficulty, string $levelDifficulty): bool
    {
        $order = ['easy' => 1, 'medium' => 2, 'hard' => 3, 'expert' => 4];
        $qLevel = $order[$questionDifficulty] ?? 1;
        $lLevel = $order[$levelDifficulty] ?? 1;

        // Allow questions at or below the level difficulty
        return $qLevel <= $lLevel + 1;
    }

    /**
     * Get questions by category
     */
    public function getQuestionsByCategory(string $category): array
    {
        $cacheKey = "daily_challenge_questions_{$category}";

        return Cache::remember($cacheKey, 3600, function () use ($category) {
            $contentPath = $this->basePath . "/content/{$category}.json";
            if (File::exists($contentPath)) {
                $questions = json_decode(File::get($contentPath), true);
                foreach ($questions as &$question) {
                    $question['category'] = $category;
                }
                return $questions;
            }
            return [];
        });
    }

    /**
     * Get daily challenge
     */
    public function getDailyChallenge(): array
    {
        $config = $this->getConfig();
        $today = Carbon::now()->format('Y-m-d');
        $seed = crc32($today);
        mt_srand($seed);

        // Mix categories for daily challenge
        $categories = ['vocabulary', 'grammar', 'idioms', 'reading'];
        $allQuestions = [];

        foreach ($categories as $category) {
            $categoryQuestions = $this->getQuestionsByCategory($category);
            $allQuestions = array_merge($allQuestions, $categoryQuestions);
        }

        // Deterministic shuffle based on date
        usort($allQuestions, function ($a, $b) {
            return mt_rand(-1, 1);
        });

        $dailyType = $config['challenge_types'][0] ?? [];
        $questionCount = $dailyType['questions'] ?? 10;

        return [
            'date' => $today,
            'type' => 'daily',
            'questions' => array_slice($allQuestions, 0, $questionCount),
            'time_limit' => $dailyType['time_limit'] ?? 300,
            'bonus_multiplier' => $dailyType['bonus_multiplier'] ?? 1.5,
        ];
    }

    /**
     * Get all categories
     */
    public function getCategories(): array
    {
        $config = $this->getConfig();
        return $config['categories'] ?? [];
    }

    /**
     * Get challenge types
     */
    public function getChallengeTypes(): array
    {
        $config = $this->getConfig();
        return $config['challenge_types'] ?? [];
    }

    /**
     * Get difficulty levels
     */
    public function getDifficultyLevels(): array
    {
        $config = $this->getConfig();
        return $config['difficulty_levels'] ?? [];
    }

    /**
     * Get powerups
     */
    public function getPowerups(): array
    {
        $config = $this->getConfig();
        return $config['powerups'] ?? [];
    }

    /**
     * Get quiz master ranks
     */
    public function getQuizMasterRanks(): array
    {
        $config = $this->getConfig();
        return $config['quiz_master_ranks'] ?? [];
    }

    /**
     * Get streak rewards
     */
    public function getStreakRewards(): array
    {
        $config = $this->getConfig();
        return $config['streak_rewards'] ?? [];
    }

    /**
     * Get scoring configuration
     */
    public function getScoringConfig(): array
    {
        $config = $this->getConfig();
        return $config['scoring'] ?? [
            'base_points' => 10,
            'time_bonus_max' => 5,
            'streak_bonus_per_correct' => 2,
            'perfect_quiz_bonus' => 50,
            'daily_completion_bonus' => 25,
            'first_attempt_bonus' => 10
        ];
    }

    /**
     * Check if a level is unlocked
     */
    public function isLevelUnlocked(int $levelNumber, ?array $progress = null): bool
    {
        if ($levelNumber === 1) {
            return true;
        }

        $progress = $progress ?? $this->getUserProgress();
        $levels = $this->getLevels();

        foreach ($levels as $level) {
            if ($level['level_number'] === $levelNumber - 1) {
                $levelId = $level['id'];
                return ($progress['levels'][$levelId]['completed'] ?? false);
            }
        }

        return false;
    }

    /**
     * Get user progress
     */
    public function getUserProgress(): array
    {
        $userId = Auth::id() ?? 'guest';
        $progressFile = $this->progressPath . "/{$userId}.json";

        if (File::exists($progressFile)) {
            return json_decode(File::get($progressFile), true);
        }

        return [
            'levels' => [],
            'stats' => [
                'challenges_completed' => 0,
                'correct_answers' => 0,
                'total_answers' => 0,
                'perfect_scores' => 0,
                'current_streak' => 0,
                'best_streak' => 0,
                'last_played_date' => null,
                'categories_played' => [],
                'total_xp' => 0,
                'total_coins' => 0,
            ],
            'achievements' => [],
            'daily_history' => [],
        ];
    }

    /**
     * Save user progress
     */
    public function saveUserProgress(array $progress): void
    {
        $userId = Auth::id() ?? 'guest';
        $progressFile = $this->progressPath . "/{$userId}.json";

        File::put($progressFile, json_encode($progress, JSON_PRETTY_PRINT));
    }

    /**
     * Get user stats
     */
    public function getUserStats(): array
    {
        $progress = $this->getUserProgress();
        $stats = $progress['stats'] ?? [];

        $stats['accuracy'] = $stats['total_answers'] > 0
            ? round(($stats['correct_answers'] / $stats['total_answers']) * 100)
            : 0;

        return $stats;
    }

    /**
     * Update streak
     */
    public function updateStreak(): array
    {
        $progress = $this->getUserProgress();
        $today = Carbon::now()->format('Y-m-d');
        $lastPlayed = $progress['stats']['last_played_date'] ?? null;

        $streakResult = ['maintained' => false, 'increased' => false, 'reward' => null];

        if ($lastPlayed) {
            $lastPlayedDate = Carbon::parse($lastPlayed);
            $daysDiff = $lastPlayedDate->diffInDays(Carbon::now());

            if ($daysDiff === 0) {
                // Already played today
                $streakResult['maintained'] = true;
            } elseif ($daysDiff === 1) {
                // Consecutive day
                $progress['stats']['current_streak']++;
                $streakResult['increased'] = true;

                // Check streak rewards
                $streakRewards = $this->getStreakRewards();
                foreach ($streakRewards as $reward) {
                    if ($progress['stats']['current_streak'] === $reward['days']) {
                        $progress['stats']['total_xp'] += $reward['xp_bonus'];
                        $progress['stats']['total_coins'] += $reward['coin_bonus'];
                        $streakResult['reward'] = $reward;
                        break;
                    }
                }
            } else {
                // Streak broken
                $progress['stats']['current_streak'] = 1;
            }
        } else {
            $progress['stats']['current_streak'] = 1;
        }

        if ($progress['stats']['current_streak'] > $progress['stats']['best_streak']) {
            $progress['stats']['best_streak'] = $progress['stats']['current_streak'];
        }

        $progress['stats']['last_played_date'] = $today;
        $this->saveUserProgress($progress);

        return $streakResult;
    }

    /**
     * Check if daily challenge completed today
     */
    public function isDailyChallengeCompleted(): bool
    {
        $progress = $this->getUserProgress();
        $today = Carbon::now()->format('Y-m-d');

        return in_array($today, $progress['daily_history'] ?? []);
    }

    /**
     * Get user achievements
     */
    public function getUserAchievements(): array
    {
        $allAchievements = $this->getAchievements();
        $progress = $this->getUserProgress();
        $userAchievements = $progress['achievements'] ?? [];
        $stats = $progress['stats'] ?? [];

        foreach ($allAchievements as &$achievement) {
            $achievement['unlocked'] = in_array($achievement['id'], $userAchievements);
            $achievement['progress'] = $this->calculateAchievementProgress($achievement, $stats);
        }

        return $allAchievements;
    }

    /**
     * Get all achievements
     */
    public function getAchievements(): array
    {
        return Cache::remember('daily_challenge_achievements', 3600, function () {
            $achievementsPath = $this->basePath . '/achievements.json';
            if (File::exists($achievementsPath)) {
                return json_decode(File::get($achievementsPath), true);
            }
            return [];
        });
    }

    /**
     * Calculate achievement progress
     */
    protected function calculateAchievementProgress(array $achievement, array $stats): int
    {
        $condition = $achievement['condition'] ?? [];
        $type = $condition['type'] ?? '';
        $target = $condition['value'] ?? 1;

        switch ($type) {
            case 'challenges_completed':
                $current = $stats['challenges_completed'] ?? 0;
                break;
            case 'perfect_score':
                $current = $stats['perfect_scores'] ?? 0;
                break;
            case 'streak':
                $current = $stats['best_streak'] ?? 0;
                break;
            case 'accuracy':
                $current = $stats['accuracy'] ?? 0;
                break;
            case 'levels_completed':
                $current = count(array_filter($stats['levels'] ?? [], fn($l) => $l['completed'] ?? false));
                break;
            default:
                return 0;
        }

        return min(100, round(($current / $target) * 100));
    }

    /**
     * Get total stars earned
     */
    public function getTotalStars(): int
    {
        $progress = $this->getUserProgress();
        $totalStars = 0;

        foreach ($progress['levels'] ?? [] as $levelProgress) {
            $totalStars += $levelProgress['stars'] ?? 0;
        }

        return $totalStars;
    }

    /**
     * Update level progress
     */
    public function updateLevelProgress(string $levelId, array $result): void
    {
        $progress = $this->getUserProgress();

        if (!isset($progress['levels'][$levelId])) {
            $progress['levels'][$levelId] = [
                'completed' => false,
                'stars' => 0,
                'best_score' => 0,
                'best_accuracy' => 0,
                'attempts' => 0,
            ];
        }

        $levelProgress = &$progress['levels'][$levelId];
        $levelProgress['attempts']++;

        if ($result['completed']) {
            $levelProgress['completed'] = true;

            if ($result['score'] > $levelProgress['best_score']) {
                $levelProgress['best_score'] = $result['score'];
            }

            if ($result['accuracy'] > $levelProgress['best_accuracy']) {
                $levelProgress['best_accuracy'] = $result['accuracy'];
            }

            if ($result['stars'] > $levelProgress['stars']) {
                $levelProgress['stars'] = $result['stars'];
            }
        }

        // Update stats
        $progress['stats']['challenges_completed'] = ($progress['stats']['challenges_completed'] ?? 0) + 1;
        $progress['stats']['correct_answers'] = ($progress['stats']['correct_answers'] ?? 0) + $result['correct_answers'];
        $progress['stats']['total_answers'] = ($progress['stats']['total_answers'] ?? 0) + $result['total_answers'];

        if ($result['accuracy'] === 100) {
            $progress['stats']['perfect_scores'] = ($progress['stats']['perfect_scores'] ?? 0) + 1;
        }

        $progress['stats']['total_xp'] = ($progress['stats']['total_xp'] ?? 0) + ($result['xp_earned'] ?? 0);
        $progress['stats']['total_coins'] = ($progress['stats']['total_coins'] ?? 0) + ($result['coins_earned'] ?? 0);

        // Track daily completion
        if ($result['is_daily'] ?? false) {
            $today = Carbon::now()->format('Y-m-d');
            if (!in_array($today, $progress['daily_history'] ?? [])) {
                $progress['daily_history'][] = $today;
            }
        }

        // Check for new achievements
        $this->checkAchievements($progress);

        $this->saveUserProgress($progress);
    }

    /**
     * Check and unlock achievements
     */
    protected function checkAchievements(array &$progress): array
    {
        $achievements = $this->getAchievements();
        $userAchievements = $progress['achievements'] ?? [];
        $stats = $progress['stats'] ?? [];
        $newAchievements = [];

        foreach ($achievements as $achievement) {
            if (in_array($achievement['id'], $userAchievements)) {
                continue;
            }

            $condition = $achievement['condition'] ?? [];
            $unlocked = $this->checkAchievementCondition($condition, $stats, $progress);

            if ($unlocked) {
                $userAchievements[] = $achievement['id'];
                $newAchievements[] = $achievement;

                $progress['stats']['total_xp'] = ($progress['stats']['total_xp'] ?? 0) + ($achievement['xp_reward'] ?? 0);
                $progress['stats']['total_coins'] = ($progress['stats']['total_coins'] ?? 0) + ($achievement['coin_reward'] ?? 0);
            }
        }

        $progress['achievements'] = $userAchievements;

        return $newAchievements;
    }

    /**
     * Check if achievement condition is met
     */
    protected function checkAchievementCondition(array $condition, array $stats, array $progress): bool
    {
        $type = $condition['type'] ?? '';
        $value = $condition['value'] ?? 0;

        switch ($type) {
            case 'challenges_completed':
                return ($stats['challenges_completed'] ?? 0) >= $value;

            case 'perfect_score':
                return ($stats['perfect_scores'] ?? 0) >= $value;

            case 'streak':
                return ($stats['best_streak'] ?? 0) >= $value;

            case 'accuracy':
                $totalAnswers = $stats['total_answers'] ?? 0;
                if ($totalAnswers < ($condition['min_quizzes'] ?? 1) * 5) return false;
                $accuracy = round(($stats['correct_answers'] / $totalAnswers) * 100);
                return $accuracy >= $value;

            case 'levels_completed':
                $completedLevels = 0;
                foreach ($progress['levels'] ?? [] as $level) {
                    if ($level['completed'] ?? false) {
                        $completedLevels++;
                    }
                }
                return $completedLevels >= $value;

            default:
                return false;
        }
    }

    /**
     * Get current rank based on XP
     */
    public function getCurrentRank(): ?array
    {
        $stats = $this->getUserStats();
        $ranks = $this->getQuizMasterRanks();
        $currentXp = $stats['total_xp'] ?? 0;

        $currentRank = null;
        foreach ($ranks as $rank) {
            if ($currentXp >= $rank['xp_required']) {
                $currentRank = $rank;
            }
        }

        return $currentRank;
    }
}
