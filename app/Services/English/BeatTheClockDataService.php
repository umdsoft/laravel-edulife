<?php

namespace App\Services\English;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class BeatTheClockDataService
{
    protected string $dataPath;
    protected string $progressPath;

    public function __construct()
    {
        $this->dataPath = base_path('data/english/games/beat-the-clock');
        $this->progressPath = storage_path('app/game-progress/beat-the-clock');

        if (!file_exists($this->progressPath)) {
            mkdir($this->progressPath, 0755, true);
        }
    }

    /**
     * Get game configuration
     */
    public function getConfig(): array
    {
        return Cache::remember('beat_the_clock_config', 3600, function () {
            $configPath = $this->dataPath . '/config.json';
            if (file_exists($configPath)) {
                return json_decode(file_get_contents($configPath), true) ?? [];
            }
            return [];
        });
    }

    /**
     * Get all levels
     */
    public function getLevels(): array
    {
        return Cache::remember('beat_the_clock_levels', 3600, function () {
            $levelsPath = $this->dataPath . '/levels.json';
            if (file_exists($levelsPath)) {
                return json_decode(file_get_contents($levelsPath), true) ?? [];
            }
            return [];
        });
    }

    /**
     * Get levels with user progress
     */
    public function getLevelsWithStatus(): array
    {
        $levels = $this->getLevels();
        $progress = $this->getUserProgress();

        return array_map(function ($level) use ($progress) {
            $levelProgress = $progress['levels'][$level['level_number']] ?? null;

            return array_merge($level, [
                'unlocked' => $this->isLevelUnlocked($level['level_number']),
                'completed' => $levelProgress['completed'] ?? false,
                'stars' => $levelProgress['stars'] ?? 0,
                'best_score' => $levelProgress['best_score'] ?? null,
                'best_accuracy' => $levelProgress['best_accuracy'] ?? null,
                'best_time' => $levelProgress['best_time'] ?? null,
            ]);
        }, $levels);
    }

    /**
     * Get a specific level
     */
    public function getLevel(int $levelNumber): ?array
    {
        $levels = $this->getLevelsWithStatus();

        foreach ($levels as $level) {
            if ($level['level_number'] === $levelNumber) {
                return $level;
            }
        }

        return null;
    }

    /**
     * Check if level is unlocked
     */
    public function isLevelUnlocked(int $levelNumber): bool
    {
        if ($levelNumber === 1) {
            return true;
        }

        $progress = $this->getUserProgress();
        $previousLevel = $progress['levels'][$levelNumber - 1] ?? null;

        return ($previousLevel['stars'] ?? 0) >= 1;
    }

    /**
     * Get questions for a level
     */
    public function getQuestionsForLevel(array $level): array
    {
        $category = $level['category'] ?? 'vocabulary';
        $questionTypes = $level['question_types'] ?? ['multiple_choice'];
        $count = $level['questions_count'] ?? 10;

        $questions = [];

        if ($category === 'mixed') {
            $categories = ['vocabulary', 'grammar', 'translation', 'idioms', 'synonyms'];
            foreach ($categories as $cat) {
                $catQuestions = $this->getContentForCategory($cat, $questionTypes);
                $questions = array_merge($questions, $catQuestions);
            }
        } else {
            $questions = $this->getContentForCategory($category, $questionTypes);
        }

        shuffle($questions);
        return array_slice($questions, 0, $count);
    }

    /**
     * Get content for a specific category
     */
    protected function getContentForCategory(string $category, array $questionTypes): array
    {
        $contentPath = $this->dataPath . "/content/{$category}.json";
        if (!file_exists($contentPath)) {
            return [];
        }

        $content = json_decode(file_get_contents($contentPath), true) ?? [];
        $questions = [];

        foreach ($questionTypes as $type) {
            if (isset($content[$type])) {
                foreach ($content[$type] as $item) {
                    $item['type'] = $type;
                    $item['category'] = $category;
                    $questions[] = $item;
                }
            }
        }

        return $questions;
    }

    /**
     * Get game modes
     */
    public function getGameModes(): array
    {
        $config = $this->getConfig();
        return $config['game_modes'] ?? [];
    }

    /**
     * Get categories
     */
    public function getCategories(): array
    {
        $config = $this->getConfig();
        return $config['categories'] ?? [];
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
     * Get time master ranks
     */
    public function getTimeMasterRanks(): array
    {
        $config = $this->getConfig();
        return $config['time_master_ranks'] ?? [];
    }

    /**
     * Get question types
     */
    public function getQuestionTypes(): array
    {
        $config = $this->getConfig();
        return $config['question_types'] ?? [];
    }

    /**
     * Get all achievements
     */
    public function getAchievements(): array
    {
        return Cache::remember('beat_the_clock_achievements', 3600, function () {
            $achievementsPath = $this->dataPath . '/achievements.json';
            if (file_exists($achievementsPath)) {
                return json_decode(file_get_contents($achievementsPath), true) ?? [];
            }
            return [];
        });
    }

    /**
     * Get user progress
     */
    public function getUserProgress(): array
    {
        $userId = Auth::id() ?? 'guest';
        $progressFile = $this->progressPath . "/user_{$userId}.json";

        if (file_exists($progressFile)) {
            return json_decode(file_get_contents($progressFile), true) ?? $this->getDefaultProgress();
        }

        return $this->getDefaultProgress();
    }

    /**
     * Save user progress
     */
    public function saveUserProgress(array $progress): void
    {
        $userId = Auth::id() ?? 'guest';
        $progressFile = $this->progressPath . "/user_{$userId}.json";

        file_put_contents($progressFile, json_encode($progress, JSON_PRETTY_PRINT));
    }

    /**
     * Get default progress structure
     */
    protected function getDefaultProgress(): array
    {
        return [
            'levels' => [],
            'stats' => [
                'games_played' => 0,
                'total_questions' => 0,
                'correct_answers' => 0,
                'incorrect_answers' => 0,
                'best_accuracy' => 0,
                'average_accuracy' => 0,
                'best_streak' => 0,
                'total_xp' => 0,
                'total_coins' => 0,
                'perfect_games' => 0,
                'total_time_seconds' => 0,
                'fast_answers' => 0,
                'survival_best' => 0,
                'time_attack_best' => 0,
                'categories_completed' => [],
            ],
            'achievements' => [],
        ];
    }

    /**
     * Get user stats
     */
    public function getUserStats(): array
    {
        $progress = $this->getUserProgress();
        return $progress['stats'] ?? $this->getDefaultProgress()['stats'];
    }

    /**
     * Get user achievements with unlock status
     */
    public function getUserAchievements(): array
    {
        $achievements = $this->getAchievements();
        $progress = $this->getUserProgress();
        $unlockedIds = $progress['achievements'] ?? [];
        $stats = $progress['stats'] ?? [];

        return array_map(function ($achievement) use ($unlockedIds, $stats) {
            $isUnlocked = in_array($achievement['id'], $unlockedIds);

            return array_merge($achievement, [
                'unlocked' => $isUnlocked,
                'progress' => $this->calculateAchievementProgress($achievement, $stats),
            ]);
        }, $achievements);
    }

    /**
     * Calculate achievement progress
     */
    protected function calculateAchievementProgress(array $achievement, array $stats): int
    {
        $condition = $achievement['condition'] ?? [];
        $type = $condition['type'] ?? '';
        $target = $condition['value'] ?? 0;

        return match ($type) {
            'games_completed' => min(100, (int)(($stats['games_played'] ?? 0) / $target * 100)),
            'correct_answers' => min(100, (int)(($stats['correct_answers'] ?? 0) / $target * 100)),
            'perfect_game', 'perfect_games' => min(100, (int)(($stats['perfect_games'] ?? 0) / $target * 100)),
            'streak_reached' => min(100, (int)(($stats['best_streak'] ?? 0) / $target * 100)),
            'fast_answers' => min(100, (int)(($stats['fast_answers'] ?? 0) / $target * 100)),
            'survival_answers' => min(100, (int)(($stats['survival_best'] ?? 0) / $target * 100)),
            'time_attack_score' => min(100, (int)(($stats['time_attack_best'] ?? 0) / $target * 100)),
            'levels_completed' => min(100, (int)((count(array_filter($this->getUserProgress()['levels'] ?? [], fn($l) => $l['completed'] ?? false))) / $target * 100)),
            'stars_earned' => min(100, (int)(($this->getTotalStars()) / $target * 100)),
            'categories_completed' => min(100, (int)((count($stats['categories_completed'] ?? [])) / $target * 100)),
            'total_xp' => min(100, (int)(($stats['total_xp'] ?? 0) / $target * 100)),
            default => 0,
        };
    }

    /**
     * Get total stars earned
     */
    public function getTotalStars(): int
    {
        $progress = $this->getUserProgress();
        $totalStars = 0;

        foreach ($progress['levels'] ?? [] as $level) {
            $totalStars += $level['stars'] ?? 0;
        }

        return $totalStars;
    }

    /**
     * Check and unlock achievements
     */
    public function checkAchievements(array $stats): array
    {
        $achievements = $this->getAchievements();
        $progress = $this->getUserProgress();
        $unlockedIds = $progress['achievements'] ?? [];
        $newlyUnlocked = [];

        foreach ($achievements as $achievement) {
            if (in_array($achievement['id'], $unlockedIds)) {
                continue;
            }

            if ($this->isAchievementUnlocked($achievement, $stats, $progress)) {
                $unlockedIds[] = $achievement['id'];
                $newlyUnlocked[] = $achievement;
            }
        }

        if (!empty($newlyUnlocked)) {
            $progress['achievements'] = $unlockedIds;
            $this->saveUserProgress($progress);
        }

        return $newlyUnlocked;
    }

    /**
     * Check if an achievement is unlocked
     */
    protected function isAchievementUnlocked(array $achievement, array $stats, array $progress): bool
    {
        $condition = $achievement['condition'] ?? [];
        $type = $condition['type'] ?? '';
        $target = $condition['value'] ?? 0;

        return match ($type) {
            'games_completed' => ($stats['games_played'] ?? 0) >= $target,
            'correct_answers' => ($stats['correct_answers'] ?? 0) >= $target,
            'perfect_game', 'perfect_games' => ($stats['perfect_games'] ?? 0) >= $target,
            'streak_reached' => ($stats['best_streak'] ?? 0) >= $target,
            'fast_answers' => ($stats['fast_answers'] ?? 0) >= $target,
            'survival_answers' => ($stats['survival_best'] ?? 0) >= $target,
            'time_attack_score' => ($stats['time_attack_best'] ?? 0) >= $target,
            'levels_completed' => count(array_filter($progress['levels'] ?? [], fn($l) => $l['completed'] ?? false)) >= $target,
            'stars_earned' => $this->getTotalStars() >= $target,
            'categories_completed' => count($stats['categories_completed'] ?? []) >= $target,
            'total_xp' => ($stats['total_xp'] ?? 0) >= $target,
            'all_levels_3_stars' => $this->hasAllLevels3Stars(),
            default => false,
        };
    }

    /**
     * Check if all levels have 3 stars
     */
    protected function hasAllLevels3Stars(): bool
    {
        $levels = $this->getLevels();
        $progress = $this->getUserProgress();

        foreach ($levels as $level) {
            $levelProgress = $progress['levels'][$level['level_number']] ?? null;
            if (!$levelProgress || ($levelProgress['stars'] ?? 0) < 3) {
                return false;
            }
        }

        return true;
    }
}
