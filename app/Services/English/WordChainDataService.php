<?php

namespace App\Services\English;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

class WordChainDataService
{
    protected string $dataPath;
    protected string $progressPath;
    protected array $config;
    protected array $levels;
    protected array $achievements;
    protected array $words;

    public function __construct()
    {
        $this->dataPath = base_path('data/english/games/word-chain');
        $this->progressPath = storage_path('app/game-progress/word-chain');

        if (!File::exists($this->progressPath)) {
            File::makeDirectory($this->progressPath, 0755, true);
        }

        $this->loadData();
    }

    protected function loadData(): void
    {
        $this->config = json_decode(File::get($this->dataPath . '/config.json'), true);
        $this->levels = json_decode(File::get($this->dataPath . '/levels.json'), true)['levels'];
        $this->achievements = json_decode(File::get($this->dataPath . '/achievements.json'), true)['achievements'];
        $this->words = json_decode(File::get($this->dataPath . '/content/words.json'), true)['words'];
    }

    public function getConfig(): array
    {
        return $this->config;
    }

    public function getScoringConfig(): array
    {
        return $this->config['scoring'];
    }

    public function getCategories(): array
    {
        return $this->config['categories'];
    }

    public function getGameModes(): array
    {
        return $this->config['game_modes'];
    }

    public function getPowerups(): array
    {
        return $this->config['powerups'];
    }

    public function getChainMasterRanks(): array
    {
        return $this->config['chain_master_ranks'];
    }

    public function getLevels(): array
    {
        return $this->levels;
    }

    public function getLevel(int $levelNumber): ?array
    {
        foreach ($this->levels as $level) {
            if ($level['level_number'] === $levelNumber) {
                return $level;
            }
        }
        return null;
    }

    public function getLevelsWithStatus(): array
    {
        $progress = $this->getUserProgress();
        $levelsWithStatus = [];

        foreach ($this->levels as $level) {
            $levelProgress = $progress['levels'][$level['id']] ?? null;
            $isUnlocked = $this->isLevelUnlocked($level['level_number']);

            $levelsWithStatus[] = array_merge($level, [
                'unlocked' => $isUnlocked,
                'completed' => $levelProgress['completed'] ?? false,
                'best_score' => $levelProgress['best_score'] ?? 0,
                'best_stars' => $levelProgress['best_stars'] ?? 0,
                'best_chain_length' => $levelProgress['best_chain_length'] ?? 0,
                'attempts' => $levelProgress['attempts'] ?? 0,
            ]);
        }

        return $levelsWithStatus;
    }

    public function isLevelUnlocked(int $levelNumber): bool
    {
        $level = $this->getLevel($levelNumber);
        if (!$level) return false;

        if ($level['unlock_requirement'] === null) return true;

        $progress = $this->getUserProgress();
        $previousLevel = $this->getLevel($level['unlock_requirement']);

        if ($previousLevel) {
            $previousProgress = $progress['levels'][$previousLevel['id']] ?? null;
            return ($previousProgress['completed'] ?? false) === true;
        }

        return false;
    }

    public function getWordsByCategory(string $categoryId): array
    {
        if ($categoryId === 'mixed') {
            $allWords = [];
            foreach ($this->words as $category => $words) {
                if ($category !== 'mixed') {
                    $allWords = array_merge($allWords, $words);
                }
            }
            return $allWords;
        }
        return $this->words[$categoryId] ?? [];
    }

    public function getWordsForLevel(array $level): array
    {
        $allWords = [];
        foreach ($level['categories'] as $category) {
            $categoryWords = $this->getWordsByCategory($category);
            $allWords = array_merge($allWords, $categoryWords);
        }
        return $allWords;
    }

    public function getWordsByStartingLetter(array $words, string $letter): array
    {
        $letter = strtoupper($letter);
        return array_filter($words, function ($word) use ($letter) {
            return strtoupper($word['word'][0]) === $letter;
        });
    }

    public function isValidWord(string $word, array $availableWords): bool
    {
        $word = strtolower(trim($word));
        foreach ($availableWords as $availableWord) {
            if (strtolower($availableWord['word']) === $word) {
                return true;
            }
        }
        return false;
    }

    public function getWordTranslation(string $word, array $availableWords): ?string
    {
        $word = strtolower(trim($word));
        foreach ($availableWords as $availableWord) {
            if (strtolower($availableWord['word']) === $word) {
                return $availableWord['translation'];
            }
        }
        return null;
    }

    public function getUserProgress(): array
    {
        $userId = Auth::id() ?? 'guest';
        $progressFile = $this->progressPath . "/{$userId}.json";

        if (File::exists($progressFile)) {
            return json_decode(File::get($progressFile), true);
        }

        return [
            'user_id' => $userId,
            'levels' => [],
            'total_xp' => 0,
            'total_chains' => 0,
            'total_words_used' => 0,
            'unique_words' => [],
            'longest_chain' => 0,
            'long_words_used' => 0,
            'no_hint_levels' => 0,
            'categories_completed' => [],
            'achievements' => [],
            'daily_streak' => 0,
            'last_played' => null,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String(),
        ];
    }

    public function saveUserProgress(array $progress): void
    {
        $userId = Auth::id() ?? 'guest';
        $progressFile = $this->progressPath . "/{$userId}.json";
        $progress['updated_at'] = now()->toIso8601String();
        File::put($progressFile, json_encode($progress, JSON_PRETTY_PRINT));
    }

    public function updateLevelProgress(string $levelId, array $summary): void
    {
        $progress = $this->getUserProgress();

        if (!isset($progress['levels'][$levelId])) {
            $progress['levels'][$levelId] = [
                'completed' => false,
                'best_score' => 0,
                'best_stars' => 0,
                'best_chain_length' => 0,
                'attempts' => 0,
                'best_time' => null,
            ];
        }

        $levelProgress = &$progress['levels'][$levelId];
        $levelProgress['attempts']++;
        $levelProgress['completed'] = true;

        if ($summary['score'] > $levelProgress['best_score']) {
            $levelProgress['best_score'] = $summary['score'];
        }

        if ($summary['stars'] > $levelProgress['best_stars']) {
            $levelProgress['best_stars'] = $summary['stars'];
        }

        if ($summary['chain_length'] > $levelProgress['best_chain_length']) {
            $levelProgress['best_chain_length'] = $summary['chain_length'];
        }

        $progress['total_xp'] += $summary['xp_earned'];
        $progress['total_chains']++;
        $progress['total_words_used'] += $summary['chain_length'];

        if ($summary['chain_length'] > $progress['longest_chain']) {
            $progress['longest_chain'] = $summary['chain_length'];
        }

        if ($summary['hints_used'] === 0) {
            $progress['no_hint_levels']++;
        }

        // Track unique words
        foreach ($summary['words_used'] ?? [] as $word) {
            if (!in_array(strtolower($word), $progress['unique_words'])) {
                $progress['unique_words'][] = strtolower($word);
            }
            if (strlen($word) >= 7) {
                $progress['long_words_used']++;
            }
        }

        // Update daily streak
        $today = now()->format('Y-m-d');
        $lastPlayed = $progress['last_played'];

        if ($lastPlayed) {
            $yesterday = now()->subDay()->format('Y-m-d');
            if ($lastPlayed === $yesterday) {
                $progress['daily_streak']++;
            } elseif ($lastPlayed !== $today) {
                $progress['daily_streak'] = 1;
            }
        } else {
            $progress['daily_streak'] = 1;
        }
        $progress['last_played'] = $today;

        $this->saveUserProgress($progress);
        $this->checkAchievements($progress);
    }

    public function getUserStats(): array
    {
        $progress = $this->getUserProgress();
        $rank = $this->calculateRank($progress['total_xp']);

        $completedLevels = 0;
        $threeStarLevels = 0;
        foreach ($progress['levels'] as $level) {
            if ($level['completed'] ?? false) {
                $completedLevels++;
            }
            if (($level['best_stars'] ?? 0) >= 3) {
                $threeStarLevels++;
            }
        }

        return [
            'total_xp' => $progress['total_xp'],
            'total_chains' => $progress['total_chains'],
            'total_words_used' => $progress['total_words_used'],
            'unique_words_count' => count($progress['unique_words']),
            'longest_chain' => $progress['longest_chain'],
            'long_words_used' => $progress['long_words_used'],
            'completed_levels' => $completedLevels,
            'three_star_levels' => $threeStarLevels,
            'total_levels' => count($this->levels),
            'daily_streak' => $progress['daily_streak'],
            'rank' => $rank,
            'achievements_count' => count($progress['achievements'] ?? []),
        ];
    }

    public function calculateRank(int $xp): array
    {
        $ranks = $this->getChainMasterRanks();
        $currentRank = $ranks[0];
        $nextRank = null;

        for ($i = count($ranks) - 1; $i >= 0; $i--) {
            if ($xp >= $ranks[$i]['min_xp']) {
                $currentRank = $ranks[$i];
                $nextRank = $ranks[$i + 1] ?? null;
                break;
            }
        }

        return [
            'current' => $currentRank,
            'next' => $nextRank,
            'progress' => $nextRank ? (($xp - $currentRank['min_xp']) / ($nextRank['min_xp'] - $currentRank['min_xp'])) * 100 : 100,
        ];
    }

    public function getAchievements(): array
    {
        return $this->achievements;
    }

    public function getUserAchievements(): array
    {
        $progress = $this->getUserProgress();
        $userAchievements = $progress['achievements'] ?? [];

        return array_map(function ($achievement) use ($userAchievements) {
            $achieved = in_array($achievement['id'], $userAchievements);
            return array_merge($achievement, ['achieved' => $achieved]);
        }, $this->achievements);
    }

    protected function checkAchievements(array $progress): void
    {
        $newAchievements = [];

        foreach ($this->achievements as $achievement) {
            if (in_array($achievement['id'], $progress['achievements'] ?? [])) {
                continue;
            }

            $achieved = false;
            $req = $achievement['requirement'];

            switch ($req['type']) {
                case 'chains_completed':
                    $achieved = $progress['total_chains'] >= $req['value'];
                    break;
                case 'chain_length':
                    $achieved = $progress['longest_chain'] >= $req['value'];
                    break;
                case 'unique_words':
                    $achieved = count($progress['unique_words']) >= $req['value'];
                    break;
                case 'long_words_used':
                    $achieved = $progress['long_words_used'] >= $req['value'];
                    break;
                case 'three_star_levels':
                    $count = 0;
                    foreach ($progress['levels'] as $level) {
                        if (($level['best_stars'] ?? 0) >= 3) $count++;
                    }
                    $achieved = $count >= $req['value'];
                    break;
                case 'no_hint_levels':
                    $achieved = $progress['no_hint_levels'] >= $req['value'];
                    break;
                case 'daily_streak':
                    $achieved = $progress['daily_streak'] >= $req['value'];
                    break;
                case 'categories_completed':
                    $achieved = count($progress['categories_completed'] ?? []) >= $req['value'];
                    break;
                case 'rank':
                    $rank = $this->calculateRank($progress['total_xp']);
                    $achieved = $rank['current']['id'] === $req['value'];
                    break;
            }

            if ($achieved) {
                $newAchievements[] = $achievement['id'];
            }
        }

        if (!empty($newAchievements)) {
            $progress['achievements'] = array_merge($progress['achievements'] ?? [], $newAchievements);
            $this->saveUserProgress($progress);
        }
    }

    public function getTotalStars(): int
    {
        $progress = $this->getUserProgress();
        $total = 0;

        foreach ($progress['levels'] as $level) {
            $total += $level['best_stars'] ?? 0;
        }

        return $total;
    }
}
