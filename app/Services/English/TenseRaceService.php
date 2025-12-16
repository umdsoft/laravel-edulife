<?php

namespace App\Services\English;

class TenseRaceService
{
    protected TenseRaceDataService $dataService;

    public function __construct(TenseRaceDataService $dataService)
    {
        $this->dataService = $dataService;
    }

    public function startSession(int $levelNumber, string $gameMode = 'mixed'): array
    {
        $level = $this->dataService->getLevel($levelNumber);
        if (!$level) {
            throw new \Exception('Level not found');
        }

        if (!$this->dataService->isLevelUnlocked($levelNumber)) {
            throw new \Exception('Level is locked');
        }

        $questions = $this->dataService->getQuestionsForLevel($levelNumber);
        if (empty($questions)) {
            throw new \Exception('No questions available');
        }

        $sessionId = uniqid('tr_', true);
        $powerups = $this->dataService->getPowerups();

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
            'questions' => $questions,
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
            'double_xp_active' => false,
            'double_xp_remaining' => 0,
            'hints_used' => 0,
            'tense_stats' => [],
            'question_type_stats' => [],
            'completed' => false,
        ];

        $this->saveSession($sessionId, $session);

        return [
            'session_id' => $sessionId,
            'level' => $level,
            'total_questions' => count($questions),
            'time_limit' => $level['time_limit'],
            'first_question' => $this->prepareQuestion($questions[0]),
        ];
    }

    public function getCurrentQuestion(string $sessionId): ?array
    {
        $session = $this->getSession($sessionId);
        if (!$session || $session['completed']) {
            return null;
        }

        $currentIndex = $session['current_index'];
        if ($currentIndex >= count($session['questions'])) {
            return null;
        }

        return $this->prepareQuestion($session['questions'][$currentIndex]);
    }

    protected function prepareQuestion(array $question): array
    {
        return [
            'id' => $question['id'],
            'type' => $question['type'],
            'sentence' => $question['sentence'],
            'options' => $question['options'],
            'tense' => $question['tense'],
            'difficulty' => $question['difficulty'],
            'target_tense' => $question['target_tense'] ?? null,
        ];
    }

    public function checkAnswer(string $sessionId, string $questionId, int $answerIndex, float $answerTime): array
    {
        $session = $this->getSession($sessionId);
        if (!$session) {
            throw new \Exception('Session not found');
        }

        if ($session['completed']) {
            throw new \Exception('Session already completed');
        }

        $currentQuestion = $session['questions'][$session['current_index']] ?? null;
        if (!$currentQuestion || $currentQuestion['id'] !== $questionId) {
            throw new \Exception('Invalid question');
        }

        $isCorrect = $answerIndex === $currentQuestion['correct'];
        $points = 0;

        if ($isCorrect) {
            $points = $this->calculatePoints($session, $currentQuestion, $answerTime);
            $session['correct']++;
            $session['streak']++;
            $session['best_streak'] = max($session['best_streak'], $session['streak']);
        } else {
            $session['wrong']++;
            $session['streak'] = 0;
        }

        $session['score'] += $points;
        $session['answers'][] = [
            'question_id' => $questionId,
            'answer' => $answerIndex,
            'correct' => $currentQuestion['correct'],
            'is_correct' => $isCorrect,
            'points' => $points,
            'time' => $answerTime,
            'tense' => $currentQuestion['tense'],
            'type' => $currentQuestion['type'],
        ];

        $session['answer_times'][] = $answerTime;
        if ($isCorrect && ($session['fastest_answer'] === null || $answerTime < $session['fastest_answer'])) {
            $session['fastest_answer'] = $answerTime;
        }

        // Update tense stats
        $tense = $currentQuestion['tense'];
        if (!isset($session['tense_stats'][$tense])) {
            $session['tense_stats'][$tense] = ['correct' => 0, 'total' => 0];
        }
        $session['tense_stats'][$tense]['total']++;
        if ($isCorrect) {
            $session['tense_stats'][$tense]['correct']++;
        }

        // Update question type stats
        $qType = $currentQuestion['type'];
        if (!isset($session['question_type_stats'][$qType])) {
            $session['question_type_stats'][$qType] = ['correct' => 0, 'total' => 0];
        }
        $session['question_type_stats'][$qType]['total']++;
        if ($isCorrect) {
            $session['question_type_stats'][$qType]['correct']++;
        }

        // Handle double XP
        if ($session['double_xp_active']) {
            $session['double_xp_remaining']--;
            if ($session['double_xp_remaining'] <= 0) {
                $session['double_xp_active'] = false;
            }
        }

        $session['current_index']++;

        $gameOver = $session['current_index'] >= count($session['questions']);
        $session['completed'] = $gameOver;

        $this->saveSession($sessionId, $session);

        $result = [
            'is_correct' => $isCorrect,
            'correct_answer' => $currentQuestion['correct'],
            'explanation' => $currentQuestion['explanation'] ?? null,
            'points_earned' => $points,
            'total_score' => $session['score'],
            'streak' => $session['streak'],
            'best_streak' => $session['best_streak'],
            'questions_answered' => $session['current_index'],
            'game_over' => $gameOver,
        ];

        if (!$gameOver && $session['current_index'] < count($session['questions'])) {
            $result['next_question'] = $this->prepareQuestion($session['questions'][$session['current_index']]);
        }

        return $result;
    }

    protected function calculatePoints(array $session, array $question, float $answerTime): int
    {
        $config = $this->dataService->getScoringConfig();
        $level = $session['level'];

        $basePoints = $config['base_points'] ?? 10;

        // Difficulty multiplier
        $difficultyMultipliers = $config['difficulty_multipliers'] ?? [];
        $difficultyMultiplier = $difficultyMultipliers[$question['difficulty']] ?? 1.0;

        // Time bonus
        $timeBonus = 0;
        $timeBonusConfig = $config['time_bonus'] ?? [];
        $maxTimeBonus = $timeBonusConfig['max_bonus'] ?? 15;
        $fastThreshold = $timeBonusConfig['fast_threshold'] ?? 5;

        if ($answerTime <= $fastThreshold) {
            $timeBonus = $maxTimeBonus;
        } elseif ($answerTime < 15) {
            $timeBonus = round($maxTimeBonus * (1 - ($answerTime - $fastThreshold) / (15 - $fastThreshold)));
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

        // Double XP powerup
        $doubleXpMultiplier = $session['double_xp_active'] ? 2.0 : 1.0;

        // Question type multiplier
        $gameModes = $this->dataService->getGameModes();
        $typeMultiplier = 1.0;
        foreach ($gameModes as $mode) {
            if ($mode['id'] === $question['type']) {
                $typeMultiplier = $mode['point_multiplier'] ?? 1.0;
                break;
            }
        }

        $points = ($basePoints + $timeBonus) * $difficultyMultiplier * $streakMultiplier * $levelMultiplier * $doubleXpMultiplier * $typeMultiplier;

        return (int)round($points);
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
            'tense_stats' => $session['tense_stats'],
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
        $level = $session['level'];
        $rewardsConfig = $this->dataService->getRewardsConfig();

        $baseXp = $level['xp_reward'] ?? 50;
        $baseCoins = $level['coin_reward'] ?? 25;

        $starMultipliers = $rewardsConfig['star_multipliers'] ?? [];
        $starMultiplier = $starMultipliers[$stars] ?? 0.25;

        // Streak bonus
        $streakBonus = 1.0;
        if ($session['best_streak'] >= 15) {
            $streakBonus = 1.3;
        } elseif ($session['best_streak'] >= 10) {
            $streakBonus = 1.2;
        } elseif ($session['best_streak'] >= 5) {
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

        // Update tense stats
        if (!isset($progress['tense_stats'])) {
            $progress['tense_stats'] = [];
        }
        foreach ($session['tense_stats'] as $tense => $stats) {
            if (!isset($progress['tense_stats'][$tense])) {
                $progress['tense_stats'][$tense] = ['correct' => 0, 'total' => 0];
            }
            $progress['tense_stats'][$tense]['correct'] += $stats['correct'];
            $progress['tense_stats'][$tense]['total'] += $stats['total'];
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
            case 'add_time':
                $result['effect'] = 'time_added';
                $result['value'] = $effect['value'];
                break;

            case 'eliminate':
                $currentQuestion = $session['questions'][$session['current_index']] ?? null;
                if ($currentQuestion) {
                    $correctIndex = $currentQuestion['correct'];
                    $wrongIndices = array_diff([0, 1, 2, 3], [$correctIndex]);
                    shuffle($wrongIndices);
                    $eliminated = array_slice($wrongIndices, 0, $effect['value']);
                    $result['eliminated_indices'] = $eliminated;
                    $result['effect'] = 'options_eliminated';
                }
                break;

            case 'hint':
                $session['hints_used']++;
                $currentQuestion = $session['questions'][$session['current_index']] ?? null;
                if ($currentQuestion) {
                    $tenseCategories = $this->dataService->getTenseCategories();
                    $tenseInfo = null;
                    foreach ($tenseCategories as $tc) {
                        if ($tc['id'] === $currentQuestion['tense']) {
                            $tenseInfo = $tc;
                            break;
                        }
                    }
                    $result['hint'] = $tenseInfo;
                    $result['effect'] = 'hint_shown';
                }
                break;

            case 'skip':
                $session['current_index']++;
                if ($session['current_index'] < count($session['questions'])) {
                    $result['next_question'] = $this->prepareQuestion($session['questions'][$session['current_index']]);
                }
                $result['effect'] = 'question_skipped';
                break;

            case 'multiplier':
                $session['double_xp_active'] = true;
                $session['double_xp_remaining'] = $effect['duration'] ?? 3;
                $result['effect'] = 'double_xp_activated';
                $result['duration'] = $effect['duration'] ?? 3;
                break;
        }

        $session['available_powerups'][$powerupId]['available'] = false;
        $session['available_powerups'][$powerupId]['used'] = true;
        $session['powerups_used'][] = $powerupId;

        $this->saveSession($sessionId, $session);

        return $result;
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
            'total_questions' => count($session['questions']),
            'completed' => $session['completed'],
            'available_powerups' => $session['available_powerups'],
            'double_xp_active' => $session['double_xp_active'],
            'double_xp_remaining' => $session['double_xp_remaining'],
        ];
    }

    protected function getSession(string $sessionId): ?array
    {
        return session("tense_race_session_{$sessionId}");
    }

    protected function saveSession(string $sessionId, array $session): void
    {
        session(["tense_race_session_{$sessionId}" => $session]);
    }
}
