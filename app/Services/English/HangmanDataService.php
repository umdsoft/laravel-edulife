<?php

namespace App\Services\English;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class HangmanDataService
{
    protected string $dataPath;
    protected int $cacheTtl = 3600;

    public function __construct()
    {
        $this->dataPath = base_path('data/english/games/hangman');
    }

    /**
     * Get game configuration
     */
    public function getConfig(): array
    {
        return Cache::remember('hangman.config', $this->cacheTtl, function () {
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
        return Cache::remember('hangman.levels', $this->cacheTtl, function () {
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
                'words_solved' => $levelProgress['words_solved'] ?? 0,
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
        return Cache::remember('hangman.achievements', $this->cacheTtl, function () {
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
            case 'words_solved':
                $current = $progress['stats']['total_words_solved'] ?? 0;
                break;
            case 'perfect_words':
                $current = $progress['stats']['perfect_words'] ?? 0;
                break;
            case 'streak':
                $current = $progress['stats']['best_streak'] ?? 0;
                break;
            case 'category_words':
                $category = $condition['category'] ?? '';
                $current = $progress['category_stats'][$category]['solved'] ?? 0;
                break;
            default:
                return 0;
        }

        return min(100, (int)(($current / $target) * 100));
    }

    /**
     * Get words for a category
     */
    public function getWords(string $category): array
    {
        return Cache::remember("hangman.words.{$category}", $this->cacheTtl, function () use ($category) {
            $path = $this->dataPath . "/words/{$category}.json";
            if (File::exists($path)) {
                $data = json_decode(File::get($path), true);
                return $data['words'] ?? [];
            }
            return [];
        });
    }

    /**
     * Get words for level
     */
    public function getWordsForLevel(int $levelNumber): array
    {
        $level = $this->getLevel($levelNumber);
        if (!$level) {
            return [];
        }

        $categories = $level['categories'] ?? [];
        $count = $level['words_count'] ?? 5;
        $maxLength = $level['max_word_length'] ?? 15;
        $difficulty = $level['difficulty'] ?? 'beginner';

        $allWords = [];
        foreach ($categories as $category) {
            $categoryWords = $this->getWords($category);
            foreach ($categoryWords as $word) {
                $word['category'] = $category;
                $allWords[] = $word;
            }
        }

        // Filter by max length and difficulty
        $allowedDifficulties = $this->getAllowedDifficulties($difficulty);
        $filteredWords = array_filter($allWords, function ($word) use ($maxLength, $allowedDifficulties) {
            return strlen($word['word']) <= $maxLength &&
                   in_array($word['difficulty'] ?? 'beginner', $allowedDifficulties);
        });

        // Shuffle and limit
        shuffle($filteredWords);
        return array_slice($filteredWords, 0, $count);
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
     * Get word master ranks
     */
    public function getWordMasterRanks(): array
    {
        $config = $this->getConfig();
        return $config['word_master_ranks'] ?? [];
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

        $cacheKey = "hangman.progress.{$userId}";

        return Cache::remember($cacheKey, 300, function () use ($userId) {
            $path = storage_path("app/game_progress/hangman/{$userId}.json");

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
                'total_words_solved' => 0,
                'total_words_failed' => 0,
                'perfect_words' => 0,
                'best_streak' => 0,
                'total_xp' => 0,
                'total_coins' => 0,
            ],
            'category_stats' => [],
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

        $dir = storage_path("app/game_progress/hangman");
        if (!File::isDirectory($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $path = "{$dir}/{$userId}.json";
        File::put($path, json_encode($progress, JSON_PRETTY_PRINT));

        Cache::forget("hangman.progress.{$userId}");
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
