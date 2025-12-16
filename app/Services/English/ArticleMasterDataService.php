<?php

namespace App\Services\English;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;

class ArticleMasterDataService
{
    protected string $dataPath;
    protected string $progressPath;
    protected array $config;
    protected array $levels;
    protected array $achievements;
    protected array $questions;

    public function __construct()
    {
        $this->dataPath = base_path('data/english/games/article-master');
        $this->progressPath = storage_path('app/game-progress/article-master');

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
        $this->questions = json_decode(File::get($this->dataPath . '/content/questions.json'), true)['questions'];
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

    public function getArticles(): array
    {
        return $this->config['articles'];
    }

    public function getPowerups(): array
    {
        return $this->config['powerups'];
    }

    public function getArticleMasterRanks(): array
    {
        return $this->config['article_master_ranks'];
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

    public function getQuestionsByCategory(string $categoryId): array
    {
        return $this->questions[$categoryId] ?? [];
    }

    public function getQuestionsForLevel(array $level): array
    {
        $allQuestions = [];

        foreach ($level['categories'] as $category) {
            $categoryQuestions = $this->getQuestionsByCategory($category);
            foreach ($categoryQuestions as $question) {
                if (in_array($question['type'], $level['question_types'])) {
                    $question['category'] = $category;
                    $allQuestions[] = $question;
                }
            }
        }

        shuffle($allQuestions);
        return array_slice($allQuestions, 0, $level['question_count']);
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
            'total_correct' => 0,
            'total_questions' => 0,
            'best_streak' => 0,
            'perfect_levels' => 0,
            'no_hint_levels' => 0,
            'article_stats' => [
                'a' => ['correct' => 0, 'total' => 0],
                'an' => ['correct' => 0, 'total' => 0],
                'the' => ['correct' => 0, 'total' => 0],
                'zero' => ['correct' => 0, 'total' => 0],
            ],
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
        $progress['total_correct'] += $summary['correct_answers'];
        $progress['total_questions'] += $summary['total_questions'];

        if ($summary['accuracy'] === 100) {
            $progress['perfect_levels']++;
        }

        if ($summary['hints_used'] === 0) {
            $progress['no_hint_levels']++;
        }

        if ($summary['best_streak'] > $progress['best_streak']) {
            $progress['best_streak'] = $summary['best_streak'];
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

    public function updateArticleStats(array &$progress, string $article, bool $correct): void
    {
        if (!isset($progress['article_stats'][$article])) {
            $progress['article_stats'][$article] = ['correct' => 0, 'total' => 0];
        }

        $progress['article_stats'][$article]['total']++;
        if ($correct) {
            $progress['article_stats'][$article]['correct']++;
        }
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

        $overallAccuracy = $progress['total_questions'] > 0
            ? round(($progress['total_correct'] / $progress['total_questions']) * 100)
            : 0;

        return [
            'total_xp' => $progress['total_xp'],
            'total_correct' => $progress['total_correct'],
            'total_questions' => $progress['total_questions'],
            'overall_accuracy' => $overallAccuracy,
            'best_streak' => $progress['best_streak'],
            'perfect_levels' => $progress['perfect_levels'],
            'no_hint_levels' => $progress['no_hint_levels'],
            'completed_levels' => $completedLevels,
            'three_star_levels' => $threeStarLevels,
            'total_levels' => count($this->levels),
            'daily_streak' => $progress['daily_streak'],
            'article_stats' => $progress['article_stats'],
            'rank' => $rank,
            'achievements_count' => count($progress['achievements'] ?? []),
        ];
    }

    public function calculateRank(int $xp): array
    {
        $ranks = $this->getArticleMasterRanks();
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
                case 'correct_answers':
                    $achieved = $progress['total_correct'] >= $req['value'];
                    break;
                case 'perfect_level':
                    $achieved = $progress['perfect_levels'] >= $req['value'];
                    break;
                case 'answer_streak':
                    $achieved = $progress['best_streak'] >= $req['value'];
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
                case 'article_mastery':
                    $stats = $progress['article_stats'][$req['value']] ?? null;
                    if ($stats && $stats['total'] >= 50) {
                        $accuracy = ($stats['correct'] / $stats['total']) * 100;
                        $achieved = $accuracy >= 90;
                    }
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
