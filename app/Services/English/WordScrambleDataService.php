<?php

namespace App\Services\English;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class WordScrambleDataService
{
    protected string $basePath;
    protected int $cacheTtl = 3600; // 1 hour cache

    public function __construct()
    {
        $this->basePath = base_path('data/english/games/word-scramble');
    }

    /**
     * Get game configuration
     */
    public function getConfig(): array
    {
        return Cache::remember('word_scramble.config', $this->cacheTtl, function () {
            $path = $this->basePath . '/config.json';
            if (File::exists($path)) {
                return json_decode(File::get($path), true) ?? [];
            }
            return [];
        });
    }

    /**
     * Get all levels with user progress
     */
    public function getLevels(mixed $userId = null): array
    {
        $levels = Cache::remember('word_scramble.levels', $this->cacheTtl, function () {
            $path = $this->basePath . '/levels.json';
            if (File::exists($path)) {
                $data = json_decode(File::get($path), true);
                return $data['levels'] ?? [];
            }
            return [];
        });

        // Add user progress if userId provided
        if ($userId) {
            $userProgress = $this->getUserProgress($userId);
            $levels = array_map(function ($level) use ($userProgress) {
                $levelProgress = $userProgress[$level['number']] ?? [];
                $level['unlocked'] = $this->isLevelUnlocked($level, $userProgress);
                $level['completed'] = $levelProgress['completed'] ?? false;
                $level['best_stars'] = $levelProgress['best_stars'] ?? 0;
                $level['best_score'] = $levelProgress['best_score'] ?? 0;
                $level['attempts'] = $levelProgress['attempts'] ?? 0;
                return $level;
            }, $levels);
        } else {
            // First level always unlocked
            $levels = array_map(function ($level, $index) {
                $level['unlocked'] = $index === 0;
                $level['completed'] = false;
                $level['best_stars'] = 0;
                $level['best_score'] = 0;
                $level['attempts'] = 0;
                return $level;
            }, $levels, array_keys($levels));
        }

        return $levels;
    }

    /**
     * Get a specific level
     */
    public function getLevel(int $levelNumber): ?array
    {
        $levels = $this->getLevels();
        foreach ($levels as $level) {
            if ($level['number'] === $levelNumber) {
                return $level;
            }
        }
        return null;
    }

    /**
     * Check if a level is unlocked for user
     */
    protected function isLevelUnlocked(array $level, array $userProgress): bool
    {
        // First level is always unlocked
        if ($level['number'] === 1) {
            return true;
        }

        $requirement = $level['unlock_requirement'] ?? null;
        if (!$requirement) {
            return true;
        }

        $requiredLevel = $requirement['level'] ?? null;
        $requiredStars = $requirement['stars'] ?? 1;

        if (!$requiredLevel) {
            return true;
        }

        $prevLevelProgress = $userProgress[$requiredLevel] ?? [];
        $prevLevelStars = $prevLevelProgress['best_stars'] ?? 0;

        return $prevLevelStars >= $requiredStars;
    }

    /**
     * Get user progress from cache/storage
     */
    public function getUserProgress(mixed $userId): array
    {
        return Cache::get("word_scramble.user_progress.{$userId}", []);
    }

    /**
     * Save user progress
     */
    public function saveUserProgress(mixed $userId, int $levelNumber, array $data): void
    {
        $progress = $this->getUserProgress($userId);

        $existing = $progress[$levelNumber] ?? [];

        // Update only if better
        $progress[$levelNumber] = [
            'completed' => true,
            'best_stars' => max($existing['best_stars'] ?? 0, $data['stars'] ?? 0),
            'best_score' => max($existing['best_score'] ?? 0, $data['score'] ?? 0),
            'attempts' => ($existing['attempts'] ?? 0) + 1,
            'last_played' => now()->toDateTimeString(),
        ];

        Cache::put("word_scramble.user_progress.{$userId}", $progress, now()->addDays(30));
    }

    /**
     * Get all categories
     */
    public function getCategories(): array
    {
        return Cache::remember('word_scramble.categories', $this->cacheTtl, function () {
            $path = $this->basePath . '/categories.json';
            if (File::exists($path)) {
                $data = json_decode(File::get($path), true);
                return $data['categories'] ?? [];
            }
            return [];
        });
    }

    /**
     * Get all achievements
     */
    public function getAchievements(): array
    {
        return Cache::remember('word_scramble.achievements', $this->cacheTtl, function () {
            $path = $this->basePath . '/achievements.json';
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
    public function getUserAchievements(mixed $userId): array
    {
        return Cache::get("word_scramble.user_achievements.{$userId}", []);
    }

    /**
     * Unlock achievement for user
     */
    public function unlockAchievement(mixed $userId, string $achievementId): bool
    {
        $achievements = $this->getUserAchievements($userId);

        if (in_array($achievementId, $achievements)) {
            return false; // Already unlocked
        }

        $achievements[] = $achievementId;
        Cache::put("word_scramble.user_achievements.{$userId}", $achievements, now()->addDays(365));

        return true;
    }

    /**
     * Get words for a specific level
     */
    public function getWordsForLevel(int $levelNumber): array
    {
        $level = $this->getLevel($levelNumber);
        if (!$level) {
            return [];
        }

        $cefrLevel = $level['cefr_level'] ?? 'A1';
        $wordLengthMin = $level['word_length_min'] ?? 3;
        $wordLengthMax = $level['word_length_max'] ?? 12;
        $wordsCount = $level['words_count'] ?? 10;

        // Map level to file
        $fileMap = [
            1 => 'a1.json',
            2 => 'a1_plus.json',
            3 => 'a2.json',
            4 => 'a2_plus.json',
            5 => 'b1.json',
            6 => 'b1_plus.json',
            7 => 'b2.json',
            8 => 'b2_plus.json',
            9 => 'c1.json',
            10 => 'c2.json',
        ];

        $fileName = $fileMap[$levelNumber] ?? 'a1.json';
        $words = $this->loadWordsFromFile($fileName);

        // Filter by word length
        $filteredWords = array_filter($words, function ($word) use ($wordLengthMin, $wordLengthMax) {
            $length = strlen($word['word']);
            return $length >= $wordLengthMin && $length <= $wordLengthMax;
        });

        // Shuffle and take required count
        $filteredWords = array_values($filteredWords);
        shuffle($filteredWords);

        return array_slice($filteredWords, 0, $wordsCount);
    }

    /**
     * Load words from a specific file
     */
    protected function loadWordsFromFile(string $fileName): array
    {
        $cacheKey = "word_scramble.words.{$fileName}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($fileName) {
            $path = $this->basePath . '/words/' . $fileName;
            if (File::exists($path)) {
                $data = json_decode(File::get($path), true);
                return $data['words'] ?? [];
            }
            return [];
        });
    }

    /**
     * Get user statistics
     */
    public function getUserStats(mixed $userId): array
    {
        $stats = Cache::get("word_scramble.user_stats.{$userId}", []);

        return [
            'total_words_completed' => $stats['total_words_completed'] ?? 0,
            'total_sessions' => $stats['total_sessions'] ?? 0,
            'total_xp_earned' => $stats['total_xp_earned'] ?? 0,
            'total_coins_earned' => $stats['total_coins_earned'] ?? 0,
            'best_streak' => $stats['best_streak'] ?? 0,
            'perfect_sessions' => $stats['perfect_sessions'] ?? 0,
            'total_time_played' => $stats['total_time_played'] ?? 0,
            'average_time_per_word' => $stats['average_time_per_word'] ?? 0,
            'accuracy_rate' => $stats['accuracy_rate'] ?? 0,
            'levels_completed' => $stats['levels_completed'] ?? 0,
            'three_star_levels' => $stats['three_star_levels'] ?? 0,
            'daily_streak' => $stats['daily_streak'] ?? 0,
            'last_played' => $stats['last_played'] ?? null,
        ];
    }

    /**
     * Update user statistics
     */
    public function updateUserStats(mixed $userId, array $sessionData): void
    {
        $stats = Cache::get("word_scramble.user_stats.{$userId}", []);

        $stats['total_words_completed'] = ($stats['total_words_completed'] ?? 0) + ($sessionData['words_completed'] ?? 0);
        $stats['total_sessions'] = ($stats['total_sessions'] ?? 0) + 1;
        $stats['total_xp_earned'] = ($stats['total_xp_earned'] ?? 0) + ($sessionData['xp_earned'] ?? 0);
        $stats['total_coins_earned'] = ($stats['total_coins_earned'] ?? 0) + ($sessionData['coins_earned'] ?? 0);
        $stats['best_streak'] = max($stats['best_streak'] ?? 0, $sessionData['best_streak'] ?? 0);

        if ($sessionData['is_perfect'] ?? false) {
            $stats['perfect_sessions'] = ($stats['perfect_sessions'] ?? 0) + 1;
        }

        $stats['total_time_played'] = ($stats['total_time_played'] ?? 0) + ($sessionData['total_time'] ?? 0);

        // Calculate average time per word
        if ($stats['total_words_completed'] > 0) {
            $stats['average_time_per_word'] = round($stats['total_time_played'] / $stats['total_words_completed'], 2);
        }

        // Update accuracy
        $totalAttempts = ($stats['total_attempts'] ?? 0) + ($sessionData['total_attempts'] ?? 0);
        $correctAttempts = ($stats['correct_attempts'] ?? 0) + ($sessionData['correct_attempts'] ?? 0);
        $stats['total_attempts'] = $totalAttempts;
        $stats['correct_attempts'] = $correctAttempts;
        $stats['accuracy_rate'] = $totalAttempts > 0 ? round(($correctAttempts / $totalAttempts) * 100, 1) : 0;

        // Update daily streak
        $today = now()->toDateString();
        $lastPlayed = $stats['last_played'] ?? null;

        if ($lastPlayed) {
            $yesterday = now()->subDay()->toDateString();
            if ($lastPlayed === $yesterday) {
                $stats['daily_streak'] = ($stats['daily_streak'] ?? 0) + 1;
            } elseif ($lastPlayed !== $today) {
                $stats['daily_streak'] = 1;
            }
        } else {
            $stats['daily_streak'] = 1;
        }

        $stats['last_played'] = $today;

        Cache::put("word_scramble.user_stats.{$userId}", $stats, now()->addDays(365));
    }

    /**
     * Get hints configuration
     */
    public function getHints(): array
    {
        $config = $this->getConfig();
        return $config['hint_system']['hints'] ?? [];
    }

    /**
     * Get scoring configuration
     */
    public function getScoring(): array
    {
        $config = $this->getConfig();
        return $config['scoring'] ?? [];
    }

    /**
     * Get rewards configuration
     */
    public function getRewards(): array
    {
        $config = $this->getConfig();
        return $config['rewards'] ?? [];
    }

    /**
     * Clear all caches
     */
    public function clearCache(): void
    {
        Cache::forget('word_scramble.config');
        Cache::forget('word_scramble.levels');
        Cache::forget('word_scramble.categories');
        Cache::forget('word_scramble.achievements');

        // Clear word files cache
        $wordFiles = ['a1', 'a1_plus', 'a2', 'a2_plus', 'b1', 'b1_plus', 'b2', 'b2_plus', 'c1', 'c2'];
        foreach ($wordFiles as $file) {
            Cache::forget("word_scramble.words.{$file}.json");
        }
    }
}
