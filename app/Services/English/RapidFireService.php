<?php

namespace App\Services\English;

use Illuminate\Support\Facades\Log;

class RapidFireService
{
    protected RapidFireDataService $dataService;
    protected int $sessionTTL = 7200; // 2 hours

    public function __construct(RapidFireDataService $dataService)
    {
        $this->dataService = $dataService;
    }

    /**
     * Start a new game session
     */
    public function startSession(int $levelNumber, string $gameMode = 'classic'): array
    {
        $level = $this->dataService->getLevel($levelNumber);
        if (!$level) {
            throw new \Exception('Level not found');
        }

        if (!$this->dataService->isLevelUnlocked($levelNumber)) {
            throw new \Exception('Level is locked');
        }

        $mode = $this->dataService->getGameMode($gameMode);
        if (!$mode) {
            $mode = $this->dataService->getGameMode('classic');
        }

        // Get questions for this level
        $questions = $this->dataService->getQuestionsForLevel($levelNumber);

        if (empty($questions)) {
            throw new \Exception('No questions available for this level');
        }

        $sessionId = uniqid('rf_', true);

        $session = [
            'id' => $sessionId,
            'level_number' => $levelNumber,
            'level' => $level,
            'mode' => $mode,
            'questions' => $questions,
            'current_index' => 0,
            'score' => 0,
            'correct' => 0,
            'wrong' => 0,
            'streak' => 0,
            'best_streak' => 0,
            'lives' => $mode['lives'] ?? null,
            'time_limit' => $mode['time_limit'] ?? $level['time_limit'],
            'time_remaining' => $mode['time_limit'] ?? $level['time_limit'],
            'started_at' => now()->timestamp,
            'answers' => [],
            'answer_times' => [],
            'fastest_answer' => null,
            'powerups_used' => [],
            'available_powerups' => $this->getAvailablePowerups(),
            'double_points_active' => false,
            'double_points_remaining' => 0,
            'freeze_time_active' => false,
            'completed' => false,
        ];

        $this->saveSession($sessionId, $session);

        return [
            'session_id' => $sessionId,
            'level' => $level,
            'mode' => $mode,
            'total_questions' => count($questions),
            'time_limit' => $session['time_limit'],
            'lives' => $session['lives'],
            'first_question' => $this->prepareQuestion($questions[0]),
        ];
    }

    /**
     * Get current question
     */
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

    /**
     * Prepare question for frontend (remove correct answer info)
     */
    protected function prepareQuestion(array $question): array
    {
        return [
            'id' => $question['id'],
            'question' => $question['question'],
            'options' => $question['options'],
            'category' => $question['category'] ?? 'general',
            'difficulty' => $question['difficulty'],
        ];
    }

    /**
     * Check answer and return result
     */
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

            // Survival mode: lose a life
            if ($session['mode']['id'] === 'survival' && $session['lives'] !== null) {
                $session['lives']--;
            }
        }

        $session['score'] += $points;
        $session['answers'][] = [
            'question_id' => $questionId,
            'answer' => $answerIndex,
            'correct' => $currentQuestion['correct'],
            'is_correct' => $isCorrect,
            'points' => $points,
            'time' => $answerTime,
        ];

        // Track answer time
        $session['answer_times'][] = $answerTime;
        if ($isCorrect && ($session['fastest_answer'] === null || $answerTime < $session['fastest_answer'])) {
            $session['fastest_answer'] = $answerTime;
        }

        // Update category stats
        $category = $currentQuestion['category'] ?? 'general';
        if (!isset($session['category_stats'])) {
            $session['category_stats'] = [];
        }
        if (!isset($session['category_stats'][$category])) {
            $session['category_stats'][$category] = ['correct' => 0, 'total' => 0];
        }
        $session['category_stats'][$category]['total']++;
        if ($isCorrect) {
            $session['category_stats'][$category]['correct']++;
        }

        // Handle double points
        if ($session['double_points_active']) {
            $session['double_points_remaining']--;
            if ($session['double_points_remaining'] <= 0) {
                $session['double_points_active'] = false;
            }
        }

        // Move to next question
        $session['current_index']++;

        // Check if game should end
        $gameOver = false;
        $reason = null;

        if ($session['mode']['id'] === 'survival' && $session['lives'] <= 0) {
            $gameOver = true;
            $reason = 'no_lives';
        } elseif ($session['current_index'] >= count($session['questions'])) {
            $gameOver = true;
            $reason = 'all_questions';
        }

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
            'lives' => $session['lives'],
            'questions_answered' => $session['current_index'],
            'game_over' => $gameOver,
            'game_over_reason' => $reason,
        ];

        if (!$gameOver && $session['current_index'] < count($session['questions'])) {
            $result['next_question'] = $this->prepareQuestion($session['questions'][$session['current_index']]);
        }

        return $result;
    }

    /**
     * Calculate points for a correct answer
     */
    protected function calculatePoints(array $session, array $question, float $answerTime): int
    {
        $config = $this->dataService->getScoringConfig();
        $level = $session['level'];

        // Base points
        $basePoints = $config['base_points'] ?? 10;

        // Difficulty multiplier
        $difficultyMultipliers = $config['difficulty_multipliers'] ?? [
            'easy' => 1.0,
            'medium' => 1.5,
            'hard' => 2.0,
        ];
        $difficultyMultiplier = $difficultyMultipliers[$question['difficulty']] ?? 1.0;

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

        // Game mode multiplier
        $modeMultiplier = $session['mode']['point_multiplier'] ?? 1.0;

        // Double points powerup
        $doublePointsMultiplier = $session['double_points_active'] ? 2.0 : 1.0;

        // Calculate final points
        $points = ($basePoints + $timeBonus) * $difficultyMultiplier * $streakMultiplier * $levelMultiplier * $modeMultiplier * $doublePointsMultiplier;

        return (int)round($points);
    }

    /**
     * Complete session (called when time runs out or player finishes)
     */
    public function completeSession(string $sessionId, float $finalTime = null): array
    {
        $session = $this->getSession($sessionId);
        if (!$session) {
            throw new \Exception('Session not found');
        }

        $session['completed'] = true;
        $session['completed_at'] = now()->timestamp;
        $session['final_time'] = $finalTime;

        // Calculate QPS (questions per second) and QPM (questions per minute)
        $totalTime = $finalTime ?? ($session['time_limit'] - ($session['time_remaining'] ?? 0));
        $totalAnswered = $session['current_index'];
        $qpm = $totalTime > 0 ? ($totalAnswered / $totalTime) * 60 : 0;

        // Calculate stars
        $stars = $this->calculateStars($session);

        // Calculate rewards
        $rewards = $this->calculateRewards($session, $stars);

        // Update user progress
        $this->updateUserProgress($session, $stars, $rewards, $qpm);

        // Check achievements
        $sessionData = [
            'correct' => $session['correct'],
            'total' => $totalAnswered,
            'best_streak' => $session['best_streak'],
            'fastest_answer' => $session['fastest_answer'],
            'qpm' => $qpm,
            'score' => $session['score'],
            'mode' => $session['mode']['id'],
            'completed' => true,
            'category_stats' => $session['category_stats'] ?? [],
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
            'qpm' => round($qpm, 1),
            'stars' => $stars,
            'xp_earned' => $rewards['xp'],
            'coins_earned' => $rewards['coins'],
            'new_achievements' => $newAchievements,
            'is_new_best' => $rewards['is_new_best'] ?? false,
            'category_stats' => $session['category_stats'] ?? [],
        ];
    }

    /**
     * Calculate stars based on performance
     */
    protected function calculateStars(array $session): int
    {
        $totalAnswered = $session['current_index'];
        if ($totalAnswered === 0) {
            return 0;
        }

        $accuracy = ($session['correct'] / $totalAnswered) * 100;
        $targetScore = $session['level']['target_score'] ?? 100;
        $scorePercentage = ($session['score'] / $targetScore) * 100;

        // Combined metric: 60% accuracy, 40% score achievement
        $combinedScore = ($accuracy * 0.6) + ($scorePercentage * 0.4);

        $rewardsConfig = $this->dataService->getRewardsConfig();
        $starThresholds = $rewardsConfig['star_thresholds'] ?? [
            'one_star' => 50,
            'two_stars' => 75,
            'three_stars' => 90,
        ];

        if ($combinedScore >= $starThresholds['three_stars']) {
            return 3;
        } elseif ($combinedScore >= $starThresholds['two_stars']) {
            return 2;
        } elseif ($combinedScore >= $starThresholds['one_star']) {
            return 1;
        }

        return 0;
    }

    /**
     * Calculate XP and coin rewards
     */
    protected function calculateRewards(array $session, int $stars): array
    {
        $level = $session['level'];
        $rewardsConfig = $this->dataService->getRewardsConfig();

        $baseXp = $level['xp_reward'] ?? 50;
        $baseCoins = $level['coin_reward'] ?? 25;

        // Star multipliers
        $starMultipliers = $rewardsConfig['star_multipliers'] ?? [
            0 => 0.25,
            1 => 0.5,
            2 => 0.75,
            3 => 1.0,
        ];
        $starMultiplier = $starMultipliers[$stars] ?? 0.25;

        // Streak bonus
        $streakBonus = 1.0;
        if ($session['best_streak'] >= 20) {
            $streakBonus = 1.3;
        } elseif ($session['best_streak'] >= 10) {
            $streakBonus = 1.2;
        } elseif ($session['best_streak'] >= 5) {
            $streakBonus = 1.1;
        }

        // Perfect round bonus
        $perfectBonus = 1.0;
        if ($session['wrong'] === 0 && $session['correct'] >= 10) {
            $perfectBonus = $rewardsConfig['perfect_bonus'] ?? 1.5;
        }

        $xp = (int)round($baseXp * $starMultiplier * $streakBonus * $perfectBonus);
        $coins = (int)round($baseCoins * $starMultiplier * $streakBonus * $perfectBonus);

        return [
            'xp' => $xp,
            'coins' => $coins,
        ];
    }

    /**
     * Update user progress after completing a session
     */
    protected function updateUserProgress(array $session, int $stars, array $rewards, float $qpm): void
    {
        $progress = $this->dataService->getUserProgress();
        $levelNumber = $session['level_number'];

        // Update level completion
        $existingCompletion = $progress['levels_completed'][$levelNumber] ?? null;
        $isNewBest = false;

        if (!$existingCompletion || $session['score'] > ($existingCompletion['best_score'] ?? 0)) {
            $isNewBest = true;
        }

        $progress['levels_completed'][$levelNumber] = [
            'stars' => max($stars, $existingCompletion['stars'] ?? 0),
            'best_score' => max($session['score'], $existingCompletion['best_score'] ?? 0),
            'best_streak' => max($session['best_streak'], $existingCompletion['best_streak'] ?? 0),
            'times_played' => ($existingCompletion['times_played'] ?? 0) + 1,
            'last_played' => now()->toISOString(),
        ];

        // Update totals
        $progress['total_xp'] = ($progress['total_xp'] ?? 0) + $rewards['xp'];
        $progress['total_coins'] = ($progress['total_coins'] ?? 0) + $rewards['coins'];
        $progress['total_questions'] = ($progress['total_questions'] ?? 0) + $session['current_index'];
        $progress['total_correct'] = ($progress['total_correct'] ?? 0) + $session['correct'];
        $progress['best_streak'] = max($progress['best_streak'] ?? 0, $session['best_streak']);
        $progress['best_qpm'] = max($progress['best_qpm'] ?? 0, $qpm);
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

        // Update category stats
        if (!isset($progress['category_stats'])) {
            $progress['category_stats'] = [];
        }
        foreach ($session['category_stats'] ?? [] as $category => $stats) {
            if (!isset($progress['category_stats'][$category])) {
                $progress['category_stats'][$category] = ['correct' => 0, 'total' => 0];
            }
            $progress['category_stats'][$category]['correct'] += $stats['correct'];
            $progress['category_stats'][$category]['total'] += $stats['total'];
        }

        $this->dataService->saveUserProgress($progress);
    }

    /**
     * Get available powerups
     */
    protected function getAvailablePowerups(): array
    {
        $powerups = $this->dataService->getPowerups();
        $available = [];

        foreach ($powerups as $powerup) {
            $available[$powerup['id']] = [
                'id' => $powerup['id'],
                'name' => $powerup['name'],
                'name_uz' => $powerup['name_uz'],
                'icon' => $powerup['icon'],
                'available' => true,
                'used' => false,
            ];
        }

        return $available;
    }

    /**
     * Use a powerup
     */
    public function usePowerup(string $sessionId, string $powerupId): array
    {
        $session = $this->getSession($sessionId);
        if (!$session) {
            throw new \Exception('Session not found');
        }

        if ($session['completed']) {
            throw new \Exception('Session already completed');
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

        if (!($session['available_powerups'][$powerupId]['available'] ?? false)) {
            throw new \Exception('Powerup not available');
        }

        $result = ['success' => true, 'powerup' => $powerup];

        switch ($powerupId) {
            case 'freeze_time':
                $session['freeze_time_active'] = true;
                $result['effect'] = 'time_frozen';
                $result['duration'] = $powerup['duration'] ?? 5;
                break;

            case 'double_points':
                $session['double_points_active'] = true;
                $session['double_points_remaining'] = $powerup['duration'] ?? 3;
                $result['effect'] = 'double_points_activated';
                $result['questions'] = $powerup['duration'] ?? 3;
                break;

            case 'extra_life':
                if ($session['lives'] !== null) {
                    $session['lives']++;
                    $result['effect'] = 'life_added';
                    $result['lives'] = $session['lives'];
                }
                break;

            case 'skip':
                // Move to next question without penalty
                $session['current_index']++;
                if ($session['current_index'] < count($session['questions'])) {
                    $result['next_question'] = $this->prepareQuestion($session['questions'][$session['current_index']]);
                }
                $result['effect'] = 'question_skipped';
                break;

            case 'fifty_fifty':
                $currentQuestion = $session['questions'][$session['current_index']] ?? null;
                if ($currentQuestion) {
                    $correctIndex = $currentQuestion['correct'];
                    $wrongIndices = array_diff([0, 1, 2, 3], [$correctIndex]);
                    shuffle($wrongIndices);
                    $eliminatedIndices = array_slice($wrongIndices, 0, 2);
                    $result['eliminated_indices'] = $eliminatedIndices;
                    $result['effect'] = 'options_eliminated';
                }
                break;
        }

        $session['available_powerups'][$powerupId]['available'] = false;
        $session['available_powerups'][$powerupId]['used'] = true;
        $session['powerups_used'][] = $powerupId;

        $this->saveSession($sessionId, $session);

        return $result;
    }

    /**
     * Update time (for time-based modes)
     */
    public function updateTime(string $sessionId, float $timeRemaining): array
    {
        $session = $this->getSession($sessionId);
        if (!$session) {
            throw new \Exception('Session not found');
        }

        $session['time_remaining'] = $timeRemaining;

        // Deactivate freeze time if it was active
        if ($session['freeze_time_active']) {
            $session['freeze_time_active'] = false;
        }

        $this->saveSession($sessionId, $session);

        return ['time_remaining' => $timeRemaining];
    }

    /**
     * Get session state
     */
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
            'lives' => $session['lives'],
            'current_index' => $session['current_index'],
            'total_questions' => count($session['questions']),
            'time_remaining' => $session['time_remaining'],
            'completed' => $session['completed'],
            'available_powerups' => $session['available_powerups'],
            'double_points_active' => $session['double_points_active'],
            'double_points_remaining' => $session['double_points_remaining'],
        ];
    }

    /**
     * Get session from storage
     */
    protected function getSession(string $sessionId): ?array
    {
        $key = "rapid_fire_session_{$sessionId}";
        return session($key);
    }

    /**
     * Save session to storage
     */
    protected function saveSession(string $sessionId, array $session): void
    {
        $key = "rapid_fire_session_{$sessionId}";
        session([$key => $session]);
    }

    /**
     * Delete session
     */
    public function deleteSession(string $sessionId): void
    {
        $key = "rapid_fire_session_{$sessionId}";
        session()->forget($key);
    }
}
