<?php

namespace App\Services\English;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class VerbConjugatorDataService
{
    protected string $dataPath;
    protected string $progressPath;

    public function __construct()
    {
        $this->dataPath = base_path('data/english/games/verb-conjugator');
        $this->progressPath = storage_path('app/game-progress/verb-conjugator');

        if (!file_exists($this->progressPath)) {
            mkdir($this->progressPath, 0755, true);
        }
    }

    /**
     * Get game configuration
     */
    public function getConfig(): array
    {
        return Cache::remember('verb_conjugator_config', 3600, function () {
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
        return Cache::remember('verb_conjugator_levels', 3600, function () {
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
                'best_accuracy' => $levelProgress['best_accuracy'] ?? null,
                'best_time' => $levelProgress['best_time'] ?? null,
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

        return ($previousLevel['stars'] ?? 0) >= 1;
    }

    /**
     * Get all verbs
     */
    public function getVerbs(): array
    {
        return Cache::remember('verb_conjugator_verbs', 3600, function () {
            $regular = [];
            $irregular = [];

            $regularPath = $this->dataPath . '/verbs/regular.json';
            $irregularPath = $this->dataPath . '/verbs/irregular.json';

            if (file_exists($regularPath)) {
                $regular = json_decode(file_get_contents($regularPath), true) ?? [];
            }

            if (file_exists($irregularPath)) {
                $irregular = json_decode(file_get_contents($irregularPath), true) ?? [];
            }

            return [
                'regular' => $regular,
                'irregular' => $irregular,
                'all' => array_merge($regular, $irregular),
            ];
        });
    }

    /**
     * Get verbs for a specific level
     */
    public function getVerbsForLevel(array $level): array
    {
        $verbs = $this->getVerbs();
        $verbType = $level['verb_type'] ?? 'all';
        $count = $level['verbs_count'] ?? 10;

        $verbList = match ($verbType) {
            'regular' => $verbs['regular'],
            'irregular' => $verbs['irregular'],
            default => $verbs['all'],
        };

        shuffle($verbList);
        return array_slice($verbList, 0, $count);
    }

    /**
     * Get tenses configuration
     */
    public function getTenses(): array
    {
        $config = $this->getConfig();
        return $config['tenses'] ?? [];
    }

    /**
     * Get pronouns configuration
     */
    public function getPronouns(): array
    {
        $config = $this->getConfig();
        return $config['pronouns'] ?? [];
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
     * Get conjugation master ranks
     */
    public function getConjugationMasterRanks(): array
    {
        $config = $this->getConfig();
        return $config['conjugation_master_ranks'] ?? [];
    }

    /**
     * Get all achievements
     */
    public function getAchievements(): array
    {
        return Cache::remember('verb_conjugator_achievements', 3600, function () {
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
                'total_conjugations' => 0,
                'correct_conjugations' => 0,
                'incorrect_conjugations' => 0,
                'best_accuracy' => 0,
                'average_accuracy' => 0,
                'best_streak' => 0,
                'total_xp' => 0,
                'total_coins' => 0,
                'perfect_games' => 0,
                'total_time_seconds' => 0,
                'verbs_mastered' => 0,
                'tenses_practiced' => [],
            ],
            'achievements' => [],
            'verb_stats' => [],
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

        return match ($type) {
            'games_completed' => min(100, (int)(($stats['games_played'] ?? 0) / $target * 100)),
            'accuracy_reached' => min(100, (int)(($stats['best_accuracy'] ?? 0) / $target * 100)),
            'conjugations_completed' => min(100, (int)(($stats['total_conjugations'] ?? 0) / $target * 100)),
            'verbs_mastered' => min(100, (int)(($stats['verbs_mastered'] ?? 0) / $target * 100)),
            'perfect_games' => min(100, (int)(($stats['perfect_games'] ?? 0) / $target * 100)),
            'streak_reached' => min(100, (int)(($stats['best_streak'] ?? 0) / $target * 100)),
            'levels_completed' => min(100, (int)(($stats['levels_completed'] ?? 0) / $target * 100)),
            'stars_earned' => min(100, (int)(($stats['total_stars'] ?? 0) / $target * 100)),
            'tenses_practiced' => min(100, (int)((count($stats['tenses_practiced'] ?? [])) / $target * 100)),
            default => 0,
        };
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

        return match ($type) {
            'games_completed' => ($stats['games_played'] ?? 0) >= $target,
            'accuracy_reached' => ($stats['best_accuracy'] ?? 0) >= $target,
            'conjugations_completed' => ($stats['total_conjugations'] ?? 0) >= $target,
            'verbs_mastered' => ($stats['verbs_mastered'] ?? 0) >= $target,
            'perfect_games' => ($stats['perfect_games'] ?? 0) >= $target,
            'streak_reached' => ($stats['best_streak'] ?? 0) >= $target,
            'levels_completed' => ($stats['levels_completed'] ?? 0) >= $target,
            'stars_earned' => ($stats['total_stars'] ?? 0) >= $target,
            'tenses_practiced' => count($stats['tenses_practiced'] ?? []) >= $target,
            'all_levels_3_stars' => $this->hasAllLevels3Stars(),
            default => false,
        };
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

    /**
     * Conjugate a verb in a specific tense
     */
    public function conjugateVerb(array $verb, string $tense, string $pronoun): string
    {
        $base = $verb['base'];
        $past = $verb['past'];
        $pastParticiple = $verb['past_participle'];
        $presentParticiple = $verb['present_participle'];
        $thirdPerson = $verb['third_person'];

        return match ($tense) {
            'present_simple' => $this->conjugatePresentSimple($verb, $pronoun),
            'past_simple' => $this->conjugatePastSimple($verb, $pronoun),
            'future_simple' => "will $base",
            'present_continuous' => $this->conjugatePresentContinuous($verb, $pronoun),
            'past_continuous' => $this->conjugatePastContinuous($verb, $pronoun),
            'present_perfect' => $this->conjugatePresentPerfect($verb, $pronoun),
            'past_perfect' => "had $pastParticiple",
            'future_perfect' => "will have $pastParticiple",
            default => $base,
        };
    }

    protected function conjugatePresentSimple(array $verb, string $pronoun): string
    {
        if (in_array($pronoun, ['he', 'she', 'it'])) {
            return $verb['third_person'];
        }
        return $verb['base'];
    }

    protected function conjugatePastSimple(array $verb, string $pronoun): string
    {
        $past = $verb['past'];
        // Handle was/were for "be"
        if ($verb['base'] === 'be') {
            return in_array($pronoun, ['i', 'he', 'she', 'it']) ? 'was' : 'were';
        }
        return $past;
    }

    protected function conjugatePresentContinuous(array $verb, string $pronoun): string
    {
        $presentParticiple = $verb['present_participle'];
        $toBe = match ($pronoun) {
            'i' => 'am',
            'he', 'she', 'it' => 'is',
            default => 'are',
        };
        return "$toBe $presentParticiple";
    }

    protected function conjugatePastContinuous(array $verb, string $pronoun): string
    {
        $presentParticiple = $verb['present_participle'];
        $toBe = in_array($pronoun, ['i', 'he', 'she', 'it']) ? 'was' : 'were';
        return "$toBe $presentParticiple";
    }

    protected function conjugatePresentPerfect(array $verb, string $pronoun): string
    {
        $pastParticiple = $verb['past_participle'];
        $have = in_array($pronoun, ['he', 'she', 'it']) ? 'has' : 'have';
        return "$have $pastParticiple";
    }

    /**
     * Generate negative form
     */
    public function generateNegative(string $conjugated, string $tense, string $pronoun): string
    {
        return match ($tense) {
            'present_simple' => in_array($pronoun, ['he', 'she', 'it']) ? "doesn't" : "don't",
            'past_simple' => "didn't",
            'future_simple' => "won't",
            'present_continuous' => str_replace(['am ', 'is ', 'are '], ["am not ", "isn't ", "aren't "], $conjugated),
            'past_continuous' => str_replace(['was ', 'were '], ["wasn't ", "weren't "], $conjugated),
            'present_perfect' => str_replace(['have ', 'has '], ["haven't ", "hasn't "], $conjugated),
            'past_perfect' => str_replace('had ', "hadn't ", $conjugated),
            'future_perfect' => str_replace('will have', "won't have", $conjugated),
            default => $conjugated,
        };
    }

    /**
     * Generate question form
     */
    public function generateQuestion(array $verb, string $tense, string $pronoun): string
    {
        $base = $verb['base'];
        $pastParticiple = $verb['past_participle'];
        $presentParticiple = $verb['present_participle'];

        return match ($tense) {
            'present_simple' => (in_array($pronoun, ['he', 'she', 'it']) ? "Does" : "Do") . " $pronoun $base?",
            'past_simple' => "Did $pronoun $base?",
            'future_simple' => "Will $pronoun $base?",
            'present_continuous' => $this->getQuestionToBe($pronoun, 'present') . " $pronoun $presentParticiple?",
            'past_continuous' => $this->getQuestionToBe($pronoun, 'past') . " $pronoun $presentParticiple?",
            'present_perfect' => (in_array($pronoun, ['he', 'she', 'it']) ? "Has" : "Have") . " $pronoun $pastParticiple?",
            'past_perfect' => "Had $pronoun $pastParticiple?",
            'future_perfect' => "Will $pronoun have $pastParticiple?",
            default => $base,
        };
    }

    protected function getQuestionToBe(string $pronoun, string $time): string
    {
        if ($time === 'present') {
            return match ($pronoun) {
                'i' => 'Am',
                'he', 'she', 'it' => 'Is',
                default => 'Are',
            };
        }
        return in_array($pronoun, ['i', 'he', 'she', 'it']) ? 'Was' : 'Were';
    }
}
