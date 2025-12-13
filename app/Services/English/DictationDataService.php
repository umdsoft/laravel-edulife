<?php

namespace App\Services\English;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;

class DictationDataService
{
    protected string $basePath;
    protected array $cefrLevels = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];

    public function __construct()
    {
        $this->basePath = base_path('data/english/games/dictation');
    }

    /**
     * Get game configuration
     */
    public function getConfig(): array
    {
        return Cache::remember('dictation_config', 3600, function () {
            $configPath = $this->basePath . '/config.json';
            if (!File::exists($configPath)) {
                return $this->getDefaultConfig();
            }
            return json_decode(File::get($configPath), true);
        });
    }

    /**
     * Get all levels with progress
     */
    public function getLevels($userId = null): array
    {
        $levels = Cache::remember('dictation_levels', 3600, function () {
            $levelsPath = $this->basePath . '/levels.json';
            if (!File::exists($levelsPath)) {
                return [];
            }
            return json_decode(File::get($levelsPath), true)['levels'] ?? [];
        });

        // Add user progress if available
        if ($userId) {
            $userProgress = $this->getUserProgress($userId);
            foreach ($levels as &$level) {
                $levelProgress = $userProgress[$level['number']] ?? null;
                $level['completed'] = $levelProgress['completed'] ?? false;
                $level['stars'] = $levelProgress['stars'] ?? 0;
                $level['best_score'] = $levelProgress['best_score'] ?? 0;
                $level['attempts'] = $levelProgress['attempts'] ?? 0;
                $level['unlocked'] = $level['number'] === 1 ||
                    ($userProgress[$level['number'] - 1]['completed'] ?? false);
            }
        }

        return $levels;
    }

    /**
     * Get single level by number
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
     * Get content for a specific level
     */
    public function getLevelContent(int $levelNumber, int $itemCount = 10): array
    {
        $level = $this->getLevel($levelNumber);
        if (!$level) {
            return [];
        }

        $cefrLevel = $level['cefr_level'];
        $contentPath = $this->basePath . '/content/' . $cefrLevel;

        if (!File::isDirectory($contentPath)) {
            return [];
        }

        // Get all JSON files for this level
        $files = File::glob($contentPath . '/*.json');
        $allItems = [];

        foreach ($files as $file) {
            $content = json_decode(File::get($file), true);
            if (isset($content['items'])) {
                foreach ($content['items'] as $item) {
                    $item['category'] = $content['category'] ?? 'general';
                    $item['content_type'] = $content['content_type'] ?? 'words';
                    $allItems[] = $item;
                }
            }
        }

        // Shuffle and return requested number
        shuffle($allItems);
        return array_slice($allItems, 0, $itemCount);
    }

    /**
     * Get specific content category
     */
    public function getContentByCategory(string $cefrLevel, string $category): array
    {
        $filePath = $this->basePath . '/content/' . $cefrLevel . '/' . $category . '.json';

        if (!File::exists($filePath)) {
            return [];
        }

        $content = json_decode(File::get($filePath), true);
        return $content['items'] ?? [];
    }

    /**
     * Get all available categories for a level
     */
    public function getCategories(string $cefrLevel): array
    {
        $contentPath = $this->basePath . '/content/' . $cefrLevel;

        if (!File::isDirectory($contentPath)) {
            return [];
        }

        $files = File::glob($contentPath . '/*.json');
        $categories = [];

        foreach ($files as $file) {
            $content = json_decode(File::get($file), true);
            $categories[] = [
                'name' => $content['category'] ?? pathinfo($file, PATHINFO_FILENAME),
                'type' => $content['content_type'] ?? 'words',
                'count' => count($content['items'] ?? []),
            ];
        }

        return $categories;
    }

    /**
     * Get user progress from cache/session
     */
    public function getUserProgress($userId): array
    {
        $cacheKey = "dictation_progress_{$userId}";
        return Cache::get($cacheKey, []);
    }

    /**
     * Save user progress
     */
    public function saveUserProgress($userId, int $levelNumber, array $progressData): void
    {
        $cacheKey = "dictation_progress_{$userId}";
        $progress = Cache::get($cacheKey, []);

        // Update progress for this level
        if (!isset($progress[$levelNumber]) ||
            ($progressData['score'] ?? 0) > ($progress[$levelNumber]['best_score'] ?? 0)) {
            $progress[$levelNumber] = array_merge(
                $progress[$levelNumber] ?? [],
                [
                    'completed' => true,
                    'stars' => max($progress[$levelNumber]['stars'] ?? 0, $progressData['stars'] ?? 0),
                    'best_score' => max($progress[$levelNumber]['best_score'] ?? 0, $progressData['score'] ?? 0),
                    'attempts' => ($progress[$levelNumber]['attempts'] ?? 0) + 1,
                    'last_played' => now()->toDateTimeString(),
                ]
            );
        } else {
            $progress[$levelNumber]['attempts'] = ($progress[$levelNumber]['attempts'] ?? 0) + 1;
            $progress[$levelNumber]['last_played'] = now()->toDateTimeString();
        }

        Cache::put($cacheKey, $progress, now()->addDays(30));
    }

    /**
     * Get total statistics for a user
     */
    public function getUserStats($userId): array
    {
        $progress = $this->getUserProgress($userId);

        $totalStars = 0;
        $completedLevels = 0;
        $totalScore = 0;
        $totalAttempts = 0;

        foreach ($progress as $levelData) {
            $totalStars += $levelData['stars'] ?? 0;
            $completedLevels += ($levelData['completed'] ?? false) ? 1 : 0;
            $totalScore += $levelData['best_score'] ?? 0;
            $totalAttempts += $levelData['attempts'] ?? 0;
        }

        return [
            'total_stars' => $totalStars,
            'max_stars' => count($this->getLevels()) * 3,
            'completed_levels' => $completedLevels,
            'total_levels' => count($this->getLevels()),
            'total_score' => $totalScore,
            'total_attempts' => $totalAttempts,
        ];
    }

    /**
     * Default configuration
     */
    protected function getDefaultConfig(): array
    {
        return [
            'game_name' => 'Dictation',
            'game_name_uz' => 'Diktant',
            'settings' => [
                'items_per_session' => 10,
                'max_attempts_per_item' => 3,
                'hint_penalty_percent' => 15,
                'playback_speed_normal' => 1.0,
                'playback_speed_slow' => 0.7,
                'max_replays' => 5,
            ],
            'scoring' => [
                'base_points_per_word' => 10,
                'perfect_bonus' => 5,
                'streak_multiplier' => 0.1,
            ],
            'star_thresholds' => [
                'one_star' => 50,
                'two_stars' => 75,
                'three_stars' => 90,
            ],
            'rewards' => [
                'xp_per_correct' => 2,
                'xp_per_perfect' => 5,
                'coins_per_session_complete' => 3,
            ],
        ];
    }

    /**
     * Clear cache for fresh data
     */
    public function clearCache(): void
    {
        Cache::forget('dictation_config');
        Cache::forget('dictation_levels');
    }
}
