<?php

namespace App\Services\English;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class ListenChooseDataService
{
    protected string $dataPath;
    protected int $cacheTTL = 3600;

    public function __construct()
    {
        $this->dataPath = base_path('data/english/games/listen-choose');
    }

    public function getConfig(): array
    {
        return Cache::remember('listen_choose_config', $this->cacheTTL, function () {
            $path = $this->dataPath . '/config.json';
            if (File::exists($path)) {
                return json_decode(File::get($path), true);
            }
            return [];
        });
    }

    public function getLevels(): array
    {
        return Cache::remember('listen_choose_levels', $this->cacheTTL, function () {
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
        return Cache::remember('listen_choose_achievements', $this->cacheTTL, function () {
            $path = $this->dataPath . '/achievements.json';
            if (File::exists($path)) {
                $data = json_decode(File::get($path), true);
                return $data['achievements'] ?? [];
            }
            return [];
        });
    }

    public function getAudioItemsByCategory(string $category): array
    {
        return Cache::remember("listen_choose_audio_{$category}", $this->cacheTTL, function () use ($category) {
            $path = $this->dataPath . "/audio/{$category}.json";
            if (File::exists($path)) {
                $data = json_decode(File::get($path), true);
                return $data['items'] ?? [];
            }
            return [];
        });
    }

    public function getItemsForLevel(int $levelNumber): array
    {
        $level = $this->getLevel($levelNumber);
        if (!$level) {
            return [];
        }

        $items = [];
        foreach ($level['categories'] as $category) {
            $categoryItems = $this->getAudioItemsByCategory($category);
            foreach ($categoryItems as $item) {
                // Filter by question type if level specifies
                if (in_array($item['type'], $level['question_types'])) {
                    $item['category'] = $category;
                    $items[] = $item;
                }
            }
        }

        shuffle($items);
        return array_slice($items, 0, $level['questions_count']);
    }

    public function getGameModes(): array
    {
        $config = $this->getConfig();
        return $config['game_modes'] ?? [];
    }

    public function getQuestionTypes(): array
    {
        $config = $this->getConfig();
        return $config['question_types'] ?? [];
    }

    public function getPowerups(): array
    {
        $config = $this->getConfig();
        return $config['powerups'] ?? [];
    }

    public function getListeningRanks(): array
    {
        $config = $this->getConfig();
        return $config['listening_ranks'] ?? [];
    }

    public function getAudioCategories(): array
    {
        $config = $this->getConfig();
        return $config['audio_categories'] ?? [];
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

    public function getAudioSettings(): array
    {
        $config = $this->getConfig();
        return $config['audio_settings'] ?? [];
    }

    public function getUserProgress(): array
    {
        $userId = auth()->id() ?? 'guest';
        return session("listen_choose_progress_{$userId}", [
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
            'category_stats' => [],
            'question_type_stats' => [],
            'total_replays_used' => 0,
            'first_try_correct' => 0,
        ]);
    }

    public function saveUserProgress(array $progress): void
    {
        $userId = auth()->id() ?? 'guest';
        session(["listen_choose_progress_{$userId}" => $progress]);
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
        $ranks = $this->getListeningRanks();

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

        $firstTryRate = 0;
        if (($progress['total_questions'] ?? 0) > 0) {
            $firstTryRate = round(($progress['first_try_correct'] ?? 0) / $progress['total_questions'] * 100);
        }

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
            'total_replays_used' => $progress['total_replays_used'] ?? 0,
            'first_try_rate' => $firstTryRate,
            'listening_rank' => [
                'current' => $currentRank,
                'next' => $nextRank,
                'xp_to_next' => $nextRank ? ($nextRank['min_xp'] - $totalXp) : 0,
                'progress' => $nextRank ? min(100, (($totalXp - ($currentRank['min_xp'] ?? 0)) / ($nextRank['min_xp'] - ($currentRank['min_xp'] ?? 0))) * 100) : 100,
            ],
            'category_stats' => $progress['category_stats'] ?? [],
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

                case 'no_replays':
                    $earned = ($sessionData['replays_used'] ?? 1) === 0;
                    break;

                case 'category_correct':
                    $count = $progress['category_stats'][$condition['category']]['correct'] ?? 0;
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
        Cache::forget('listen_choose_config');
        Cache::forget('listen_choose_levels');
        Cache::forget('listen_choose_achievements');

        $categories = ['basic_words', 'everyday_phrases', 'numbers_dates', 'similar_sounds', 'fast_speech', 'accents'];
        foreach ($categories as $category) {
            Cache::forget("listen_choose_audio_{$category}");
        }
    }
}
