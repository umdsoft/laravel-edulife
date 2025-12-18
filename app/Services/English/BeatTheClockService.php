<?php

namespace App\Services\English;

use Illuminate\Support\Str;

class BeatTheClockService
{
    protected BeatTheClockDataService $dataService;
    protected GameScoringService $scoringService;
    protected string $sessionPath;

    public function __construct(BeatTheClockDataService $dataService, GameScoringService $scoringService)
    {
        $this->dataService = $dataService;
        $this->scoringService = $scoringService;
        $this->sessionPath = storage_path('app/game-sessions/beat-the-clock');

        if (!file_exists($this->sessionPath)) {
            mkdir($this->sessionPath, 0755, true);
        }
    }

    /**
     * Start a new game session
     */
    public function startSession(int $levelNumber, string $mode = 'classic'): array
    {
        $level = $this->dataService->getLevel($levelNumber);

        if (!$level) {
            throw new \Exception('Level not found');
        }

        if (!$level['unlocked']) {
            throw new \Exception('Level is locked');
        }

        $questions = $this->dataService->getQuestionsForLevel($level);

        $sessionId = Str::uuid()->toString();

        $timeLimit = $level['total_time'] ?? null;
        $timePerQuestion = $level['time_per_question'] ?? 10;

        // Adjust for mode
        if ($mode === 'blitz') {
            $timePerQuestion = max(3, (int)($timePerQuestion * 0.5));
        } elseif ($mode === 'time_attack') {
            $timeLimit = $level['total_time'] ?? 60;
        }

        $session = [
            'id' => $sessionId,
            'level_number' => $levelNumber,
            'level' => $level,
            'mode' => $mode,
            'questions' => $questions,
            'current_question' => 0,
            'answers' => [],
            'score' => 0,
            'correct_count' => 0,
            'incorrect_count' => 0,
            'streak' => 0,
            'best_streak' => 0,
            'lives' => $level['lives'] ?? ($level['survival_mode'] ?? false ? 1 : null),
            'powerups_used' => [],
            'time_started' => time(),
            'time_limit' => $timeLimit,
            'time_per_question' => $timePerQuestion,
            'time_remaining' => $timeLimit,
            'double_points_remaining' => 0,
            'time_frozen_until' => null,
            'completed' => false,
            'fast_answers' => 0,
        ];

        $this->saveSession($session);

        return [
            'session_id' => $sessionId,
            'level' => $level,
            'questions' => $this->sanitizeQuestionsForClient($questions),
            'total_questions' => count($questions),
            'time_limit' => $timeLimit,
            'time_per_question' => $timePerQuestion,
            'mode' => $mode,
            'lives' => $session['lives'],
        ];
    }

    /**
     * Sanitize questions for client
     */
    protected function sanitizeQuestionsForClient(array $questions): array
    {
        return array_map(function ($question) {
            $sanitized = [
                'type' => $question['type'],
                'category' => $question['category'],
            ];

            if ($question['type'] === 'multiple_choice' || $question['type'] === 'fill_blank') {
                $sanitized['question'] = $question['question'] ?? $question['sentence'] ?? '';
                $sanitized['question_uz'] = $question['question_uz'] ?? $question['sentence_uz'] ?? '';
                $sanitized['options'] = $question['options'];
            } elseif ($question['type'] === 'true_false') {
                $sanitized['statement'] = $question['statement'];
                $sanitized['statement_uz'] = $question['statement_uz'];
                $sanitized['options'] = ['True', 'False'];
            } elseif ($question['type'] === 'match') {
                $sanitized['pairs'] = array_map(fn($p) => ['word' => $p['word']], $question['pairs']);
                $sanitized['matches'] = array_map(fn($p) => $p['match'], $question['pairs']);
                shuffle($sanitized['matches']);
            }

            return $sanitized;
        }, $questions);
    }

    /**
     * Submit an answer
     */
    public function submitAnswer(string $sessionId, int $questionIndex, $answer, float $timeSpent): array
    {
        $session = $this->getSession($sessionId);

        if (!$session) {
            throw new \Exception('Session not found');
        }

        if ($session['completed']) {
            throw new \Exception('Session already completed');
        }

        $question = $session['questions'][$questionIndex] ?? null;

        if (!$question) {
            throw new \Exception('Question not found');
        }

        $isCorrect = $this->checkAnswer($question, $answer);
        $pointsEarned = 0;
        $gameOver = false;

        if ($isCorrect) {
            $session['correct_count']++;
            $session['streak']++;
            $session['best_streak'] = max($session['best_streak'], $session['streak']);

            // Calculate points
            $basePoints = $session['level']['base_points'] ?? 10;
            $streakBonus = min($session['streak'] * 2, 20);
            $timeBonus = $this->calculateTimeBonus($timeSpent, $session['level']);
            $pointsEarned = $basePoints + $streakBonus + $timeBonus;

            // Double points powerup
            if ($session['double_points_remaining'] > 0) {
                $pointsEarned *= 2;
                $session['double_points_remaining']--;
            }

            $session['score'] += $pointsEarned;

            // Track fast answers
            if ($timeSpent <= 2) {
                $session['fast_answers']++;
            }

            // Time attack mode - add time
            if ($session['mode'] === 'time_attack' && $session['time_remaining'] !== null) {
                $timeBonus = $session['level']['time_correct_bonus'] ?? 5;
                $session['time_remaining'] += $timeBonus;
            }
        } else {
            $session['incorrect_count']++;
            $session['streak'] = 0;

            // Survival mode - lose life or game over
            if ($session['lives'] !== null) {
                $session['lives']--;
                if ($session['lives'] <= 0) {
                    $gameOver = true;
                }
            }

            // Time attack mode - subtract time
            if ($session['mode'] === 'time_attack' && $session['time_remaining'] !== null) {
                $timePenalty = $session['level']['time_wrong_penalty'] ?? 10;
                $session['time_remaining'] = max(0, $session['time_remaining'] - $timePenalty);
                if ($session['time_remaining'] <= 0) {
                    $gameOver = true;
                }
            }
        }

        $session['answers'][$questionIndex] = [
            'answer' => $answer,
            'correct' => $isCorrect,
            'correct_answer' => $this->getCorrectAnswer($question),
            'points' => $pointsEarned,
            'time_spent' => $timeSpent,
        ];

        $session['current_question'] = $questionIndex + 1;

        // Check if game should end
        if ($gameOver) {
            $session['completed'] = true;
        }

        $this->saveSession($session);

        $result = [
            'correct' => $isCorrect,
            'correct_answer' => $this->getCorrectAnswer($question),
            'points_earned' => $pointsEarned,
            'streak' => $session['streak'],
            'total_score' => $session['score'],
            'lives' => $session['lives'],
            'time_remaining' => $session['time_remaining'],
            'game_over' => $gameOver,
            'progress' => [
                'current' => $questionIndex + 1,
                'total' => count($session['questions']),
                'correct' => $session['correct_count'],
                'incorrect' => $session['incorrect_count'],
            ],
        ];

        if ($isCorrect && $question['hint'] ?? null) {
            // Don't show hint after correct answer
        } elseif (!$isCorrect && isset($question['hint'])) {
            $result['hint'] = $question['hint'];
        }

        return $result;
    }

    /**
     * Check if answer is correct
     */
    protected function checkAnswer(array $question, $answer): bool
    {
        $type = $question['type'];

        if ($type === 'multiple_choice' || $type === 'fill_blank') {
            return strtolower(trim($answer)) === strtolower(trim($question['correct']));
        } elseif ($type === 'true_false') {
            $correctAnswer = $question['correct'] ? 'true' : 'false';
            return strtolower(trim($answer)) === $correctAnswer;
        } elseif ($type === 'match') {
            // For match, answer should be array of pairs
            if (!is_array($answer)) {
                return false;
            }
            foreach ($question['pairs'] as $pair) {
                $found = false;
                foreach ($answer as $submitted) {
                    if ($submitted['word'] === $pair['word'] && $submitted['match'] === $pair['match']) {
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    return false;
                }
            }
            return true;
        }

        return false;
    }

    /**
     * Get correct answer for display
     */
    protected function getCorrectAnswer(array $question): mixed
    {
        $type = $question['type'];

        if ($type === 'multiple_choice' || $type === 'fill_blank') {
            return $question['correct'];
        } elseif ($type === 'true_false') {
            return $question['correct'] ? 'True' : 'False';
        } elseif ($type === 'match') {
            return $question['pairs'];
        }

        return null;
    }

    /**
     * Calculate time bonus
     */
    protected function calculateTimeBonus(float $timeSpent, array $level): int
    {
        $config = $this->dataService->getConfig();
        $thresholds = $config['scoring']['speed_thresholds'] ?? [];

        if ($timeSpent <= ($thresholds['lightning']['time'] ?? 2)) {
            return $thresholds['lightning']['bonus'] ?? 10;
        } elseif ($timeSpent <= ($thresholds['fast']['time'] ?? 4)) {
            return $thresholds['fast']['bonus'] ?? 5;
        } elseif ($timeSpent <= ($thresholds['normal']['time'] ?? 8)) {
            return $thresholds['normal']['bonus'] ?? 2;
        }

        return 0;
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

        $usedCount = $session['powerups_used'][$powerupId] ?? 0;
        $maxUses = $powerup['uses_per_game'] ?? 1;

        if ($usedCount >= $maxUses) {
            throw new \Exception('Powerup already used maximum times');
        }

        $session['powerups_used'][$powerupId] = $usedCount + 1;

        $result = $this->applyPowerup($session, $powerup);
        $session = array_merge($session, $result['session_updates'] ?? []);

        $this->saveSession($session);

        return [
            'powerup_id' => $powerupId,
            'effect' => $result['effect'],
            'remaining_uses' => $maxUses - ($usedCount + 1),
        ];
    }

    /**
     * Apply powerup effect
     */
    protected function applyPowerup(array $session, array $powerup): array
    {
        $currentQuestion = $session['questions'][$session['current_question']] ?? null;

        return match ($powerup['id']) {
            'time_boost' => [
                'effect' => [
                    'type' => 'time_boost',
                    'time_added' => $powerup['time_bonus'] ?? 10,
                ],
                'session_updates' => [
                    'time_remaining' => ($session['time_remaining'] ?? 0) + ($powerup['time_bonus'] ?? 10),
                ],
            ],
            'fifty_fifty' => [
                'effect' => [
                    'type' => 'fifty_fifty',
                    'eliminated_options' => $this->eliminateWrongOptions($currentQuestion),
                ],
            ],
            'skip' => [
                'effect' => [
                    'type' => 'skip',
                    'message' => 'Question skipped',
                ],
                'session_updates' => [
                    'current_question' => $session['current_question'] + 1,
                ],
            ],
            'freeze' => [
                'effect' => [
                    'type' => 'freeze',
                    'duration' => $powerup['freeze_duration'] ?? 5,
                ],
                'session_updates' => [
                    'time_frozen_until' => time() + ($powerup['freeze_duration'] ?? 5),
                ],
            ],
            'double_points' => [
                'effect' => [
                    'type' => 'double_points',
                    'duration' => $powerup['duration'] ?? 3,
                ],
                'session_updates' => [
                    'double_points_remaining' => $powerup['duration'] ?? 3,
                ],
            ],
            'hint' => [
                'effect' => [
                    'type' => 'hint',
                    'message' => $currentQuestion['hint'] ?? 'Think carefully about the question.',
                ],
            ],
            default => [
                'effect' => ['type' => 'unknown'],
            ],
        };
    }

    /**
     * Eliminate wrong options
     */
    protected function eliminateWrongOptions(?array $question): array
    {
        if (!$question || !isset($question['options'])) {
            return [];
        }

        $correct = $question['correct'] ?? '';
        $options = $question['options'];
        $wrong = array_filter($options, fn($o) => strtolower($o) !== strtolower($correct));

        shuffle($wrong);
        return array_slice($wrong, 0, 2);
    }

    /**
     * Update time remaining
     */
    public function updateTimeRemaining(string $sessionId, int $timeRemaining): array
    {
        $session = $this->getSession($sessionId);

        if (!$session) {
            throw new \Exception('Session not found');
        }

        $session['time_remaining'] = $timeRemaining;

        if ($timeRemaining <= 0 && $session['time_limit'] !== null) {
            $session['completed'] = true;
        }

        $this->saveSession($session);

        return [
            'time_remaining' => $session['time_remaining'],
            'completed' => $session['completed'],
        ];
    }

    /**
     * Complete session
     */
    public function completeSession(string $sessionId): array
    {
        $session = $this->getSession($sessionId);

        if (!$session) {
            throw new \Exception('Session not found');
        }

        $session['completed'] = true;
        $session['time_completed'] = time();
        $session['time_taken'] = $session['time_completed'] - $session['time_started'];

        $totalQuestions = count($session['questions']);
        $accuracy = $totalQuestions > 0
            ? round(($session['correct_count'] / $totalQuestions) * 100, 1)
            : 0;

        // Calculate stars
        $stars = $this->calculateStars($accuracy, $session['level']);

        // Calculate XP and coins
        $xpEarned = $this->calculateXP($session, $stars);
        $coinsEarned = $this->calculateCoins($session, $stars);

        // Update progress
        $progress = $this->dataService->getUserProgress();
        $levelNumber = $session['level_number'];

        $existingLevel = $progress['levels'][$levelNumber] ?? null;
        $isNewBest = !$existingLevel ||
            ($session['score'] > ($existingLevel['best_score'] ?? 0));

        $progress['levels'][$levelNumber] = [
            'completed' => true,
            'stars' => max($stars, $existingLevel['stars'] ?? 0),
            'best_score' => max($session['score'], $existingLevel['best_score'] ?? 0),
            'best_accuracy' => max($accuracy, $existingLevel['best_accuracy'] ?? 0),
            'best_time' => $existingLevel
                ? min($session['time_taken'], $existingLevel['best_time'] ?? PHP_INT_MAX)
                : $session['time_taken'],
            'attempts' => ($existingLevel['attempts'] ?? 0) + 1,
        ];

        // Update stats
        $progress['stats']['games_played'] = ($progress['stats']['games_played'] ?? 0) + 1;
        $progress['stats']['total_questions'] = ($progress['stats']['total_questions'] ?? 0) + $totalQuestions;
        $progress['stats']['correct_answers'] = ($progress['stats']['correct_answers'] ?? 0) + $session['correct_count'];
        $progress['stats']['incorrect_answers'] = ($progress['stats']['incorrect_answers'] ?? 0) + $session['incorrect_count'];
        $progress['stats']['best_accuracy'] = max($accuracy, $progress['stats']['best_accuracy'] ?? 0);
        $progress['stats']['best_streak'] = max($session['best_streak'], $progress['stats']['best_streak'] ?? 0);
        $progress['stats']['total_xp'] = ($progress['stats']['total_xp'] ?? 0) + $xpEarned;
        $progress['stats']['total_coins'] = ($progress['stats']['total_coins'] ?? 0) + $coinsEarned;
        $progress['stats']['total_time_seconds'] = ($progress['stats']['total_time_seconds'] ?? 0) + $session['time_taken'];
        $progress['stats']['fast_answers'] = ($progress['stats']['fast_answers'] ?? 0) + $session['fast_answers'];

        if ($accuracy === 100) {
            $progress['stats']['perfect_games'] = ($progress['stats']['perfect_games'] ?? 0) + 1;
        }

        // Track survival mode
        if ($session['mode'] === 'survival') {
            $progress['stats']['survival_best'] = max($session['correct_count'], $progress['stats']['survival_best'] ?? 0);
        }

        // Track time attack mode
        if ($session['mode'] === 'time_attack') {
            $progress['stats']['time_attack_best'] = max($session['score'], $progress['stats']['time_attack_best'] ?? 0);
        }

        // Track category completion
        $category = $session['level']['category'] ?? 'vocabulary';
        if (!in_array($category, $progress['stats']['categories_completed'] ?? [])) {
            $progress['stats']['categories_completed'][] = $category;
        }

        // Calculate average accuracy
        $totalGames = $progress['stats']['games_played'];
        $totalCorrect = $progress['stats']['correct_answers'];
        $totalAnswers = $progress['stats']['total_questions'];
        $progress['stats']['average_accuracy'] = $totalAnswers > 0
            ? round(($totalCorrect / $totalAnswers) * 100, 1)
            : 0;

        $this->dataService->saveUserProgress($progress);

        // Check achievements
        $newAchievements = $this->dataService->checkAchievements($progress['stats']);

        $this->saveSession($session);

        return [
            'score' => $session['score'],
            'accuracy' => $accuracy,
            'stars' => $stars,
            'correct_count' => $session['correct_count'],
            'incorrect_count' => $session['incorrect_count'],
            'best_streak' => $session['best_streak'],
            'time_taken' => $session['time_taken'],
            'xp_earned' => $xpEarned,
            'coins_earned' => $coinsEarned,
            'is_new_best' => $isNewBest,
            'new_achievements' => $newAchievements,
            'level_unlocked' => $stars >= 1 ? $levelNumber + 1 : null,
            'fast_answers' => $session['fast_answers'],
        ];
    }

    /**
     * Calculate stars based on accuracy
     */
    protected function calculateStars(float $accuracy, array $level): int
    {
        $thresholds = $level['star_thresholds'] ?? [60, 80, 95];

        if ($accuracy >= $thresholds[2]) {
            return 3;
        } elseif ($accuracy >= $thresholds[1]) {
            return 2;
        } elseif ($accuracy >= $thresholds[0]) {
            return 1;
        }

        return 0;
    }

    /**
     * Calculate XP earned
     */
    protected function calculateXP(array $session, int $stars): int
    {
        $baseXP = $session['level']['xp_reward'] ?? 30;
        $starMultiplier = 1 + ($stars * 0.25);
        $accuracyBonus = $session['correct_count'] * 2;
        $speedBonus = $session['fast_answers'] * 3;

        return (int)(($baseXP * $starMultiplier) + $accuracyBonus + $speedBonus);
    }

    /**
     * Calculate coins earned
     */
    protected function calculateCoins(array $session, int $stars): int
    {
        $baseCoins = $session['level']['coin_reward'] ?? 5;
        $starBonus = $stars * 3;
        $streakBonus = (int)($session['best_streak'] / 5);

        return $baseCoins + $starBonus + $streakBonus;
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
            'current_question' => $session['current_question'],
            'total_questions' => count($session['questions']),
            'score' => $session['score'],
            'correct_count' => $session['correct_count'],
            'incorrect_count' => $session['incorrect_count'],
            'streak' => $session['streak'],
            'best_streak' => $session['best_streak'],
            'lives' => $session['lives'],
            'completed' => $session['completed'],
            'time_started' => $session['time_started'],
            'time_limit' => $session['time_limit'],
            'time_remaining' => $session['time_remaining'],
            'time_per_question' => $session['time_per_question'],
        ];
    }

    /**
     * Get session
     */
    protected function getSession(string $sessionId): ?array
    {
        $sessionFile = $this->sessionPath . "/{$sessionId}.json";

        if (!file_exists($sessionFile)) {
            return null;
        }

        return json_decode(file_get_contents($sessionFile), true);
    }

    /**
     * Save session
     */
    protected function saveSession(array $session): void
    {
        $sessionFile = $this->sessionPath . "/{$session['id']}.json";
        file_put_contents($sessionFile, json_encode($session, JSON_PRETTY_PRINT));
    }
}
