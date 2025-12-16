<?php

namespace App\Services\English;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;

class RapidFireDataService
{
    protected string $dataPath;
    protected int $cacheTTL = 3600; // 1 hour

    public function __construct()
    {
        $this->dataPath = base_path('data/english/games/rapid-fire');
    }

    /**
     * Get game configuration
     */
    public function getConfig(): array
    {
        return Cache::remember('rapid_fire_config', $this->cacheTTL, function () {
            $path = $this->dataPath . '/config.json';
            if (File::exists($path)) {
                return json_decode(File::get($path), true);
            }
            return [];
        });
    }

    /**
     * Get all levels
     */
    public function getLevels(): array
    {
        return Cache::remember('rapid_fire_levels', $this->cacheTTL, function () {
            $path = $this->dataPath . '/levels.json';
            if (File::exists($path)) {
                $data = json_decode(File::get($path), true);
                return $data['levels'] ?? [];
            }
            return [];
        });
    }

    /**
     * Get a specific level by number
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
     * Get all achievements
     */
    public function getAchievements(): array
    {
        return Cache::remember('rapid_fire_achievements', $this->cacheTTL, function () {
            $path = $this->dataPath . '/achievements.json';
            if (File::exists($path)) {
                $data = json_decode(File::get($path), true);
                return $data['achievements'] ?? [];
            }
            return [];
        });
    }

    /**
     * Get questions for a specific category
     */
    public function getQuestionsByCategory(string $category): array
    {
        return Cache::remember("rapid_fire_questions_{$category}", $this->cacheTTL, function () use ($category) {
            $path = $this->dataPath . "/questions/{$category}.json";
            if (File::exists($path)) {
                $data = json_decode(File::get($path), true);
                return $data['questions'] ?? [];
            }
            return [];
        });
    }

    /**
     * Get questions for multiple categories
     */
    public function getQuestionsByCategories(array $categories): array
    {
        $questions = [];
        foreach ($categories as $category) {
            $categoryQuestions = $this->getQuestionsByCategory($category);
            foreach ($categoryQuestions as $question) {
                $question['category'] = $category;
                $questions[] = $question;
            }
        }
        return $questions;
    }

    /**
     * Get questions filtered by difficulty
     */
    public function getQuestionsByDifficulty(array $questions, string $difficulty): array
    {
        return array_filter($questions, function ($q) use ($difficulty) {
            return $q['difficulty'] === $difficulty;
        });
    }

    /**
     * Get questions filtered by CEFR level
     */
    public function getQuestionsByCefrLevel(array $questions, string $cefrLevel): array
    {
        $cefrLevels = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];
        $maxIndex = array_search($cefrLevel, $cefrLevels);

        return array_filter($questions, function ($q) use ($cefrLevels, $maxIndex) {
            $qIndex = array_search($q['cefr_level'], $cefrLevels);
            return $qIndex !== false && $qIndex <= $maxIndex;
        });
    }

    /**
     * Get questions for a specific level
     */
    public function getQuestionsForLevel(int $levelNumber): array
    {
        $level = $this->getLevel($levelNumber);
        if (!$level) {
            return [];
        }

        $questions = $this->getQuestionsByCategories($level['categories']);
        $questions = $this->getQuestionsByCefrLevel($questions, $level['cefr_level']);

        // Shuffle and limit questions
        shuffle($questions);

        return $questions;
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
     * Get a specific game mode
     */
    public function getGameMode(string $modeId): ?array
    {
        $modes = $this->getGameModes();
        foreach ($modes as $mode) {
            if ($mode['id'] === $modeId) {
                return $mode;
            }
        }
        return null;
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
     * Get speed ranks
     */
    public function getSpeedRanks(): array
    {
        $config = $this->getConfig();
        return $config['speed_ranks'] ?? [];
    }

    /**
     * Get scoring configuration
     */
    public function getScoringConfig(): array
    {
        $config = $this->getConfig();
        return $config['scoring'] ?? [];
    }

    /**
     * Get rewards configuration
     */
    public function getRewardsConfig(): array
    {
        $config = $this->getConfig();
        return $config['rewards'] ?? [];
    }

    /**
     * Get user progress from session
     */
    public function getUserProgress(): array
    {
        $userId = auth()->id() ?? 'guest';
        return session("rapid_fire_progress_{$userId}", [
            'levels_completed' => [],
            'total_xp' => 0,
            'total_coins' => 0,
            'total_questions' => 0,
            'total_correct' => 0,
            'best_streak' => 0,
            'best_qpm' => 0, // Questions per minute
            'achievements' => [],
            'daily_streak' => 0,
            'last_played' => null,
            'games_played' => 0,
            'category_stats' => [],
        ]);
    }

    /**
     * Save user progress to session
     */
    public function saveUserProgress(array $progress): void
    {
        $userId = auth()->id() ?? 'guest';
        session(["rapid_fire_progress_{$userId}" => $progress]);
    }

    /**
     * Get level completion data for a user
     */
    public function getLevelCompletion(int $levelNumber): ?array
    {
        $progress = $this->getUserProgress();
        return $progress['levels_completed'][$levelNumber] ?? null;
    }

    /**
     * Check if a level is unlocked
     */
    public function isLevelUnlocked(int $levelNumber): bool
    {
        if ($levelNumber === 1) {
            return true;
        }

        $level = $this->getLevel($levelNumber);
        if (!$level || !isset($level['unlock_requirement'])) {
            return false;
        }

        $requirement = $level['unlock_requirement'];
        $prevLevelCompletion = $this->getLevelCompletion($requirement['level']);

        if (!$prevLevelCompletion) {
            return false;
        }

        return ($prevLevelCompletion['stars'] ?? 0) >= $requirement['stars'];
    }

    /**
     * Get levels with unlock status
     */
    public function getLevelsWithStatus(): array
    {
        $levels = $this->getLevels();
        $result = [];

        foreach ($levels as $level) {
            $completion = $this->getLevelCompletion($level['level_number']);
            $level['is_unlocked'] = $this->isLevelUnlocked($level['level_number']);
            $level['stars'] = $completion['stars'] ?? 0;
            $level['best_score'] = $completion['best_score'] ?? 0;
            $level['best_streak'] = $completion['best_streak'] ?? 0;
            $level['times_played'] = $completion['times_played'] ?? 0;
            $result[] = $level;
        }

        return $result;
    }

    /**
     * Get user statistics
     */
    public function getUserStats(): array
    {
        $progress = $this->getUserProgress();
        $speedRanks = $this->getSpeedRanks();

        // Calculate current speed rank
        $currentRank = null;
        $nextRank = null;
        $qpm = $progress['best_qpm'] ?? 0;

        foreach ($speedRanks as $index => $rank) {
            if ($qpm >= $rank['min_qpm']) {
                $currentRank = $rank;
                $nextRank = $speedRanks[$index + 1] ?? null;
            }
        }

        // Calculate accuracy
        $accuracy = 0;
        if ($progress['total_questions'] > 0) {
            $accuracy = round(($progress['total_correct'] / $progress['total_questions']) * 100);
        }

        // Count completed levels
        $levelsCompleted = count(array_filter($progress['levels_completed'], function ($l) {
            return ($l['stars'] ?? 0) > 0;
        }));

        return [
            'total_xp' => $progress['total_xp'] ?? 0,
            'total_coins' => $progress['total_coins'] ?? 0,
            'total_questions' => $progress['total_questions'] ?? 0,
            'total_correct' => $progress['total_correct'] ?? 0,
            'accuracy' => $accuracy,
            'best_streak' => $progress['best_streak'] ?? 0,
            'best_qpm' => $progress['best_qpm'] ?? 0,
            'games_played' => $progress['games_played'] ?? 0,
            'levels_completed' => $levelsCompleted,
            'daily_streak' => $progress['daily_streak'] ?? 0,
            'speed_rank' => [
                'current' => $currentRank,
                'next' => $nextRank,
                'qpm_to_next' => $nextRank ? ($nextRank['min_qpm'] - $qpm) : 0,
            ],
            'category_stats' => $progress['category_stats'] ?? [],
            'achievements_count' => count($progress['achievements'] ?? []),
        ];
    }

    /**
     * Get total stars earned
     */
    public function getTotalStars(): int
    {
        $progress = $this->getUserProgress();
        $total = 0;

        foreach ($progress['levels_completed'] as $level) {
            $total += $level['stars'] ?? 0;
        }

        return $total;
    }

    /**
     * Check and award achievements
     */
    public function checkAchievements(array $sessionData): array
    {
        $progress = $this->getUserProgress();
        $achievements = $this->getAchievements();
        $newAchievements = [];

        foreach ($achievements as $achievement) {
            // Skip if already earned
            if (in_array($achievement['id'], $progress['achievements'])) {
                continue;
            }

            $earned = false;
            $condition = $achievement['condition'];

            switch ($condition['type']) {
                case 'games_completed':
                    $earned = ($progress['games_played'] ?? 0) >= $condition['value'];
                    break;

                case 'correct_in_round':
                    $earned = ($sessionData['correct'] ?? 0) >= $condition['value'];
                    break;

                case 'answer_time':
                    $earned = ($sessionData['fastest_answer'] ?? 999) <= $condition['value'];
                    break;

                case 'streak':
                    $earned = max($progress['best_streak'] ?? 0, $sessionData['best_streak'] ?? 0) >= $condition['value'];
                    break;

                case 'qpm':
                    $earned = max($progress['best_qpm'] ?? 0, $sessionData['qpm'] ?? 0) >= $condition['value'];
                    break;

                case 'category_correct':
                    $categoryStats = $progress['category_stats'][$condition['category']] ?? [];
                    $earned = ($categoryStats['correct'] ?? 0) >= $condition['value'];
                    break;

                case 'round_accuracy':
                    if (($sessionData['total'] ?? 0) > 0) {
                        $accuracy = (($sessionData['correct'] ?? 0) / $sessionData['total']) * 100;
                        $earned = $accuracy >= $condition['value'];
                    }
                    break;

                case 'survival_questions':
                    if (($sessionData['mode'] ?? '') === 'survival') {
                        $earned = ($sessionData['total'] ?? 0) >= $condition['value'];
                    }
                    break;

                case 'blitz_score':
                    if (($sessionData['mode'] ?? '') === 'blitz') {
                        $earned = ($sessionData['score'] ?? 0) >= $condition['value'];
                    }
                    break;

                case 'marathon_complete':
                    if (($sessionData['mode'] ?? '') === 'marathon') {
                        $earned = ($sessionData['completed'] ?? false);
                    }
                    break;

                case 'daily_streak':
                    $earned = ($progress['daily_streak'] ?? 0) >= $condition['value'];
                    break;

                case 'total_questions':
                    $earned = ($progress['total_questions'] ?? 0) >= $condition['value'];
                    break;

                case 'all_levels_3_stars':
                    $levels = $this->getLevels();
                    $all3Stars = true;
                    foreach ($levels as $level) {
                        $completion = $progress['levels_completed'][$level['level_number']] ?? null;
                        if (!$completion || ($completion['stars'] ?? 0) < 3) {
                            $all3Stars = false;
                            break;
                        }
                    }
                    $earned = $all3Stars;
                    break;

                case 'total_xp':
                    $earned = ($progress['total_xp'] ?? 0) >= $condition['value'];
                    break;

                case 'speed_rank':
                    $stats = $this->getUserStats();
                    $earned = ($stats['speed_rank']['current']['id'] ?? '') === $condition['value'];
                    break;
            }

            if ($earned) {
                $newAchievements[] = $achievement;
                $progress['achievements'][] = $achievement['id'];
            }
        }

        if (!empty($newAchievements)) {
            $this->saveUserProgress($progress);
        }

        return $newAchievements;
    }

    /**
     * Get user's earned achievements
     */
    public function getUserAchievements(): array
    {
        $progress = $this->getUserProgress();
        $allAchievements = $this->getAchievements();
        $earnedIds = $progress['achievements'] ?? [];

        $earned = [];
        $locked = [];

        foreach ($allAchievements as $achievement) {
            if (in_array($achievement['id'], $earnedIds)) {
                $achievement['earned'] = true;
                $earned[] = $achievement;
            } else {
                $achievement['earned'] = false;
                $locked[] = $achievement;
            }
        }

        return [
            'earned' => $earned,
            'locked' => $locked,
            'total_earned' => count($earned),
            'total' => count($allAchievements),
        ];
    }

    /**
     * Clear cache
     */
    public function clearCache(): void
    {
        Cache::forget('rapid_fire_config');
        Cache::forget('rapid_fire_levels');
        Cache::forget('rapid_fire_achievements');

        $categories = ['vocabulary', 'grammar', 'spelling', 'idioms', 'collocations', 'phrasal_verbs', 'antonyms', 'synonyms'];
        foreach ($categories as $category) {
            Cache::forget("rapid_fire_questions_{$category}");
        }
    }
}
