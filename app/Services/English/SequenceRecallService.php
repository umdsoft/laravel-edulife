<?php

namespace App\Services\English;

class SequenceRecallService
{
    protected SequenceRecallDataService $dataService;

    public function __construct(SequenceRecallDataService $dataService)
    {
        $this->dataService = $dataService;
    }

    public function startSession(int $levelNumber): array
    {
        $level = $this->dataService->getLevel($levelNumber);
        if (!$level) {
            throw new \Exception('Level not found');
        }

        if (!$this->dataService->isLevelUnlocked($levelNumber)) {
            throw new \Exception('Level is locked');
        }

        $sessionId = uniqid('sr_', true);
        $powerups = $this->dataService->getPowerups();
        $displaySettings = $this->dataService->getDisplaySettings();

        $availablePowerups = [];
        foreach ($powerups as $p) {
            $availablePowerups[$p['id']] = [
                'id' => $p['id'],
                'name' => $p['name'],
                'name_uz' => $p['name_uz'],
                'icon' => $p['icon'],
                'available' => true,
                'used' => false,
            ];
        }

        // Generate first sequence
        $firstSequence = $this->dataService->generateSequenceForLevel($levelNumber);

        $session = [
            'id' => $sessionId,
            'level_number' => $levelNumber,
            'level' => $level,
            'current_round' => 0,
            'total_rounds' => $level['rounds_count'],
            'score' => 0,
            'correct' => 0,
            'wrong' => 0,
            'streak' => 0,
            'best_streak' => 0,
            'started_at' => now()->timestamp,
            'answers' => [],
            'answer_times' => [],
            'fastest_answer' => null,
            'powerups_used' => [],
            'available_powerups' => $availablePowerups,
            'hints_used' => 0,
            'perfect_sequences' => 0,
            'longest_correct' => 0,
            'sequence_type_stats' => [],
            'current_sequence' => $firstSequence,
            'display_time_per_item' => $level['display_time_per_item'] ?? $displaySettings['item_display_time'],
            'answer_time' => $level['answer_time'],
            'completed' => false,
        ];

        $this->saveSession($sessionId, $session);

        return [
            'session_id' => $sessionId,
            'level' => $level,
            'total_rounds' => $level['rounds_count'],
            'sequence_length' => $level['sequence_length'],
            'sequence_type' => $level['sequence_type'],
            'display_time_per_item' => $session['display_time_per_item'],
            'answer_time' => $session['answer_time'],
            'first_sequence' => $this->prepareSequenceForDisplay($firstSequence),
        ];
    }

    protected function prepareSequenceForDisplay(array $sequence): array
    {
        return [
            'type' => $sequence['type'],
            'items' => $sequence['items'],
            'sequence_type' => $sequence['sequence_type'],
            'translation' => $sequence['translation'] ?? null,
        ];
    }

    public function checkAnswer(string $sessionId, array $userAnswer, float $answerTime): array
    {
        $session = $this->getSession($sessionId);
        if (!$session) {
            throw new \Exception('Session not found');
        }

        if ($session['completed']) {
            throw new \Exception('Session already completed');
        }

        $currentSequence = $session['current_sequence'];
        $correctAnswer = $this->getCorrectAnswer($currentSequence);

        // Compare answers
        $isCorrect = $userAnswer === $correctAnswer;
        $correctPositions = $this->countCorrectPositions($userAnswer, $correctAnswer);
        $isPerfect = $correctPositions === count($correctAnswer);

        $points = 0;

        if ($isCorrect) {
            $points = $this->calculatePoints($session, count($correctAnswer), $answerTime, $isPerfect);
            $session['correct']++;
            $session['streak']++;
            $session['best_streak'] = max($session['best_streak'], $session['streak']);

            if ($isPerfect) {
                $session['perfect_sequences']++;
            }

            if (count($correctAnswer) > $session['longest_correct']) {
                $session['longest_correct'] = count($correctAnswer);
            }
        } else {
            $session['wrong']++;
            $session['streak'] = 0;
        }

        $session['score'] += $points;
        $session['answers'][] = [
            'round' => $session['current_round'],
            'user_answer' => $userAnswer,
            'correct_answer' => $correctAnswer,
            'is_correct' => $isCorrect,
            'correct_positions' => $correctPositions,
            'points' => $points,
            'time' => $answerTime,
            'sequence_type' => $currentSequence['sequence_type'],
        ];

        $session['answer_times'][] = $answerTime;
        if ($isCorrect && ($session['fastest_answer'] === null || $answerTime < $session['fastest_answer'])) {
            $session['fastest_answer'] = $answerTime;
        }

        // Update sequence type stats
        $seqType = $currentSequence['sequence_type'];
        if (!isset($session['sequence_type_stats'][$seqType])) {
            $session['sequence_type_stats'][$seqType] = ['correct' => 0, 'total' => 0];
        }
        $session['sequence_type_stats'][$seqType]['total']++;
        if ($isCorrect) {
            $session['sequence_type_stats'][$seqType]['correct']++;
        }

        $session['current_round']++;

        $gameOver = $session['current_round'] >= $session['total_rounds'];
        $session['completed'] = $gameOver;

        // Generate next sequence if not game over
        $nextSequence = null;
        if (!$gameOver) {
            $newSequence = $this->dataService->generateSequenceForLevel($session['level_number']);
            $session['current_sequence'] = $newSequence;
            $nextSequence = $this->prepareSequenceForDisplay($newSequence);
        }

        $this->saveSession($sessionId, $session);

        return [
            'is_correct' => $isCorrect,
            'correct_answer' => $correctAnswer,
            'correct_positions' => $correctPositions,
            'points_earned' => $points,
            'total_score' => $session['score'],
            'streak' => $session['streak'],
            'best_streak' => $session['best_streak'],
            'current_round' => $session['current_round'],
            'game_over' => $gameOver,
            'next_sequence' => $nextSequence,
        ];
    }

    protected function getCorrectAnswer(array $sequence): array
    {
        $items = $sequence['items'];
        $type = $sequence['sequence_type'];

        switch ($type) {
            case 'reverse':
                return array_reverse($items);

            case 'alphabetical':
                $sorted = $items;
                sort($sorted, SORT_STRING | SORT_FLAG_CASE);
                return $sorted;

            case 'forward':
            default:
                return $items;
        }
    }

    protected function countCorrectPositions(array $userAnswer, array $correctAnswer): int
    {
        $correct = 0;
        $count = min(count($userAnswer), count($correctAnswer));

        for ($i = 0; $i < $count; $i++) {
            if (isset($userAnswer[$i]) && isset($correctAnswer[$i]) && $userAnswer[$i] === $correctAnswer[$i]) {
                $correct++;
            }
        }

        return $correct;
    }

    protected function calculatePoints(array $session, int $sequenceLength, float $answerTime, bool $isPerfect): int
    {
        $config = $this->dataService->getScoringConfig();
        $level = $session['level'];

        $basePoints = ($config['base_points_per_item'] ?? 10) * $sequenceLength;

        // Sequence length bonus
        $lengthBonuses = $config['sequence_length_bonus'] ?? [];
        $lengthMultiplier = $lengthBonuses[$sequenceLength] ?? 1.0;

        // Speed bonus
        $speedBonus = 0;
        $speedConfig = $config['speed_bonus'] ?? [];
        $maxBonus = $speedConfig['max_bonus'] ?? 20;
        $fastThreshold = $speedConfig['fast_threshold'] ?? 5;

        if ($answerTime <= $fastThreshold) {
            $speedBonus = $maxBonus;
        } elseif ($answerTime < $fastThreshold * 3) {
            $speedBonus = round($maxBonus * (1 - ($answerTime - $fastThreshold) / ($fastThreshold * 2)));
        }

        // Perfect sequence bonus
        $perfectMultiplier = $isPerfect ? ($config['perfect_sequence_bonus'] ?? 1.5) : 1.0;

        // Streak bonus
        $streakMultiplier = 1.0;
        $streakBonuses = $config['streak_bonuses'] ?? [];
        foreach ($streakBonuses as $threshold => $multiplier) {
            if ($session['streak'] >= (int)$threshold) {
                $streakMultiplier = $multiplier;
            }
        }

        // Level difficulty multiplier
        $difficultyMultipliers = $config['difficulty_multipliers'] ?? [];
        $levelMultiplier = $difficultyMultipliers[$level['difficulty']] ?? ($level['difficulty_multiplier'] ?? 1.0);

        // No hint bonus
        $noHintMultiplier = $session['hints_used'] === 0 ? ($config['no_hint_bonus'] ?? 1.2) : 1.0;

        $points = ($basePoints + $speedBonus) * $lengthMultiplier * $perfectMultiplier * $streakMultiplier * $levelMultiplier * $noHintMultiplier;

        return (int)round($points);
    }

    public function usePowerup(string $sessionId, string $powerupId): array
    {
        $session = $this->getSession($sessionId);
        if (!$session) {
            throw new \Exception('Session not found');
        }

        if ($session['completed']) {
            throw new \Exception('Session already completed');
        }

        if (!($session['available_powerups'][$powerupId]['available'] ?? false)) {
            throw new \Exception('Powerup not available');
        }

        $powerups = $this->dataService->getPowerups();
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

        $result = ['success' => true, 'powerup' => $powerup];
        $effect = $powerup['effect'];

        switch ($effect['type']) {
            case 'view_time':
                $result['effect'] = 'extra_view';
                $result['view_time'] = $effect['value'];
                $result['sequence'] = $this->prepareSequenceForDisplay($session['current_sequence']);
                break;

            case 'reveal_position':
                $session['hints_used']++;
                $correctAnswer = $this->getCorrectAnswer($session['current_sequence']);
                $randomPosition = array_rand($correctAnswer);
                $result['effect'] = 'position_revealed';
                $result['position'] = $randomPosition;
                $result['item'] = $correctAnswer[$randomPosition];
                break;

            case 'remove_wrong':
                $currentSequence = $session['current_sequence'];
                $allItems = $currentSequence['items'];
                $correctAnswer = $this->getCorrectAnswer($currentSequence);

                // Find items that are in wrong positions (for distraction)
                // This is simplified - just return the sequence with one less distractor option
                $result['effect'] = 'wrong_removed';
                break;

            case 'slow_speed':
                $session['display_time_per_item'] = (int)($session['display_time_per_item'] * $effect['multiplier']);
                $result['effect'] = 'slow_display';
                $result['new_display_time'] = $session['display_time_per_item'];
                break;

            case 'add_time':
                $result['effect'] = 'time_added';
                $result['value'] = $effect['value'];
                break;
        }

        $session['available_powerups'][$powerupId]['available'] = false;
        $session['available_powerups'][$powerupId]['used'] = true;
        $session['powerups_used'][] = $powerupId;

        $this->saveSession($sessionId, $session);

        return $result;
    }

    public function completeSession(string $sessionId): array
    {
        $session = $this->getSession($sessionId);
        if (!$session) {
            throw new \Exception('Session not found');
        }

        $session['completed'] = true;
        $session['completed_at'] = now()->timestamp;

        $totalRounds = $session['current_round'];
        $stars = $this->calculateStars($session);
        $rewards = $this->calculateRewards($session, $stars);

        $this->updateUserProgress($session, $stars, $rewards);

        $sessionData = [
            'correct' => $session['correct'],
            'total' => $totalRounds,
            'best_streak' => $session['best_streak'],
            'fastest_answer' => $session['fastest_answer'],
            'hints_used' => $session['hints_used'],
            'perfect_sequences' => $session['perfect_sequences'],
            'longest_correct' => $session['longest_correct'],
        ];
        $newAchievements = $this->dataService->checkAchievements($sessionData);

        $this->saveSession($sessionId, $session);

        return [
            'score' => $session['score'],
            'correct' => $session['correct'],
            'wrong' => $session['wrong'],
            'total_rounds' => $totalRounds,
            'accuracy' => $totalRounds > 0 ? round(($session['correct'] / $totalRounds) * 100) : 0,
            'best_streak' => $session['best_streak'],
            'fastest_answer' => $session['fastest_answer'],
            'perfect_sequences' => $session['perfect_sequences'],
            'stars' => $stars,
            'xp_earned' => $rewards['xp'],
            'coins_earned' => $rewards['coins'],
            'new_achievements' => $newAchievements,
            'is_new_best' => $rewards['is_new_best'] ?? false,
            'sequence_type_stats' => $session['sequence_type_stats'],
        ];
    }

    protected function calculateStars(array $session): int
    {
        $totalRounds = $session['current_round'];
        if ($totalRounds === 0) {
            return 0;
        }

        $accuracy = ($session['correct'] / $totalRounds) * 100;
        $rewardsConfig = $this->dataService->getRewardsConfig();
        $thresholds = $rewardsConfig['star_thresholds'] ?? [];

        if ($accuracy >= ($thresholds['three_stars'] ?? 90)) {
            return 3;
        } elseif ($accuracy >= ($thresholds['two_stars'] ?? 70)) {
            return 2;
        } elseif ($accuracy >= ($thresholds['one_star'] ?? 50)) {
            return 1;
        }

        return 0;
    }

    protected function calculateRewards(array $session, int $stars): array
    {
        $level = $session['level'];
        $rewardsConfig = $this->dataService->getRewardsConfig();

        $baseXp = $level['xp_reward'] ?? 50;
        $baseCoins = $level['coin_reward'] ?? 25;

        $starMultipliers = $rewardsConfig['star_multipliers'] ?? [];
        $starMultiplier = $starMultipliers[$stars] ?? 0.25;

        // Streak bonus
        $streakBonus = 1.0;
        if ($session['best_streak'] >= 8) {
            $streakBonus = 1.3;
        } elseif ($session['best_streak'] >= 5) {
            $streakBonus = 1.2;
        } elseif ($session['best_streak'] >= 3) {
            $streakBonus = 1.1;
        }

        // Perfect round bonus
        $perfectBonus = 1.0;
        if ($session['wrong'] === 0 && $session['correct'] >= 5) {
            $perfectBonus = $rewardsConfig['perfect_bonus'] ?? 1.5;
        }

        // No hint bonus
        $noHintBonus = 1.0;
        if ($session['hints_used'] === 0) {
            $noHintBonus = $rewardsConfig['no_hint_bonus'] ?? 1.2;
        }

        $xp = (int)round($baseXp * $starMultiplier * $streakBonus * $perfectBonus * $noHintBonus);
        $coins = (int)round($baseCoins * $starMultiplier * $streakBonus * $perfectBonus);

        return [
            'xp' => $xp,
            'coins' => $coins,
        ];
    }

    protected function updateUserProgress(array $session, int $stars, array $rewards): void
    {
        $progress = $this->dataService->getUserProgress();
        $levelNumber = $session['level_number'];

        $existingCompletion = $progress['levels_completed'][$levelNumber] ?? null;
        $isNewBest = !$existingCompletion || $session['score'] > ($existingCompletion['best_score'] ?? 0);

        $totalRounds = $session['current_round'];
        $accuracy = $totalRounds > 0 ? round(($session['correct'] / $totalRounds) * 100) : 0;

        $progress['levels_completed'][$levelNumber] = [
            'stars' => max($stars, $existingCompletion['stars'] ?? 0),
            'best_score' => max($session['score'], $existingCompletion['best_score'] ?? 0),
            'best_accuracy' => max($accuracy, $existingCompletion['best_accuracy'] ?? 0),
            'times_played' => ($existingCompletion['times_played'] ?? 0) + 1,
            'last_played' => now()->toISOString(),
        ];

        $progress['total_xp'] = ($progress['total_xp'] ?? 0) + $rewards['xp'];
        $progress['total_coins'] = ($progress['total_coins'] ?? 0) + $rewards['coins'];
        $progress['total_sequences'] = ($progress['total_sequences'] ?? 0) + $totalRounds;
        $progress['correct_sequences'] = ($progress['correct_sequences'] ?? 0) + $session['correct'];
        $progress['best_streak'] = max($progress['best_streak'] ?? 0, $session['best_streak']);
        $progress['games_played'] = ($progress['games_played'] ?? 0) + 1;
        $progress['hints_used'] = ($progress['hints_used'] ?? 0) + $session['hints_used'];
        $progress['perfect_sequences'] = ($progress['perfect_sequences'] ?? 0) + $session['perfect_sequences'];
        $progress['longest_sequence'] = max($progress['longest_sequence'] ?? 0, $session['longest_correct']);

        // Update daily streak
        $lastPlayed = $progress['last_played'] ?? null;
        $today = now()->toDateString();

        if ($lastPlayed) {
            $lastPlayedDate = \Carbon\Carbon::parse($lastPlayed)->toDateString();
            $yesterday = now()->subDay()->toDateString();

            if ($lastPlayedDate === $yesterday) {
                $progress['daily_streak'] = ($progress['daily_streak'] ?? 0) + 1;
            } elseif ($lastPlayedDate !== $today) {
                $progress['daily_streak'] = 1;
            }
        } else {
            $progress['daily_streak'] = 1;
        }
        $progress['last_played'] = $today;

        // Update sequence type stats
        if (!isset($progress['sequence_type_stats'])) {
            $progress['sequence_type_stats'] = [];
        }
        foreach ($session['sequence_type_stats'] as $type => $stats) {
            if (!isset($progress['sequence_type_stats'][$type])) {
                $progress['sequence_type_stats'][$type] = ['correct' => 0, 'total' => 0];
            }
            $progress['sequence_type_stats'][$type]['correct'] += $stats['correct'];
            $progress['sequence_type_stats'][$type]['total'] += $stats['total'];
        }

        $this->dataService->saveUserProgress($progress);
    }

    public function getSessionState(string $sessionId): ?array
    {
        $session = $this->getSession($sessionId);
        if (!$session) {
            return null;
        }

        return [
            'score' => $session['score'],
            'correct' => $session['correct'],
            'wrong' => $session['wrong'],
            'streak' => $session['streak'],
            'best_streak' => $session['best_streak'],
            'current_round' => $session['current_round'],
            'total_rounds' => $session['total_rounds'],
            'completed' => $session['completed'],
            'available_powerups' => $session['available_powerups'],
            'hints_used' => $session['hints_used'],
        ];
    }

    protected function getSession(string $sessionId): ?array
    {
        return session("sequence_recall_session_{$sessionId}");
    }

    protected function saveSession(string $sessionId, array $session): void
    {
        session(["sequence_recall_session_{$sessionId}" => $session]);
    }
}
