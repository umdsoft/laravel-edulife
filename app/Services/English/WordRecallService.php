<?php

namespace App\Services\English;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class WordRecallService
{
    protected WordRecallDataService $dataService;
    protected string $sessionsPath;

    public function __construct(WordRecallDataService $dataService)
    {
        $this->dataService = $dataService;
        $this->sessionsPath = storage_path('app/game_sessions/word_recall');

        if (!File::exists($this->sessionsPath)) {
            File::makeDirectory($this->sessionsPath, 0755, true);
        }
    }

    public function startSession(int $levelNumber, string $mode = 'classic'): array
    {
        $level = $this->dataService->getLevel($levelNumber);

        if (!$level) {
            throw new \Exception('Level not found');
        }

        if (!$this->dataService->isLevelUnlocked($levelNumber)) {
            throw new \Exception('Level is locked');
        }

        $config = $this->dataService->getConfig();
        $gameModes = $config['game_modes'] ?? [];
        $selectedMode = null;

        foreach ($gameModes as $gm) {
            if ($gm['id'] === $mode) {
                $selectedMode = $gm;
                break;
            }
        }

        if (!$selectedMode) {
            $selectedMode = $gameModes[0] ?? ['id' => 'classic', 'display_time' => 3, 'recall_time' => 30];
        }

        $sessionId = Str::uuid()->toString();
        $session = [
            'id' => $sessionId,
            'user_id' => Auth::id(),
            'level_number' => $levelNumber,
            'level' => $level,
            'mode' => $selectedMode,
            'current_round' => 0,
            'total_rounds' => $level['rounds'] ?? 5,
            'words_per_round' => $level['words_per_round'] ?? 3,
            'score' => 0,
            'correct_words' => 0,
            'wrong_words' => 0,
            'perfect_rounds' => 0,
            'streak' => 0,
            'best_streak' => 0,
            'lives' => $level['lives'] ?? 3,
            'display_time' => $selectedMode['display_time'] ?? $level['display_time'] ?? 3,
            'recall_time' => $selectedMode['recall_time'] ?? $level['recall_time'] ?? 30,
            'current_words' => [],
            'rounds_data' => [],
            'powerups_used' => [],
            'double_points_active' => false,
            'started_at' => now()->toISOString(),
            'completed' => false,
        ];

        $this->saveSession($session);

        return [
            'session_id' => $sessionId,
            'level' => $level,
            'mode' => $selectedMode,
            'total_rounds' => $session['total_rounds'],
            'words_per_round' => $session['words_per_round'],
            'display_time' => $session['display_time'],
            'recall_time' => $session['recall_time'],
            'lives' => $session['lives'],
        ];
    }

    public function getNextRound(string $sessionId): array
    {
        $session = $this->getSession($sessionId);

        if (!$session) {
            throw new \Exception('Session not found');
        }

        if ($session['current_round'] >= $session['total_rounds']) {
            throw new \Exception('No more rounds');
        }

        $level = $session['level'];
        $wordsCount = $session['words_per_round'];

        // Get words for this round
        $words = $this->dataService->getWordsForRound($level, $wordsCount);
        $session['current_words'] = $words;
        $session['current_round']++;

        $this->saveSession($session);

        // Prepare words for display
        $displayWords = array_map(fn($w) => ['word' => $w['word']], $words);

        // For reverse mode, they need to recall in reverse
        // For random mode, we'll shuffle positions during recall
        $mode = $session['mode'];

        return [
            'round' => $session['current_round'],
            'total_rounds' => $session['total_rounds'],
            'words' => $displayWords,
            'display_time' => $session['display_time'],
            'recall_time' => $session['recall_time'],
            'words_count' => count($words),
            'reverse_order' => $mode['reverse_order'] ?? false,
            'random_positions' => $mode['random_positions'] ?? false,
        ];
    }

    public function checkRound(string $sessionId, array $userAnswers, float $timeSpent): array
    {
        $session = $this->getSession($sessionId);

        if (!$session) {
            throw new \Exception('Session not found');
        }

        $currentWords = $session['current_words'];
        $mode = $session['mode'];
        $config = $this->dataService->getConfig();
        $scoring = $config['scoring'] ?? [];

        // Prepare expected order
        $expectedWords = array_map(fn($w) => strtolower(trim($w['word'])), $currentWords);

        if ($mode['reverse_order'] ?? false) {
            $expectedWords = array_reverse($expectedWords);
        }

        // Compare answers
        $correctCount = 0;
        $wordResults = [];

        for ($i = 0; $i < count($expectedWords); $i++) {
            $expected = $expectedWords[$i];
            $userAnswer = isset($userAnswers[$i]) ? strtolower(trim($userAnswers[$i])) : '';
            $isCorrect = $expected === $userAnswer;

            if ($isCorrect) {
                $correctCount++;
            }

            $wordResults[] = [
                'expected' => $currentWords[$mode['reverse_order'] ?? false ? count($currentWords) - 1 - $i : $i]['word'],
                'user_answer' => $userAnswers[$i] ?? '',
                'is_correct' => $isCorrect,
            ];
        }

        $isPerfect = $correctCount === count($expectedWords);
        $points = 0;
        $xpEarned = 0;
        $coinsEarned = 0;

        // Calculate points
        $basePoints = $correctCount * ($scoring['base_points_per_word'] ?? 50);

        if ($isPerfect) {
            $basePoints += $scoring['sequence_bonus'] ?? 100;
            $basePoints += $scoring['perfect_round_bonus'] ?? 200;
            $session['perfect_rounds']++;
            $session['streak']++;

            if ($session['streak'] > $session['best_streak']) {
                $session['best_streak'] = $session['streak'];
            }
        } else {
            $session['streak'] = 0;
            $session['lives']--;
        }

        // Time bonus
        $timeBonus = max(0, ($scoring['time_bonus_max'] ?? 75) * (1 - $timeSpent / $session['recall_time']));
        $basePoints += (int)$timeBonus;

        // Streak multiplier
        $streakMultiplier = 1 + ($session['streak'] * ($scoring['streak_multiplier'] ?? 0.2));
        $points = (int)($basePoints * $streakMultiplier);

        // Difficulty multiplier
        $diffKey = $session['words_per_round'] . '_words';
        $diffMultiplier = $scoring['difficulty_multipliers'][$diffKey] ?? 1.0;
        $points = (int)($points * $diffMultiplier);

        // Double points powerup
        if ($session['double_points_active']) {
            $points *= 2;
            $session['double_points_active'] = false;
        }

        // XP
        $xpRewards = $config['xp_rewards'] ?? [];
        $xpEarned = $correctCount * ($xpRewards['correct_word'] ?? 10);
        if ($isPerfect) {
            $xpEarned += $xpRewards['perfect_round'] ?? 75;
        }
        if ($session['streak'] >= 3) {
            $xpEarned += $xpRewards['streak_bonus'] ?? 15;
        }

        // Coins
        $coinRewards = $config['coin_rewards'] ?? [];
        $coinsEarned = $correctCount * ($coinRewards['correct_word'] ?? 2);
        if ($isPerfect) {
            $coinsEarned += $coinRewards['perfect_round'] ?? 15;
        }

        $session['score'] += $points;
        $session['correct_words'] += $correctCount;
        $session['wrong_words'] += count($expectedWords) - $correctCount;

        // Record round data
        $session['rounds_data'][] = [
            'round' => $session['current_round'],
            'words' => $currentWords,
            'user_answers' => $userAnswers,
            'word_results' => $wordResults,
            'correct_count' => $correctCount,
            'is_perfect' => $isPerfect,
            'time_spent' => $timeSpent,
            'points_earned' => $points,
        ];

        $hasMore = $session['current_round'] < $session['total_rounds'] && $session['lives'] > 0;
        $gameOver = $session['lives'] <= 0;

        $this->saveSession($session);

        return [
            'is_perfect' => $isPerfect,
            'correct_count' => $correctCount,
            'total_words' => count($expectedWords),
            'word_results' => $wordResults,
            'points_earned' => $points,
            'xp_earned' => $xpEarned,
            'coins_earned' => $coinsEarned,
            'current_score' => $session['score'],
            'streak' => $session['streak'],
            'lives' => $session['lives'],
            'has_more' => $hasMore,
            'game_over' => $gameOver,
            'progress' => [
                'current' => $session['current_round'],
                'total' => $session['total_rounds'],
            ],
        ];
    }

    public function usePowerup(string $sessionId, string $powerupId): array
    {
        $session = $this->getSession($sessionId);

        if (!$session) {
            throw new \Exception('Session not found');
        }

        $config = $this->dataService->getConfig();
        $powerups = $config['powerups'] ?? [];

        $powerup = null;
        foreach ($powerups as $p) {
            if ($p['id'] === $powerupId) {
                $powerup = $p;
                break;
            }
        }

        if (!$powerup) {
            throw new \Exception('Powerup not found');
        }

        $usedCount = $session['powerups_used'][$powerupId] ?? 0;
        if ($usedCount >= ($powerup['uses_per_game'] ?? 1)) {
            throw new \Exception('Powerup limit reached');
        }

        $result = ['powerup_id' => $powerupId, 'effect' => $powerup['effect']];

        switch ($powerup['effect']) {
            case 'add_time':
                $result['extra_time'] = $powerup['value'] ?? 10;
                break;

            case 'slow_display':
                $session['display_time'] = ($session['display_time'] ?? 3) + 1;
                $result['new_display_time'] = $session['display_time'];
                break;

            case 'show_first_letters':
                $hints = array_map(fn($w) => substr($w['word'], 0, 1) . str_repeat('_', strlen($w['word']) - 1), $session['current_words']);
                $result['hints'] = $hints;
                break;

            case 'skip':
                $session['current_round']++;
                $hasMore = $session['current_round'] < $session['total_rounds'];
                $result['skipped'] = true;
                $result['has_more'] = $hasMore;
                break;

            case 'add_life':
                $session['lives']++;
                $result['lives'] = $session['lives'];
                break;

            case 'double_points':
                $session['double_points_active'] = true;
                $result['double_points_active'] = true;
                break;
        }

        $session['powerups_used'][$powerupId] = $usedCount + 1;
        $this->saveSession($session);

        return $result;
    }

    public function completeSession(string $sessionId): array
    {
        $session = $this->getSession($sessionId);

        if (!$session) {
            throw new \Exception('Session not found');
        }

        $session['completed'] = true;
        $session['completed_at'] = now()->toISOString();

        $config = $this->dataService->getConfig();
        $starThresholds = $config['star_thresholds'] ?? [];
        $xpRewards = $config['xp_rewards'] ?? [];
        $coinRewards = $config['coin_rewards'] ?? [];

        $totalWords = $session['correct_words'] + $session['wrong_words'];
        $accuracy = $totalWords > 0 ? round(($session['correct_words'] / $totalWords) * 100) : 0;

        // Stars
        $stars = 0;
        if ($accuracy >= ($starThresholds['three_stars'] ?? 95)) {
            $stars = 3;
        } elseif ($accuracy >= ($starThresholds['two_stars'] ?? 80)) {
            $stars = 2;
        } elseif ($accuracy >= ($starThresholds['one_star'] ?? 60)) {
            $stars = 1;
        }

        // XP
        $xpEarned = $xpRewards['level_complete'] ?? 200;
        if ($stars === 3) {
            $xpEarned += $xpRewards['three_stars'] ?? 250;
        }
        if ($accuracy === 100) {
            $xpEarned += $xpRewards['no_mistakes'] ?? 200;
        }

        // Coins
        $coinsEarned = $coinRewards['level_complete'] ?? 50;
        if ($stars === 3) {
            $coinsEarned += $coinRewards['three_stars'] ?? 100;
        }

        // Update progress
        $progress = $this->dataService->getUserProgress();
        $levelId = $session['level']['id'];
        $isFirst = !isset($progress['levels'][$levelId]) || !$progress['levels'][$levelId]['completed'];

        if ($isFirst) {
            $xpEarned += $xpRewards['first_time_complete'] ?? 400;
        }

        $existing = $progress['levels'][$levelId] ?? [];
        $progress['levels'][$levelId] = [
            'completed' => true,
            'stars' => max($stars, $existing['stars'] ?? 0),
            'best_score' => max($session['score'], $existing['best_score'] ?? 0),
            'best_accuracy' => max($accuracy, $existing['best_accuracy'] ?? 0),
            'attempts' => ($existing['attempts'] ?? 0) + 1,
            'last_played' => now()->toISOString(),
        ];

        $progress['total_xp'] = ($progress['total_xp'] ?? 0) + $xpEarned;
        $progress['total_coins'] = ($progress['total_coins'] ?? 0) + $coinsEarned;
        $progress['total_words_correct'] = ($progress['total_words_correct'] ?? 0) + $session['correct_words'];
        $progress['total_rounds_completed'] = ($progress['total_rounds_completed'] ?? 0) + count($session['rounds_data']);
        $progress['perfect_rounds'] = ($progress['perfect_rounds'] ?? 0) + $session['perfect_rounds'];
        $progress['games_played'] = ($progress['games_played'] ?? 0) + 1;

        if ($session['best_streak'] > ($progress['best_streak'] ?? 0)) {
            $progress['best_streak'] = $session['best_streak'];
        }

        if ($session['words_per_round'] > ($progress['max_words_round'] ?? 0)) {
            $progress['max_words_round'] = $session['words_per_round'];
        }

        // Daily streak
        $lastPlayed = $progress['last_played'] ?? null;
        $today = now()->toDateString();
        if ($lastPlayed) {
            $lastDate = date('Y-m-d', strtotime($lastPlayed));
            $yesterday = now()->subDay()->toDateString();
            if ($lastDate === $yesterday) {
                $progress['daily_streak'] = ($progress['daily_streak'] ?? 0) + 1;
            } elseif ($lastDate !== $today) {
                $progress['daily_streak'] = 1;
            }
        } else {
            $progress['daily_streak'] = 1;
        }
        $progress['last_played'] = now()->toISOString();

        // Achievements
        $newAchievements = $this->checkAchievements($progress, $session);
        foreach ($newAchievements as $ach) {
            if (!in_array($ach['id'], $progress['achievements'] ?? [])) {
                $progress['achievements'][] = $ach['id'];
                $xpEarned += $ach['xp_reward'] ?? 0;
                $coinsEarned += $ach['coin_reward'] ?? 0;
            }
        }

        $this->dataService->saveUserProgress($progress);
        $this->saveSession($session);

        // Rank
        $ranks = $this->dataService->getMemoryMasterRanks();
        $currentRank = null;
        foreach ($ranks as $r) {
            if ($progress['total_xp'] >= $r['xp_required']) {
                $currentRank = $r;
            }
        }

        return [
            'score' => $session['score'],
            'correct_words' => $session['correct_words'],
            'wrong_words' => $session['wrong_words'],
            'accuracy' => $accuracy,
            'stars' => $stars,
            'perfect_rounds' => $session['perfect_rounds'],
            'best_streak' => $session['best_streak'],
            'xp_earned' => $xpEarned,
            'coins_earned' => $coinsEarned,
            'total_xp' => $progress['total_xp'],
            'is_first_complete' => $isFirst,
            'new_achievements' => $newAchievements,
            'current_rank' => $currentRank,
        ];
    }

    protected function checkAchievements(array $progress, array $session): array
    {
        $all = $this->dataService->getAllAchievements();
        $unlocked = $progress['achievements'] ?? [];
        $new = [];

        foreach ($all as $ach) {
            if (in_array($ach['id'], $unlocked)) continue;

            $condition = $ach['condition'] ?? '';
            if (preg_match('/(\w+)\s*>=\s*(\d+)/', $condition, $m)) {
                $field = $m[1];
                $target = (int)$m[2];
                $current = 0;

                switch ($field) {
                    case 'rounds_completed':
                        $current = $progress['total_rounds_completed'] ?? 0;
                        break;
                    case 'perfect_rounds':
                        $current = $progress['perfect_rounds'] ?? 0;
                        break;
                    case 'total_words_correct':
                        $current = $progress['total_words_correct'] ?? 0;
                        break;
                    case 'streak':
                        $current = max($progress['best_streak'] ?? 0, $session['best_streak'] ?? 0);
                        break;
                    case 'three_star_levels':
                        $current = count(array_filter($progress['levels'] ?? [], fn($l) => ($l['stars'] ?? 0) >= 3));
                        break;
                    case 'levels_completed':
                        $current = count(array_filter($progress['levels'] ?? [], fn($l) => $l['completed'] ?? false));
                        break;
                    case 'max_words_round':
                        $current = max($progress['max_words_round'] ?? 0, $session['words_per_round'] ?? 0);
                        break;
                    case 'daily_streak':
                        $current = $progress['daily_streak'] ?? 0;
                        break;
                }

                if ($current >= $target) {
                    $new[] = $ach;
                }
            }
        }

        return $new;
    }

    public function getSessionState(string $sessionId): ?array
    {
        $session = $this->getSession($sessionId);
        if (!$session) return null;

        return [
            'session_id' => $sessionId,
            'level' => $session['level'],
            'current_round' => $session['current_round'],
            'total_rounds' => $session['total_rounds'],
            'score' => $session['score'],
            'lives' => $session['lives'],
            'streak' => $session['streak'],
            'completed' => $session['completed'],
        ];
    }

    protected function saveSession(array $session): void
    {
        $path = $this->sessionsPath . '/' . $session['id'] . '.json';
        File::put($path, json_encode($session, JSON_PRETTY_PRINT));
    }

    protected function getSession(string $sessionId): ?array
    {
        $path = $this->sessionsPath . '/' . $sessionId . '.json';
        if (!File::exists($path)) return null;
        return json_decode(File::get($path), true);
    }
}
