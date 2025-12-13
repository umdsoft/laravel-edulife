<?php

namespace App\Services\English;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class MinimalPairsDataService
{
    protected string $basePath;
    protected int $cacheTtl = 3600; // 1 hour cache

    public function __construct()
    {
        $this->basePath = base_path('data/english/games/minimal-pairs');
    }

    /**
     * Get game configuration
     */
    public function getConfig(): array
    {
        return Cache::remember('minimal_pairs.config', $this->cacheTtl, function () {
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
        $levels = Cache::remember('minimal_pairs.levels', $this->cacheTtl, function () {
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
                $level['best_accuracy'] = $levelProgress['best_accuracy'] ?? 0;
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
                $level['best_accuracy'] = 0;
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
        return Cache::get("minimal_pairs.user_progress.{$userId}", []);
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
            'best_accuracy' => max($existing['best_accuracy'] ?? 0, $data['accuracy'] ?? 0),
            'attempts' => ($existing['attempts'] ?? 0) + 1,
            'last_played' => now()->toDateTimeString(),
        ];

        Cache::put("minimal_pairs.user_progress.{$userId}", $progress, now()->addDays(30));
    }

    /**
     * Get all achievements
     */
    public function getAchievements(): array
    {
        return Cache::remember('minimal_pairs.achievements', $this->cacheTtl, function () {
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
        return Cache::get("minimal_pairs.user_achievements.{$userId}", []);
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
        Cache::put("minimal_pairs.user_achievements.{$userId}", $achievements, now()->addDays(365));

        return true;
    }

    /**
     * Get pairs for a specific level based on phoneme categories
     */
    public function getPairsForLevel(int $levelNumber): array
    {
        $level = $this->getLevel($levelNumber);
        if (!$level) {
            return [];
        }

        $pairsCount = $level['pairs_count'] ?? 10;
        $categories = $level['phoneme_categories'] ?? [];

        $allPairs = [];

        // Load pairs from each category
        foreach ($categories as $categoryId) {
            $categoryPairs = $this->getPairsByCategory($categoryId);
            foreach ($categoryPairs as $pair) {
                $pair['category'] = $categoryId;
                $allPairs[] = $pair;
            }
        }

        // Shuffle and take required count
        shuffle($allPairs);

        return array_slice($allPairs, 0, $pairsCount);
    }

    /**
     * Get pairs by category
     */
    public function getPairsByCategory(string $categoryId): array
    {
        $cacheKey = "minimal_pairs.pairs.{$categoryId}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($categoryId) {
            // Determine if vowel or consonant
            $vowelCategories = ['i_vs_ii', 'ae_vs_e', 'uh_vs_aa', 'o_vs_ou', 'u_vs_uu', 'schwa_vs_er'];
            $type = in_array($categoryId, $vowelCategories) ? 'vowels' : 'consonants';

            $path = $this->basePath . "/pairs/{$type}/{$categoryId}.json";

            if (File::exists($path)) {
                $data = json_decode(File::get($path), true);
                return $data['pairs'] ?? [];
            }
            return [];
        });
    }

    /**
     * Get category info
     */
    public function getCategoryInfo(string $categoryId): ?array
    {
        $vowelCategories = ['i_vs_ii', 'ae_vs_e', 'uh_vs_aa', 'o_vs_ou', 'u_vs_uu', 'schwa_vs_er'];
        $type = in_array($categoryId, $vowelCategories) ? 'vowels' : 'consonants';

        $path = $this->basePath . "/pairs/{$type}/{$categoryId}.json";

        if (File::exists($path)) {
            $data = json_decode(File::get($path), true);
            return [
                'category_id' => $data['category_id'] ?? $categoryId,
                'category_name' => $data['category_name'] ?? $categoryId,
                'category_name_uz' => $data['category_name_uz'] ?? $categoryId,
                'phoneme_1' => $data['phoneme_1'] ?? [],
                'phoneme_2' => $data['phoneme_2'] ?? [],
                'type' => $type,
            ];
        }
        return null;
    }

    /**
     * Get all phoneme categories
     */
    public function getAllCategories(): array
    {
        $config = $this->getConfig();
        return $config['phoneme_categories'] ?? [];
    }

    /**
     * Get user statistics
     */
    public function getUserStats(mixed $userId): array
    {
        $stats = Cache::get("minimal_pairs.user_stats.{$userId}", []);

        return [
            'total_pairs_completed' => $stats['total_pairs_completed'] ?? 0,
            'total_sessions' => $stats['total_sessions'] ?? 0,
            'total_xp_earned' => $stats['total_xp_earned'] ?? 0,
            'total_coins_earned' => $stats['total_coins_earned'] ?? 0,
            'best_streak' => $stats['best_streak'] ?? 0,
            'perfect_sessions' => $stats['perfect_sessions'] ?? 0,
            'total_listening_time' => $stats['total_listening_time'] ?? 0,
            'average_response_time' => $stats['average_response_time'] ?? 0,
            'accuracy_rate' => $stats['accuracy_rate'] ?? 0,
            'levels_completed' => $stats['levels_completed'] ?? 0,
            'three_star_levels' => $stats['three_star_levels'] ?? 0,
            'daily_streak' => $stats['daily_streak'] ?? 0,
            'last_played' => $stats['last_played'] ?? null,
            'listen_choose_sessions' => $stats['listen_choose_sessions'] ?? 0,
            'same_different_sessions' => $stats['same_different_sessions'] ?? 0,
            'odd_one_out_sessions' => $stats['odd_one_out_sessions'] ?? 0,
            'speak_compare_sessions' => $stats['speak_compare_sessions'] ?? 0,
            'categories_mastered' => $stats['categories_mastered'] ?? [],
            'total_replays' => $stats['total_replays'] ?? 0,
            'listening_rank' => $this->calculateListeningRank($stats),
        ];
    }

    /**
     * Calculate listening rank based on stats
     */
    protected function calculateListeningRank(array $stats): array
    {
        $config = $this->getConfig();
        $ranks = $config['listening_ranks'] ?? [];
        $totalPairs = $stats['total_pairs_completed'] ?? 0;

        $currentRank = null;
        $nextRank = null;

        foreach ($ranks as $index => $rank) {
            if ($totalPairs >= $rank['min_pairs']) {
                $currentRank = $rank;
                $nextRank = $ranks[$index + 1] ?? null;
            }
        }

        if (!$currentRank && !empty($ranks)) {
            $currentRank = $ranks[0];
            $nextRank = $ranks[1] ?? null;
        }

        return [
            'current' => $currentRank,
            'next' => $nextRank,
            'progress' => $nextRank
                ? min(100, (($totalPairs - ($currentRank['min_pairs'] ?? 0)) / (($nextRank['min_pairs'] ?? 1) - ($currentRank['min_pairs'] ?? 0))) * 100)
                : 100,
        ];
    }

    /**
     * Update user statistics
     */
    public function updateUserStats(mixed $userId, array $sessionData): void
    {
        $stats = Cache::get("minimal_pairs.user_stats.{$userId}", []);

        $stats['total_pairs_completed'] = ($stats['total_pairs_completed'] ?? 0) + ($sessionData['pairs_completed'] ?? 0);
        $stats['total_sessions'] = ($stats['total_sessions'] ?? 0) + 1;
        $stats['total_xp_earned'] = ($stats['total_xp_earned'] ?? 0) + ($sessionData['xp_earned'] ?? 0);
        $stats['total_coins_earned'] = ($stats['total_coins_earned'] ?? 0) + ($sessionData['coins_earned'] ?? 0);
        $stats['best_streak'] = max($stats['best_streak'] ?? 0, $sessionData['best_streak'] ?? 0);
        $stats['total_replays'] = ($stats['total_replays'] ?? 0) + ($sessionData['replays_used'] ?? 0);

        if ($sessionData['is_perfect'] ?? false) {
            $stats['perfect_sessions'] = ($stats['perfect_sessions'] ?? 0) + 1;
        }

        $stats['total_listening_time'] = ($stats['total_listening_time'] ?? 0) + ($sessionData['total_time'] ?? 0);

        // Calculate average response time
        $totalResponses = ($stats['total_responses'] ?? 0) + ($sessionData['total_responses'] ?? 0);
        $totalResponseTime = ($stats['total_response_time'] ?? 0) + ($sessionData['total_response_time'] ?? 0);
        $stats['total_responses'] = $totalResponses;
        $stats['total_response_time'] = $totalResponseTime;
        if ($totalResponses > 0) {
            $stats['average_response_time'] = round($totalResponseTime / $totalResponses, 2);
        }

        // Update accuracy
        $totalAttempts = ($stats['total_attempts'] ?? 0) + ($sessionData['total_attempts'] ?? 0);
        $correctAttempts = ($stats['correct_attempts'] ?? 0) + ($sessionData['correct_attempts'] ?? 0);
        $stats['total_attempts'] = $totalAttempts;
        $stats['correct_attempts'] = $correctAttempts;
        $stats['accuracy_rate'] = $totalAttempts > 0 ? round(($correctAttempts / $totalAttempts) * 100, 1) : 0;

        // Track mode-specific sessions
        $gameMode = $sessionData['game_mode'] ?? 'listen_choose';
        $modeKey = str_replace('_', '_', $gameMode) . '_sessions';
        $stats[$modeKey] = ($stats[$modeKey] ?? 0) + 1;

        // Track categories mastered
        if ($sessionData['category_mastered'] ?? null) {
            $categoriesMastered = $stats['categories_mastered'] ?? [];
            if (!in_array($sessionData['category_mastered'], $categoriesMastered)) {
                $categoriesMastered[] = $sessionData['category_mastered'];
                $stats['categories_mastered'] = $categoriesMastered;
            }
        }

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

        Cache::put("minimal_pairs.user_stats.{$userId}", $stats, now()->addDays(365));
    }

    /**
     * Get game modes configuration
     */
    public function getGameModes(): array
    {
        $config = $this->getConfig();
        return $config['game_modes'] ?? [];
    }

    /**
     * Get powerups configuration
     */
    public function getPowerups(): array
    {
        $config = $this->getConfig();
        return $config['powerups'] ?? [];
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
        Cache::forget('minimal_pairs.config');
        Cache::forget('minimal_pairs.levels');
        Cache::forget('minimal_pairs.achievements');

        // Clear pairs cache
        $categories = [
            'i_vs_ii', 'ae_vs_e', 'uh_vs_aa', 'o_vs_ou', 'u_vs_uu', 'schwa_vs_er',
            'b_vs_v', 'p_vs_b', 'f_vs_v', 'th_vs_s', 'dh_vs_d', 'r_vs_l'
        ];
        foreach ($categories as $category) {
            Cache::forget("minimal_pairs.pairs.{$category}");
        }
    }
}
