<?php

namespace App\Services\English;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class WordSearchDataService
{
    protected string $basePath;
    protected string $progressPath;

    public function __construct()
    {
        $this->basePath = base_path('data/english/games/word-search');
        $this->progressPath = storage_path('app/game-progress/word-search');

        if (!File::exists($this->progressPath)) {
            File::makeDirectory($this->progressPath, 0755, true);
        }
    }

    public function getConfig(): array
    {
        return Cache::remember('word_search_config', 3600, function () {
            $configPath = $this->basePath . '/config.json';
            if (File::exists($configPath)) {
                return json_decode(File::get($configPath), true);
            }
            return [];
        });
    }

    public function getLevelsWithStatus(): array
    {
        $levels = $this->getLevels();
        $progress = $this->getUserProgress();

        foreach ($levels as &$level) {
            $levelProgress = $progress['levels'][$level['id']] ?? null;
            $level['completed'] = $levelProgress['completed'] ?? false;
            $level['stars'] = $levelProgress['stars'] ?? 0;
            $level['best_score'] = $levelProgress['best_score'] ?? 0;
            $level['best_time'] = $levelProgress['best_time'] ?? null;
            $level['attempts'] = $levelProgress['attempts'] ?? 0;
            $level['unlocked'] = $this->isLevelUnlocked($level['level_number'], $progress);
        }

        return $levels;
    }

    public function getLevels(): array
    {
        return Cache::remember('word_search_levels', 3600, function () {
            $levelsPath = $this->basePath . '/levels.json';
            if (File::exists($levelsPath)) {
                return json_decode(File::get($levelsPath), true);
            }
            return [];
        });
    }

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

    public function getWordsByCategories(array $categories): array
    {
        $allWords = $this->getAllWords();
        $words = [];

        foreach ($categories as $category) {
            if (isset($allWords[$category])) {
                $words = array_merge($words, $allWords[$category]);
            }
        }

        return $words;
    }

    public function getAllWords(): array
    {
        return Cache::remember('word_search_words', 3600, function () {
            $wordsPath = $this->basePath . '/content/words.json';
            if (File::exists($wordsPath)) {
                return json_decode(File::get($wordsPath), true);
            }
            return [];
        });
    }

    public function getGridSize(string $sizeId): array
    {
        $config = $this->getConfig();
        foreach ($config['grid_sizes'] ?? [] as $size) {
            if ($size['id'] === $sizeId) {
                return $size;
            }
        }
        return ['rows' => 10, 'cols' => 10, 'word_count' => 8];
    }

    public function getDirections(): array
    {
        $config = $this->getConfig();
        return $config['directions'] ?? [];
    }

    public function getGameModes(): array
    {
        $config = $this->getConfig();
        return $config['game_modes'] ?? [];
    }

    public function getPowerups(): array
    {
        $config = $this->getConfig();
        return $config['powerups'] ?? [];
    }

    public function getCategories(): array
    {
        $config = $this->getConfig();
        return $config['categories'] ?? [];
    }

    public function getWordFinderRanks(): array
    {
        $config = $this->getConfig();
        return $config['word_finder_ranks'] ?? [];
    }

    public function getScoringConfig(): array
    {
        $config = $this->getConfig();
        return $config['scoring'] ?? [];
    }

    public function isLevelUnlocked(int $levelNumber, ?array $progress = null): bool
    {
        if ($levelNumber === 1) return true;

        $progress = $progress ?? $this->getUserProgress();
        $levels = $this->getLevels();

        foreach ($levels as $level) {
            if ($level['level_number'] === $levelNumber - 1) {
                return ($progress['levels'][$level['id']]['completed'] ?? false);
            }
        }

        return false;
    }

    public function getUserProgress(): array
    {
        $userId = Auth::id() ?? 'guest';
        $progressFile = $this->progressPath . "/{$userId}.json";

        if (File::exists($progressFile)) {
            return json_decode(File::get($progressFile), true);
        }

        return [
            'levels' => [],
            'stats' => [
                'puzzles_completed' => 0,
                'words_found' => 0,
                'total_time' => 0,
                'best_streak' => 0,
                'category_words' => [],
                'total_xp' => 0,
                'total_coins' => 0,
            ],
            'achievements' => [],
        ];
    }

    public function saveUserProgress(array $progress): void
    {
        $userId = Auth::id() ?? 'guest';
        $progressFile = $this->progressPath . "/{$userId}.json";
        File::put($progressFile, json_encode($progress, JSON_PRETTY_PRINT));
    }

    public function getUserStats(): array
    {
        $progress = $this->getUserProgress();
        return $progress['stats'] ?? [];
    }

    public function getUserAchievements(): array
    {
        $allAchievements = $this->getAchievements();
        $progress = $this->getUserProgress();
        $userAchievements = $progress['achievements'] ?? [];
        $stats = $progress['stats'] ?? [];

        foreach ($allAchievements as &$achievement) {
            $achievement['unlocked'] = in_array($achievement['id'], $userAchievements);
            $achievement['progress'] = $this->calculateAchievementProgress($achievement, $stats);
        }

        return $allAchievements;
    }

    public function getAchievements(): array
    {
        return Cache::remember('word_search_achievements', 3600, function () {
            $achievementsPath = $this->basePath . '/achievements.json';
            if (File::exists($achievementsPath)) {
                return json_decode(File::get($achievementsPath), true);
            }
            return [];
        });
    }

    protected function calculateAchievementProgress(array $achievement, array $stats): int
    {
        $condition = $achievement['condition'] ?? [];
        $type = $condition['type'] ?? '';
        $target = $condition['value'] ?? 1;

        switch ($type) {
            case 'words_found':
                $current = $stats['words_found'] ?? 0;
                break;
            case 'puzzles_completed':
                $current = $stats['puzzles_completed'] ?? 0;
                break;
            case 'streak':
                $current = $stats['best_streak'] ?? 0;
                break;
            case 'level_completed':
                $current = $stats['highest_level'] ?? 0;
                break;
            default:
                return 0;
        }

        return min(100, round(($current / $target) * 100));
    }

    public function getTotalStars(): int
    {
        $progress = $this->getUserProgress();
        $totalStars = 0;
        foreach ($progress['levels'] ?? [] as $levelProgress) {
            $totalStars += $levelProgress['stars'] ?? 0;
        }
        return $totalStars;
    }

    public function updateLevelProgress(string $levelId, array $result): void
    {
        $progress = $this->getUserProgress();

        if (!isset($progress['levels'][$levelId])) {
            $progress['levels'][$levelId] = [
                'completed' => false,
                'stars' => 0,
                'best_score' => 0,
                'best_time' => null,
                'attempts' => 0,
            ];
        }

        $levelProgress = &$progress['levels'][$levelId];
        $levelProgress['attempts']++;

        if ($result['completed']) {
            $levelProgress['completed'] = true;

            if ($result['score'] > $levelProgress['best_score']) {
                $levelProgress['best_score'] = $result['score'];
            }

            if ($levelProgress['best_time'] === null || $result['time_taken'] < $levelProgress['best_time']) {
                $levelProgress['best_time'] = $result['time_taken'];
            }

            if ($result['stars'] > $levelProgress['stars']) {
                $levelProgress['stars'] = $result['stars'];
            }
        }

        $progress['stats']['puzzles_completed'] = ($progress['stats']['puzzles_completed'] ?? 0) + 1;
        $progress['stats']['words_found'] = ($progress['stats']['words_found'] ?? 0) + ($result['words_found'] ?? 0);
        $progress['stats']['total_xp'] = ($progress['stats']['total_xp'] ?? 0) + ($result['xp_earned'] ?? 0);
        $progress['stats']['total_coins'] = ($progress['stats']['total_coins'] ?? 0) + ($result['coins_earned'] ?? 0);

        if (($result['level_number'] ?? 0) > ($progress['stats']['highest_level'] ?? 0)) {
            $progress['stats']['highest_level'] = $result['level_number'];
        }

        $this->saveUserProgress($progress);
    }

    public function getCurrentRank(): ?array
    {
        $stats = $this->getUserStats();
        $ranks = $this->getWordFinderRanks();
        $currentXp = $stats['total_xp'] ?? 0;

        $currentRank = null;
        foreach ($ranks as $rank) {
            if ($currentXp >= $rank['xp_required']) {
                $currentRank = $rank;
            }
        }

        return $currentRank;
    }
}
