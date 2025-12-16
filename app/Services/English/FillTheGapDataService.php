<?php

namespace App\Services\English;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class FillTheGapDataService
{
    protected string $dataPath;
    protected string $progressPath;

    public function __construct()
    {
        $this->dataPath = base_path('data/english/games/fill-the-gap');
        $this->progressPath = storage_path('app/game-progress/fill-the-gap');

        if (!file_exists($this->progressPath)) {
            mkdir($this->progressPath, 0755, true);
        }
    }

    /**
     * Get game configuration
     */
    public function getConfig(): array
    {
        return Cache::remember('fill_the_gap_config', 3600, function () {
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
        return Cache::remember('fill_the_gap_levels', 3600, function () {
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
     * Get sentences for a level
     */
    public function getSentences(array $level, int $count = null): array
    {
        $category = $level['category'] ?? 'vocabulary';
        $count = $count ?? $level['sentences_count'] ?? 10;

        $sentencesPath = $this->dataPath . "/sentences/{$category}.json";
        if (!file_exists($sentencesPath)) {
            // Fallback to vocabulary
            $sentencesPath = $this->dataPath . '/sentences/vocabulary.json';
        }

        if (!file_exists($sentencesPath)) {
            return [];
        }

        $allSentences = json_decode(file_get_contents($sentencesPath), true) ?? [];

        // Filter by difficulty
        $difficulty = $level['difficulty'] ?? 'beginner';
        $difficultyOrder = ['beginner', 'elementary', 'intermediate', 'advanced', 'expert', 'master'];
        $levelIndex = array_search($difficulty, $difficultyOrder);

        $filtered = array_filter($allSentences, function ($sentence) use ($levelIndex, $difficultyOrder) {
            $sentenceDifficulty = $sentence['difficulty'] ?? 'beginner';
            $sentenceIndex = array_search($sentenceDifficulty, $difficultyOrder);
            return $sentenceIndex <= $levelIndex + 1;
        });

        $filtered = array_values($filtered);
        shuffle($filtered);

        return array_slice($filtered, 0, $count);
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
     * Get gap master ranks
     */
    public function getGapMasterRanks(): array
    {
        $config = $this->getConfig();
        return $config['gap_master_ranks'] ?? [];
    }

    /**
     * Get all achievements
     */
    public function getAchievements(): array
    {
        return Cache::remember('fill_the_gap_achievements', 3600, function () {
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
                'gaps_filled' => 0,
                'correct_answers' => 0,
                'total_answers' => 0,
                'accuracy' => 0,
                'best_streak' => 0,
                'total_xp' => 0,
                'total_coins' => 0,
                'perfect_games' => 0,
                'articles_correct' => 0,
                'prepositions_correct' => 0,
                'verbs_correct' => 0,
                'idioms_correct' => 0,
                'typing_correct' => 0,
            ],
            'achievements' => [],
            'category_stats' => [],
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

        switch ($type) {
            case 'gaps_filled':
                return min(100, (int)(($stats['gaps_filled'] ?? 0) / $target * 100));
            case 'games_played':
                return min(100, (int)(($stats['games_played'] ?? 0) / $target * 100));
            case 'streak':
                return min(100, (int)(($stats['best_streak'] ?? 0) / $target * 100));
            case 'perfect_sentences':
                return min(100, (int)(($stats['perfect_sentences'] ?? 0) / $target * 100));
            case 'articles_correct':
                return min(100, (int)(($stats['articles_correct'] ?? 0) / $target * 100));
            case 'prepositions_correct':
                return min(100, (int)(($stats['prepositions_correct'] ?? 0) / $target * 100));
            case 'verbs_correct':
                return min(100, (int)(($stats['verbs_correct'] ?? 0) / $target * 100));
            case 'idioms_correct':
                return min(100, (int)(($stats['idioms_correct'] ?? 0) / $target * 100));
            case 'typing_correct':
                return min(100, (int)(($stats['typing_correct'] ?? 0) / $target * 100));
            case 'levels_completed':
                return min(100, (int)(($stats['levels_completed'] ?? 0) / $target * 100));
            case 'stars_earned':
                return min(100, (int)(($stats['total_stars'] ?? 0) / $target * 100));
            case 'perfect_games':
                return min(100, (int)(($stats['perfect_games'] ?? 0) / $target * 100));
            default:
                return 0;
        }
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

            if ($this->isAchievementUnlocked($achievement, $stats)) {
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
    protected function isAchievementUnlocked(array $achievement, array $stats): bool
    {
        $condition = $achievement['condition'] ?? [];
        $type = $condition['type'] ?? '';
        $target = $condition['value'] ?? 0;

        switch ($type) {
            case 'gaps_filled':
                return ($stats['gaps_filled'] ?? 0) >= $target;
            case 'games_played':
                return ($stats['games_played'] ?? 0) >= $target;
            case 'streak':
                return ($stats['best_streak'] ?? 0) >= $target;
            case 'perfect_sentences':
                return ($stats['perfect_sentences'] ?? 0) >= $target;
            case 'articles_correct':
                return ($stats['articles_correct'] ?? 0) >= $target;
            case 'prepositions_correct':
                return ($stats['prepositions_correct'] ?? 0) >= $target;
            case 'verbs_correct':
                return ($stats['verbs_correct'] ?? 0) >= $target;
            case 'idioms_correct':
                return ($stats['idioms_correct'] ?? 0) >= $target;
            case 'typing_correct':
                return ($stats['typing_correct'] ?? 0) >= $target;
            case 'levels_completed':
                return ($stats['levels_completed'] ?? 0) >= $target;
            case 'stars_earned':
                return ($stats['total_stars'] ?? 0) >= $target;
            case 'perfect_games':
            case 'perfect_game':
                return ($stats['perfect_games'] ?? 0) >= $target;
            case 'fast_completion':
                return ($stats['fast_completions'] ?? 0) >= 1;
            case 'all_levels_3_stars':
                return $this->hasAllLevels3Stars();
            default:
                return false;
        }
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
