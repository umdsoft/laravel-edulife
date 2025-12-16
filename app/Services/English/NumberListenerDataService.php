<?php

namespace App\Services\English;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class NumberListenerDataService
{
    protected string $dataPath;
    protected int $cacheDuration = 3600; // 1 hour

    public function __construct()
    {
        $this->dataPath = base_path('data/english/games/number-listener');
    }

    /**
     * Get game configuration
     */
    public function getConfig(): array
    {
        return Cache::remember('number_listener_config', $this->cacheDuration, function () {
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
        return Cache::remember('number_listener_levels', $this->cacheDuration, function () {
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
        $previousLevel = $levelNumber - 1;

        return isset($userProgress['levels'][$previousLevel]) &&
               ($userProgress['levels'][$previousLevel]['completed'] ?? false);
    }

    /**
     * Get numbers for a specific category
     */
    public function getNumbersByCategory(string $category): array
    {
        return Cache::remember("number_listener_numbers_{$category}", $this->cacheDuration, function () use ($category) {
            $filePath = $this->dataPath . "/numbers/{$category}.json";
            if (File::exists($filePath)) {
                return json_decode(File::get($filePath), true);
            }
            return [];
        });
    }

    /**
     * Get numbers for a level
     */
    public function getNumbersForLevel(array $level): array
    {
        $category = $level['category'] ?? 'basic';

        if ($category === 'mixed') {
            return $this->getMixedNumbers($level);
        }

        $numbers = $this->getNumbersByCategory($category);

        // If level has specific range, filter numbers
        if (isset($level['number_range'])) {
            $min = $level['number_range'][0];
            $max = $level['number_range'][1];

            $numbers = array_filter($numbers, function ($num) use ($min, $max) {
                $value = $num['number'] ?? $num['amount'] ?? 0;
                return $value >= $min && $value <= $max;
            });
        }

        // Shuffle and limit to level's numbers_count
        $numbers = array_values($numbers);
        shuffle($numbers);

        $count = $level['numbers_count'] ?? 10;
        return array_slice($numbers, 0, $count);
    }

    /**
     * Get mixed numbers from all categories
     */
    protected function getMixedNumbers(array $level): array
    {
        $allNumbers = [];
        $categories = ['basic', 'tens', 'hundreds', 'time', 'money'];

        foreach ($categories as $category) {
            $numbers = $this->getNumbersByCategory($category);
            foreach ($numbers as $num) {
                $num['category'] = $category;
                $allNumbers[] = $num;
            }
        }

        shuffle($allNumbers);
        $count = $level['numbers_count'] ?? 15;
        return array_slice($allNumbers, 0, $count);
    }

    /**
     * Generate random numbers for a level
     */
    public function generateRandomNumbers(array $level): array
    {
        $numbers = [];
        $count = $level['numbers_count'] ?? 10;
        $range = $level['number_range'] ?? [1, 100];
        $step = $level['step'] ?? 1;

        for ($i = 0; $i < $count; $i++) {
            if ($step > 1) {
                $num = rand(intval($range[0] / $step), intval($range[1] / $step)) * $step;
            } else {
                $num = rand($range[0], $range[1]);
            }

            $numbers[] = [
                'number' => $num,
                'word' => $this->numberToWords($num),
            ];
        }

        return $numbers;
    }

    /**
     * Convert number to words
     */
    public function numberToWords(int $number): string
    {
        $ones = ['', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine',
                 'ten', 'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen',
                 'seventeen', 'eighteen', 'nineteen'];
        $tens = ['', '', 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety'];

        if ($number < 20) {
            return $ones[$number];
        }

        if ($number < 100) {
            $ten = intval($number / 10);
            $one = $number % 10;
            return $tens[$ten] . ($one > 0 ? '-' . $ones[$one] : '');
        }

        if ($number < 1000) {
            $hundred = intval($number / 100);
            $remainder = $number % 100;
            $result = $ones[$hundred] . ' hundred';
            if ($remainder > 0) {
                $result .= ' and ' . $this->numberToWords($remainder);
            }
            return $result;
        }

        if ($number < 10000) {
            $thousand = intval($number / 1000);
            $remainder = $number % 1000;
            $result = $ones[$thousand] . ' thousand';
            if ($remainder > 0) {
                if ($remainder < 100) {
                    $result .= ' and ' . $this->numberToWords($remainder);
                } else {
                    $result .= ' ' . $this->numberToWords($remainder);
                }
            }
            return $result;
        }

        return (string)$number;
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
     * Get number master ranks
     */
    public function getNumberMasterRanks(): array
    {
        $config = $this->getConfig();
        return $config['number_master_ranks'] ?? [];
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

        $progressPath = storage_path("app/game_progress/number_listener/user_{$userId}.json");

        if (File::exists($progressPath)) {
            return json_decode(File::get($progressPath), true);
        }

        return $this->getDefaultProgress();
    }

    /**
     * Get default progress structure
     */
    protected function getDefaultProgress(): array
    {
        return [
            'levels' => [],
            'total_xp' => 0,
            'total_coins' => 0,
            'total_numbers_correct' => 0,
            'total_numbers_attempted' => 0,
            'best_streak' => 0,
            'games_played' => 0,
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

        $progressDir = storage_path('app/game_progress/number_listener');
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

        $totalAttempted = $progress['total_numbers_attempted'] ?? 0;
        $totalCorrect = $progress['total_numbers_correct'] ?? 0;

        return [
            'games_played' => $progress['games_played'] ?? 0,
            'total_numbers_correct' => $totalCorrect,
            'total_numbers_attempted' => $totalAttempted,
            'accuracy' => $totalAttempted > 0 ? round(($totalCorrect / $totalAttempted) * 100) : 0,
            'best_streak' => $progress['best_streak'] ?? 0,
            'total_xp' => $progress['total_xp'] ?? 0,
            'total_coins' => $progress['total_coins'] ?? 0,
            'daily_streak' => $progress['daily_streak'] ?? 0,
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
        return Cache::remember('number_listener_achievements', $this->cacheDuration, function () {
            $achievementsPath = $this->dataPath . '/achievements.json';
            if (File::exists($achievementsPath)) {
                return json_decode(File::get($achievementsPath), true);
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

        // Parse conditions like "total_correct >= 100"
        if (preg_match('/(\w+)\s*>=\s*(\d+)/', $condition, $matches)) {
            $field = $matches[1];
            $target = (int)$matches[2];

            $current = 0;
            switch ($field) {
                case 'numbers_heard':
                case 'total_correct':
                    $current = $userProgress['total_numbers_correct'] ?? 0;
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
            }

            return min(100, round(($current / $target) * 100));
        }

        return 0;
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
}
