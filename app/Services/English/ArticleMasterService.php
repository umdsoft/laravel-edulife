<?php

namespace App\Services\English;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ArticleMasterService
{
    protected ArticleMasterDataService $dataService;
    protected GameScoringService $scoringService;
    protected string $sessionPath;

    public function __construct(ArticleMasterDataService $dataService, GameScoringService $scoringService)
    {
        $this->dataService = $dataService;
        $this->scoringService = $scoringService;
        $this->sessionPath = storage_path('app/game-sessions/article-master');

        if (!File::exists($this->sessionPath)) {
            File::makeDirectory($this->sessionPath, 0755, true);
        }
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

        $questions = $this->dataService->getQuestionsForLevel($level);
        $sessionId = Str::uuid()->toString();

        $session = [
            'id' => $sessionId,
            'user_id' => Auth::id() ?? 'guest',
            'level_id' => $level['id'],
            'level_number' => $levelNumber,
            'questions' => $questions,
            'current_index' => 0,
            'score' => 0,
            'correct_answers' => 0,
            'total_answers' => 0,
            'streak' => 0,
            'best_streak' => 0,
            'hints_used' => 0,
            'skips_used' => 0,
            'powerups_used' => [],
            'time_limit' => $level['time_limit'],
            'time_remaining' => $level['time_limit'],
            'started_at' => now()->toIso8601String(),
            'completed' => false,
            'answers' => [],
        ];

        $this->saveSession($session);

        return [
            'session_id' => $sessionId,
            'level' => $level,
            'questions' => $this->getQuestionsForClient($questions),
            'question_count' => count($questions),
            'time_limit' => $level['time_limit'],
        ];
    }

    protected function getQuestionsForClient(array $questions): array
    {
        return array_map(function ($q, $index) {
            $clientQuestion = [
                'index' => $index,
                'id' => $q['id'],
                'type' => $q['type'],
                'sentence' => $q['sentence'],
                'options' => $q['options'],
                'translation' => $q['translation'],
                'category' => $q['category'],
            ];

            return $clientQuestion;
        }, $questions, array_keys($questions));
    }

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

        $isCorrect = $this->normalizeAnswer($answer) === $this->normalizeAnswer($correctAnswer);
        $scoringConfig = $this->dataService->getScoringConfig();

        $pointsEarned = 0;
        if ($isCorrect) {
            $pointsEarned = $scoringConfig['base_points'];

            $session['streak']++;
            $pointsEarned += $session['streak'] * $scoringConfig['streak_bonus_per_question'];

            if (!isset($session['answers'][$questionIndex])) {
                $pointsEarned += $scoringConfig['no_hint_bonus'];
            }

            // Time bonus
            if ($session['time_limit'] && $timeSpent < 10) {
                $pointsEarned = (int)($pointsEarned * $scoringConfig['time_bonus_multiplier']);
            }

            $session['correct_answers']++;

            if ($session['streak'] > $session['best_streak']) {
                $session['best_streak'] = $session['streak'];
            }
        } else {
            $session['streak'] = 0;
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

        $isComplete = $session['current_index'] >= count($session['questions']);

        if ($isComplete) {
            $session['completed'] = true;
            $session['completed_at'] = now()->toIso8601String();
        }

        $this->saveSession($session);

        $result = [
            'correct' => $isCorrect,
            'correct_answer' => $correctAnswer,
            'rule' => $question['rule'] ?? null,
            'rule_uz' => $question['rule_uz'] ?? null,
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

    protected function normalizeAnswer(string $answer): string
    {
        $normalized = strtolower(trim($answer));
        $normalized = preg_replace('/[^a-z0-9,\s-]/', '', $normalized);
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        return $normalized;
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
            case 'hint':
                $session['hints_used']++;
                if ($currentQuestion) {
                    $answer = $currentQuestion['answer'];
                    // Give first part of answer as hint
                    $parts = explode(',', $answer);
                    $result['hint'] = "Birinchi artikl: " . trim($parts[0]);
                }
                break;

            case 'fifty_fifty':
                if ($currentQuestion && isset($currentQuestion['options'])) {
                    $correctAnswer = $currentQuestion['answer'];
                    $options = $currentQuestion['options'];
                    $wrongOptions = array_filter($options, function ($opt) use ($correctAnswer) {
                        return $this->normalizeAnswer($opt) !== $this->normalizeAnswer($correctAnswer);
                    });

                    shuffle($wrongOptions);
                    $removedOptions = array_slice($wrongOptions, 0, 2);

                    $result['removed_options'] = $removedOptions;
                }
                break;

            case 'skip':
                $session['skips_used']++;
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

            case 'extra_time':
                $timeBonus = $powerup['time_bonus'] ?? 30;
                if ($session['time_remaining'] !== null) {
                    $session['time_remaining'] += $timeBonus;
                }
                $result['time_bonus'] = $timeBonus;
                $result['time_remaining'] = $session['time_remaining'];
                break;

            case 'show_rule':
                if ($currentQuestion) {
                    $result['rule'] = $currentQuestion['rule'] ?? null;
                    $result['rule_uz'] = $currentQuestion['rule_uz'] ?? null;
                }
                break;
        }

        $this->saveSession($session);

        return $result;
    }

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

        $this->dataService->updateLevelProgress($session['level_id'], $summary);

        return $summary;
    }

    protected function generateSummary(array $session): array
    {
        $totalQuestions = count($session['questions']);
        $correctAnswers = $session['correct_answers'];
        $accuracy = $totalQuestions > 0 ? round(($correctAnswers / $totalQuestions) * 100) : 0;

        $stars = 0;
        if ($accuracy >= 60) $stars = 1;
        if ($accuracy >= 80) $stars = 2;
        if ($accuracy >= 95) $stars = 3;

        $xpEarned = $session['score'];
        if ($stars === 3) {
            $xpEarned += 50;
        }
        if ($session['hints_used'] === 0) {
            $xpEarned += 25;
        }

        $coinsEarned = (int)($session['score'] / 10);
        $coinsEarned += $stars * 5;

        return [
            'completed' => true,
            'level_number' => $session['level_number'],
            'score' => $session['score'],
            'correct_answers' => $correctAnswers,
            'total_questions' => $totalQuestions,
            'accuracy' => $accuracy,
            'stars' => $stars,
            'best_streak' => $session['best_streak'],
            'hints_used' => $session['hints_used'],
            'skips_used' => $session['skips_used'],
            'xp_earned' => $xpEarned,
            'coins_earned' => $coinsEarned,
            'time_expired' => $session['time_expired'] ?? false,
        ];
    }

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
        ];
    }

    protected function getSession(string $sessionId): ?array
    {
        $sessionFile = $this->sessionPath . "/{$sessionId}.json";

        if (File::exists($sessionFile)) {
            return json_decode(File::get($sessionFile), true);
        }

        return null;
    }

    protected function saveSession(array $session): void
    {
        $sessionFile = $this->sessionPath . "/{$session['id']}.json";
        File::put($sessionFile, json_encode($session, JSON_PRETTY_PRINT));
    }
}
