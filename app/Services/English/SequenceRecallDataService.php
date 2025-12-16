<?php

namespace App\Services\English;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class SequenceRecallDataService
{
    protected string $dataPath;
    protected int $cacheTTL = 3600;

    public function __construct()
    {
        $this->dataPath = base_path('data/english/games/sequence-recall');
    }

    public function getConfig(): array
    {
        return Cache::remember('sequence_recall_config', $this->cacheTTL, function () {
            $path = $this->dataPath . '/config.json';
            if (File::exists($path)) {
                return json_decode(File::get($path), true);
            }
            return [];
        });
    }

    public function getLevels(): array
    {
        return Cache::remember('sequence_recall_levels', $this->cacheTTL, function () {
            $path = $this->dataPath . '/levels.json';
            if (File::exists($path)) {
                $data = json_decode(File::get($path), true);
                return $data['levels'] ?? [];
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

    public function getAchievements(): array
    {
        return Cache::remember('sequence_recall_achievements', $this->cacheTTL, function () {
            $path = $this->dataPath . '/achievements.json';
            if (File::exists($path)) {
                $data = json_decode(File::get($path), true);
                return $data['achievements'] ?? [];
            }
            return [];
        });
    }

    public function getWords(): array
    {
        return Cache::remember('sequence_recall_words', $this->cacheTTL, function () {
            $path = $this->dataPath . '/sequences/words.json';
            if (File::exists($path)) {
                $data = json_decode(File::get($path), true);
                return $data['words'] ?? [];
            }
            return [];
        });
    }

    public function getPhrases(): array
    {
        return Cache::remember('sequence_recall_phrases', $this->cacheTTL, function () {
            $path = $this->dataPath . '/sequences/phrases.json';
            if (File::exists($path)) {
                $data = json_decode(File::get($path), true);
                return $data['phrases'] ?? [];
            }
            return [];
        });
    }

    public function getWordsForCategory(string $category): array
    {
        $words = $this->getWords();
        return $words[$category] ?? [];
    }

    public function generateSequenceForLevel(int $levelNumber): array
    {
        $level = $this->getLevel($levelNumber);
        if (!$level) {
            return [];
        }

        $categories = $level['categories'] ?? ['mixed'];
        $length = $level['sequence_length'] ?? 4;

        // Special case for phrases
        if (in_array('phrases', $categories)) {
            return $this->generatePhraseSequence($length);
        }

        // Generate word sequence
        $allWords = [];
        foreach ($categories as $category) {
            $categoryWords = $this->getWordsForCategory($category);
            $allWords = array_merge($allWords, $categoryWords);
        }

        if (empty($allWords)) {
            $allWords = $this->getWordsForCategory('mixed');
        }

        shuffle($allWords);
        $sequence = array_slice($allWords, 0, $length);

        return [
            'type' => 'words',
            'items' => $sequence,
            'sequence_type' => $level['sequence_type'] ?? 'forward',
        ];
    }

    protected function generatePhraseSequence(int $length): array
    {
        $phrases = $this->getPhrases();
        shuffle($phrases);

        // Find a phrase that matches or is close to the length
        foreach ($phrases as $phrase) {
            if (count($phrase['words']) <= $length + 2 && count($phrase['words']) >= $length - 1) {
                return [
                    'type' => 'phrase',
                    'items' => $phrase['words'],
                    'translation' => $phrase['translation'],
                    'sequence_type' => 'forward',
                ];
            }
        }

        // Fallback to first phrase
        return [
            'type' => 'phrase',
            'items' => $phrases[0]['words'] ?? [],
            'translation' => $phrases[0]['translation'] ?? '',
            'sequence_type' => 'forward',
        ];
    }

    public function getGameModes(): array
    {
        $config = $this->getConfig();
        return $config['game_modes'] ?? [];
    }

    public function getSequenceTypes(): array
    {
        $config = $this->getConfig();
        return $config['sequence_types'] ?? [];
    }

    public function getPowerups(): array
    {
        $config = $this->getConfig();
        return $config['powerups'] ?? [];
    }

    public function getMemoryRanks(): array
    {
        $config = $this->getConfig();
        return $config['memory_ranks'] ?? [];
    }

    public function getWordCategories(): array
    {
        $config = $this->getConfig();
        return $config['word_categories'] ?? [];
    }

    public function getScoringConfig(): array
    {
        $config = $this->getConfig();
        return $config['scoring'] ?? [];
    }

    public function getRewardsConfig(): array
    {
        $config = $this->getConfig();
        return $config['rewards'] ?? [];
    }

    public function getDisplaySettings(): array
    {
        $config = $this->getConfig();
        return $config['display_settings'] ?? [];
    }

    public function getUserProgress(): array
    {
        $userId = auth()->id() ?? 'guest';
        return session("sequence_recall_progress_{$userId}", [
            'levels_completed' => [],
            'total_xp' => 0,
            'total_coins' => 0,
            'total_sequences' => 0,
            'correct_sequences' => 0,
            'best_streak' => 0,
            'achievements' => [],
            'daily_streak' => 0,
            'last_played' => null,
            'games_played' => 0,
            'sequence_type_stats' => [],
            'longest_sequence' => 0,
            'perfect_sequences' => 0,
            'hints_used' => 0,
        ]);
    }

    public function saveUserProgress(array $progress): void
    {
        $userId = auth()->id() ?? 'guest';
        session(["sequence_recall_progress_{$userId}" => $progress]);
    }

    public function getLevelCompletion(int $levelNumber): ?array
    {
        $progress = $this->getUserProgress();
        return $progress['levels_completed'][$levelNumber] ?? null;
    }

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

    public function getLevelsWithStatus(): array
    {
        $levels = $this->getLevels();
        $result = [];

        foreach ($levels as $level) {
            $completion = $this->getLevelCompletion($level['level_number']);
            $level['is_unlocked'] = $this->isLevelUnlocked($level['level_number']);
            $level['stars'] = $completion['stars'] ?? 0;
            $level['best_score'] = $completion['best_score'] ?? 0;
            $level['best_accuracy'] = $completion['best_accuracy'] ?? 0;
            $level['times_played'] = $completion['times_played'] ?? 0;
            $result[] = $level;
        }

        return $result;
    }

    public function getUserStats(): array
    {
        $progress = $this->getUserProgress();
        $ranks = $this->getMemoryRanks();

        $currentRank = null;
        $nextRank = null;
        $totalXp = $progress['total_xp'] ?? 0;

        foreach ($ranks as $index => $rank) {
            if ($totalXp >= $rank['min_xp']) {
                $currentRank = $rank;
                $nextRank = $ranks[$index + 1] ?? null;
            }
        }

        $accuracy = 0;
        if (($progress['total_sequences'] ?? 0) > 0) {
            $accuracy = round(($progress['correct_sequences'] / $progress['total_sequences']) * 100);
        }

        $levelsCompleted = count(array_filter($progress['levels_completed'] ?? [], function ($l) {
            return ($l['stars'] ?? 0) > 0;
        }));

        return [
            'total_xp' => $totalXp,
            'total_coins' => $progress['total_coins'] ?? 0,
            'total_sequences' => $progress['total_sequences'] ?? 0,
            'correct_sequences' => $progress['correct_sequences'] ?? 0,
            'accuracy' => $accuracy,
            'best_streak' => $progress['best_streak'] ?? 0,
            'games_played' => $progress['games_played'] ?? 0,
            'levels_completed' => $levelsCompleted,
            'daily_streak' => $progress['daily_streak'] ?? 0,
            'longest_sequence' => $progress['longest_sequence'] ?? 0,
            'perfect_sequences' => $progress['perfect_sequences'] ?? 0,
            'memory_rank' => [
                'current' => $currentRank,
                'next' => $nextRank,
                'xp_to_next' => $nextRank ? ($nextRank['min_xp'] - $totalXp) : 0,
                'progress' => $nextRank ? min(100, (($totalXp - ($currentRank['min_xp'] ?? 0)) / ($nextRank['min_xp'] - ($currentRank['min_xp'] ?? 0))) * 100) : 100,
            ],
            'sequence_type_stats' => $progress['sequence_type_stats'] ?? [],
        ];
    }

    public function getTotalStars(): int
    {
        $progress = $this->getUserProgress();
        $total = 0;
        foreach ($progress['levels_completed'] ?? [] as $level) {
            $total += $level['stars'] ?? 0;
        }
        return $total;
    }

    public function checkAchievements(array $sessionData): array
    {
        $progress = $this->getUserProgress();
        $achievements = $this->getAchievements();
        $newAchievements = [];

        foreach ($achievements as $achievement) {
            if (in_array($achievement['id'], $progress['achievements'] ?? [])) {
                continue;
            }

            $earned = false;
            $condition = $achievement['condition'];

            switch ($condition['type']) {
                case 'games_completed':
                    $earned = ($progress['games_played'] ?? 0) >= $condition['value'];
                    break;

                case 'streak':
                    $earned = max($progress['best_streak'] ?? 0, $sessionData['best_streak'] ?? 0) >= $condition['value'];
                    break;

                case 'answer_time':
                    $earned = ($sessionData['fastest_answer'] ?? 999) <= $condition['value'];
                    break;

                case 'accuracy':
                    if (($sessionData['total'] ?? 0) > 0) {
                        $accuracy = (($sessionData['correct'] ?? 0) / $sessionData['total']) * 100;
                        $earned = $accuracy >= $condition['value'];
                    }
                    break;

                case 'no_hints':
                    $earned = ($sessionData['hints_used'] ?? 1) === 0;
                    break;

                case 'perfect_sequence':
                    $earned = ($sessionData['perfect_sequences'] ?? 0) >= $condition['value'];
                    break;

                case 'sequence_type_correct':
                    $typeStats = $progress['sequence_type_stats'][$condition['sequence_type']] ?? [];
                    $earned = ($typeStats['correct'] ?? 0) >= $condition['value'];
                    break;

                case 'sequence_length':
                    $earned = max($progress['longest_sequence'] ?? 0, $sessionData['longest_correct'] ?? 0) >= $condition['value'];
                    break;

                case 'daily_streak':
                    $earned = ($progress['daily_streak'] ?? 0) >= $condition['value'];
                    break;

                case 'total_sequences':
                    $earned = ($progress['total_sequences'] ?? 0) >= $condition['value'];
                    break;

                case 'all_levels_completed':
                    $levels = $this->getLevels();
                    $allCompleted = true;
                    foreach ($levels as $level) {
                        $completion = $progress['levels_completed'][$level['level_number']] ?? null;
                        if (!$completion || ($completion['stars'] ?? 0) < $condition['value']) {
                            $allCompleted = false;
                            break;
                        }
                    }
                    $earned = $allCompleted;
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

    public function clearCache(): void
    {
        Cache::forget('sequence_recall_config');
        Cache::forget('sequence_recall_levels');
        Cache::forget('sequence_recall_achievements');
        Cache::forget('sequence_recall_words');
        Cache::forget('sequence_recall_phrases');
    }
}
