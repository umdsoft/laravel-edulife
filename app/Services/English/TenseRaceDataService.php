<?php

namespace App\Services\English;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class TenseRaceDataService
{
    protected string $dataPath;
    protected int $cacheTTL = 3600;

    public function __construct()
    {
        $this->dataPath = base_path('data/english/games/tense-race');
    }

    public function getConfig(): array
    {
        return Cache::remember('tense_race_config', $this->cacheTTL, function () {
            $path = $this->dataPath . '/config.json';
            if (File::exists($path)) {
                return json_decode(File::get($path), true);
            }
            return [];
        });
    }

    public function getLevels(): array
    {
        return Cache::remember('tense_race_levels', $this->cacheTTL, function () {
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
        return Cache::remember('tense_race_achievements', $this->cacheTTL, function () {
            $path = $this->dataPath . '/achievements.json';
            if (File::exists($path)) {
                $data = json_decode(File::get($path), true);
                return $data['achievements'] ?? [];
            }
            return [];
        });
    }

    public function getQuestionsByTense(string $tense): array
    {
        return Cache::remember("tense_race_questions_{$tense}", $this->cacheTTL, function () use ($tense) {
            $path = $this->dataPath . "/questions/{$tense}.json";
            if (File::exists($path)) {
                $data = json_decode(File::get($path), true);
                return $data['questions'] ?? [];
            }
            return [];
        });
    }

    public function getQuestionsForLevel(int $levelNumber): array
    {
        $level = $this->getLevel($levelNumber);
        if (!$level) {
            return [];
        }

        $questions = [];
        foreach ($level['tenses'] as $tense) {
            $tenseQuestions = $this->getQuestionsByTense($tense);
            foreach ($tenseQuestions as $question) {
                $question['tense'] = $tense;
                // Filter by question type if level specifies
                if (in_array($question['type'], $level['question_types'])) {
                    $questions[] = $question;
                }
            }
        }

        shuffle($questions);
        return array_slice($questions, 0, $level['questions_count']);
    }

    public function getGameModes(): array
    {
        $config = $this->getConfig();
        return $config['game_modes'] ?? [];
    }

    public function getTenseCategories(): array
    {
        $config = $this->getConfig();
        return $config['tense_categories'] ?? [];
    }

    public function getPowerups(): array
    {
        $config = $this->getConfig();
        return $config['powerups'] ?? [];
    }

    public function getTenseMasterRanks(): array
    {
        $config = $this->getConfig();
        return $config['tense_master_ranks'] ?? [];
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

    public function getUserProgress(): array
    {
        $userId = auth()->id() ?? 'guest';
        return session("tense_race_progress_{$userId}", [
            'levels_completed' => [],
            'total_xp' => 0,
            'total_coins' => 0,
            'total_questions' => 0,
            'total_correct' => 0,
            'best_streak' => 0,
            'achievements' => [],
            'daily_streak' => 0,
            'last_played' => null,
            'games_played' => 0,
            'tense_stats' => [],
            'question_type_stats' => [],
        ]);
    }

    public function saveUserProgress(array $progress): void
    {
        $userId = auth()->id() ?? 'guest';
        session(["tense_race_progress_{$userId}" => $progress]);
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
        $ranks = $this->getTenseMasterRanks();

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
        if (($progress['total_questions'] ?? 0) > 0) {
            $accuracy = round(($progress['total_correct'] / $progress['total_questions']) * 100);
        }

        $levelsCompleted = count(array_filter($progress['levels_completed'] ?? [], function ($l) {
            return ($l['stars'] ?? 0) > 0;
        }));

        return [
            'total_xp' => $totalXp,
            'total_coins' => $progress['total_coins'] ?? 0,
            'total_questions' => $progress['total_questions'] ?? 0,
            'total_correct' => $progress['total_correct'] ?? 0,
            'accuracy' => $accuracy,
            'best_streak' => $progress['best_streak'] ?? 0,
            'games_played' => $progress['games_played'] ?? 0,
            'levels_completed' => $levelsCompleted,
            'daily_streak' => $progress['daily_streak'] ?? 0,
            'tense_master_rank' => [
                'current' => $currentRank,
                'next' => $nextRank,
                'xp_to_next' => $nextRank ? ($nextRank['min_xp'] - $totalXp) : 0,
                'progress' => $nextRank ? min(100, (($totalXp - ($currentRank['min_xp'] ?? 0)) / ($nextRank['min_xp'] - ($currentRank['min_xp'] ?? 0))) * 100) : 100,
            ],
            'tense_stats' => $progress['tense_stats'] ?? [],
            'question_type_stats' => $progress['question_type_stats'] ?? [],
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

                case 'no_powerups':
                    $earned = empty($sessionData['powerups_used'] ?? []);
                    break;

                case 'tense_correct':
                    $count = 0;
                    foreach ($condition['tenses'] as $tense) {
                        $count += $progress['tense_stats'][$tense]['correct'] ?? 0;
                    }
                    $earned = $count >= $condition['value'];
                    break;

                case 'question_type_correct':
                    $typeStats = $progress['question_type_stats'][$condition['question_type']] ?? [];
                    $earned = ($typeStats['correct'] ?? 0) >= $condition['value'];
                    break;

                case 'daily_streak':
                    $earned = ($progress['daily_streak'] ?? 0) >= $condition['value'];
                    break;

                case 'total_questions':
                    $earned = ($progress['total_questions'] ?? 0) >= $condition['value'];
                    break;

                case 'all_tenses_practiced':
                    $allTenses = ['present_simple', 'present_continuous', 'present_perfect', 'present_perfect_continuous', 'past_simple', 'past_continuous', 'past_perfect', 'past_perfect_continuous', 'future_simple', 'future_continuous', 'future_perfect', 'going_to'];
                    $allPracticed = true;
                    foreach ($allTenses as $tense) {
                        if (($progress['tense_stats'][$tense]['correct'] ?? 0) < $condition['value']) {
                            $allPracticed = false;
                            break;
                        }
                    }
                    $earned = $allPracticed;
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
        Cache::forget('tense_race_config');
        Cache::forget('tense_race_levels');
        Cache::forget('tense_race_achievements');

        $tenses = ['present_simple', 'present_continuous', 'present_perfect', 'present_perfect_continuous', 'past_simple', 'past_continuous', 'past_perfect', 'past_perfect_continuous', 'future_simple', 'future_continuous', 'future_perfect', 'going_to'];
        foreach ($tenses as $tense) {
            Cache::forget("tense_race_questions_{$tense}");
        }
    }
}
