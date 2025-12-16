<?php

namespace App\Services\English;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

class AnagramSolverDataService
{
    protected string $dataPath;
    protected string $progressPath;
    protected array $config;
    protected array $levels;
    protected array $achievements;
    protected array $words;

    public function __construct()
    {
        $this->dataPath = base_path('data/english/games/anagram-solver');
        $this->progressPath = storage_path('app/game-progress/anagram-solver');

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

    public function getAnagramMasterRanks(): array
    {
        return $this->config['anagram_master_ranks'];
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
                'best_accuracy' => $levelProgress['best_accuracy'] ?? 0,
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
        return $this->words[$categoryId] ?? [];
    }

    public function getWordsForLevel(array $level): array
    {
        $allWords = [];

        foreach ($level['categories'] as $category) {
            $categoryWords = $this->getWordsByCategory($category);
            foreach ($categoryWords as $word) {
                $wordLength = strlen($word['word']);
                if ($wordLength >= $level['min_word_length'] && $wordLength <= $level['max_word_length']) {
                    $word['category'] = $category;
                    $allWords[] = $word;
                }
            }
        }

        shuffle($allWords);
        return array_slice($allWords, 0, $level['word_count']);
    }

    public function scrambleWord(string $word): string
    {
        $letters = str_split(strtoupper($word));
        $originalLetters = $letters;

        // Keep shuffling until we get a different arrangement
        $maxAttempts = 10;
        $attempts = 0;
        do {
            shuffle($letters);
            $attempts++;
        } while ($letters === $originalLetters && $attempts < $maxAttempts);

        return implode('', $letters);
    }

    public function isValidSolution(string $guess, string $answer): bool
    {
        return strtolower(trim($guess)) === strtolower(trim($answer));
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
            'words_solved' => 0,
            'perfect_levels' => 0,
            'best_streak' => 0,
            'fast_solves' => 0,
            'long_words_solved' => 0,
            'no_hint_levels' => 0,
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
                'best_accuracy' => 0,
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

        if ($summary['accuracy'] > $levelProgress['best_accuracy']) {
            $levelProgress['best_accuracy'] = $summary['accuracy'];
        }

        $progress['total_xp'] += $summary['xp_earned'];
        $progress['words_solved'] += $summary['words_solved'];

        if ($summary['accuracy'] === 100) {
            $progress['perfect_levels']++;
        }

        if ($summary['hints_used'] === 0) {
            $progress['no_hint_levels']++;
        }

        if ($summary['best_streak'] > $progress['best_streak']) {
            $progress['best_streak'] = $summary['best_streak'];
        }

        $progress['fast_solves'] += $summary['fast_solves'] ?? 0;
        $progress['long_words_solved'] += $summary['long_words_solved'] ?? 0;

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
            'words_solved' => $progress['words_solved'],
            'perfect_levels' => $progress['perfect_levels'],
            'best_streak' => $progress['best_streak'],
            'fast_solves' => $progress['fast_solves'],
            'long_words_solved' => $progress['long_words_solved'],
            'no_hint_levels' => $progress['no_hint_levels'],
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
        $ranks = $this->getAnagramMasterRanks();
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
                case 'words_solved':
                    $achieved = $progress['words_solved'] >= $req['value'];
                    break;
                case 'perfect_levels':
                    $achieved = $progress['perfect_levels'] >= $req['value'];
                    break;
                case 'best_streak':
                    $achieved = $progress['best_streak'] >= $req['value'];
                    break;
                case 'fast_solves':
                    $achieved = $progress['fast_solves'] >= $req['value'];
                    break;
                case 'long_word_solved':
                    $achieved = $progress['long_words_solved'] >= 1;
                    break;
                case 'long_words_count':
                    $achieved = $progress['long_words_solved'] >= $req['value'];
                    break;
                case 'no_hint_levels':
                    $achieved = $progress['no_hint_levels'] >= $req['value'];
                    break;
                case 'three_star_levels':
                    $count = 0;
                    foreach ($progress['levels'] as $level) {
                        if (($level['best_stars'] ?? 0) >= 3) $count++;
                    }
                    $achieved = $count >= $req['value'];
                    break;
                case 'daily_streak':
                    $achieved = $progress['daily_streak'] >= $req['value'];
                    break;
                case 'levels_completed':
                    $count = 0;
                    foreach ($progress['levels'] as $level) {
                        if ($level['completed'] ?? false) $count++;
                    }
                    $achieved = $count >= $req['value'];
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
