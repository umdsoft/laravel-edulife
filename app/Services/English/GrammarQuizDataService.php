<?php

namespace App\Services\English;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

class GrammarQuizDataService
{
    private string $dataPath;
    private int $cacheTtl = 3600; // 1 hour cache

    public function __construct()
    {
        $this->dataPath = base_path('data/english/games/grammar-quiz');
    }

    /**
     * Get game configuration
     */
    public function getConfig(): array
    {
        return Cache::remember('grammar_quiz_config', $this->cacheTtl, function () {
            $path = $this->dataPath . '/config.json';
            if (!file_exists($path)) {
                return [];
            }
            return json_decode(file_get_contents($path), true) ?? [];
        });
    }

    /**
     * Get all levels
     */
    public function getLevels(): array
    {
        return Cache::remember('grammar_quiz_levels', $this->cacheTtl, function () {
            $path = $this->dataPath . '/levels.json';
            if (!file_exists($path)) {
                return [];
            }
            $data = json_decode(file_get_contents($path), true);
            return $data['levels'] ?? [];
        });
    }

    /**
     * Get single level data
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
     * Get levels with user progress
     */
    public function getLevelsWithProgress($userId): array
    {
        $levels = $this->getLevels();
        $progress = $this->getUserProgress($userId);

        return array_map(function ($level) use ($progress) {
            $levelProgress = $progress['levels'][$level['level_number']] ?? null;

            return array_merge($level, [
                'completed' => $levelProgress['completed'] ?? false,
                'stars' => $levelProgress['stars'] ?? 0,
                'best_score' => $levelProgress['best_score'] ?? 0,
                'best_accuracy' => $levelProgress['best_accuracy'] ?? 0,
                'attempts' => $levelProgress['attempts'] ?? 0,
                'is_unlocked' => $this->isLevelUnlocked($level, $progress),
            ]);
        }, $levels);
    }

    /**
     * Check if level is unlocked
     */
    private function isLevelUnlocked(array $level, array $progress): bool
    {
        if (empty($level['unlock_requirement'])) {
            return true;
        }

        $req = $level['unlock_requirement'];
        $requiredLevel = $req['level'] ?? null;
        $requiredStars = $req['stars'] ?? 1;

        if ($requiredLevel === null) {
            return true;
        }

        $prevLevelProgress = $progress['levels'][$requiredLevel] ?? null;
        if (!$prevLevelProgress) {
            return false;
        }

        return ($prevLevelProgress['stars'] ?? 0) >= $requiredStars;
    }

    /**
     * Get user progress
     */
    public function getUserProgress($userId): array
    {
        $cacheKey = "grammar_quiz_progress_{$userId}";

        return Cache::remember($cacheKey, 300, function () use ($userId) {
            // In a real app, this would come from database
            // For now, return empty progress structure
            return [
                'levels' => [],
                'total_xp' => 0,
                'total_coins' => 0,
                'total_questions_answered' => 0,
                'total_correct' => 0,
                'current_streak' => 0,
                'best_streak' => 0,
                'categories_explored' => [],
                'achievements' => [],
            ];
        });
    }

    /**
     * Get questions for a level
     */
    public function getQuestionsForLevel(int $levelNumber): array
    {
        $level = $this->getLevel($levelNumber);
        if (!$level) {
            return [];
        }

        $categories = $level['categories'] ?? [];
        $quizTypes = $level['quiz_types'] ?? [];
        $questionCount = $level['questions_count'] ?? 15;

        $allQuestions = [];

        // Load questions from each category
        foreach ($categories as $category) {
            $categoryQuestions = $this->getCategoryQuestions($category);

            // Filter by quiz types and difficulty
            $filtered = array_filter($categoryQuestions, function ($q) use ($quizTypes, $level) {
                $typeMatch = empty($quizTypes) || in_array($q['type'], $quizTypes);
                $difficultyMatch = $this->matchesDifficulty($q['difficulty'], $level['difficulty']);
                return $typeMatch && $difficultyMatch;
            });

            $allQuestions = array_merge($allQuestions, $filtered);
        }

        // Shuffle and limit to required count
        shuffle($allQuestions);
        return array_slice($allQuestions, 0, $questionCount);
    }

    /**
     * Check if question difficulty matches level difficulty
     */
    private function matchesDifficulty(string $questionDifficulty, string $levelDifficulty): bool
    {
        $difficultyOrder = ['beginner', 'elementary', 'intermediate', 'upper_intermediate', 'advanced', 'proficient'];

        $levelIndex = array_search($levelDifficulty, $difficultyOrder);
        $questionIndex = array_search($questionDifficulty, $difficultyOrder);

        // Allow questions from current difficulty and one level below
        return $questionIndex <= $levelIndex && $questionIndex >= max(0, $levelIndex - 1);
    }

    /**
     * Get questions for a specific category
     */
    public function getCategoryQuestions(string $categoryId): array
    {
        $cacheKey = "grammar_quiz_questions_{$categoryId}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($categoryId) {
            $path = $this->dataPath . "/questions/{$categoryId}.json";
            if (!file_exists($path)) {
                return [];
            }
            $data = json_decode(file_get_contents($path), true);
            return $data['questions'] ?? [];
        });
    }

    /**
     * Get achievements
     */
    public function getAchievements(): array
    {
        return Cache::remember('grammar_quiz_achievements', $this->cacheTtl, function () {
            $path = $this->dataPath . '/achievements.json';
            if (!file_exists($path)) {
                return [];
            }
            $data = json_decode(file_get_contents($path), true);
            return $data['achievements'] ?? [];
        });
    }

    /**
     * Get grammar categories from config
     */
    public function getCategories(): array
    {
        $config = $this->getConfig();
        return $config['grammar_categories'] ?? [];
    }

    /**
     * Get quiz types from config
     */
    public function getQuizTypes(): array
    {
        $config = $this->getConfig();
        return $config['quiz_types'] ?? [];
    }

    /**
     * Get powerups from config
     */
    public function getPowerups(): array
    {
        $config = $this->getConfig();
        return $config['powerups'] ?? [];
    }

    /**
     * Get difficulty levels from config
     */
    public function getDifficultyLevels(): array
    {
        $config = $this->getConfig();
        return $config['difficulty_levels'] ?? [];
    }

    /**
     * Get grammarian ranks from config
     */
    public function getGrammarianRanks(): array
    {
        $config = $this->getConfig();
        return $config['grammarian_ranks'] ?? [];
    }

    /**
     * Calculate user's grammarian rank based on XP
     */
    public function calculateGrammarianRank(int $totalXp): array
    {
        $ranks = $this->getGrammarianRanks();
        $currentRank = $ranks[0];
        $nextRank = null;

        for ($i = 0; $i < count($ranks); $i++) {
            if ($totalXp >= $ranks[$i]['min_xp']) {
                $currentRank = $ranks[$i];
                $nextRank = $ranks[$i + 1] ?? null;
            } else {
                break;
            }
        }

        $progress = 0;
        if ($nextRank) {
            $currentMin = $currentRank['min_xp'];
            $nextMin = $nextRank['min_xp'];
            $progress = (($totalXp - $currentMin) / ($nextMin - $currentMin)) * 100;
        } else {
            $progress = 100; // Max rank achieved
        }

        return [
            'current' => $currentRank,
            'next' => $nextRank,
            'progress' => min(100, max(0, $progress)),
            'xp_to_next' => $nextRank ? ($nextRank['min_xp'] - $totalXp) : 0,
        ];
    }

    /**
     * Get user stats
     */
    public function getUserStats($userId): array
    {
        $progress = $this->getUserProgress($userId);
        $rankInfo = $this->calculateGrammarianRank($progress['total_xp'] ?? 0);

        $totalQuestions = $progress['total_questions_answered'] ?? 0;
        $totalCorrect = $progress['total_correct'] ?? 0;
        $accuracy = $totalQuestions > 0 ? round(($totalCorrect / $totalQuestions) * 100, 1) : 0;

        return [
            'total_xp' => $progress['total_xp'] ?? 0,
            'total_coins' => $progress['total_coins'] ?? 0,
            'total_questions' => $totalQuestions,
            'total_correct' => $totalCorrect,
            'accuracy' => $accuracy,
            'current_streak' => $progress['current_streak'] ?? 0,
            'best_streak' => $progress['best_streak'] ?? 0,
            'levels_completed' => $this->countCompletedLevels($progress),
            'categories_explored' => count($progress['categories_explored'] ?? []),
            'achievements_earned' => count($progress['achievements'] ?? []),
            'grammarian_rank' => $rankInfo,
        ];
    }

    /**
     * Count completed levels
     */
    private function countCompletedLevels(array $progress): int
    {
        $count = 0;
        foreach ($progress['levels'] ?? [] as $level) {
            if ($level['completed'] ?? false) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Get category info
     */
    public function getCategoryInfo(string $categoryId): ?array
    {
        $categories = $this->getCategories();
        foreach ($categories as $category) {
            if ($category['id'] === $categoryId) {
                $questions = $this->getCategoryQuestions($categoryId);
                return array_merge($category, [
                    'total_questions' => count($questions),
                ]);
            }
        }
        return null;
    }

    /**
     * Save user progress
     */
    public function saveUserProgress($userId, array $progress): void
    {
        $cacheKey = "grammar_quiz_progress_{$userId}";
        Cache::put($cacheKey, $progress, 3600);

        // In a real app, also save to database here
    }

    /**
     * Update level progress
     */
    public function updateLevelProgress($userId, int $levelNumber, array $results): array
    {
        $progress = $this->getUserProgress($userId);

        $currentLevel = $progress['levels'][$levelNumber] ?? [
            'completed' => false,
            'stars' => 0,
            'best_score' => 0,
            'best_accuracy' => 0,
            'attempts' => 0,
        ];

        // Update level progress
        $currentLevel['attempts']++;
        $currentLevel['completed'] = true;

        if ($results['score'] > $currentLevel['best_score']) {
            $currentLevel['best_score'] = $results['score'];
        }

        if ($results['accuracy'] > $currentLevel['best_accuracy']) {
            $currentLevel['best_accuracy'] = $results['accuracy'];
        }

        if ($results['stars'] > $currentLevel['stars']) {
            $currentLevel['stars'] = $results['stars'];
        }

        $progress['levels'][$levelNumber] = $currentLevel;

        // Update totals
        $progress['total_xp'] = ($progress['total_xp'] ?? 0) + $results['xp_earned'];
        $progress['total_coins'] = ($progress['total_coins'] ?? 0) + $results['coins_earned'];
        $progress['total_questions_answered'] = ($progress['total_questions_answered'] ?? 0) + $results['total_questions'];
        $progress['total_correct'] = ($progress['total_correct'] ?? 0) + $results['correct_answers'];

        if ($results['max_streak'] > ($progress['best_streak'] ?? 0)) {
            $progress['best_streak'] = $results['max_streak'];
        }

        // Add explored categories
        foreach ($results['categories_used'] ?? [] as $cat) {
            if (!in_array($cat, $progress['categories_explored'] ?? [])) {
                $progress['categories_explored'][] = $cat;
            }
        }

        $this->saveUserProgress($userId, $progress);

        return $progress;
    }
}
