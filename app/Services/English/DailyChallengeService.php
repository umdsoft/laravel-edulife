<?php

namespace App\Services\English;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DailyChallengeService
{
    protected DailyChallengeDataService $dataService;
    protected string $sessionPath;

    public function __construct(DailyChallengeDataService $dataService)
    {
        $this->dataService = $dataService;
        $this->sessionPath = storage_path('app/game-sessions/daily-challenge');

        if (!File::exists($this->sessionPath)) {
            File::makeDirectory($this->sessionPath, 0755, true);
        }
    }

    /**
     * Start a new game session
     */
    public function startSession(int $levelNumber, string $challengeType = 'daily'): array
    {
        $level = $this->dataService->getLevel($levelNumber);

        if (!$level) {
            throw new \Exception('Level not found');
        }

        if (!$this->dataService->isLevelUnlocked($levelNumber)) {
            throw new \Exception('Level is locked');
        }

        $questions = $level['questions'];
        $sessionId = Str::uuid()->toString();

        // Update streak
        $streakResult = $this->dataService->updateStreak();

        $session = [
            'id' => $sessionId,
            'user_id' => Auth::id() ?? 'guest',
            'level_id' => $level['id'],
            'level_number' => $levelNumber,
            'challenge_type' => $challengeType,
            'questions' => $this->prepareQuestions($questions),
            'current_index' => 0,
            'score' => 0,
            'correct_answers' => 0,
            'total_answers' => 0,
            'streak' => 0,
            'best_streak' => 0,
            'time_limit' => $level['time_limit'] ?? null,
            'time_remaining' => $level['time_limit'] ?? null,
            'double_points_active' => false,
            'timer_frozen' => false,
            'freeze_end_time' => null,
            'powerups_used' => [],
            'started_at' => now()->toIso8601String(),
            'completed' => false,
            'answers' => [],
            'streak_result' => $streakResult,
        ];

        $this->saveSession($session);

        return [
            'session_id' => $sessionId,
            'level' => $level,
            'questions' => $this->getQuestionsForClient($session['questions']),
            'challenge_type' => $challengeType,
            'time_limit' => $session['time_limit'],
            'streak_result' => $streakResult,
        ];
    }

    /**
     * Start daily challenge
     */
    public function startDailyChallenge(): array
    {
        if ($this->dataService->isDailyChallengeCompleted()) {
            throw new \Exception('Daily challenge already completed today');
        }

        $dailyChallenge = $this->dataService->getDailyChallenge();
        $sessionId = Str::uuid()->toString();

        $streakResult = $this->dataService->updateStreak();

        $session = [
            'id' => $sessionId,
            'user_id' => Auth::id() ?? 'guest',
            'level_id' => 'daily_' . $dailyChallenge['date'],
            'level_number' => 0,
            'challenge_type' => 'daily',
            'is_daily' => true,
            'questions' => $this->prepareQuestions($dailyChallenge['questions']),
            'current_index' => 0,
            'score' => 0,
            'correct_answers' => 0,
            'total_answers' => 0,
            'streak' => 0,
            'best_streak' => 0,
            'bonus_multiplier' => $dailyChallenge['bonus_multiplier'],
            'time_limit' => $dailyChallenge['time_limit'],
            'time_remaining' => $dailyChallenge['time_limit'],
            'double_points_active' => false,
            'timer_frozen' => false,
            'powerups_used' => [],
            'started_at' => now()->toIso8601String(),
            'completed' => false,
            'answers' => [],
            'streak_result' => $streakResult,
        ];

        $this->saveSession($session);

        return [
            'session_id' => $sessionId,
            'date' => $dailyChallenge['date'],
            'questions' => $this->getQuestionsForClient($session['questions']),
            'time_limit' => $session['time_limit'],
            'bonus_multiplier' => $session['bonus_multiplier'],
            'streak_result' => $streakResult,
        ];
    }

    /**
     * Prepare questions for the session
     */
    protected function prepareQuestions(array $questions): array
    {
        $prepared = [];

        foreach ($questions as $question) {
            $preparedQuestion = [
                'id' => $question['id'],
                'question' => $question['question'],
                'question_uz' => $question['question_uz'] ?? '',
                'type' => $question['type'] ?? 'multiple_choice',
                'category' => $question['category'] ?? 'general',
                'difficulty' => $question['difficulty'] ?? 'easy',
                'options' => $question['options'] ?? [],
                'answer' => $question['answer'],
                'explanation' => $question['explanation'] ?? '',
                'explanation_uz' => $question['explanation_uz'] ?? '',
            ];

            // Handle reading passages
            if (isset($question['passage'])) {
                $preparedQuestion['passage'] = $question['passage'];
                $preparedQuestion['passage_uz'] = $question['passage_uz'] ?? '';
            }

            // Handle listening audio text
            if (isset($question['audio_text'])) {
                $preparedQuestion['audio_text'] = $question['audio_text'];
                $preparedQuestion['audio_text_uz'] = $question['audio_text_uz'] ?? '';
            }

            // Shuffle options
            if (!empty($preparedQuestion['options'])) {
                shuffle($preparedQuestion['options']);
            }

            $prepared[] = $preparedQuestion;
        }

        return $prepared;
    }

    /**
     * Get questions for client (without answers)
     */
    protected function getQuestionsForClient(array $questions): array
    {
        $clientQuestions = [];

        foreach ($questions as $question) {
            $clientQuestion = [
                'id' => $question['id'],
                'question' => $question['question'],
                'question_uz' => $question['question_uz'],
                'type' => $question['type'],
                'category' => $question['category'],
                'difficulty' => $question['difficulty'],
                'options' => $question['options'],
            ];

            if (isset($question['passage'])) {
                $clientQuestion['passage'] = $question['passage'];
                $clientQuestion['passage_uz'] = $question['passage_uz'];
            }

            if (isset($question['audio_text'])) {
                $clientQuestion['audio_text'] = $question['audio_text'];
            }

            $clientQuestions[] = $clientQuestion;
        }

        return $clientQuestions;
    }

    /**
     * Submit an answer
     */
    public function submitAnswer(string $sessionId, int $questionIndex, string $answer, float $timeSpent): array
    {
        $session = $this->getSession($sessionId);

        if (!$session) {
            throw new \Exception('Session not found');
        }

        if ($session['completed']) {
            throw new \Exception('Session already completed');
        }

        if ($questionIndex !== $session['current_index']) {
            throw new \Exception('Invalid question index');
        }

        $question = $session['questions'][$questionIndex];
        $correctAnswer = $question['answer'];
        $isCorrect = strtolower(trim($answer)) === strtolower(trim($correctAnswer));

        $scoringConfig = $this->dataService->getScoringConfig();
        $pointsEarned = 0;

        if ($isCorrect) {
            $pointsEarned = $scoringConfig['base_points'];

            // Time bonus
            $timeLimit = $session['time_limit'] ? ($session['time_limit'] / count($session['questions'])) : 30;
            if ($timeSpent < $timeLimit * 0.5) {
                $pointsEarned += $scoringConfig['time_bonus_max'];
            } elseif ($timeSpent < $timeLimit * 0.75) {
                $pointsEarned += (int)($scoringConfig['time_bonus_max'] * 0.5);
            }

            // Streak bonus
            $session['streak']++;
            $pointsEarned += $session['streak'] * $scoringConfig['streak_bonus_per_correct'];

            // Double points powerup
            if ($session['double_points_active']) {
                $pointsEarned *= 2;
                $session['double_points_active'] = false;
            }

            // Daily bonus multiplier
            if (isset($session['bonus_multiplier']) && $session['bonus_multiplier'] > 1) {
                $pointsEarned = (int)($pointsEarned * $session['bonus_multiplier']);
            }

            $session['correct_answers']++;

            if ($session['streak'] > $session['best_streak']) {
                $session['best_streak'] = $session['streak'];
            }
        } else {
            $session['streak'] = 0;
            $session['double_points_active'] = false;
        }

        $session['score'] += $pointsEarned;
        $session['total_answers']++;
        $session['answers'][$questionIndex] = [
            'answer' => $answer,
            'correct' => $isCorrect,
            'time_spent' => $timeSpent,
            'points' => $pointsEarned,
        ];

        $session['current_index']++;

        // Check if session is complete
        $isComplete = $session['current_index'] >= count($session['questions']);

        if ($isComplete) {
            $session['completed'] = true;
            $session['completed_at'] = now()->toIso8601String();
        }

        $this->saveSession($session);

        $result = [
            'correct' => $isCorrect,
            'correct_answer' => $correctAnswer,
            'explanation' => $question['explanation'],
            'explanation_uz' => $question['explanation_uz'],
            'points_earned' => $pointsEarned,
            'score' => $session['score'],
            'streak' => $session['streak'],
            'current_index' => $session['current_index'],
            'is_complete' => $isComplete,
        ];

        if ($isComplete) {
            $result['summary'] = $this->completeSession($sessionId);
        }

        return $result;
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

        $usedCount = $session['powerups_used'][$powerupId] ?? 0;
        $maxUses = $powerup['uses_per_game'] ?? 1;

        if ($usedCount >= $maxUses) {
            throw new \Exception('Powerup limit reached');
        }

        $session['powerups_used'][$powerupId] = $usedCount + 1;

        $result = [
            'powerup_id' => $powerupId,
            'uses_remaining' => $maxUses - $usedCount - 1,
        ];

        $currentQuestion = $session['questions'][$session['current_index']] ?? null;

        switch ($powerupId) {
            case 'extra_time':
                $timeBonus = $powerup['time_bonus'] ?? 30;
                if ($session['time_remaining'] !== null) {
                    $session['time_remaining'] += $timeBonus;
                }
                $result['time_bonus'] = $timeBonus;
                $result['time_remaining'] = $session['time_remaining'];
                break;

            case 'fifty_fifty':
                if ($currentQuestion && !empty($currentQuestion['options'])) {
                    $correctAnswer = $currentQuestion['answer'];
                    $wrongOptions = array_filter($currentQuestion['options'], fn($o) => $o !== $correctAnswer);
                    shuffle($wrongOptions);
                    $removedOptions = array_slice($wrongOptions, 0, 2);
                    $result['removed_options'] = $removedOptions;
                }
                break;

            case 'skip':
                $session['current_index']++;
                $result['skipped'] = true;
                $result['current_index'] = $session['current_index'];

                if ($session['current_index'] >= count($session['questions'])) {
                    $session['completed'] = true;
                    $session['completed_at'] = now()->toIso8601String();
                    $result['is_complete'] = true;
                    $result['summary'] = $this->generateSummary($session);
                }
                break;

            case 'hint':
                if ($currentQuestion) {
                    $answer = $currentQuestion['answer'];
                    $hint = substr($answer, 0, min(3, strlen($answer))) . '...';
                    $result['hint'] = "The answer starts with: {$hint}";
                }
                break;

            case 'double_points':
                $session['double_points_active'] = true;
                $result['activated'] = true;
                break;

            case 'freeze_timer':
                $freezeDuration = $powerup['freeze_duration'] ?? 15;
                $session['timer_frozen'] = true;
                $session['freeze_end_time'] = now()->addSeconds($freezeDuration)->toIso8601String();
                $result['freeze_duration'] = $freezeDuration;
                break;
        }

        $this->saveSession($session);

        return $result;
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

        if ($timeRemaining <= 0 && !$session['completed']) {
            $session['completed'] = true;
            $session['completed_at'] = now()->toIso8601String();
            $session['time_expired'] = true;
        }

        $this->saveSession($session);

        return [
            'time_remaining' => $session['time_remaining'],
            'completed' => $session['completed'],
        ];
    }

    /**
     * Complete a session
     */
    public function completeSession(string $sessionId): array
    {
        $session = $this->getSession($sessionId);

        if (!$session) {
            throw new \Exception('Session not found');
        }

        if (!$session['completed']) {
            $session['completed'] = true;
            $session['completed_at'] = now()->toIso8601String();
            $this->saveSession($session);
        }

        $summary = $this->generateSummary($session);

        // Update user progress
        $this->dataService->updateLevelProgress($session['level_id'], array_merge($summary, [
            'is_daily' => $session['is_daily'] ?? false,
        ]));

        return $summary;
    }

    /**
     * Generate session summary
     */
    protected function generateSummary(array $session): array
    {
        $totalQuestions = count($session['questions']);
        $correctAnswers = $session['correct_answers'];
        $accuracy = $totalQuestions > 0 ? round(($correctAnswers / $totalQuestions) * 100) : 0;

        // Calculate stars
        $stars = 0;
        if ($accuracy >= 60) $stars = 1;
        if ($accuracy >= 80) $stars = 2;
        if ($accuracy >= 95) $stars = 3;

        $scoringConfig = $this->dataService->getScoringConfig();

        // Calculate XP
        $xpEarned = $session['score'];
        if ($accuracy === 100) {
            $xpEarned += $scoringConfig['perfect_quiz_bonus'];
        }
        if ($session['is_daily'] ?? false) {
            $xpEarned += $scoringConfig['daily_completion_bonus'];
        }

        // Calculate coins
        $coinsEarned = (int)($session['score'] / 10);
        $coinsEarned += $stars * 5;

        return [
            'completed' => true,
            'level_number' => $session['level_number'],
            'score' => $session['score'],
            'correct_answers' => $correctAnswers,
            'total_answers' => $totalQuestions,
            'accuracy' => $accuracy,
            'stars' => $stars,
            'streak' => $session['best_streak'],
            'xp_earned' => $xpEarned,
            'coins_earned' => $coinsEarned,
            'time_expired' => $session['time_expired'] ?? false,
            'is_daily' => $session['is_daily'] ?? false,
        ];
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
            'session_id' => $session['id'],
            'current_index' => $session['current_index'],
            'score' => $session['score'],
            'correct_answers' => $session['correct_answers'],
            'streak' => $session['streak'],
            'time_remaining' => $session['time_remaining'],
            'completed' => $session['completed'],
            'questions' => $this->getQuestionsForClient($session['questions']),
            'powerups_used' => $session['powerups_used'],
            'double_points_active' => $session['double_points_active'],
        ];
    }

    /**
     * Get session from storage
     */
    protected function getSession(string $sessionId): ?array
    {
        $sessionFile = $this->sessionPath . "/{$sessionId}.json";

        if (File::exists($sessionFile)) {
            return json_decode(File::get($sessionFile), true);
        }

        return null;
    }

    /**
     * Save session to storage
     */
    protected function saveSession(array $session): void
    {
        $sessionFile = $this->sessionPath . "/{$session['id']}.json";
        File::put($sessionFile, json_encode($session, JSON_PRETTY_PRINT));
    }
}
