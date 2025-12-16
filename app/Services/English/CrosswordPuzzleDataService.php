<?php

namespace App\Services\English;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

class CrosswordPuzzleDataService
{
    protected string $dataPath;
    protected string $progressPath;
    protected array $config;
    protected array $levels;
    protected array $achievements;
    protected array $words;

    public function __construct()
    {
        $this->dataPath = base_path('data/english/games/crossword-puzzle');
        $this->progressPath = storage_path('app/game-progress/crossword-puzzle');

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

    public function getGridSizes(): array
    {
        return $this->config['grid_sizes'];
    }

    public function getPowerups(): array
    {
        return $this->config['powerups'];
    }

    public function getCrosswordMasterRanks(): array
    {
        return $this->config['crossword_master_ranks'];
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
                $word['category'] = $category;
                $allWords[] = $word;
            }
        }

        shuffle($allWords);
        return array_slice($allWords, 0, $level['word_count']);
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
            'total_puzzles' => 0,
            'total_words_found' => 0,
            'best_streak' => 0,
            'no_hint_puzzles' => 0,
            'categories_completed' => [],
            'achievements' => [],
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

        if ($summary['time_taken'] && (!$levelProgress['best_time'] || $summary['time_taken'] < $levelProgress['best_time'])) {
            $levelProgress['best_time'] = $summary['time_taken'];
        }

        $progress['total_xp'] += $summary['xp_earned'];
        $progress['total_puzzles']++;
        $progress['total_words_found'] += $summary['words_found'];

        if ($summary['hints_used'] === 0) {
            $progress['no_hint_puzzles']++;
        }

        if ($summary['best_streak'] > $progress['best_streak']) {
            $progress['best_streak'] = $summary['best_streak'];
        }

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
            'total_puzzles' => $progress['total_puzzles'],
            'total_words_found' => $progress['total_words_found'],
            'best_streak' => $progress['best_streak'],
            'no_hint_puzzles' => $progress['no_hint_puzzles'],
            'completed_levels' => $completedLevels,
            'three_star_levels' => $threeStarLevels,
            'total_levels' => count($this->levels),
            'rank' => $rank,
            'achievements_count' => count($progress['achievements'] ?? []),
        ];
    }

    public function calculateRank(int $xp): array
    {
        $ranks = $this->getCrosswordMasterRanks();
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
                case 'puzzles_completed':
                    $achieved = $progress['total_puzzles'] >= $req['value'];
                    break;
                case 'words_found':
                    $achieved = $progress['total_words_found'] >= $req['value'];
                    break;
                case 'no_hint_puzzle':
                    $achieved = $progress['no_hint_puzzles'] >= $req['value'];
                    break;
                case 'word_streak':
                    $achieved = $progress['best_streak'] >= $req['value'];
                    break;
                case 'three_star_levels':
                    $count = 0;
                    foreach ($progress['levels'] as $level) {
                        if (($level['best_stars'] ?? 0) >= 3) $count++;
                    }
                    $achieved = $count >= $req['value'];
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

    public function getCategoryStats(): array
    {
        $progress = $this->getUserProgress();
        $stats = [];

        foreach ($this->config['categories'] as $category) {
            $categoryLevels = array_filter($this->levels, function ($level) use ($category) {
                return in_array($category['id'], $level['categories']);
            });

            $completedCount = 0;
            foreach ($categoryLevels as $level) {
                if (($progress['levels'][$level['id']]['completed'] ?? false)) {
                    $completedCount++;
                }
            }

            $stats[$category['id']] = [
                'category' => $category,
                'total_levels' => count($categoryLevels),
                'completed_levels' => $completedCount,
            ];
        }

        return $stats;
    }
}
