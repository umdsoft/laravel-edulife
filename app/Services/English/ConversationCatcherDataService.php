<?php

namespace App\Services\English;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class ConversationCatcherDataService
{
    protected string $basePath;
    protected string $progressPath;

    public function __construct()
    {
        $this->basePath = base_path('data/english/games/conversation-catcher');
        $this->progressPath = storage_path('app/game-progress/conversation-catcher');

        if (!File::exists($this->progressPath)) {
            File::makeDirectory($this->progressPath, 0755, true);
        }
    }

    /**
     * Get game configuration
     */
    public function getConfig(): array
    {
        return Cache::remember('conversation_catcher_config', 3600, function () {
            $configPath = $this->basePath . '/config.json';
            if (File::exists($configPath)) {
                return json_decode(File::get($configPath), true);
            }
            return [];
        });
    }

    /**
     * Get all levels with user progress status
     */
    public function getLevelsWithStatus(): array
    {
        $levels = $this->getLevels();
        $progress = $this->getUserProgress();

        foreach ($levels as &$level) {
            $levelProgress = $progress['levels'][$level['id']] ?? null;

            $level['completed'] = $levelProgress['completed'] ?? false;
            $level['stars'] = $levelProgress['stars'] ?? 0;
            $level['best_score'] = $levelProgress['best_score'] ?? 0;
            $level['best_accuracy'] = $levelProgress['best_accuracy'] ?? 0;
            $level['attempts'] = $levelProgress['attempts'] ?? 0;
            $level['unlocked'] = $this->isLevelUnlocked($level['level_number'], $progress);
        }

        return $levels;
    }

    /**
     * Get all levels
     */
    public function getLevels(): array
    {
        return Cache::remember('conversation_catcher_levels', 3600, function () {
            $levelsPath = $this->basePath . '/levels.json';
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
                $level['dialogues'] = $this->getDialoguesForLevel($level);
                return $level;
            }
        }

        return null;
    }

    /**
     * Get dialogues for a level based on its categories
     */
    public function getDialoguesForLevel(array $level): array
    {
        $dialogues = [];
        $categories = $level['categories'] ?? ['greetings'];

        foreach ($categories as $category) {
            $categoryDialogues = $this->getDialoguesByCategory($category);
            $dialogues = array_merge($dialogues, $categoryDialogues);
        }

        shuffle($dialogues);

        $dialogueCount = $level['dialogues_count'] ?? 10;
        return array_slice($dialogues, 0, $dialogueCount);
    }

    /**
     * Get dialogues by category
     */
    public function getDialoguesByCategory(string $category): array
    {
        $cacheKey = "conversation_catcher_dialogues_{$category}";

        return Cache::remember($cacheKey, 3600, function () use ($category) {
            $contentPath = $this->basePath . "/content/{$category}.json";
            if (File::exists($contentPath)) {
                $dialogues = json_decode(File::get($contentPath), true);
                foreach ($dialogues as &$dialogue) {
                    $dialogue['category'] = $category;
                }
                return $dialogues;
            }
            return [];
        });
    }

    /**
     * Get all categories
     */
    public function getCategories(): array
    {
        $config = $this->getConfig();
        $categories = $config['categories'] ?? [];

        array_unshift($categories, [
            'id' => 'all',
            'name' => 'All Categories',
            'name_uz' => 'Barcha kategoriyalar',
            'icon' => '📚',
            'color' => 'from-gray-500 to-gray-600'
        ]);

        return $categories;
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
     * Get powerups
     */
    public function getPowerups(): array
    {
        $config = $this->getConfig();
        return $config['powerups'] ?? [];
    }

    /**
     * Get conversation master ranks
     */
    public function getConversationMasterRanks(): array
    {
        $config = $this->getConfig();
        return $config['conversation_master_ranks'] ?? [];
    }

    /**
     * Get scoring configuration
     */
    public function getScoringConfig(): array
    {
        $config = $this->getConfig();
        return $config['scoring'] ?? [
            'base_points' => 10,
            'perfect_dialogue_bonus' => 25,
            'streak_bonus_per_dialogue' => 3,
            'time_bonus_multiplier' => 1.5,
            'first_try_bonus' => 5
        ];
    }

    /**
     * Check if a level is unlocked
     */
    public function isLevelUnlocked(int $levelNumber, ?array $progress = null): bool
    {
        if ($levelNumber === 1) {
            return true;
        }

        $progress = $progress ?? $this->getUserProgress();
        $levels = $this->getLevels();

        foreach ($levels as $level) {
            if ($level['level_number'] === $levelNumber - 1) {
                $levelId = $level['id'];
                return ($progress['levels'][$levelId]['completed'] ?? false);
            }
        }

        return false;
    }

    /**
     * Get user progress
     */
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
                'games_played' => 0,
                'correct_answers' => 0,
                'total_answers' => 0,
                'perfect_dialogues' => 0,
                'best_streak' => 0,
                'categories_mastered' => [],
                'total_xp' => 0,
                'total_coins' => 0,
            ],
            'achievements' => [],
        ];
    }

    /**
     * Save user progress
     */
    public function saveUserProgress(array $progress): void
    {
        $userId = Auth::id() ?? 'guest';
        $progressFile = $this->progressPath . "/{$userId}.json";

        File::put($progressFile, json_encode($progress, JSON_PRETTY_PRINT));
    }

    /**
     * Get user stats
     */
    public function getUserStats(): array
    {
        $progress = $this->getUserProgress();
        $stats = $progress['stats'] ?? [];

        $stats['accuracy'] = $stats['total_answers'] > 0
            ? round(($stats['correct_answers'] / $stats['total_answers']) * 100)
            : 0;

        return $stats;
    }

    /**
     * Get user achievements
     */
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

    /**
     * Get all achievements
     */
    public function getAchievements(): array
    {
        return Cache::remember('conversation_catcher_achievements', 3600, function () {
            $achievementsPath = $this->basePath . '/achievements.json';
            if (File::exists($achievementsPath)) {
                return json_decode(File::get($achievementsPath), true);
            }
            return [];
        });
    }

    /**
     * Calculate achievement progress
     */
    protected function calculateAchievementProgress(array $achievement, array $stats): int
    {
        $condition = $achievement['condition'] ?? [];
        $type = $condition['type'] ?? '';
        $target = $condition['value'] ?? 1;

        switch ($type) {
            case 'dialogues_completed':
                $current = $stats['correct_answers'] ?? 0;
                break;
            case 'perfect_dialogues':
                $current = $stats['perfect_dialogues'] ?? 0;
                break;
            case 'streak':
                $current = $stats['best_streak'] ?? 0;
                break;
            case 'games_played':
                $current = $stats['games_played'] ?? 0;
                break;
            case 'categories_mastered':
                $current = count($stats['categories_mastered'] ?? []);
                break;
            case 'accuracy':
                $current = $stats['accuracy'] ?? 0;
                break;
            default:
                return 0;
        }

        return min(100, round(($current / $target) * 100));
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

    /**
     * Update level progress
     */
    public function updateLevelProgress(string $levelId, array $result): void
    {
        $progress = $this->getUserProgress();

        if (!isset($progress['levels'][$levelId])) {
            $progress['levels'][$levelId] = [
                'completed' => false,
                'stars' => 0,
                'best_score' => 0,
                'best_accuracy' => 0,
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

            if ($result['accuracy'] > $levelProgress['best_accuracy']) {
                $levelProgress['best_accuracy'] = $result['accuracy'];
            }

            if ($result['stars'] > $levelProgress['stars']) {
                $levelProgress['stars'] = $result['stars'];
            }
        }

        // Update stats
        $progress['stats']['games_played'] = ($progress['stats']['games_played'] ?? 0) + 1;
        $progress['stats']['correct_answers'] = ($progress['stats']['correct_answers'] ?? 0) + $result['correct_answers'];
        $progress['stats']['total_answers'] = ($progress['stats']['total_answers'] ?? 0) + $result['total_answers'];
        $progress['stats']['perfect_dialogues'] = ($progress['stats']['perfect_dialogues'] ?? 0) + ($result['perfect_dialogues'] ?? 0);

        if (($result['streak'] ?? 0) > ($progress['stats']['best_streak'] ?? 0)) {
            $progress['stats']['best_streak'] = $result['streak'];
        }

        $progress['stats']['total_xp'] = ($progress['stats']['total_xp'] ?? 0) + ($result['xp_earned'] ?? 0);
        $progress['stats']['total_coins'] = ($progress['stats']['total_coins'] ?? 0) + ($result['coins_earned'] ?? 0);

        // Check for new achievements
        $this->checkAchievements($progress);

        $this->saveUserProgress($progress);
    }

    /**
     * Check and unlock achievements
     */
    protected function checkAchievements(array &$progress): array
    {
        $achievements = $this->getAchievements();
        $userAchievements = $progress['achievements'] ?? [];
        $stats = $progress['stats'] ?? [];
        $newAchievements = [];

        foreach ($achievements as $achievement) {
            if (in_array($achievement['id'], $userAchievements)) {
                continue;
            }

            $condition = $achievement['condition'] ?? [];
            $unlocked = $this->checkAchievementCondition($condition, $stats, $progress);

            if ($unlocked) {
                $userAchievements[] = $achievement['id'];
                $newAchievements[] = $achievement;

                // Award XP and coins for achievement
                $progress['stats']['total_xp'] = ($progress['stats']['total_xp'] ?? 0) + ($achievement['xp_reward'] ?? 0);
                $progress['stats']['total_coins'] = ($progress['stats']['total_coins'] ?? 0) + ($achievement['coin_reward'] ?? 0);
            }
        }

        $progress['achievements'] = $userAchievements;

        return $newAchievements;
    }

    /**
     * Check if achievement condition is met
     */
    protected function checkAchievementCondition(array $condition, array $stats, array $progress): bool
    {
        $type = $condition['type'] ?? '';
        $value = $condition['value'] ?? 0;

        switch ($type) {
            case 'dialogues_completed':
                return ($stats['correct_answers'] ?? 0) >= $value;

            case 'perfect_dialogues':
                return ($stats['perfect_dialogues'] ?? 0) >= $value;

            case 'streak':
                return ($stats['best_streak'] ?? 0) >= $value;

            case 'games_played':
                return ($stats['games_played'] ?? 0) >= $value;

            case 'categories_mastered':
                return count($stats['categories_mastered'] ?? []) >= $value;

            case 'accuracy':
                $totalAnswers = $stats['total_answers'] ?? 0;
                if ($totalAnswers < 20) return false;
                $accuracy = round(($stats['correct_answers'] / $totalAnswers) * 100);
                return $accuracy >= $value;

            case 'levels_completed':
                $completedLevels = 0;
                foreach ($progress['levels'] ?? [] as $level) {
                    if ($level['completed'] ?? false) {
                        $completedLevels++;
                    }
                }
                return $completedLevels >= $value;

            case 'total_stars':
                $totalStars = 0;
                foreach ($progress['levels'] ?? [] as $level) {
                    $totalStars += $level['stars'] ?? 0;
                }
                return $totalStars >= $value;

            default:
                return false;
        }
    }

    /**
     * Get current rank based on XP
     */
    public function getCurrentRank(): ?array
    {
        $stats = $this->getUserStats();
        $ranks = $this->getConversationMasterRanks();
        $currentXp = $stats['total_xp'] ?? 0;

        $currentRank = null;
        foreach ($ranks as $rank) {
            if ($currentXp >= $rank['xp_required']) {
                $currentRank = $rank;
            }
        }

        return $currentRank;
    }

    /**
     * Get category stats
     */
    public function getCategoryStats(): array
    {
        $progress = $this->getUserProgress();
        $config = $this->getConfig();
        $categories = $config['categories'] ?? [];

        $categoryStats = [];
        foreach ($categories as $category) {
            $categoryStats[$category['id']] = [
                'id' => $category['id'],
                'name' => $category['name'],
                'dialogues_completed' => 0,
                'perfect_dialogues' => 0,
                'mastered' => false,
            ];
        }

        return $categoryStats;
    }
}
