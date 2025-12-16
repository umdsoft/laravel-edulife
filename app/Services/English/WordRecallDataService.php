<?php

namespace App\Services\English;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class WordRecallDataService
{
    protected string $dataPath;
    protected int $cacheDuration = 3600;

    public function __construct()
    {
        $this->dataPath = base_path('data/english/games/word-recall');
    }

    public function getConfig(): array
    {
        return Cache::remember('word_recall_config', $this->cacheDuration, function () {
            $path = $this->dataPath . '/config.json';
            if (File::exists($path)) {
                return json_decode(File::get($path), true);
            }
            return [];
        });
    }

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
                'unlocked' => $this->isLevelUnlocked($level['level_number']),
            ]);
        }, $levels);
    }

    public function getLevels(): array
    {
        return Cache::remember('word_recall_levels', $this->cacheDuration, function () {
            $path = $this->dataPath . '/levels.json';
            if (File::exists($path)) {
                return json_decode(File::get($path), true);
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

    public function isLevelUnlocked(int $levelNumber): bool
    {
        if ($levelNumber === 1) return true;

        $userProgress = $this->getUserProgress();
        $levels = $this->getLevels();

        $prevLevel = null;
        foreach ($levels as $level) {
            if ($level['level_number'] === $levelNumber - 1) {
                $prevLevel = $level;
                break;
            }
        }

        if (!$prevLevel) return false;

        $prevProgress = $userProgress['levels'][$prevLevel['id']] ?? null;
        if (!$prevProgress || !($prevProgress['completed'] ?? false)) {
            return false;
        }

        if ($levelNumber === 15) {
            return ($prevProgress['stars'] ?? 0) >= 2;
        }

        return true;
    }

    public function getWordsByCategory(string $category): array
    {
        return Cache::remember("word_recall_words_{$category}", $this->cacheDuration, function () use ($category) {
            $path = $this->dataPath . "/words/{$category}.json";
            if (File::exists($path)) {
                return json_decode(File::get($path), true);
            }
            return [];
        });
    }

    public function getWordsForRound(array $level, int $count): array
    {
        $category = $level['category'] ?? 'nouns';

        if ($category === 'mixed') {
            return $this->getMixedWords($count);
        }

        $words = $this->getWordsByCategory($category);
        shuffle($words);
        return array_slice($words, 0, $count);
    }

    protected function getMixedWords(int $count): array
    {
        $categories = ['nouns', 'verbs', 'adjectives', 'food', 'nature'];
        $allWords = [];

        foreach ($categories as $cat) {
            $words = $this->getWordsByCategory($cat);
            foreach ($words as $word) {
                $word['category'] = $cat;
                $allWords[] = $word;
            }
        }

        shuffle($allWords);
        return array_slice($allWords, 0, $count);
    }

    public function getGameModes(): array
    {
        $config = $this->getConfig();
        return $config['game_modes'] ?? [];
    }

    public function getCategories(): array
    {
        $config = $this->getConfig();
        return $config['categories'] ?? [];
    }

    public function getPowerups(): array
    {
        $config = $this->getConfig();
        return $config['powerups'] ?? [];
    }

    public function getMemoryMasterRanks(): array
    {
        $config = $this->getConfig();
        return $config['memory_master_ranks'] ?? [];
    }

    public function getUserProgress(): array
    {
        $userId = Auth::id();
        if (!$userId) return $this->getDefaultProgress();

        $path = storage_path("app/game_progress/word_recall/user_{$userId}.json");
        if (File::exists($path)) {
            return json_decode(File::get($path), true);
        }
        return $this->getDefaultProgress();
    }

    protected function getDefaultProgress(): array
    {
        return [
            'levels' => [],
            'total_xp' => 0,
            'total_coins' => 0,
            'total_words_correct' => 0,
            'total_rounds_completed' => 0,
            'perfect_rounds' => 0,
            'best_streak' => 0,
            'games_played' => 0,
            'max_words_round' => 0,
            'achievements' => [],
            'daily_streak' => 0,
            'last_played' => null,
        ];
    }

    public function saveUserProgress(array $progress): bool
    {
        $userId = Auth::id();
        if (!$userId) return false;

        $dir = storage_path('app/game_progress/word_recall');
        if (!File::exists($dir)) {
            File::makeDirectory($dir, 0755, true);
        }

        $path = $dir . "/user_{$userId}.json";
        return File::put($path, json_encode($progress, JSON_PRETTY_PRINT)) !== false;
    }

    public function getUserStats(): array
    {
        $progress = $this->getUserProgress();

        return [
            'games_played' => $progress['games_played'] ?? 0,
            'total_words_correct' => $progress['total_words_correct'] ?? 0,
            'total_rounds_completed' => $progress['total_rounds_completed'] ?? 0,
            'perfect_rounds' => $progress['perfect_rounds'] ?? 0,
            'best_streak' => $progress['best_streak'] ?? 0,
            'total_xp' => $progress['total_xp'] ?? 0,
            'total_coins' => $progress['total_coins'] ?? 0,
            'daily_streak' => $progress['daily_streak'] ?? 0,
            'max_words_round' => $progress['max_words_round'] ?? 0,
        ];
    }

    public function getUserAchievements(): array
    {
        $all = $this->getAllAchievements();
        $progress = $this->getUserProgress();
        $unlocked = $progress['achievements'] ?? [];

        return array_map(function ($ach) use ($unlocked, $progress) {
            return array_merge($ach, [
                'unlocked' => in_array($ach['id'], $unlocked),
                'progress' => $this->calculateAchievementProgress($ach, $progress),
            ]);
        }, $all);
    }

    public function getAllAchievements(): array
    {
        return Cache::remember('word_recall_achievements', $this->cacheDuration, function () {
            $path = $this->dataPath . '/achievements.json';
            if (File::exists($path)) {
                return json_decode(File::get($path), true);
            }
            return [];
        });
    }

    protected function calculateAchievementProgress(array $ach, array $progress): int
    {
        $condition = $ach['condition'] ?? '';

        if (preg_match('/(\w+)\s*>=\s*(\d+)/', $condition, $m)) {
            $field = $m[1];
            $target = (int)$m[2];
            $current = 0;

            switch ($field) {
                case 'rounds_completed':
                    $current = $progress['total_rounds_completed'] ?? 0;
                    break;
                case 'perfect_rounds':
                    $current = $progress['perfect_rounds'] ?? 0;
                    break;
                case 'total_words_correct':
                    $current = $progress['total_words_correct'] ?? 0;
                    break;
                case 'streak':
                    $current = $progress['best_streak'] ?? 0;
                    break;
                case 'three_star_levels':
                    $current = count(array_filter($progress['levels'] ?? [], fn($l) => ($l['stars'] ?? 0) >= 3));
                    break;
                case 'levels_completed':
                    $current = count(array_filter($progress['levels'] ?? [], fn($l) => $l['completed'] ?? false));
                    break;
                case 'max_words_round':
                    $current = $progress['max_words_round'] ?? 0;
                    break;
                case 'daily_streak':
                    $current = $progress['daily_streak'] ?? 0;
                    break;
            }

            return min(100, round(($current / $target) * 100));
        }

        return 0;
    }

    public function getTotalStars(): int
    {
        $progress = $this->getUserProgress();
        $total = 0;
        foreach ($progress['levels'] ?? [] as $lp) {
            $total += $lp['stars'] ?? 0;
        }
        return $total;
    }
}
