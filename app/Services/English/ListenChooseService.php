<?php

namespace App\Services\English;

class ListenChooseService
{
    protected ListenChooseDataService $dataService;
    protected GameScoringService $scoringService;

    public function __construct(ListenChooseDataService $dataService, GameScoringService $scoringService)
    {
        $this->dataService = $dataService;
        $this->scoringService = $scoringService;
    }

    public function startSession(int $levelNumber, string $gameMode = 'word'): array
    {
        $level = $this->dataService->getLevel($levelNumber);
        if (!$level) {
            throw new \Exception('Level not found');
        }

        if (!$this->dataService->isLevelUnlocked($levelNumber)) {
            throw new \Exception('Level is locked');
        }

        $items = $this->dataService->getItemsForLevel($levelNumber);
        if (empty($items)) {
            throw new \Exception('No audio items available');
        }

        $sessionId = uniqid('lc_', true);
        $powerups = $this->dataService->getPowerups();
        $audioSettings = $this->dataService->getAudioSettings();

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

        $session = [
            'id' => $sessionId,
            'level_number' => $levelNumber,
            'level' => $level,
            'game_mode' => $gameMode,
            'items' => $items,
            'current_index' => 0,
            'score' => 0,
            'correct' => 0,
            'wrong' => 0,
            'streak' => 0,
            'best_streak' => 0,
            'time_limit' => $level['time_limit'],
            'time_remaining' => $level['time_limit'],
            'started_at' => now()->timestamp,
            'answers' => [],
            'answer_times' => [],
            'fastest_answer' => null,
            'powerups_used' => [],
            'available_powerups' => $availablePowerups,
            'replays_used' => 0,
            'current_replays' => 0,
            'max_replays' => $level['max_replays'] ?? $audioSettings['max_replays_per_question'],
            'slow_speed_used' => false,
            'transcript_shown' => false,
            'first_try_correct' => 0,
            'category_stats' => [],
            'question_type_stats' => [],
            'completed' => false,
        ];

        $this->saveSession($sessionId, $session);

        return [
            'session_id' => $sessionId,
            'level' => $level,
            'total_questions' => count($items),
            'time_limit' => $level['time_limit'],
            'max_replays' => $session['max_replays'],
            'first_item' => $this->prepareItem($items[0]),
        ];
    }

    public function getCurrentItem(string $sessionId): ?array
    {
        $session = $this->getSession($sessionId);
        if (!$session || $session['completed']) {
            return null;
        }

        $currentIndex = $session['current_index'];
        if ($currentIndex >= count($session['items'])) {
            return null;
        }

        return $this->prepareItem($session['items'][$currentIndex]);
    }

    protected function prepareItem(array $item): array
    {
        $prepared = [
            'id' => $item['id'],
            'type' => $item['type'],
            'audio_text' => $item['audio_text'],
            'options' => $item['options'],
            'category' => $item['category'] ?? null,
            'difficulty' => $item['difficulty'],
            'accent' => $item['accent'] ?? null,
        ];

        // Add specific fields based on type
        if (isset($item['question'])) {
            $prepared['question'] = $item['question'];
        }
        if (isset($item['sentence'])) {
            $prepared['sentence'] = $item['sentence'];
        }

        return $prepared;
    }

    public function checkAnswer(string $sessionId, string $itemId, int $answerIndex, float $answerTime, bool $usedReplay = false): array
    {
        $session = $this->getSession($sessionId);
        if (!$session) {
            throw new \Exception('Session not found');
        }

        if ($session['completed']) {
            throw new \Exception('Session already completed');
        }

        $currentItem = $session['items'][$session['current_index']] ?? null;
        if (!$currentItem || $currentItem['id'] !== $itemId) {
            throw new \Exception('Invalid item');
        }

        $isCorrect = $answerIndex === $currentItem['correct'];
        $isFirstTry = $session['current_replays'] === 0 && !$session['slow_speed_used'] && !$session['transcript_shown'];
        $points = 0;

        if ($isCorrect) {
            $points = $this->calculatePoints($session, $currentItem, $answerTime, $isFirstTry);
            $session['correct']++;
            $session['streak']++;
            $session['best_streak'] = max($session['best_streak'], $session['streak']);
            if ($isFirstTry) {
                $session['first_try_correct']++;
            }
        } else {
            $session['wrong']++;
            $session['streak'] = 0;
        }

        $session['score'] += $points;
        $session['answers'][] = [
            'item_id' => $itemId,
            'answer' => $answerIndex,
            'correct' => $currentItem['correct'],
            'is_correct' => $isCorrect,
            'points' => $points,
            'time' => $answerTime,
            'category' => $currentItem['category'] ?? null,
            'type' => $currentItem['type'],
            'replays_used' => $session['current_replays'],
            'first_try' => $isFirstTry,
        ];

        $session['answer_times'][] = $answerTime;
        if ($isCorrect && ($session['fastest_answer'] === null || $answerTime < $session['fastest_answer'])) {
            $session['fastest_answer'] = $answerTime;
        }

        // Update category stats
        $category = $currentItem['category'] ?? 'unknown';
        if (!isset($session['category_stats'][$category])) {
            $session['category_stats'][$category] = ['correct' => 0, 'total' => 0];
        }
        $session['category_stats'][$category]['total']++;
        if ($isCorrect) {
            $session['category_stats'][$category]['correct']++;
        }

        // Update question type stats
        $qType = $currentItem['type'];
        if (!isset($session['question_type_stats'][$qType])) {
            $session['question_type_stats'][$qType] = ['correct' => 0, 'total' => 0];
        }
        $session['question_type_stats'][$qType]['total']++;
        if ($isCorrect) {
            $session['question_type_stats'][$qType]['correct']++;
        }

        // Reset per-question states
        $session['current_replays'] = 0;
        $session['slow_speed_used'] = false;
        $session['transcript_shown'] = false;

        $session['current_index']++;

        $gameOver = $session['current_index'] >= count($session['items']);
        $session['completed'] = $gameOver;

        $this->saveSession($sessionId, $session);

        $result = [
            'is_correct' => $isCorrect,
            'correct_answer' => $currentItem['correct'],
            'transcript' => $currentItem['transcript'] ?? $currentItem['audio_text'],
            'hint' => $currentItem['hint'] ?? null,
            'points_earned' => $points,
            'total_score' => $session['score'],
            'streak' => $session['streak'],
            'best_streak' => $session['best_streak'],
            'questions_answered' => $session['current_index'],
            'game_over' => $gameOver,
        ];

        if (!$gameOver && $session['current_index'] < count($session['items'])) {
            $result['next_item'] = $this->prepareItem($session['items'][$session['current_index']]);
        }

        return $result;
    }

    protected function calculatePoints(array $session, array $item, float $answerTime, bool $isFirstTry): int
    {
        $config = $this->dataService->getScoringConfig();
        $level = $session['level'];

        $basePoints = $config['base_points'] ?? 10;

        // Difficulty multiplier
        $difficultyMultipliers = $config['difficulty_multipliers'] ?? [];
        $difficultyMultiplier = $difficultyMultipliers[$item['difficulty']] ?? 1.0;

        // Time bonus
        $timeBonus = 0;
        $timeBonusConfig = $config['time_bonus'] ?? [];
        $maxTimeBonus = $timeBonusConfig['max_bonus'] ?? 10;
        $fastThreshold = $timeBonusConfig['fast_threshold'] ?? 3;

        if ($answerTime <= $fastThreshold) {
            $timeBonus = $maxTimeBonus;
        } elseif ($answerTime < 10) {
            $timeBonus = round($maxTimeBonus * (1 - ($answerTime - $fastThreshold) / (10 - $fastThreshold)));
        }

        // Streak bonus
        $streakMultiplier = 1.0;
        $streakBonuses = $config['streak_bonuses'] ?? [];
        foreach ($streakBonuses as $threshold => $multiplier) {
            if ($session['streak'] >= (int)$threshold) {
                $streakMultiplier = $multiplier;
            }
        }

        // Level difficulty multiplier
        $levelMultiplier = $level['difficulty_multiplier'] ?? 1.0;

        // First try bonus
        $firstTryMultiplier = $isFirstTry ? ($config['first_try_bonus'] ?? 1.2) : 1.0;

        // Penalty for using replay
        $replayPenalty = 1.0;
        if ($session['current_replays'] > 0) {
            $replayPenalty = pow($config['replay_penalty'] ?? 0.8, $session['current_replays']);
        }

        // Penalty for slow speed
        $slowSpeedPenalty = $session['slow_speed_used'] ? ($config['slow_speed_penalty'] ?? 0.9) : 1.0;

        // Penalty for showing transcript
        $transcriptPenalty = $session['transcript_shown'] ? ($config['transcript_penalty'] ?? 0.5) : 1.0;

        $points = ($basePoints + $timeBonus) * $difficultyMultiplier * $streakMultiplier * $levelMultiplier * $firstTryMultiplier * $replayPenalty * $slowSpeedPenalty * $transcriptPenalty;

        return (int)round($points);
    }

    public function useReplay(string $sessionId): array
    {
        $session = $this->getSession($sessionId);
        if (!$session) {
            throw new \Exception('Session not found');
        }

        if ($session['completed']) {
            throw new \Exception('Session already completed');
        }

        if ($session['current_replays'] >= $session['max_replays']) {
            throw new \Exception('Maximum replays reached');
        }

        $session['current_replays']++;
        $session['replays_used']++;

        $this->saveSession($sessionId, $session);

        return [
            'success' => true,
            'replays_remaining' => $session['max_replays'] - $session['current_replays'],
            'total_replays_used' => $session['replays_used'],
        ];
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
            case 'replay':
                $session['current_replays'] = max(0, $session['current_replays'] - $effect['value']);
                $result['effect'] = 'extra_replay';
                $result['replays_remaining'] = $session['max_replays'] - $session['current_replays'];
                break;

            case 'slow_speed':
                $session['slow_speed_used'] = true;
                $result['effect'] = 'slow_speed';
                $result['speed'] = $effect['value'];
                break;

            case 'eliminate':
                $currentItem = $session['items'][$session['current_index']] ?? null;
                if ($currentItem) {
                    $correctIndex = $currentItem['correct'];
                    $wrongIndices = array_diff([0, 1, 2, 3], [$correctIndex]);
                    shuffle($wrongIndices);
                    $eliminated = array_slice($wrongIndices, 0, $effect['value']);
                    $result['eliminated_indices'] = $eliminated;
                    $result['effect'] = 'options_eliminated';
                }
                break;

            case 'show_transcript':
                $session['transcript_shown'] = true;
                $currentItem = $session['items'][$session['current_index']] ?? null;
                if ($currentItem) {
                    $result['transcript'] = $currentItem['transcript'] ?? $currentItem['audio_text'];
                    $result['effect'] = 'transcript_shown';
                }
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

    public function completeSession(string $sessionId, float $finalTime = null): array
    {
        $session = $this->getSession($sessionId);
        if (!$session) {
            throw new \Exception('Session not found');
        }

        $session['completed'] = true;
        $session['completed_at'] = now()->timestamp;
        $session['final_time'] = $finalTime;

        $totalAnswered = $session['current_index'];
        $stars = $this->calculateStars($session);
        $rewards = $this->calculateRewards($session, $stars);

        $this->updateUserProgress($session, $stars, $rewards);

        $sessionData = [
            'correct' => $session['correct'],
            'total' => $totalAnswered,
            'best_streak' => $session['best_streak'],
            'fastest_answer' => $session['fastest_answer'],
            'replays_used' => $session['replays_used'],
            'powerups_used' => $session['powerups_used'],
        ];
        $newAchievements = $this->dataService->checkAchievements($sessionData);

        $this->saveSession($sessionId, $session);

        return [
            'score' => $session['score'],
            'correct' => $session['correct'],
            'wrong' => $session['wrong'],
            'total_questions' => $totalAnswered,
            'accuracy' => $totalAnswered > 0 ? round(($session['correct'] / $totalAnswered) * 100) : 0,
            'best_streak' => $session['best_streak'],
            'fastest_answer' => $session['fastest_answer'],
            'stars' => $stars,
            'xp_earned' => $rewards['xp'],
            'coins_earned' => $rewards['coins'],
            'new_achievements' => $newAchievements,
            'is_new_best' => $rewards['is_new_best'] ?? false,
            'replays_used' => $session['replays_used'],
            'first_try_rate' => $totalAnswered > 0 ? round(($session['first_try_correct'] / $totalAnswered) * 100) : 0,
            'category_stats' => $session['category_stats'],
            'question_type_stats' => $session['question_type_stats'],
        ];
    }

    protected function calculateStars(array $session): int
    {
        $totalAnswered = $session['current_index'];
        if ($totalAnswered === 0) {
            return 0;
        }

        $accuracy = ($session['correct'] / $totalAnswered) * 100;
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
        $totalAnswered = $session['current_index'];
        $isPerfect = $session['wrong'] === 0 && $session['correct'] >= 5;

        $progress = $this->dataService->getUserProgress();
        $levelNumber = $session['level_number'];
        $isFirstCompletion = !isset($progress['levels_completed'][$levelNumber]);

        $user = auth()->user();

        $rewards = $this->scoringService->calculateSessionRewards([
            'correct' => $session['correct'],
            'total' => $totalAnswered,
            'difficulty' => $session['level']['difficulty'] ?? 'medium',
            'streak' => $session['best_streak'],
            'is_perfect' => $isPerfect,
            'is_first_completion' => $isFirstCompletion,
        ], $user);

        return [
            'xp' => $rewards['xp_earned'],
            'coins' => $rewards['coins_earned'],
            'is_new_best' => false,
        ];
    }

    protected function updateUserProgress(array $session, int $stars, array $rewards): void
    {
        $progress = $this->dataService->getUserProgress();
        $levelNumber = $session['level_number'];

        $existingCompletion = $progress['levels_completed'][$levelNumber] ?? null;
        $isNewBest = !$existingCompletion || $session['score'] > ($existingCompletion['best_score'] ?? 0);

        $totalAnswered = $session['current_index'];
        $accuracy = $totalAnswered > 0 ? round(($session['correct'] / $totalAnswered) * 100) : 0;

        $progress['levels_completed'][$levelNumber] = [
            'stars' => max($stars, $existingCompletion['stars'] ?? 0),
            'best_score' => max($session['score'], $existingCompletion['best_score'] ?? 0),
            'best_accuracy' => max($accuracy, $existingCompletion['best_accuracy'] ?? 0),
            'times_played' => ($existingCompletion['times_played'] ?? 0) + 1,
            'last_played' => now()->toISOString(),
        ];

        $progress['total_xp'] = ($progress['total_xp'] ?? 0) + $rewards['xp'];
        $progress['total_coins'] = ($progress['total_coins'] ?? 0) + $rewards['coins'];
        $progress['total_questions'] = ($progress['total_questions'] ?? 0) + $totalAnswered;
        $progress['total_correct'] = ($progress['total_correct'] ?? 0) + $session['correct'];
        $progress['best_streak'] = max($progress['best_streak'] ?? 0, $session['best_streak']);
        $progress['games_played'] = ($progress['games_played'] ?? 0) + 1;
        $progress['total_replays_used'] = ($progress['total_replays_used'] ?? 0) + $session['replays_used'];
        $progress['first_try_correct'] = ($progress['first_try_correct'] ?? 0) + $session['first_try_correct'];

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

        // Update category stats
        if (!isset($progress['category_stats'])) {
            $progress['category_stats'] = [];
        }
        foreach ($session['category_stats'] as $category => $stats) {
            if (!isset($progress['category_stats'][$category])) {
                $progress['category_stats'][$category] = ['correct' => 0, 'total' => 0];
            }
            $progress['category_stats'][$category]['correct'] += $stats['correct'];
            $progress['category_stats'][$category]['total'] += $stats['total'];
        }

        // Update question type stats
        if (!isset($progress['question_type_stats'])) {
            $progress['question_type_stats'] = [];
        }
        foreach ($session['question_type_stats'] as $type => $stats) {
            if (!isset($progress['question_type_stats'][$type])) {
                $progress['question_type_stats'][$type] = ['correct' => 0, 'total' => 0];
            }
            $progress['question_type_stats'][$type]['correct'] += $stats['correct'];
            $progress['question_type_stats'][$type]['total'] += $stats['total'];
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
            'current_index' => $session['current_index'],
            'total_questions' => count($session['items']),
            'completed' => $session['completed'],
            'available_powerups' => $session['available_powerups'],
            'current_replays' => $session['current_replays'],
            'max_replays' => $session['max_replays'],
            'replays_used' => $session['replays_used'],
        ];
    }

    protected function getSession(string $sessionId): ?array
    {
        return session("listen_choose_session_{$sessionId}");
    }

    protected function saveSession(string $sessionId, array $session): void
    {
        session(["listen_choose_session_{$sessionId}" => $session]);
    }
}
