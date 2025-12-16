<?php

namespace App\Services\English;

use Illuminate\Support\Str;

class VerbConjugatorService
{
    protected VerbConjugatorDataService $dataService;
    protected string $sessionPath;

    public function __construct(VerbConjugatorDataService $dataService)
    {
        $this->dataService = $dataService;
        $this->sessionPath = storage_path('app/game-sessions/verb-conjugator');

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

        $verbs = $this->dataService->getVerbsForLevel($level);
        $questions = $this->generateQuestions($level, $verbs);

        $sessionId = Str::uuid()->toString();
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
            'powerups_used' => [],
            'time_started' => time(),
            'time_limit' => $this->getTimeLimit($level, $mode),
            'completed' => false,
        ];

        $this->saveSession($session);

        return [
            'session_id' => $sessionId,
            'level' => $level,
            'questions' => $this->sanitizeQuestionsForClient($questions),
            'total_questions' => count($questions),
            'time_limit' => $session['time_limit'],
            'mode' => $mode,
        ];
    }

    /**
     * Generate questions for the level
     */
    protected function generateQuestions(array $level, array $verbs): array
    {
        $questions = [];
        $tenses = $level['tenses'] ?? ['present_simple'];
        $pronouns = $this->dataService->getPronouns();
        $questionTypes = $level['question_types'] ?? ['conjugate'];
        $count = $level['questions_count'] ?? 10;

        for ($i = 0; $i < $count; $i++) {
            $verb = $verbs[$i % count($verbs)];
            $tense = $tenses[array_rand($tenses)];
            $pronoun = $pronouns[array_rand($pronouns)];
            $type = $questionTypes[array_rand($questionTypes)];

            $question = $this->generateQuestion($verb, $tense, $pronoun, $type);
            $questions[] = $question;
        }

        shuffle($questions);
        return $questions;
    }

    /**
     * Generate a single question
     */
    protected function generateQuestion(array $verb, string $tense, array $pronoun, string $type): array
    {
        $tensesConfig = $this->dataService->getTenses();
        $tenseName = '';
        foreach ($tensesConfig as $t) {
            if ($t['id'] === $tense) {
                $tenseName = $t['name'];
                break;
            }
        }

        $correctAnswer = $this->dataService->conjugateVerb($verb, $tense, $pronoun['id']);

        return match ($type) {
            'negative' => $this->generateNegativeQuestion($verb, $tense, $pronoun, $tenseName),
            'question' => $this->generateQuestionFormQuestion($verb, $tense, $pronoun, $tenseName),
            'identify' => $this->generateIdentifyQuestion($verb, $tense, $pronoun, $tenseName, $correctAnswer),
            default => [
                'type' => 'conjugate',
                'verb' => $verb,
                'tense' => $tense,
                'tense_name' => $tenseName,
                'pronoun' => $pronoun,
                'prompt' => "Conjugate '{$verb['base']}' in {$tenseName} for '{$pronoun['name']}'",
                'prompt_uz' => "'{$verb['base']}' fe'lini {$tenseName} zamonida '{$pronoun['name']}' uchun tusang",
                'correct_answer' => $correctAnswer,
                'options' => $this->generateOptions($verb, $tense, $pronoun['id'], $correctAnswer),
            ],
        };
    }

    /**
     * Generate negative form question
     */
    protected function generateNegativeQuestion(array $verb, string $tense, array $pronoun, string $tenseName): array
    {
        $conjugated = $this->dataService->conjugateVerb($verb, $tense, $pronoun['id']);
        $negative = $this->dataService->generateNegative($conjugated, $tense, $pronoun['id']);

        return [
            'type' => 'negative',
            'verb' => $verb,
            'tense' => $tense,
            'tense_name' => $tenseName,
            'pronoun' => $pronoun,
            'prompt' => "Make negative: '{$pronoun['name']} {$conjugated}'",
            'prompt_uz' => "Inkor shaklini yozing: '{$pronoun['name']} {$conjugated}'",
            'correct_answer' => $negative,
            'options' => $this->generateNegativeOptions($verb, $tense, $pronoun['id'], $negative),
        ];
    }

    /**
     * Generate question form question
     */
    protected function generateQuestionFormQuestion(array $verb, string $tense, array $pronoun, string $tenseName): array
    {
        $questionForm = $this->dataService->generateQuestion($verb, $tense, $pronoun['id']);

        return [
            'type' => 'question',
            'verb' => $verb,
            'tense' => $tense,
            'tense_name' => $tenseName,
            'pronoun' => $pronoun,
            'prompt' => "Make question form for '{$verb['base']}' in {$tenseName}",
            'prompt_uz' => "'{$verb['base']}' fe'lini so'roq shaklida yozing",
            'correct_answer' => $questionForm,
            'options' => $this->generateQuestionFormOptions($verb, $tense, $pronoun['id'], $questionForm),
        ];
    }

    /**
     * Generate identify tense question
     */
    protected function generateIdentifyQuestion(array $verb, string $tense, array $pronoun, string $tenseName, string $conjugated): array
    {
        $tensesConfig = $this->dataService->getTenses();

        return [
            'type' => 'identify',
            'verb' => $verb,
            'tense' => $tense,
            'tense_name' => $tenseName,
            'pronoun' => $pronoun,
            'prompt' => "What tense is '{$pronoun['name']} {$conjugated}'?",
            'prompt_uz' => "'{$pronoun['name']} {$conjugated}' qaysi zamonda?",
            'correct_answer' => $tenseName,
            'options' => array_map(fn($t) => $t['name'], array_slice($tensesConfig, 0, 4)),
        ];
    }

    /**
     * Generate options for conjugation
     */
    protected function generateOptions(array $verb, string $tense, string $pronoun, string $correct): array
    {
        $options = [$correct];
        $allTenses = ['present_simple', 'past_simple', 'future_simple', 'present_continuous'];

        // Add wrong options
        foreach ($allTenses as $t) {
            if ($t !== $tense && count($options) < 4) {
                $wrongAnswer = $this->dataService->conjugateVerb($verb, $t, $pronoun);
                if (!in_array($wrongAnswer, $options)) {
                    $options[] = $wrongAnswer;
                }
            }
        }

        // Add more options if needed
        $wrongForms = [
            $verb['base'] . 's',
            $verb['base'] . 'ed',
            $verb['base'] . 'ing',
            'will ' . $verb['base'],
        ];

        foreach ($wrongForms as $wrong) {
            if (count($options) < 4 && !in_array($wrong, $options)) {
                $options[] = $wrong;
            }
        }

        shuffle($options);
        return array_slice($options, 0, 4);
    }

    /**
     * Generate negative options
     */
    protected function generateNegativeOptions(array $verb, string $tense, string $pronoun, string $correct): array
    {
        $options = [$correct];

        $wrongOptions = [
            "don't " . $verb['base'],
            "doesn't " . $verb['base'],
            "didn't " . $verb['base'],
            "won't " . $verb['base'],
            "isn't " . $verb['present_participle'],
            "aren't " . $verb['present_participle'],
            "wasn't " . $verb['present_participle'],
            "haven't " . $verb['past_participle'],
            "hasn't " . $verb['past_participle'],
        ];

        foreach ($wrongOptions as $wrong) {
            if (count($options) < 4 && !in_array($wrong, $options) && $wrong !== $correct) {
                $options[] = $wrong;
            }
        }

        shuffle($options);
        return array_slice($options, 0, 4);
    }

    /**
     * Generate question form options
     */
    protected function generateQuestionFormOptions(array $verb, string $tense, string $pronoun, string $correct): array
    {
        $options = [$correct];

        $wrongOptions = [
            "Do $pronoun " . $verb['base'] . "?",
            "Does $pronoun " . $verb['base'] . "?",
            "Did $pronoun " . $verb['base'] . "?",
            "Will $pronoun " . $verb['base'] . "?",
            "Is $pronoun " . $verb['present_participle'] . "?",
            "Are $pronoun " . $verb['present_participle'] . "?",
            "Was $pronoun " . $verb['present_participle'] . "?",
            "Have $pronoun " . $verb['past_participle'] . "?",
            "Has $pronoun " . $verb['past_participle'] . "?",
        ];

        foreach ($wrongOptions as $wrong) {
            if (count($options) < 4 && !in_array($wrong, $options) && $wrong !== $correct) {
                $options[] = $wrong;
            }
        }

        shuffle($options);
        return array_slice($options, 0, 4);
    }

    /**
     * Get time limit based on level and mode
     */
    protected function getTimeLimit(array $level, string $mode): ?int
    {
        if ($mode === 'zen') {
            return null;
        }

        $baseTime = $level['time_limit'] ?? 60;

        return match ($mode) {
            'speed' => (int)($baseTime * 0.7),
            'challenge' => (int)($baseTime * 0.5),
            default => $baseTime,
        };
    }

    /**
     * Sanitize questions for client
     */
    protected function sanitizeQuestionsForClient(array $questions): array
    {
        return array_map(function ($question) {
            return [
                'type' => $question['type'],
                'verb' => [
                    'base' => $question['verb']['base'],
                    'meaning_uz' => $question['verb']['meaning_uz'],
                ],
                'tense' => $question['tense'],
                'tense_name' => $question['tense_name'],
                'pronoun' => $question['pronoun'],
                'prompt' => $question['prompt'],
                'prompt_uz' => $question['prompt_uz'],
                'options' => $question['options'],
            ];
        }, $questions);
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

        $question = $session['questions'][$questionIndex] ?? null;

        if (!$question) {
            throw new \Exception('Question not found');
        }

        $isCorrect = $this->checkAnswer($question, $answer);
        $pointsEarned = 0;

        if ($isCorrect) {
            $session['correct_count']++;
            $session['streak']++;
            $session['best_streak'] = max($session['best_streak'], $session['streak']);

            // Calculate points
            $basePoints = $session['level']['base_points'] ?? 10;
            $streakBonus = min($session['streak'] * 2, 20);
            $timeBonus = $this->calculateTimeBonus($timeSpent, $session['mode']);
            $pointsEarned = $basePoints + $streakBonus + $timeBonus;

            $session['score'] += $pointsEarned;
        } else {
            $session['incorrect_count']++;
            $session['streak'] = 0;
        }

        $session['answers'][$questionIndex] = [
            'answer' => $answer,
            'correct' => $isCorrect,
            'correct_answer' => $question['correct_answer'],
            'points' => $pointsEarned,
            'time_spent' => $timeSpent,
        ];

        $session['current_question'] = $questionIndex + 1;

        $this->saveSession($session);

        return [
            'correct' => $isCorrect,
            'correct_answer' => $question['correct_answer'],
            'points_earned' => $pointsEarned,
            'streak' => $session['streak'],
            'total_score' => $session['score'],
            'progress' => [
                'current' => $questionIndex + 1,
                'total' => count($session['questions']),
                'correct' => $session['correct_count'],
                'incorrect' => $session['incorrect_count'],
            ],
        ];
    }

    /**
     * Check if answer is correct
     */
    protected function checkAnswer(array $question, string $answer): bool
    {
        $correct = strtolower(trim($question['correct_answer']));
        $given = strtolower(trim($answer));

        // Direct match
        if ($correct === $given) {
            return true;
        }

        // Remove question mark for comparison
        $correct = rtrim($correct, '?');
        $given = rtrim($given, '?');

        return $correct === $given;
    }

    /**
     * Calculate time bonus
     */
    protected function calculateTimeBonus(float $timeSpent, string $mode): int
    {
        if ($mode === 'zen') {
            return 0;
        }

        if ($timeSpent <= 3) {
            return 10;
        } elseif ($timeSpent <= 5) {
            return 5;
        } elseif ($timeSpent <= 10) {
            return 2;
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
            'hint' => [
                'effect' => [
                    'type' => 'hint',
                    'message' => $this->generateHint($currentQuestion),
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
            'eliminate' => [
                'effect' => [
                    'type' => 'eliminate',
                    'eliminated_options' => $this->eliminateWrongOptions($currentQuestion),
                ],
            ],
            'time_freeze' => [
                'effect' => [
                    'type' => 'time_freeze',
                    'duration' => 10,
                ],
            ],
            'double_points' => [
                'effect' => [
                    'type' => 'double_points',
                    'duration' => 3,
                ],
            ],
            'streak_shield' => [
                'effect' => [
                    'type' => 'streak_shield',
                    'protection' => 1,
                ],
            ],
            default => [
                'effect' => [
                    'type' => 'unknown',
                ],
            ],
        };
    }

    /**
     * Generate hint for question
     */
    protected function generateHint(array $question): string
    {
        $correct = $question['correct_answer'];

        return match ($question['type']) {
            'conjugate' => "The answer starts with '" . substr($correct, 0, 2) . "...'",
            'negative' => "Remember: use auxiliary verb + not",
            'question' => "Start with the auxiliary verb",
            'identify' => "Look at the verb form and auxiliary verbs",
            default => "Think about the tense pattern",
        };
    }

    /**
     * Eliminate wrong options
     */
    protected function eliminateWrongOptions(array $question): array
    {
        $correct = $question['correct_answer'];
        $options = $question['options'];
        $wrong = array_filter($options, fn($o) => $o !== $correct);

        shuffle($wrong);
        return array_slice($wrong, 0, 2);
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

        if ($session['completed']) {
            throw new \Exception('Session already completed');
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
        $progress['stats']['total_conjugations'] = ($progress['stats']['total_conjugations'] ?? 0) + $totalQuestions;
        $progress['stats']['correct_conjugations'] = ($progress['stats']['correct_conjugations'] ?? 0) + $session['correct_count'];
        $progress['stats']['incorrect_conjugations'] = ($progress['stats']['incorrect_conjugations'] ?? 0) + $session['incorrect_count'];
        $progress['stats']['best_accuracy'] = max($accuracy, $progress['stats']['best_accuracy'] ?? 0);
        $progress['stats']['best_streak'] = max($session['best_streak'], $progress['stats']['best_streak'] ?? 0);
        $progress['stats']['total_xp'] = ($progress['stats']['total_xp'] ?? 0) + $xpEarned;
        $progress['stats']['total_coins'] = ($progress['stats']['total_coins'] ?? 0) + $coinsEarned;
        $progress['stats']['total_time_seconds'] = ($progress['stats']['total_time_seconds'] ?? 0) + $session['time_taken'];

        if ($accuracy === 100) {
            $progress['stats']['perfect_games'] = ($progress['stats']['perfect_games'] ?? 0) + 1;
        }

        // Track practiced tenses
        $tenses = $session['level']['tenses'] ?? [];
        $practicedTenses = $progress['stats']['tenses_practiced'] ?? [];
        foreach ($tenses as $tense) {
            if (!in_array($tense, $practicedTenses)) {
                $practicedTenses[] = $tense;
            }
        }
        $progress['stats']['tenses_practiced'] = $practicedTenses;

        // Calculate average accuracy
        $totalGames = $progress['stats']['games_played'];
        $totalCorrect = $progress['stats']['correct_conjugations'];
        $totalAnswers = $progress['stats']['total_conjugations'];
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
        $baseXP = $session['level']['xp_reward'] ?? 50;
        $starMultiplier = 1 + ($stars * 0.25);
        $accuracyBonus = $session['correct_count'] * 2;

        return (int)(($baseXP * $starMultiplier) + $accuracyBonus);
    }

    /**
     * Calculate coins earned
     */
    protected function calculateCoins(array $session, int $stars): int
    {
        $baseCoins = $session['level']['coin_reward'] ?? 10;
        $starBonus = $stars * 5;

        return $baseCoins + $starBonus;
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
            'completed' => $session['completed'],
            'time_started' => $session['time_started'],
            'time_limit' => $session['time_limit'],
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
