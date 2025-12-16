<?php

namespace App\Services\English;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class SpellingBeeDataService
{
    protected string $dataPath;
    protected int $cacheDuration = 3600;

    public function __construct()
    {
        $this->dataPath = base_path('data/english/games/spelling-bee');
    }

    /**
     * Get game configuration
     */
    public function getConfig(): array
    {
        return Cache::remember('spelling_bee_config', $this->cacheDuration, function () {
            $configPath = $this->dataPath . '/config.json';
            if (File::exists($configPath)) {
                return json_decode(File::get($configPath), true);
            }
            return [];
        });
    }

    /**
     * Get all levels with user status
     */
    public function getLevelsWithStatus(): array
    {
        $levels = $this->getLevels();
        $userProgress = $this->getUserProgress();

        return array_map(function ($level) use ($userProgress) {
            $levelProgress = $userProgress['levels'][$level['id']] ?? null;

            return array_merge($level, [
                'completed' => $levelProgress['completed'] ?? false,
                'stars' => $levelProgress['stars'] ?? 0,
                'best_score' => $levelProgress['best_score'] ?? null,
                'best_accuracy' => $levelProgress['best_accuracy'] ?? null,
                'unlocked' => $this->isLevelUnlocked($level['level_number']),
            ]);
        }, $levels);
    }

    /**
     * Get all levels
     */
    public function getLevels(): array
    {
        return Cache::remember('spelling_bee_levels', $this->cacheDuration, function () {
            $levelsPath = $this->dataPath . '/levels.json';
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

        $userProgress = $this->getUserProgress();
        $levels = $this->getLevels();

        // Find previous level
        $prevLevel = null;
        foreach ($levels as $level) {
            if ($level['level_number'] === $levelNumber - 1) {
                $prevLevel = $level;
                break;
            }
        }

        if (!$prevLevel) {
            return false;
        }

        // Check if previous level is completed
        $prevLevelProgress = $userProgress['levels'][$prevLevel['id']] ?? null;
        if (!$prevLevelProgress || !($prevLevelProgress['completed'] ?? false)) {
            return false;
        }

        // Special requirements for level 15
        if ($levelNumber === 15) {
            return ($prevLevelProgress['stars'] ?? 0) >= 2;
        }

        return true;
    }

    /**
     * Get words by category
     */
    public function getWordsByCategory(string $category): array
    {
        return Cache::remember("spelling_bee_words_{$category}", $this->cacheDuration, function () use ($category) {
            $filePath = $this->dataPath . "/words/{$category}.json";
            if (File::exists($filePath)) {
                return json_decode(File::get($filePath), true);
            }
            return [];
        });
    }

    /**
     * Get words for a level
     */
    public function getWordsForLevel(array $level): array
    {
        $category = $level['category'] ?? 'basic';
        $words = $this->getWordsByCategory($category);

        // Filter by word length
        $minLen = $level['word_length_min'] ?? 3;
        $maxLen = $level['word_length_max'] ?? 15;

        $words = array_filter($words, function ($word) use ($minLen, $maxLen) {
            $len = strlen($word['word']);
            return $len >= $minLen && $len <= $maxLen;
        });

        // Filter by special focus if specified
        if (isset($level['special_focus'])) {
            $focus = $level['special_focus'];
            $filteredWords = array_filter($words, function ($word) use ($focus) {
                switch ($focus) {
                    case 'double_letters':
                        return $word['has_double'] ?? false;
                    case 'silent_letters':
                        return $word['silent_letter'] ?? false;
                    case 'vowel_combinations':
                        return $word['vowel_combination'] ?? false;
                    case 'affixes':
                        return $word['has_affix'] ?? false;
                    case 'irregular_patterns':
                        return $word['irregular'] ?? false;
                    default:
                        return true;
                }
            });

            // If enough filtered words, use them; otherwise mix with regular
            if (count($filteredWords) >= $level['words_count']) {
                $words = $filteredWords;
            }
        }

        $words = array_values($words);
        shuffle($words);

        return array_slice($words, 0, $level['words_count'] ?? 10);
    }

    /**
     * Get mixed words for final level
     */
    public function getMixedWords(int $count): array
    {
        $categories = ['basic', 'common', 'intermediate', 'advanced', 'challenging', 'expert'];
        $allWords = [];

        foreach ($categories as $category) {
            $words = $this->getWordsByCategory($category);
            foreach ($words as $word) {
                $word['source_category'] = $category;
                $allWords[] = $word;
            }
        }

        shuffle($allWords);
        return array_slice($allWords, 0, $count);
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
     * Get spelling master ranks
     */
    public function getSpellingMasterRanks(): array
    {
        $config = $this->getConfig();
        return $config['spelling_master_ranks'] ?? [];
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

        $progressPath = storage_path("app/game_progress/spelling_bee/user_{$userId}.json");

        if (File::exists($progressPath)) {
            return json_decode(File::get($progressPath), true);
        }

        return $this->getDefaultProgress();
    }

    /**
     * Get default progress
     */
    protected function getDefaultProgress(): array
    {
        return [
            'levels' => [],
            'total_xp' => 0,
            'total_coins' => 0,
            'total_words_correct' => 0,
            'total_words_attempted' => 0,
            'best_streak' => 0,
            'games_played' => 0,
            'longest_word_spelled' => 0,
            'category_stats' => [],
            'achievements' => [],
            'daily_streak' => 0,
            'last_played' => null,
        ];
    }

    /**
     * Save user progress
     */
    public function saveUserProgress(array $progress): bool
    {
        $userId = Auth::id();
        if (!$userId) {
            return false;
        }

        $progressDir = storage_path('app/game_progress/spelling_bee');
        if (!File::exists($progressDir)) {
            File::makeDirectory($progressDir, 0755, true);
        }

        $progressPath = $progressDir . "/user_{$userId}.json";
        return File::put($progressPath, json_encode($progress, JSON_PRETTY_PRINT)) !== false;
    }

    /**
     * Get user statistics
     */
    public function getUserStats(): array
    {
        $progress = $this->getUserProgress();

        $totalAttempted = $progress['total_words_attempted'] ?? 0;
        $totalCorrect = $progress['total_words_correct'] ?? 0;

        return [
            'games_played' => $progress['games_played'] ?? 0,
            'total_words_correct' => $totalCorrect,
            'total_words_attempted' => $totalAttempted,
            'accuracy' => $totalAttempted > 0 ? round(($totalCorrect / $totalAttempted) * 100) : 0,
            'best_streak' => $progress['best_streak'] ?? 0,
            'total_xp' => $progress['total_xp'] ?? 0,
            'total_coins' => $progress['total_coins'] ?? 0,
            'daily_streak' => $progress['daily_streak'] ?? 0,
            'longest_word_spelled' => $progress['longest_word_spelled'] ?? 0,
            'category_stats' => $progress['category_stats'] ?? [],
        ];
    }

    /**
     * Get user achievements
     */
    public function getUserAchievements(): array
    {
        $allAchievements = $this->getAllAchievements();
        $userProgress = $this->getUserProgress();
        $unlockedIds = $userProgress['achievements'] ?? [];

        return array_map(function ($achievement) use ($unlockedIds, $userProgress) {
            $unlocked = in_array($achievement['id'], $unlockedIds);
            return array_merge($achievement, [
                'unlocked' => $unlocked,
                'progress' => $this->calculateAchievementProgress($achievement, $userProgress),
            ]);
        }, $allAchievements);
    }

    /**
     * Get all achievements
     */
    public function getAllAchievements(): array
    {
        return Cache::remember('spelling_bee_achievements', $this->cacheDuration, function () {
            $path = $this->dataPath . '/achievements.json';
            if (File::exists($path)) {
                return json_decode(File::get($path), true);
            }
            return [];
        });
    }

    /**
     * Calculate achievement progress
     */
    protected function calculateAchievementProgress(array $achievement, array $userProgress): int
    {
        $condition = $achievement['condition'] ?? '';

        if (preg_match('/(\w+)\s*>=\s*(\d+)/', $condition, $matches)) {
            $field = $matches[1];
            $target = (int)$matches[2];

            $current = 0;
            switch ($field) {
                case 'words_spelled':
                case 'total_correct':
                    $current = $userProgress['total_words_correct'] ?? 0;
                    break;
                case 'streak':
                    $current = $userProgress['best_streak'] ?? 0;
                    break;
                case 'levels_completed':
                    $current = count(array_filter($userProgress['levels'] ?? [], fn($l) => $l['completed'] ?? false));
                    break;
                case 'three_star_levels':
                    $current = count(array_filter($userProgress['levels'] ?? [], fn($l) => ($l['stars'] ?? 0) >= 3));
                    break;
                case 'daily_streak':
                    $current = $userProgress['daily_streak'] ?? 0;
                    break;
                case 'perfect_levels':
                    $current = count(array_filter($userProgress['levels'] ?? [], fn($l) => ($l['best_accuracy'] ?? 0) >= 100));
                    break;
                case 'longest_word':
                    $current = $userProgress['longest_word_spelled'] ?? 0;
                    break;
            }

            return min(100, round(($current / $target) * 100));
        }

        return 0;
    }

    /**
     * Get total stars
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
}
