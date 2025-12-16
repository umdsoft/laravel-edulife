<?php

namespace App\Services\English;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class TrueFalseDataService
{
    protected string $dataPath;
    protected int $cacheTtl = 3600;

    public function __construct()
    {
        $this->dataPath = base_path('data/english/games/true-false');
    }

    /**
     * Get game configuration
     */
    public function getConfig(): array
    {
        return Cache::remember('true_false.config', $this->cacheTtl, function () {
            $path = $this->dataPath . '/config.json';
            if (File::exists($path)) {
                return json_decode(File::get($path), true);
            }
            return [];
        });
    }

    /**
     * Get all levels
     */
    public function getLevels(): array
    {
        return Cache::remember('true_false.levels', $this->cacheTtl, function () {
            $path = $this->dataPath . '/levels.json';
            if (File::exists($path)) {
                $data = json_decode(File::get($path), true);
                return $data['levels'] ?? [];
            }
            return [];
        });
    }

    /**
     * Get specific level
     */
    public function getLevel(int $levelNumber): ?array
    {
        $levels = $this->getLevels();
        foreach ($levels as $level) {
            if ($level['level_number'] === $levelNumber) {
                return $level;
            }
        }
        return null;
    }

    /**
     * Get levels with user progress status
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
                'best_score' => $levelProgress['best_score'] ?? 0,
                'best_accuracy' => $levelProgress['best_accuracy'] ?? 0,
                'attempts' => $levelProgress['attempts'] ?? 0,
            ]);
        }, $levels);
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

        if (!$previousLevel || !$previousLevel['completed']) {
            return false;
        }

        $level = $this->getLevel($levelNumber);
        if (!$level) {
            return false;
        }

        $requirement = $level['unlock_requirement'] ?? null;
        if ($requirement && str_contains($requirement, '3 stars')) {
            return ($previousLevel['stars'] ?? 0) >= 3;
        }
        if ($requirement && str_contains($requirement, '2 stars')) {
            return ($previousLevel['stars'] ?? 0) >= 2;
        }

        return true;
    }

    /**
     * Get all achievements
     */
    public function getAchievements(): array
    {
        return Cache::remember('true_false.achievements', $this->cacheTtl, function () {
            $path = $this->dataPath . '/achievements.json';
            if (File::exists($path)) {
                $data = json_decode(File::get($path), true);
                return $data['achievements'] ?? [];
            }
            return [];
        });
    }

    /**
     * Get user achievements
     */
    public function getUserAchievements(): array
    {
        $achievements = $this->getAchievements();
        $progress = $this->getUserProgress();
        $unlockedIds = $progress['achievements'] ?? [];

        return array_map(function ($achievement) use ($unlockedIds, $progress) {
            $unlocked = in_array($achievement['id'], $unlockedIds);
            $progressValue = $this->calculateAchievementProgress($achievement, $progress);

            return array_merge($achievement, [
                'unlocked' => $unlocked,
                'progress' => $progressValue,
            ]);
        }, $achievements);
    }

    /**
     * Calculate achievement progress
     */
    protected function calculateAchievementProgress(array $achievement, array $progress): int
    {
        $condition = $achievement['condition'] ?? [];
        $type = $condition['type'] ?? '';
        $target = $condition['value'] ?? 1;

        switch ($type) {
            case 'correct_answers':
                $current = $progress['stats']['total_correct'] ?? 0;
                break;
            case 'perfect_rounds':
                $current = $progress['stats']['perfect_rounds'] ?? 0;
                break;
            case 'streak':
                $current = $progress['stats']['best_streak'] ?? 0;
                break;
            case 'total_rounds':
                $current = $progress['stats']['games_played'] ?? 0;
                break;
            case 'fast_answers':
                $current = $progress['stats']['fast_answers'] ?? 0;
                break;
            case 'category_correct':
                $category = $condition['category'] ?? '';
                $current = $progress['category_stats'][$category]['correct'] ?? 0;
                break;
            default:
                return 0;
        }

        return min(100, (int)(($current / $target) * 100));
    }

    /**
     * Get statements for a category
     */
    public function getStatements(string $category): array
    {
        return Cache::remember("true_false.statements.{$category}", $this->cacheTtl, function () use ($category) {
            $path = $this->dataPath . "/statements/{$category}.json";
            if (File::exists($path)) {
                $data = json_decode(File::get($path), true);
                return $data['statements'] ?? [];
            }
            return [];
        });
    }

    /**
     * Get statements for level
     */
    public function getStatementsForLevel(int $levelNumber, ?string $mode = null): array
    {
        $level = $this->getLevel($levelNumber);
        if (!$level) {
            return [];
        }

        $categories = $level['categories'] ?? ['mixed'];
        $count = $level['statements_count'] ?? 10;
        $statements = [];

        if (in_array('mixed', $categories)) {
            $allCategories = ['grammar', 'vocabulary', 'culture', 'pronunciation', 'spelling'];
            foreach ($allCategories as $cat) {
                $catStatements = $this->getStatements($cat);
                $statements = array_merge($statements, $catStatements);
            }
        } else {
            foreach ($categories as $category) {
                $catStatements = $this->getStatements($category);
                $statements = array_merge($statements, $catStatements);
            }
        }

        // Filter by difficulty
        $difficulty = $level['difficulty'] ?? 'beginner';
        $allowedDifficulties = $this->getAllowedDifficulties($difficulty);

        $statements = array_filter($statements, function ($stmt) use ($allowedDifficulties) {
            return in_array($stmt['difficulty'] ?? 'beginner', $allowedDifficulties);
        });

        // Shuffle and limit
        shuffle($statements);
        return array_slice($statements, 0, $count);
    }

    /**
     * Get allowed difficulties for a level difficulty
     */
    protected function getAllowedDifficulties(string $difficulty): array
    {
        switch ($difficulty) {
            case 'beginner':
                return ['beginner'];
            case 'intermediate':
                return ['beginner', 'intermediate'];
            case 'advanced':
                return ['intermediate', 'advanced'];
            case 'expert':
                return ['advanced', 'expert'];
            default:
                return ['beginner', 'intermediate', 'advanced'];
        }
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
     * Get truth master ranks
     */
    public function getTruthMasterRanks(): array
    {
        $config = $this->getConfig();
        return $config['truth_master_ranks'] ?? [];
    }

    /**
     * Get user progress
     */
    public function getUserProgress(): array
    {
        $userId = Auth::id();
        if (!$userId) {
            return $this->getDefaultProgress();
        }

        $cacheKey = "true_false.progress.{$userId}";

        return Cache::remember($cacheKey, 300, function () use ($userId) {
            $path = storage_path("app/game_progress/true_false/{$userId}.json");

            if (File::exists($path)) {
                return json_decode(File::get($path), true);
            }

            return $this->getDefaultProgress();
        });
    }

    /**
     * Get default progress structure
     */
    protected function getDefaultProgress(): array
    {
        return [
            'levels' => [],
            'achievements' => [],
            'stats' => [
                'games_played' => 0,
                'total_correct' => 0,
                'total_answered' => 0,
                'accuracy' => 0,
                'best_streak' => 0,
                'perfect_rounds' => 0,
                'fast_answers' => 0,
                'total_xp' => 0,
                'total_coins' => 0,
            ],
            'category_stats' => [],
            'mode_stats' => [],
        ];
    }

    /**
     * Save user progress
     */
    public function saveUserProgress(array $progress): void
    {
        $userId = Auth::id();
        if (!$userId) {
            return;
        }

        $dir = storage_path("app/game_progress/true_false");
        if (!File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $path = "{$dir}/{$userId}.json";
        File::put($path, json_encode($progress, JSON_PRETTY_PRINT));

        Cache::forget("true_false.progress.{$userId}");
    }

    /**
     * Get user stats
     */
    public function getUserStats(): array
    {
        $progress = $this->getUserProgress();
        return $progress['stats'] ?? [];
    }

    /**
     * Get total stars
     */
    public function getTotalStars(): int
    {
        $progress = $this->getUserProgress();
        $total = 0;

        foreach ($progress['levels'] ?? [] as $level) {
            $total += $level['stars'] ?? 0;
        }

        return $total;
    }
}
