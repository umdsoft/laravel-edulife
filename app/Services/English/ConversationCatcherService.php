<?php

namespace App\Services\English;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class ConversationCatcherService
{
    protected ConversationCatcherDataService $dataService;
    protected string $sessionPath;

    public function __construct(ConversationCatcherDataService $dataService)
    {
        $this->dataService = $dataService;
        $this->sessionPath = storage_path('app/game-sessions/conversation-catcher');

        if (!File::exists($this->sessionPath)) {
            File::makeDirectory($this->sessionPath, 0755, true);
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

        if (!$this->dataService->isLevelUnlocked($levelNumber)) {
            throw new \Exception('Level is locked');
        }

        $dialogues = $level['dialogues'];
        $sessionId = Str::uuid()->toString();

        $session = [
            'id' => $sessionId,
            'user_id' => Auth::id() ?? 'guest',
            'level_id' => $level['id'],
            'level_number' => $levelNumber,
            'mode' => $mode,
            'dialogues' => $this->prepareDialogues($dialogues),
            'current_index' => 0,
            'score' => 0,
            'correct_answers' => 0,
            'total_answers' => 0,
            'streak' => 0,
            'best_streak' => 0,
            'perfect_dialogues' => 0,
            'hints_used' => 0,
            'skips_used' => 0,
            'powerups_used' => [],
            'time_per_dialogue' => $level['time_per_dialogue'] ?? null,
            'total_time' => $level['total_time'] ?? null,
            'time_remaining' => $level['total_time'] ?? null,
            'started_at' => now()->toIso8601String(),
            'completed' => false,
            'answers' => [],
        ];

        $this->saveSession($session);

        return [
            'session_id' => $sessionId,
            'level' => $level,
            'dialogues' => $this->getDialoguesForClient($session['dialogues']),
            'mode' => $mode,
            'time_per_dialogue' => $session['time_per_dialogue'],
            'total_time' => $session['total_time'],
        ];
    }

    /**
     * Prepare dialogues for the session
     */
    protected function prepareDialogues(array $dialogues): array
    {
        $prepared = [];

        foreach ($dialogues as $dialogue) {
            $dialogueData = [
                'id' => $dialogue['id'],
                'context' => $dialogue['context'],
                'context_uz' => $dialogue['context_uz'] ?? '',
                'category' => $dialogue['category'] ?? 'general',
                'lines' => [],
            ];

            foreach ($dialogue['dialogue'] as $line) {
                $lineData = [
                    'speaker' => $line['speaker'],
                    'text' => $line['text'],
                    'text_uz' => $line['text_uz'] ?? '',
                ];

                if (isset($line['answer'])) {
                    $lineData['is_question'] = true;
                    $lineData['answer'] = $line['answer'];
                    $lineData['answer_uz'] = $line['answer_uz'] ?? '';
                    $lineData['options'] = $line['options'] ?? [];

                    shuffle($lineData['options']);
                } else {
                    $lineData['is_question'] = false;
                }

                $dialogueData['lines'][] = $lineData;
            }

            $prepared[] = $dialogueData;
        }

        return $prepared;
    }

    /**
     * Get dialogues for client (without answers)
     */
    protected function getDialoguesForClient(array $dialogues): array
    {
        $clientDialogues = [];

        foreach ($dialogues as $dialogue) {
            $clientDialogue = [
                'id' => $dialogue['id'],
                'context' => $dialogue['context'],
                'context_uz' => $dialogue['context_uz'],
                'category' => $dialogue['category'],
                'lines' => [],
            ];

            foreach ($dialogue['lines'] as $line) {
                $clientLine = [
                    'speaker' => $line['speaker'],
                    'text' => $line['is_question'] ? '___' : $line['text'],
                    'text_uz' => $line['text_uz'],
                    'is_question' => $line['is_question'],
                ];

                if ($line['is_question']) {
                    $clientLine['options'] = $line['options'];
                }

                $clientDialogue['lines'][] = $clientLine;
            }

            $clientDialogues[] = $clientDialogue;
        }

        return $clientDialogues;
    }

    /**
     * Submit an answer
     */
    public function submitAnswer(string $sessionId, int $dialogueIndex, string $answer, float $timeSpent): array
    {
        $session = $this->getSession($sessionId);

        if (!$session) {
            throw new \Exception('Session not found');
        }

        if ($session['completed']) {
            throw new \Exception('Session already completed');
        }

        if ($dialogueIndex !== $session['current_index']) {
            throw new \Exception('Invalid dialogue index');
        }

        $dialogue = $session['dialogues'][$dialogueIndex];
        $correctAnswer = null;

        foreach ($dialogue['lines'] as $line) {
            if ($line['is_question']) {
                $correctAnswer = $line['answer'];
                break;
            }
        }

        $isCorrect = $this->normalizeAnswer($answer) === $this->normalizeAnswer($correctAnswer);
        $scoringConfig = $this->dataService->getScoringConfig();

        $pointsEarned = 0;
        if ($isCorrect) {
            $pointsEarned = $scoringConfig['base_points'];

            // Streak bonus
            $session['streak']++;
            $pointsEarned += $session['streak'] * $scoringConfig['streak_bonus_per_dialogue'];

            // First try bonus (no hints used for this dialogue)
            if (!in_array($dialogueIndex, $session['answers'] ?? [])) {
                $pointsEarned += $scoringConfig['first_try_bonus'];
            }

            // Time bonus for timed modes
            if ($session['time_per_dialogue'] && $timeSpent < $session['time_per_dialogue'] * 0.5) {
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
        $session['answers'][$dialogueIndex] = [
            'answer' => $answer,
            'correct' => $isCorrect,
            'time_spent' => $timeSpent,
            'points' => $pointsEarned,
        ];

        $session['current_index']++;

        // Check if session is complete
        $isComplete = $session['current_index'] >= count($session['dialogues']);

        if ($isComplete) {
            $session['completed'] = true;
            $session['completed_at'] = now()->toIso8601String();
        }

        $this->saveSession($session);

        $result = [
            'correct' => $isCorrect,
            'correct_answer' => $correctAnswer,
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
     * Normalize answer for comparison
     */
    protected function normalizeAnswer(string $answer): string
    {
        $normalized = strtolower(trim($answer));
        $normalized = preg_replace('/[^\w\s]/', '', $normalized);
        $normalized = preg_replace('/\s+/', ' ', $normalized);
        return $normalized;
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

        $currentDialogue = $session['dialogues'][$session['current_index']] ?? null;

        switch ($powerupId) {
            case 'hint':
                $session['hints_used']++;
                if ($currentDialogue) {
                    foreach ($currentDialogue['lines'] as $line) {
                        if ($line['is_question']) {
                            $answer = $line['answer'];
                            $words = explode(' ', $answer);
                            $hintWords = array_slice($words, 0, min(3, count($words)));
                            $result['hint'] = implode(' ', $hintWords) . '...';
                            break;
                        }
                    }
                }
                break;

            case 'skip':
                $session['skips_used']++;
                $session['current_index']++;
                $result['skipped'] = true;
                $result['current_index'] = $session['current_index'];

                if ($session['current_index'] >= count($session['dialogues'])) {
                    $session['completed'] = true;
                    $session['completed_at'] = now()->toIso8601String();
                    $result['is_complete'] = true;
                    $result['summary'] = $this->generateSummary($session);
                }
                break;

            case 'reveal_word':
                if ($currentDialogue) {
                    foreach ($currentDialogue['lines'] as $line) {
                        if ($line['is_question']) {
                            $answer = $line['answer'];
                            $words = explode(' ', $answer);
                            $revealIndex = rand(0, count($words) - 1);
                            $result['revealed_word'] = $words[$revealIndex];
                            $result['word_position'] = $revealIndex;
                            break;
                        }
                    }
                }
                break;

            case 'extra_time':
                $timeBonus = $powerup['time_bonus'] ?? 15;
                if ($session['time_remaining'] !== null) {
                    $session['time_remaining'] += $timeBonus;
                }
                $result['time_bonus'] = $timeBonus;
                $result['time_remaining'] = $session['time_remaining'];
                break;

            case 'replay_audio':
                $result['replay_allowed'] = true;
                break;

            case 'translation':
                if ($currentDialogue) {
                    $result['context_uz'] = $currentDialogue['context_uz'];
                    foreach ($currentDialogue['lines'] as $line) {
                        if ($line['is_question']) {
                            $result['answer_uz'] = $line['answer_uz'] ?? '';
                            break;
                        }
                    }
                }
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
        $this->dataService->updateLevelProgress($session['level_id'], $summary);

        return $summary;
    }

    /**
     * Generate session summary
     */
    protected function generateSummary(array $session): array
    {
        $totalDialogues = count($session['dialogues']);
        $correctAnswers = $session['correct_answers'];
        $accuracy = $totalDialogues > 0 ? round(($correctAnswers / $totalDialogues) * 100) : 0;

        // Calculate stars
        $stars = 0;
        if ($accuracy >= 60) $stars = 1;
        if ($accuracy >= 80) $stars = 2;
        if ($accuracy >= 95) $stars = 3;

        // Calculate XP
        $xpEarned = $session['score'];
        if ($stars === 3) {
            $xpEarned += 50; // Perfect game bonus
        }

        // Calculate coins
        $coinsEarned = (int)($session['score'] / 10);
        $coinsEarned += $stars * 5;

        // Count perfect dialogues (first try, correct)
        $perfectDialogues = 0;
        foreach ($session['answers'] as $answer) {
            if ($answer['correct'] ?? false) {
                $perfectDialogues++;
            }
        }

        return [
            'completed' => true,
            'level_number' => $session['level_number'],
            'score' => $session['score'],
            'correct_answers' => $correctAnswers,
            'total_answers' => $totalDialogues,
            'accuracy' => $accuracy,
            'stars' => $stars,
            'streak' => $session['best_streak'],
            'perfect_dialogues' => $perfectDialogues,
            'hints_used' => $session['hints_used'],
            'skips_used' => $session['skips_used'],
            'xp_earned' => $xpEarned,
            'coins_earned' => $coinsEarned,
            'time_expired' => $session['time_expired'] ?? false,
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
            'dialogues' => $this->getDialoguesForClient($session['dialogues']),
            'powerups_used' => $session['powerups_used'],
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
