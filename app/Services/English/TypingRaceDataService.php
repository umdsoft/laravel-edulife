<?php

namespace App\Services\English;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class TypingRaceDataService
{
    protected string $dataPath;
    protected string $progressPath;

    public function __construct()
    {
        $this->dataPath = base_path('data/english/games/typing-race');
        $this->progressPath = storage_path('app/game-progress/typing-race');

        if (!file_exists($this->progressPath)) {
            mkdir($this->progressPath, 0755, true);
        }
    }

    /**
     * Get game configuration
     */
    public function getConfig(): array
    {
        return Cache::remember('typing_race_config', 3600, function () {
            $configPath = $this->dataPath . '/config.json';
            if (file_exists($configPath)) {
                return json_decode(file_get_contents($configPath), true) ?? [];
            }
            return [];
        });
    }

    /**
     * Get all levels
     */
    public function getLevels(): array
    {
        return Cache::remember('typing_race_levels', 3600, function () {
            $levelsPath = $this->dataPath . '/levels.json';
            if (file_exists($levelsPath)) {
                return json_decode(file_get_contents($levelsPath), true) ?? [];
            }
            return [];
        });
    }

    /**
     * Get levels with user progress
     */
    public function getLevelsWithStatus(): array
    {
        $levels = $this->getLevels();
        $progress = $this->getUserProgress();

        return array_map(function ($level) use ($progress) {
            $levelProgress = $progress['levels'][$level['level_number']] ?? null;

            return array_merge($level, [
                'unlocked' => $this->isLevelUnlocked($level['level_number']),
                'completed' => $levelProgress['completed'] ?? false,
                'stars' => $levelProgress['stars'] ?? 0,
                'best_score' => $levelProgress['best_score'] ?? null,
                'best_wpm' => $levelProgress['best_wpm'] ?? null,
                'best_accuracy' => $levelProgress['best_accuracy'] ?? null,
            ]);
        }, $levels);
    }

    /**
     * Get a specific level
     */
    public function getLevel(int $levelNumber): ?array
    {
        $levels = $this->getLevelsWithStatus();

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

        $progress = $this->getUserProgress();
        $previousLevel = $progress['levels'][$levelNumber - 1] ?? null;

        // Need at least 1 star on previous level
        return ($previousLevel['stars'] ?? 0) >= 1;
    }

    /**
     * Get content for a level
     */
    public function getContent(array $level, int $count = null): array
    {
        $contentType = $level['content_type'] ?? 'words';
        $count = $count ?? $level['items_count'] ?? 10;

        switch ($contentType) {
            case 'words':
                return $this->getWords($level, $count);
            case 'sentences':
                return $this->getSentences($level, $count);
            case 'quotes':
                return $this->getQuotes($count);
            case 'paragraphs':
                return $this->getParagraphs($level, $count);
            case 'technical':
                return $this->getTechnicalContent($count);
            default:
                return $this->getWords($level, $count);
        }
    }

    /**
     * Get words for typing
     */
    protected function getWords(array $level, int $count): array
    {
        $wordsPath = $this->dataPath . '/content/words.json';
        if (!file_exists($wordsPath)) {
            return [];
        }

        $allWords = json_decode(file_get_contents($wordsPath), true);
        $minLength = $level['word_length_min'] ?? 3;
        $maxLength = $level['word_length_max'] ?? 6;

        // Collect words from appropriate categories
        $words = [];
        foreach ($allWords as $category => $categoryWords) {
            foreach ($categoryWords as $word) {
                $length = strlen($word);
                if ($length >= $minLength && $length <= $maxLength) {
                    $words[] = $word;
                }
            }
        }

        shuffle($words);
        return array_slice($words, 0, $count);
    }

    /**
     * Get sentences for typing
     */
    protected function getSentences(array $level, int $count): array
    {
        $sentencesPath = $this->dataPath . '/content/sentences.json';
        if (!file_exists($sentencesPath)) {
            return [];
        }

        $allSentences = json_decode(file_get_contents($sentencesPath), true);
        $minWords = $level['word_count_min'] ?? 2;
        $maxWords = $level['word_count_max'] ?? 10;

        // Collect sentences from appropriate categories
        $sentences = [];
        foreach ($allSentences as $category => $categorySentences) {
            foreach ($categorySentences as $sentence) {
                $wordCount = str_word_count($sentence);
                if ($wordCount >= $minWords && $wordCount <= $maxWords) {
                    $sentences[] = $sentence;
                }
            }
        }

        shuffle($sentences);
        return array_slice($sentences, 0, $count);
    }

    /**
     * Get quotes for typing
     */
    protected function getQuotes(int $count): array
    {
        $quotesPath = $this->dataPath . '/content/quotes.json';
        if (!file_exists($quotesPath)) {
            return [];
        }

        $quotes = json_decode(file_get_contents($quotesPath), true);
        shuffle($quotes);

        return array_map(function ($quote) {
            return $quote['text'];
        }, array_slice($quotes, 0, $count));
    }

    /**
     * Get paragraphs for typing
     */
    protected function getParagraphs(array $level, int $count): array
    {
        $paragraphsPath = $this->dataPath . '/content/paragraphs.json';
        if (!file_exists($paragraphsPath)) {
            return [];
        }

        $paragraphs = json_decode(file_get_contents($paragraphsPath), true);
        $difficulty = $level['difficulty'] ?? 'intermediate';

        // Filter by difficulty
        $filtered = array_filter($paragraphs, function ($p) use ($difficulty) {
            $pDifficulty = $p['difficulty'] ?? 'intermediate';
            $difficultyOrder = ['beginner', 'elementary', 'intermediate', 'advanced', 'expert', 'master'];
            $levelIndex = array_search($difficulty, $difficultyOrder);
            $pIndex = array_search($pDifficulty, $difficultyOrder);
            return $pIndex <= $levelIndex + 1;
        });

        shuffle($filtered);

        return array_map(function ($p) {
            return $p['text'];
        }, array_slice($filtered, 0, $count));
    }

    /**
     * Get technical content for typing
     */
    protected function getTechnicalContent(int $count): array
    {
        $techPath = $this->dataPath . '/content/technical.json';
        if (!file_exists($techPath)) {
            return [];
        }

        $technical = json_decode(file_get_contents($techPath), true);

        // Mix different technical content types
        $content = [];

        // Add some code snippets
        if (isset($technical['code_snippets'])) {
            $snippets = $technical['code_snippets'];
            shuffle($snippets);
            $content = array_merge($content, array_slice($snippets, 0, (int)($count * 0.4)));
        }

        // Add some tech sentences
        if (isset($technical['tech_sentences'])) {
            $sentences = $technical['tech_sentences'];
            shuffle($sentences);
            $content = array_merge($content, array_slice($sentences, 0, (int)($count * 0.6)));
        }

        shuffle($content);
        return array_slice($content, 0, $count);
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
     * Get typing master ranks
     */
    public function getTypingMasterRanks(): array
    {
        $config = $this->getConfig();
        return $config['typing_master_ranks'] ?? [];
    }

    /**
     * Get all achievements
     */
    public function getAchievements(): array
    {
        return Cache::remember('typing_race_achievements', 3600, function () {
            $achievementsPath = $this->dataPath . '/achievements.json';
            if (file_exists($achievementsPath)) {
                return json_decode(file_get_contents($achievementsPath), true) ?? [];
            }
            return [];
        });
    }

    /**
     * Get user progress
     */
    public function getUserProgress(): array
    {
        $userId = Auth::id() ?? 'guest';
        $progressFile = $this->progressPath . "/user_{$userId}.json";

        if (file_exists($progressFile)) {
            return json_decode(file_get_contents($progressFile), true) ?? $this->getDefaultProgress();
        }

        return $this->getDefaultProgress();
    }

    /**
     * Save user progress
     */
    public function saveUserProgress(array $progress): void
    {
        $userId = Auth::id() ?? 'guest';
        $progressFile = $this->progressPath . "/user_{$userId}.json";

        file_put_contents($progressFile, json_encode($progress, JSON_PRETTY_PRINT));
    }

    /**
     * Get default progress structure
     */
    protected function getDefaultProgress(): array
    {
        return [
            'levels' => [],
            'stats' => [
                'games_played' => 0,
                'total_characters' => 0,
                'total_words' => 0,
                'total_correct' => 0,
                'total_incorrect' => 0,
                'best_wpm' => 0,
                'average_wpm' => 0,
                'best_accuracy' => 0,
                'average_accuracy' => 0,
                'best_streak' => 0,
                'total_xp' => 0,
                'total_coins' => 0,
                'perfect_games' => 0,
                'total_time_seconds' => 0,
            ],
            'achievements' => [],
            'category_stats' => [],
        ];
    }

    /**
     * Get user stats
     */
    public function getUserStats(): array
    {
        $progress = $this->getUserProgress();
        return $progress['stats'] ?? $this->getDefaultProgress()['stats'];
    }

    /**
     * Get user achievements with unlock status
     */
    public function getUserAchievements(): array
    {
        $achievements = $this->getAchievements();
        $progress = $this->getUserProgress();
        $unlockedIds = $progress['achievements'] ?? [];
        $stats = $progress['stats'] ?? [];

        return array_map(function ($achievement) use ($unlockedIds, $stats) {
            $isUnlocked = in_array($achievement['id'], $unlockedIds);

            return array_merge($achievement, [
                'unlocked' => $isUnlocked,
                'progress' => $this->calculateAchievementProgress($achievement, $stats),
            ]);
        }, $achievements);
    }

    /**
     * Calculate achievement progress
     */
    protected function calculateAchievementProgress(array $achievement, array $stats): int
    {
        $condition = $achievement['condition'] ?? [];
        $type = $condition['type'] ?? '';
        $target = $condition['value'] ?? 0;

        switch ($type) {
            case 'games_completed':
                return min(100, (int)(($stats['games_played'] ?? 0) / $target * 100));
            case 'wpm_reached':
                return min(100, (int)(($stats['best_wpm'] ?? 0) / $target * 100));
            case 'accuracy_reached':
                return min(100, (int)(($stats['best_accuracy'] ?? 0) / $target * 100));
            case 'words_typed':
                return min(100, (int)(($stats['total_words'] ?? 0) / $target * 100));
            case 'sentences_typed':
                return min(100, (int)(($stats['sentences_typed'] ?? 0) / $target * 100));
            case 'paragraphs_typed':
                return min(100, (int)(($stats['paragraphs_typed'] ?? 0) / $target * 100));
            case 'levels_completed':
                return min(100, (int)(($stats['levels_completed'] ?? 0) / $target * 100));
            case 'stars_earned':
                return min(100, (int)(($stats['total_stars'] ?? 0) / $target * 100));
            case 'perfect_games':
                return min(100, (int)(($stats['perfect_games'] ?? 0) / $target * 100));
            default:
                return 0;
        }
    }

    /**
     * Get total stars earned
     */
    public function getTotalStars(): int
    {
        $progress = $this->getUserProgress();
        $totalStars = 0;

        foreach ($progress['levels'] ?? [] as $level) {
            $totalStars += $level['stars'] ?? 0;
        }

        return $totalStars;
    }

    /**
     * Check and unlock achievements
     */
    public function checkAchievements(array $stats): array
    {
        $achievements = $this->getAchievements();
        $progress = $this->getUserProgress();
        $unlockedIds = $progress['achievements'] ?? [];
        $newlyUnlocked = [];

        foreach ($achievements as $achievement) {
            if (in_array($achievement['id'], $unlockedIds)) {
                continue;
            }

            if ($this->isAchievementUnlocked($achievement, $stats)) {
                $unlockedIds[] = $achievement['id'];
                $newlyUnlocked[] = $achievement;
            }
        }

        if (!empty($newlyUnlocked)) {
            $progress['achievements'] = $unlockedIds;
            $this->saveUserProgress($progress);
        }

        return $newlyUnlocked;
    }

    /**
     * Check if an achievement is unlocked
     */
    protected function isAchievementUnlocked(array $achievement, array $stats): bool
    {
        $condition = $achievement['condition'] ?? [];
        $type = $condition['type'] ?? '';
        $target = $condition['value'] ?? 0;

        switch ($type) {
            case 'games_completed':
                return ($stats['games_played'] ?? 0) >= $target;
            case 'wpm_reached':
                return ($stats['best_wpm'] ?? 0) >= $target;
            case 'accuracy_reached':
                return ($stats['best_accuracy'] ?? 0) >= $target;
            case 'words_typed':
                return ($stats['total_words'] ?? 0) >= $target;
            case 'sentences_typed':
                return ($stats['sentences_typed'] ?? 0) >= $target;
            case 'paragraphs_typed':
                return ($stats['paragraphs_typed'] ?? 0) >= $target;
            case 'levels_completed':
                return ($stats['levels_completed'] ?? 0) >= $target;
            case 'stars_earned':
                return ($stats['total_stars'] ?? 0) >= $target;
            case 'perfect_games':
                return ($stats['perfect_games'] ?? 0) >= $target;
            case 'perfect_accuracy':
                return ($stats['perfect_rounds'] ?? 0) >= $target;
            case 'high_accuracy_games':
                return ($stats['high_accuracy_games'] ?? 0) >= $target;
            case 'all_levels_3_stars':
                return $this->hasAllLevels3Stars();
            default:
                return false;
        }
    }

    /**
     * Check if all levels have 3 stars
     */
    protected function hasAllLevels3Stars(): bool
    {
        $levels = $this->getLevels();
        $progress = $this->getUserProgress();

        foreach ($levels as $level) {
            $levelProgress = $progress['levels'][$level['level_number']] ?? null;
            if (!$levelProgress || ($levelProgress['stars'] ?? 0) < 3) {
                return false;
            }
        }

        return true;
    }
}
