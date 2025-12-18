<?php

namespace App\Services\English;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class GrammarQuizService
{
    private GrammarQuizDataService $dataService;
    private GameScoringService $scoringService;
    private int $sessionTtl = 7200; // 2 hours

    public function __construct(GrammarQuizDataService $dataService, GameScoringService $scoringService)
    {
        $this->dataService = $dataService;
        $this->scoringService = $scoringService;
    }

    /**
     * Start a new game session
     */
    public function startSession($userId, int $levelNumber, string $quizType = 'mixed'): array
    {
        $level = $this->dataService->getLevel($levelNumber);
        if (!$level) {
            return ['success' => false, 'error' => 'Level not found'];
        }

        // Get questions for this level
        $questions = $this->dataService->getQuestionsForLevel($levelNumber);
        if (empty($questions)) {
            return ['success' => false, 'error' => 'No questions available for this level'];
        }

        // Prepare questions with options
        $preparedQuestions = $this->prepareQuestions($questions, $quizType);

        $sessionId = Str::uuid()->toString();
        $config = $this->dataService->getConfig();

        $session = [
            'id' => $sessionId,
            'user_id' => $userId,
            'level_number' => $levelNumber,
            'level_data' => $level,
            'quiz_type' => $quizType,
            'questions' => $preparedQuestions,
            'current_index' => 0,
            'answers' => [],
            'score' => 0,
            'streak' => 0,
            'max_streak' => 0,
            'hints_used' => 0,
            'skips_used' => 0,
            'fifty_fifty_used' => 0,
            'double_points_active' => false,
            'time_started' => now()->timestamp,
            'time_limit' => $level['time_limit'] ?? 600,
            'extra_time_added' => 0,
            'categories_used' => [],
            'completed' => false,
            'config' => [
                'scoring' => $config['scoring'] ?? [],
                'rewards' => $config['rewards'] ?? [],
                'star_thresholds' => $config['star_thresholds'] ?? [],
            ],
        ];

        // Track categories used
        foreach ($preparedQuestions as $q) {
            $cat = $q['category'] ?? $q['subcategory'] ?? 'unknown';
            if (!in_array($cat, $session['categories_used'])) {
                $session['categories_used'][] = $cat;
            }
        }

        Cache::put("grammar_quiz_session_{$sessionId}", $session, $this->sessionTtl);

        return [
            'success' => true,
            'session_id' => $sessionId,
            'level' => $level,
            'total_questions' => count($preparedQuestions),
            'time_limit' => $session['time_limit'],
            'current_question' => $this->formatQuestionForClient($preparedQuestions[0]),
            'current_index' => 0,
        ];
    }

    /**
     * Prepare questions for the session
     */
    private function prepareQuestions(array $questions, string $quizType): array
    {
        // Filter by quiz type if specified
        if ($quizType !== 'mixed' && $quizType !== 'all') {
            $questions = array_filter($questions, fn($q) => $q['type'] === $quizType);
            $questions = array_values($questions);
        }

        // Shuffle and prepare each question
        shuffle($questions);

        return array_map(function ($q) {
            // Ensure all questions have proper structure
            $prepared = $q;

            // Shuffle options if they exist
            if (isset($prepared['options']) && is_array($prepared['options'])) {
                shuffle($prepared['options']);
            }

            return $prepared;
        }, $questions);
    }

    /**
     * Format question for client (hide answer)
     */
    private function formatQuestionForClient(array $question): array
    {
        $clientQuestion = [
            'id' => $question['id'],
            'type' => $question['type'],
            'difficulty' => $question['difficulty'],
            'points' => $question['points'] ?? 100,
            'category' => $question['category'] ?? null,
            'subcategory' => $question['subcategory'] ?? null,
        ];

        switch ($question['type']) {
            case 'fill_blank':
                $clientQuestion['sentence'] = $question['sentence'];
                $clientQuestion['blank_position'] = $question['blank_position'] ?? null;
                $clientQuestion['options'] = $question['options'] ?? [];
                break;

            case 'multiple_choice':
                $clientQuestion['sentence'] = $question['sentence'];
                $clientQuestion['options'] = $question['options'] ?? [];
                break;

            case 'error_correction':
                $clientQuestion['sentence'] = $question['sentence'];
                $clientQuestion['error_position'] = $question['error_position'] ?? [];
                $clientQuestion['is_correct_sentence'] = $question['is_correct_sentence'] ?? false;
                break;

            case 'sentence_transformation':
                $clientQuestion['instruction'] = $question['instruction'];
                $clientQuestion['original_sentence'] = $question['original_sentence'];
                $clientQuestion['hints'] = $question['hints'] ?? [];
                break;
        }

        return $clientQuestion;
    }

    /**
     * Check answer
     */
    public function checkAnswer(string $sessionId, $answer, int $timeSpent): array
    {
        $session = Cache::get("grammar_quiz_session_{$sessionId}");
        if (!$session) {
            return ['success' => false, 'error' => 'Session not found'];
        }

        if ($session['completed']) {
            return ['success' => false, 'error' => 'Session already completed'];
        }

        $currentIndex = $session['current_index'];
        $question = $session['questions'][$currentIndex];

        // Validate answer
        $validation = $this->validateAnswer($question, $answer);
        $isCorrect = $validation['is_correct'];

        // Calculate score
        $scoreData = $this->calculateScore(
            $question,
            $isCorrect,
            $timeSpent,
            $session['streak'],
            $session['level_data'],
            $session['double_points_active'],
            $session['hints_used'] > 0
        );

        // Update streak
        if ($isCorrect) {
            $session['streak']++;
            if ($session['streak'] > $session['max_streak']) {
                $session['max_streak'] = $session['streak'];
            }
        } else {
            $session['streak'] = 0;
        }

        // Add score
        $session['score'] += $scoreData['total_points'];

        // Reset double points after use
        if ($session['double_points_active']) {
            $session['double_points_active'] = false;
        }

        // Record answer
        $session['answers'][] = [
            'question_id' => $question['id'],
            'user_answer' => $answer,
            'correct_answer' => $question['correct_answer'],
            'is_correct' => $isCorrect,
            'time_spent' => $timeSpent,
            'points_earned' => $scoreData['total_points'],
            'streak_at_answer' => $session['streak'],
        ];

        // Move to next question
        $session['current_index']++;
        $hasNextQuestion = $session['current_index'] < count($session['questions']);

        Cache::put("grammar_quiz_session_{$sessionId}", $session, $this->sessionTtl);

        $response = [
            'success' => true,
            'is_correct' => $isCorrect,
            'correct_answer' => $question['correct_answer'],
            'explanation' => $question['explanation'] ?? null,
            'grammar_rule' => $question['grammar_rule'] ?? null,
            'grammar_rule_uz' => $question['grammar_rule_uz'] ?? null,
            'points_earned' => $scoreData['total_points'],
            'score_breakdown' => $scoreData,
            'current_score' => $session['score'],
            'current_streak' => $session['streak'],
            'has_next_question' => $hasNextQuestion,
        ];

        if ($hasNextQuestion) {
            $response['next_question'] = $this->formatQuestionForClient($session['questions'][$session['current_index']]);
            $response['next_index'] = $session['current_index'];
        }

        return $response;
    }

    /**
     * Validate answer based on question type
     */
    private function validateAnswer(array $question, $answer): array
    {
        $correctAnswer = $question['correct_answer'];

        switch ($question['type']) {
            case 'fill_blank':
            case 'multiple_choice':
                $isCorrect = $this->normalizeAnswer($answer) === $this->normalizeAnswer($correctAnswer);
                break;

            case 'error_correction':
                // Check if it's a "correct sentence" question
                if ($question['is_correct_sentence'] ?? false) {
                    $isCorrect = strtolower(trim($answer)) === 'correct' || $answer === $question['sentence'];
                } else {
                    $isCorrect = $this->normalizeAnswer($answer) === $this->normalizeAnswer($correctAnswer);
                }
                break;

            case 'sentence_transformation':
                $isCorrect = $this->compareSentences($answer, $correctAnswer);
                break;

            default:
                $isCorrect = $this->normalizeAnswer($answer) === $this->normalizeAnswer($correctAnswer);
        }

        return [
            'is_correct' => $isCorrect,
            'normalized_answer' => $this->normalizeAnswer($answer),
            'normalized_correct' => $this->normalizeAnswer($correctAnswer),
        ];
    }

    /**
     * Normalize answer for comparison
     */
    private function normalizeAnswer($answer): string
    {
        if (is_array($answer)) {
            $answer = implode(' ', $answer);
        }
        return strtolower(trim(preg_replace('/\s+/', ' ', (string)$answer)));
    }

    /**
     * Compare sentences with flexibility
     */
    private function compareSentences(string $userAnswer, string $correctAnswer): bool
    {
        $normalizedUser = $this->normalizeAnswer($userAnswer);
        $normalizedCorrect = $this->normalizeAnswer($correctAnswer);

        // Exact match
        if ($normalizedUser === $normalizedCorrect) {
            return true;
        }

        // Handle multiple correct answers (separated by /)
        if (strpos($correctAnswer, '/') !== false) {
            $alternatives = explode('/', $correctAnswer);
            foreach ($alternatives as $alt) {
                if ($this->normalizeAnswer($alt) === $normalizedUser) {
                    return true;
                }
            }
        }

        // Allow minor punctuation differences
        $userClean = preg_replace('/[^\w\s]/', '', $normalizedUser);
        $correctClean = preg_replace('/[^\w\s]/', '', $normalizedCorrect);

        if ($userClean === $correctClean) {
            return true;
        }

        // Use similarity check for transformation questions
        similar_text($normalizedUser, $normalizedCorrect, $similarity);
        return $similarity >= 90;
    }

    /**
     * Calculate score for an answer
     */
    private function calculateScore(
        array $question,
        bool $isCorrect,
        int $timeSpent,
        int $currentStreak,
        array $level,
        bool $doublePointsActive,
        bool $hintUsed
    ): array {
        if (!$isCorrect) {
            return [
                'base_points' => 0,
                'time_bonus' => 0,
                'streak_bonus' => 0,
                'difficulty_bonus' => 0,
                'type_bonus' => 0,
                'no_hint_bonus' => 0,
                'double_points_multiplier' => 1,
                'total_points' => 0,
            ];
        }

        $config = $this->dataService->getConfig();
        $scoring = $config['scoring'] ?? [];

        // Base points from question
        $basePoints = $question['points'] ?? ($scoring['base_points'] ?? 100);

        // Time bonus (faster = more points)
        $maxTimeBonus = $scoring['time_bonus_max'] ?? 50;
        $expectedTime = 30; // seconds
        $timeFactor = max(0, 1 - ($timeSpent / ($expectedTime * 2)));
        $timeBonus = (int)($maxTimeBonus * $timeFactor);

        // Streak multiplier
        $streakBase = $scoring['streak_multiplier_base'] ?? 1.0;
        $streakIncrement = $scoring['streak_multiplier_increment'] ?? 0.2;
        $streakMax = $scoring['streak_multiplier_max'] ?? 3.0;
        $streakMultiplier = min($streakMax, $streakBase + ($currentStreak * $streakIncrement));

        // Difficulty multiplier from level
        $difficultyMultiplier = $level['difficulty_multiplier'] ?? 1.0;

        // Quiz type multiplier
        $quizTypes = $config['quiz_types'] ?? [];
        $typeMultiplier = $quizTypes[$question['type']]['point_multiplier'] ?? 1.0;

        // No hint bonus
        $noHintBonus = !$hintUsed ? ($scoring['no_hint_bonus'] ?? 15) : 0;

        // Calculate total
        $subtotal = $basePoints + $timeBonus + $noHintBonus;
        $multipliedScore = $subtotal * $streakMultiplier * $difficultyMultiplier * $typeMultiplier;

        // Double points
        $doubleMultiplier = $doublePointsActive ? 2 : 1;
        $totalPoints = (int)($multipliedScore * $doubleMultiplier);

        return [
            'base_points' => $basePoints,
            'time_bonus' => $timeBonus,
            'streak_multiplier' => round($streakMultiplier, 2),
            'difficulty_multiplier' => $difficultyMultiplier,
            'type_multiplier' => $typeMultiplier,
            'no_hint_bonus' => $noHintBonus,
            'double_points_multiplier' => $doubleMultiplier,
            'total_points' => $totalPoints,
        ];
    }

    /**
     * Use hint
     */
    public function useHint(string $sessionId): array
    {
        $session = Cache::get("grammar_quiz_session_{$sessionId}");
        if (!$session) {
            return ['success' => false, 'error' => 'Session not found'];
        }

        $level = $session['level_data'];
        $difficulty = $this->dataService->getDifficultyLevels()[$level['difficulty']] ?? [];
        $maxHints = $difficulty['hints_allowed'] ?? 3;

        if ($session['hints_used'] >= $maxHints) {
            return ['success' => false, 'error' => 'No hints remaining'];
        }

        $session['hints_used']++;
        $currentQuestion = $session['questions'][$session['current_index']];

        Cache::put("grammar_quiz_session_{$sessionId}", $session, $this->sessionTtl);

        return [
            'success' => true,
            'hint' => [
                'grammar_rule' => $currentQuestion['grammar_rule'] ?? null,
                'grammar_rule_uz' => $currentQuestion['grammar_rule_uz'] ?? null,
            ],
            'hints_remaining' => $maxHints - $session['hints_used'],
        ];
    }

    /**
     * Skip question
     */
    public function skipQuestion(string $sessionId): array
    {
        $session = Cache::get("grammar_quiz_session_{$sessionId}");
        if (!$session) {
            return ['success' => false, 'error' => 'Session not found'];
        }

        $currentQuestion = $session['questions'][$session['current_index']];

        // Record skipped answer
        $session['answers'][] = [
            'question_id' => $currentQuestion['id'],
            'user_answer' => null,
            'correct_answer' => $currentQuestion['correct_answer'],
            'is_correct' => false,
            'time_spent' => 0,
            'points_earned' => 0,
            'skipped' => true,
        ];

        // Reset streak
        $session['streak'] = 0;
        $session['skips_used']++;
        $session['current_index']++;

        $hasNextQuestion = $session['current_index'] < count($session['questions']);

        Cache::put("grammar_quiz_session_{$sessionId}", $session, $this->sessionTtl);

        $response = [
            'success' => true,
            'correct_answer' => $currentQuestion['correct_answer'],
            'explanation' => $currentQuestion['explanation'] ?? null,
            'has_next_question' => $hasNextQuestion,
        ];

        if ($hasNextQuestion) {
            $response['next_question'] = $this->formatQuestionForClient($session['questions'][$session['current_index']]);
            $response['next_index'] = $session['current_index'];
        }

        return $response;
    }

    /**
     * Use 50/50 powerup
     */
    public function useFiftyFifty(string $sessionId): array
    {
        $session = Cache::get("grammar_quiz_session_{$sessionId}");
        if (!$session) {
            return ['success' => false, 'error' => 'Session not found'];
        }

        $currentQuestion = $session['questions'][$session['current_index']];

        // Only works for questions with options
        if (!isset($currentQuestion['options']) || count($currentQuestion['options']) < 4) {
            return ['success' => false, 'error' => '50/50 not available for this question type'];
        }

        $session['fifty_fifty_used']++;

        $correctAnswer = $currentQuestion['correct_answer'];
        $options = $currentQuestion['options'];

        // Keep correct answer and one random wrong answer
        $wrongOptions = array_filter($options, fn($o) => $this->normalizeAnswer($o) !== $this->normalizeAnswer($correctAnswer));
        $wrongOptions = array_values($wrongOptions);
        shuffle($wrongOptions);

        $remainingOptions = [$correctAnswer, $wrongOptions[0]];
        shuffle($remainingOptions);

        Cache::put("grammar_quiz_session_{$sessionId}", $session, $this->sessionTtl);

        return [
            'success' => true,
            'remaining_options' => $remainingOptions,
        ];
    }

    /**
     * Add extra time
     */
    public function addExtraTime(string $sessionId, int $seconds = 15): array
    {
        $session = Cache::get("grammar_quiz_session_{$sessionId}");
        if (!$session) {
            return ['success' => false, 'error' => 'Session not found'];
        }

        $session['extra_time_added'] += $seconds;
        $session['time_limit'] += $seconds;

        Cache::put("grammar_quiz_session_{$sessionId}", $session, $this->sessionTtl);

        return [
            'success' => true,
            'extra_seconds' => $seconds,
            'new_time_limit' => $session['time_limit'],
        ];
    }

    /**
     * Activate double points for next question
     */
    public function activateDoublePoints(string $sessionId): array
    {
        $session = Cache::get("grammar_quiz_session_{$sessionId}");
        if (!$session) {
            return ['success' => false, 'error' => 'Session not found'];
        }

        $session['double_points_active'] = true;

        Cache::put("grammar_quiz_session_{$sessionId}", $session, $this->sessionTtl);

        return [
            'success' => true,
            'message' => 'Double points activated for next correct answer',
        ];
    }

    /**
     * Complete session
     */
    public function completeSession(string $sessionId): array
    {
        $session = Cache::get("grammar_quiz_session_{$sessionId}");
        if (!$session) {
            return ['success' => false, 'error' => 'Session not found'];
        }

        if ($session['completed']) {
            return ['success' => false, 'error' => 'Session already completed'];
        }

        $session['completed'] = true;
        $session['time_ended'] = now()->timestamp;

        // Calculate results
        $totalQuestions = count($session['questions']);
        $correctAnswers = count(array_filter($session['answers'], fn($a) => $a['is_correct'] ?? false));
        $accuracy = $totalQuestions > 0 ? ($correctAnswers / $totalQuestions) * 100 : 0;

        // Calculate stars
        $config = $session['config'];
        $starThresholds = $config['star_thresholds'] ?? ['one_star' => 50, 'two_stars' => 75, 'three_stars' => 90];

        $stars = 0;
        if ($accuracy >= $starThresholds['three_stars']) {
            $stars = 3;
        } elseif ($accuracy >= $starThresholds['two_stars']) {
            $stars = 2;
        } elseif ($accuracy >= $starThresholds['one_star']) {
            $stars = 1;
        }

        // Calculate rewards using GameScoringService
        $user = \App\Models\User::find($session['user_id']);
        $sessionRewards = $this->scoringService->calculateSessionRewards([
            'correct' => $correctAnswers,
            'total' => $totalQuestions,
            'difficulty' => $session['level_data']['difficulty'] ?? 'medium',
            'streak' => $session['max_streak'],
            'is_perfect' => $accuracy >= 100,
            'is_first_completion' => !$this->dataService->isLevelCompleted($session['user_id'], $session['level_number']),
        ], $user);

        $xpEarned = $sessionRewards['xp_earned'];
        $coinsEarned = $sessionRewards['coins_earned'];

        // Combo bonuses
        $comboBonuses = $this->calculateComboBonuses($session['max_streak'], $config['scoring'] ?? []);

        $results = [
            'success' => true,
            'level_number' => $session['level_number'],
            'score' => $session['score'],
            'total_questions' => $totalQuestions,
            'correct_answers' => $correctAnswers,
            'accuracy' => round($accuracy, 1),
            'stars' => $stars,
            'xp_earned' => $xpEarned,
            'coins_earned' => $coinsEarned,
            'max_streak' => $session['max_streak'],
            'hints_used' => $session['hints_used'],
            'skips_used' => $session['skips_used'],
            'fifty_fifty_used' => $session['fifty_fifty_used'],
            'time_taken' => $session['time_ended'] - $session['time_started'],
            'combo_bonuses' => $comboBonuses,
            'categories_used' => $session['categories_used'],
            'answers_breakdown' => $this->getAnswersBreakdown($session['answers']),
        ];

        // Update user progress
        $this->dataService->updateLevelProgress($session['user_id'], $session['level_number'], $results);

        Cache::put("grammar_quiz_session_{$sessionId}", $session, 300); // Keep for 5 min after completion

        return $results;
    }

    /**
     * Calculate XP reward
     */
    private function calculateXpReward(array $session, int $correct, int $total, int $stars, array $rewards): int
    {
        $xp = 0;

        // XP per correct answer
        $xpPerCorrect = $rewards['xp_per_correct'] ?? 10;
        $xpPerPerfect = $rewards['xp_per_perfect'] ?? 20;

        $xp += $correct * $xpPerCorrect;

        // Bonus for no mistakes
        if ($correct === $total && $total > 0) {
            $xp += $rewards['xp_bonus_no_mistakes'] ?? 50;
        }

        // Bonus for full streak
        if ($session['max_streak'] >= $total && $total > 0) {
            $xp += $rewards['xp_bonus_full_streak'] ?? 100;
        }

        // Level XP reward
        $xp += $session['level_data']['xp_reward'] ?? 0;

        // Difficulty multiplier
        $xp = (int)($xp * ($session['level_data']['difficulty_multiplier'] ?? 1.0));

        return $xp;
    }

    /**
     * Calculate coin reward
     */
    private function calculateCoinReward(int $stars, float $accuracy, array $rewards): int
    {
        $coins = 0;

        // Coins per star
        $coinsPerStar = $rewards['coins_per_star'] ?? 15;
        $coins += $stars * $coinsPerStar;

        // Completion bonus
        $coins += $rewards['coins_completion_bonus'] ?? 25;

        // Perfect game bonus
        if ($accuracy >= 100) {
            $coins += $rewards['coins_perfect_game'] ?? 100;
        }

        return $coins;
    }

    /**
     * Calculate combo bonuses
     */
    private function calculateComboBonuses(int $maxStreak, array $scoring): array
    {
        $thresholds = $scoring['combo_thresholds'] ?? [3, 5, 10, 15, 20];
        $bonuses = $scoring['combo_bonuses'] ?? [10, 25, 50, 100, 200];

        $earnedBonuses = [];
        for ($i = 0; $i < count($thresholds); $i++) {
            if ($maxStreak >= $thresholds[$i]) {
                $earnedBonuses[] = [
                    'streak' => $thresholds[$i],
                    'bonus' => $bonuses[$i] ?? 0,
                ];
            }
        }

        return $earnedBonuses;
    }

    /**
     * Get answers breakdown
     */
    private function getAnswersBreakdown(array $answers): array
    {
        $byCategory = [];
        $byType = [];

        foreach ($answers as $answer) {
            $isCorrect = $answer['is_correct'] ?? false;

            // This would require storing category/type in answers
            // For now, return basic stats
        }

        return [
            'total' => count($answers),
            'correct' => count(array_filter($answers, fn($a) => $a['is_correct'] ?? false)),
            'skipped' => count(array_filter($answers, fn($a) => $a['skipped'] ?? false)),
        ];
    }

    /**
     * Get session state
     */
    public function getSessionState(string $sessionId): array
    {
        $session = Cache::get("grammar_quiz_session_{$sessionId}");
        if (!$session) {
            return ['success' => false, 'error' => 'Session not found'];
        }

        $totalQuestions = count($session['questions']);
        $correctAnswers = count(array_filter($session['answers'], fn($a) => $a['is_correct'] ?? false));

        $state = [
            'success' => true,
            'session_id' => $sessionId,
            'level_number' => $session['level_number'],
            'current_index' => $session['current_index'],
            'total_questions' => $totalQuestions,
            'answered_questions' => count($session['answers']),
            'correct_answers' => $correctAnswers,
            'score' => $session['score'],
            'streak' => $session['streak'],
            'max_streak' => $session['max_streak'],
            'hints_used' => $session['hints_used'],
            'time_started' => $session['time_started'],
            'time_limit' => $session['time_limit'],
            'completed' => $session['completed'],
        ];

        if (!$session['completed'] && $session['current_index'] < $totalQuestions) {
            $state['current_question'] = $this->formatQuestionForClient($session['questions'][$session['current_index']]);
        }

        return $state;
    }
}
