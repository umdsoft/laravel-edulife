<?php

namespace App\Services\English;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;

class VocabularyQuizDataService
{
    protected string $basePath;
    protected array $cefrLevels = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];

    public function __construct()
    {
        $this->basePath = base_path('data/english/games/vocabulary-quiz');
    }

    /**
     * Get game configuration
     */
    public function getConfig(): array
    {
        return Cache::remember('vocabulary_quiz_config', 3600, function () {
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
        $levels = Cache::remember('vocabulary_quiz_levels', 3600, function () {
            $levelsPath = $this->basePath . '/levels.json';
            if (!File::exists($levelsPath)) {
                return [];
            }
            return json_decode(File::get($levelsPath), true)['levels'] ?? [];
        });

        // Add user progress if available
        if ($userId) {
            $userProgress = $this->getUserProgress($userId);
            $totalStars = 0;

            foreach ($levels as $index => &$level) {
                $levelProgress = $userProgress[$level['number']] ?? null;
                $level['completed'] = $levelProgress['completed'] ?? false;
                $level['stars'] = $levelProgress['stars'] ?? 0;
                $level['best_score'] = $levelProgress['best_score'] ?? 0;
                $level['attempts'] = $levelProgress['attempts'] ?? 0;

                // Calculate total stars for unlock requirements
                $totalStars += $level['stars'];

                // Check if level is unlocked based on required stars
                $level['unlocked'] = $level['number'] === 1 || $totalStars >= $level['required_stars'];
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
     * Get categories configuration
     */
    public function getCategories(): array
    {
        return Cache::remember('vocabulary_quiz_categories', 3600, function () {
            $categoriesPath = $this->basePath . '/categories.json';
            if (!File::exists($categoriesPath)) {
                return [];
            }
            return json_decode(File::get($categoriesPath), true)['categories'] ?? [];
        });
    }

    /**
     * Get powerups configuration
     */
    public function getPowerups(): array
    {
        return Cache::remember('vocabulary_quiz_powerups', 3600, function () {
            $powerupsPath = $this->basePath . '/powerups.json';
            if (!File::exists($powerupsPath)) {
                return [];
            }
            return json_decode(File::get($powerupsPath), true)['powerups'] ?? [];
        });
    }

    /**
     * Get achievements configuration
     */
    public function getAchievements(): array
    {
        return Cache::remember('vocabulary_quiz_achievements', 3600, function () {
            $achievementsPath = $this->basePath . '/achievements.json';
            if (!File::exists($achievementsPath)) {
                return [];
            }
            return json_decode(File::get($achievementsPath), true)['achievements'] ?? [];
        });
    }

    /**
     * Get vocabulary words for a specific level
     */
    public function getLevelVocabulary(int $levelNumber, int $wordCount = 10): array
    {
        $level = $this->getLevel($levelNumber);
        if (!$level) {
            return [];
        }

        $cefrLevel = $level['cefr_level'];
        $vocabularyPath = $this->basePath . '/vocabulary/' . $cefrLevel;

        if (!File::isDirectory($vocabularyPath)) {
            return [];
        }

        // Get all JSON files for this level
        $files = File::glob($vocabularyPath . '/*.json');
        $allWords = [];

        foreach ($files as $file) {
            $content = json_decode(File::get($file), true);
            if (isset($content['words'])) {
                foreach ($content['words'] as $word) {
                    $word['category'] = $content['category'] ?? 'general';
                    $word['category_name'] = $content['category_name'] ?? 'General';
                    $allWords[] = $word;
                }
            }
        }

        // Shuffle and return requested number
        shuffle($allWords);
        return array_slice($allWords, 0, $wordCount);
    }

    /**
     * Get vocabulary by category
     */
    public function getVocabularyByCategory(string $cefrLevel, string $category): array
    {
        $filePath = $this->basePath . '/vocabulary/' . $cefrLevel . '/' . $category . '.json';

        if (!File::exists($filePath)) {
            return [];
        }

        $content = json_decode(File::get($filePath), true);
        return $content['words'] ?? [];
    }

    /**
     * Get all vocabulary for generating distractors
     */
    public function getAllVocabulary(string $cefrLevel): array
    {
        $vocabularyPath = $this->basePath . '/vocabulary/' . $cefrLevel;

        if (!File::isDirectory($vocabularyPath)) {
            return [];
        }

        $files = File::glob($vocabularyPath . '/*.json');
        $allWords = [];

        foreach ($files as $file) {
            $content = json_decode(File::get($file), true);
            if (isset($content['words'])) {
                $allWords = array_merge($allWords, $content['words']);
            }
        }

        return $allWords;
    }

    /**
     * Get user progress from cache
     */
    public function getUserProgress($userId): array
    {
        $cacheKey = "vocabulary_quiz_progress_{$userId}";
        return Cache::get($cacheKey, []);
    }

    /**
     * Save user progress
     */
    public function saveUserProgress($userId, int $levelNumber, array $progressData): void
    {
        $cacheKey = "vocabulary_quiz_progress_{$userId}";
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
                    'accuracy' => max($progress[$levelNumber]['accuracy'] ?? 0, $progressData['accuracy'] ?? 0),
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
        $wordsLearned = 0;

        foreach ($progress as $levelData) {
            $totalStars += $levelData['stars'] ?? 0;
            $completedLevels += ($levelData['completed'] ?? false) ? 1 : 0;
            $totalScore += $levelData['best_score'] ?? 0;
            $totalAttempts += $levelData['attempts'] ?? 0;
        }

        // Calculate words learned based on completed levels
        $levels = $this->getLevels();
        foreach ($levels as $level) {
            if (isset($progress[$level['number']]) && ($progress[$level['number']]['completed'] ?? false)) {
                $wordsLearned += $level['word_count'] ?? 0;
            }
        }

        return [
            'total_stars' => $totalStars,
            'max_stars' => count($levels) * 3,
            'completed_levels' => $completedLevels,
            'total_levels' => count($levels),
            'total_score' => $totalScore,
            'total_attempts' => $totalAttempts,
            'words_learned' => $wordsLearned,
        ];
    }

    /**
     * Get user achievements
     */
    public function getUserAchievements($userId): array
    {
        $cacheKey = "vocabulary_quiz_achievements_{$userId}";
        return Cache::get($cacheKey, []);
    }

    /**
     * Save user achievement
     */
    public function saveUserAchievement($userId, string $achievementId): void
    {
        $cacheKey = "vocabulary_quiz_achievements_{$userId}";
        $achievements = Cache::get($cacheKey, []);

        if (!in_array($achievementId, $achievements)) {
            $achievements[] = $achievementId;
            Cache::put($cacheKey, $achievements, now()->addDays(365));
        }
    }

    /**
     * Default configuration
     */
    protected function getDefaultConfig(): array
    {
        return [
            'game_name' => 'Vocabulary Quiz',
            'game_name_uz' => 'So\'z Viktorinasi',
            'settings' => [
                'questions_per_session' => 10,
                'base_time_limit' => 20,
                'min_options' => 4,
                'max_options' => 4,
            ],
            'scoring' => [
                'base_points' => 100,
                'time_bonus_enabled' => true,
                'time_bonus_max' => 50,
            ],
            'star_thresholds' => [
                'one_star' => 50,
                'two_stars' => 75,
                'three_stars' => 90,
            ],
            'rewards' => [
                'xp_per_correct' => 5,
                'coins_per_session' => 5,
            ],
        ];
    }

    /**
     * Clear cache for fresh data
     */
    public function clearCache(): void
    {
        Cache::forget('vocabulary_quiz_config');
        Cache::forget('vocabulary_quiz_levels');
        Cache::forget('vocabulary_quiz_categories');
        Cache::forget('vocabulary_quiz_powerups');
        Cache::forget('vocabulary_quiz_achievements');
    }
}
