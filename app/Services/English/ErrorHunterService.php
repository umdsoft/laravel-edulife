<?php

namespace App\Services\English;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class ErrorHunterService
{
    protected ErrorHunterDataService $dataService;

    public function __construct(ErrorHunterDataService $dataService)
    {
        $this->dataService = $dataService;
    }

    /**
     * Start a new game session
     */
    public function startSession(mixed $userId, int $levelNumber, string $gameMode = 'spot_error'): array
    {
        $level = $this->dataService->getLevel($levelNumber);
        if (!$level) {
            return ['success' => false, 'error' => 'Level not found'];
        }

        $sentences = $this->dataService->getSentencesForLevel($levelNumber);
        if (empty($sentences)) {
            return ['success' => false, 'error' => 'No sentences available for this level'];
        }

        $config = $this->dataService->getConfig();
        $gameModes = $config['game_modes'] ?? [];
        $modeConfig = $gameModes[$gameMode] ?? null;

        if (!$modeConfig) {
            return ['success' => false, 'error' => 'Invalid game mode'];
        }

        // Prepare sentences based on game mode
        $preparedData = match ($gameMode) {
            'spot_error' => $this->prepareSpotErrorSentences($sentences),
            'fix_error' => $this->prepareFixErrorSentences($sentences),
            'rewrite' => $this->prepareRewriteSentences($sentences),
            default => $this->prepareSpotErrorSentences($sentences),
        };

        $sessionId = Str::uuid()->toString();
        $timeLimit = $level['time_limit'][$gameMode] ?? 120;

        $session = [
            'id' => $sessionId,
            'user_id' => $userId,
            'level_number' => $levelNumber,
            'level' => $level,
            'game_mode' => $gameMode,
            'sentences' => $preparedData['sentences'],
            'total_sentences' => count($sentences),
            'current_sentence_index' => 0,
            'completed_sentences' => 0,
            'score' => 0,
            'xp_earned' => 0,
            'coins_earned' => 0,
            'streak' => 0,
            'best_streak' => 0,
            'correct_answers' => 0,
            'wrong_answers' => 0,
            'powerups_used' => [],
            'time_started' => now()->toDateTimeString(),
            'time_limit' => $timeLimit,
            'answer_times' => [],
            'is_perfect' => true,
            'status' => 'active',
            'error_types_found' => [],
            'hints_used' => 0,
        ];

        // Store session in cache for 2 hours
        Cache::put("error_hunter.session.{$sessionId}", $session, now()->addHours(2));

        return [
            'success' => true,
            'session_id' => $sessionId,
            'level' => $level,
            'game_mode' => $gameMode,
            'total_sentences' => count($sentences),
            'time_limit' => $timeLimit,
            'current_sentence' => $preparedData['sentences'][0] ?? null,
            'powerups' => $this->dataService->getPowerups(),
            'error_types' => $this->dataService->getErrorTypes(),
        ];
    }

    /**
     * Prepare sentences for Spot Error mode
     */
    protected function prepareSpotErrorSentences(array $sentences): array
    {
        $prepared = [];

        foreach ($sentences as $sentence) {
            // Split sentence into words for clicking
            $words = preg_split('/(\s+)/', $sentence['text'], -1, PREG_SPLIT_DELIM_CAPTURE);
            $wordList = [];
            $wordIndex = 0;

            foreach ($words as $word) {
                if (trim($word) !== '') {
                    $isError = (strtolower(trim($word, '.,!?;:')) === strtolower($sentence['error_word']));
                    $wordList[] = [
                        'index' => $wordIndex,
                        'word' => $word,
                        'is_error' => $isError,
                    ];
                    $wordIndex++;
                }
            }

            $prepared[] = [
                'id' => $sentence['id'],
                'words' => $wordList,
                'error_type' => $sentence['error_type'],
                'error_index' => $sentence['error_index'],
                'correct_word' => $sentence['correct_word'],
                'correct_text' => $sentence['correct_text'],
                'explanation' => $sentence['explanation'],
                'explanation_uz' => $sentence['explanation_uz'],
                'difficulty' => $sentence['difficulty'],
            ];
        }

        return ['sentences' => $prepared];
    }

    /**
     * Prepare sentences for Fix Error mode
     */
    protected function prepareFixErrorSentences(array $sentences): array
    {
        $prepared = [];

        foreach ($sentences as $sentence) {
            // Split sentence into words
            $words = preg_split('/(\s+)/', $sentence['text'], -1, PREG_SPLIT_DELIM_CAPTURE);
            $wordList = [];
            $wordIndex = 0;

            foreach ($words as $word) {
                if (trim($word) !== '') {
                    $isError = (strtolower(trim($word, '.,!?;:')) === strtolower($sentence['error_word']));
                    $wordList[] = [
                        'index' => $wordIndex,
                        'word' => $word,
                        'is_error' => $isError,
                    ];
                    $wordIndex++;
                }
            }

            // Shuffle options
            $options = $sentence['options'] ?? [$sentence['error_word'], $sentence['correct_word']];
            shuffle($options);

            $prepared[] = [
                'id' => $sentence['id'],
                'text' => $sentence['text'],
                'words' => $wordList,
                'error_word' => $sentence['error_word'],
                'error_index' => $sentence['error_index'],
                'error_type' => $sentence['error_type'],
                'options' => $options,
                'correct_word' => $sentence['correct_word'],
                'correct_text' => $sentence['correct_text'],
                'explanation' => $sentence['explanation'],
                'explanation_uz' => $sentence['explanation_uz'],
                'difficulty' => $sentence['difficulty'],
            ];
        }

        return ['sentences' => $prepared];
    }

    /**
     * Prepare sentences for Rewrite mode
     */
    protected function prepareRewriteSentences(array $sentences): array
    {
        $prepared = [];

        foreach ($sentences as $sentence) {
            $prepared[] = [
                'id' => $sentence['id'],
                'text' => $sentence['text'],
                'error_word' => $sentence['error_word'],
                'error_type' => $sentence['error_type'],
                'correct_text' => $sentence['correct_text'],
                'explanation' => $sentence['explanation'],
                'explanation_uz' => $sentence['explanation_uz'],
                'difficulty' => $sentence['difficulty'],
            ];
        }

        return ['sentences' => $prepared];
    }

    /**
     * Check answer for any mode
     */
    public function checkAnswer(string $sessionId, string $sentenceId, mixed $answer, int $timeSpent, string $mode): array
    {
        $session = Cache::get("error_hunter.session.{$sessionId}");
        if (!$session) {
            return ['success' => false, 'error' => 'Session not found'];
        }

        if ($session['status'] !== 'active') {
            return ['success' => false, 'error' => 'Session is not active'];
        }

        // Find the current sentence
        $currentSentence = null;
        foreach ($session['sentences'] as $s) {
            if ($s['id'] === $sentenceId) {
                $currentSentence = $s;
                break;
            }
        }

        if (!$currentSentence) {
            return ['success' => false, 'error' => 'Sentence not found'];
        }

        $isCorrect = match ($mode) {
            'spot_error' => $this->checkSpotAnswer($currentSentence, $answer),
            'fix_error' => $this->checkFixAnswer($currentSentence, $answer),
            'rewrite' => $this->checkRewriteAnswer($currentSentence, $answer),
            default => false,
        };

        if ($isCorrect) {
            // Track error type found
            $session['error_types_found'][] = $currentSentence['error_type'];
            return $this->handleCorrectAnswer($session, $currentSentence, $timeSpent);
        } else {
            return $this->handleWrongAnswer($session, $currentSentence);
        }
    }

    /**
     * Check Spot Error answer
     */
    protected function checkSpotAnswer(array $sentence, int $wordIndex): bool
    {
        return $wordIndex === $sentence['error_index'];
    }

    /**
     * Check Fix Error answer
     */
    protected function checkFixAnswer(array $sentence, string $selectedOption): bool
    {
        return strtolower(trim($selectedOption)) === strtolower(trim($sentence['correct_word']));
    }

    /**
     * Check Rewrite answer
     */
    protected function checkRewriteAnswer(array $sentence, string $userText): bool
    {
        // Normalize both texts for comparison
        $userNormalized = $this->normalizeText($userText);
        $correctNormalized = $this->normalizeText($sentence['correct_text']);

        return $userNormalized === $correctNormalized;
    }

    /**
     * Normalize text for comparison
     */
    protected function normalizeText(string $text): string
    {
        // Convert to lowercase
        $text = strtolower($text);
        // Remove extra spaces
        $text = preg_replace('/\s+/', ' ', $text);
        // Trim
        $text = trim($text);
        // Normalize punctuation spacing
        $text = preg_replace('/\s*([.,!?;:])\s*/', '$1 ', $text);
        $text = trim($text);

        return $text;
    }

    /**
     * Handle correct answer
     */
    protected function handleCorrectAnswer(array $session, array $sentence, int $timeSpent): array
    {
        $config = $this->dataService->getConfig();
        $scoring = $config['scoring'] ?? [];
        $rewards = $config['rewards'] ?? [];
        $level = $session['level'];
        $gameMode = $session['game_mode'];

        // Calculate score
        $basePoints = $scoring['base_points_per_correct'] ?? 100;

        // Time bonus
        $timeBonus = 0;
        if (isset($scoring['time_bonus']['enabled']) && $scoring['time_bonus']['enabled']) {
            $fastThreshold = $scoring['time_bonus']['fast_threshold_seconds'] ?? 5;
            if ($timeSpent <= $fastThreshold) {
                $maxBonusPercent = $scoring['time_bonus']['max_bonus_percent'] ?? 50;
                $timeBonus = round($basePoints * ($maxBonusPercent / 100) * (1 - $timeSpent / $fastThreshold));
            }
        }

        // Update streak
        $session['streak']++;
        $session['best_streak'] = max($session['best_streak'], $session['streak']);

        // Streak multiplier
        $streakMultiplier = 1.0;
        if (isset($scoring['streak_multiplier']['enabled']) && $scoring['streak_multiplier']['enabled']) {
            $increment = $scoring['streak_multiplier']['increment_per_streak'] ?? 0.2;
            $maxMultiplier = $scoring['streak_multiplier']['max_multiplier'] ?? 3.0;
            $streakMultiplier = min($maxMultiplier, 1.0 + (($session['streak'] - 1) * $increment));
        }

        // Difficulty multiplier
        $difficultyMultiplier = $scoring['difficulty_multiplier'][$level['difficulty']] ?? 1.0;

        // Mode multiplier
        $modeMultiplier = $scoring['mode_multiplier'][$gameMode] ?? 1.0;

        // Calculate total score
        $totalScore = round(($basePoints + $timeBonus) * $streakMultiplier * $difficultyMultiplier * $modeMultiplier);

        // Calculate XP
        $xpPerCorrect = $rewards['xp']['per_correct_base'] ?? 20;
        $xpPerStreak = $rewards['xp']['per_streak_correct'] ?? 5;
        $speedBonusMax = $rewards['xp']['speed_bonus_max'] ?? 30;

        $xpEarned = $xpPerCorrect;

        if ($session['streak'] > 1) {
            $xpEarned += $xpPerStreak * ($session['streak'] - 1);
        }

        // Speed bonus XP
        if ($timeSpent <= 2) {
            $xpEarned += $speedBonusMax;
        } elseif ($timeSpent <= 4) {
            $xpEarned += round($speedBonusMax * 0.5);
        }

        // Rewrite mode bonus
        if ($gameMode === 'rewrite') {
            $xpEarned += $rewards['xp']['rewrite_mode_bonus'] ?? 20;
        }

        // Apply difficulty multiplier to XP
        $xpEarned = round($xpEarned * $difficultyMultiplier);

        // Calculate coins
        $coinsEarned = 0;
        if ($session['wrong_answers'] === 0) {
            $coinsEarned = $rewards['coins']['per_perfect_sentence'] ?? 3;
        }

        // Streak coin bonus
        $streakCoinBonuses = $rewards['coins']['streak_bonus'] ?? [];
        foreach ($streakCoinBonuses as $streakLevel => $bonus) {
            if ($session['streak'] == (int)$streakLevel) {
                $coinsEarned += $bonus;
            }
        }

        // Update session
        $session['score'] += $totalScore;
        $session['xp_earned'] += $xpEarned;
        $session['coins_earned'] += $coinsEarned;
        $session['correct_answers']++;
        $session['completed_sentences']++;
        $session['current_sentence_index']++;
        $session['answer_times'][] = $timeSpent;

        // Check streak milestones
        $streakMilestone = null;
        $milestones = $scoring['streak_multiplier']['milestones'] ?? [];
        foreach ($milestones as $milestone) {
            if ($session['streak'] === $milestone['streak']) {
                $streakMilestone = $milestone;
                $session['xp_earned'] += $milestone['bonus_xp'] ?? 0;
                break;
            }
        }

        // Check if session complete
        $isComplete = $session['completed_sentences'] >= $session['total_sentences'];

        // Get next sentence
        $nextSentence = null;
        if (!$isComplete && isset($session['sentences'][$session['current_sentence_index']])) {
            $nextSentence = $session['sentences'][$session['current_sentence_index']];
        }

        // Save session
        Cache::put("error_hunter.session.{$session['id']}", $session, now()->addHours(2));

        return [
            'success' => true,
            'correct' => true,
            'score_earned' => $totalScore,
            'xp_earned' => $xpEarned,
            'coins_earned' => $coinsEarned,
            'streak' => $session['streak'],
            'streak_multiplier' => round($streakMultiplier, 2),
            'time_bonus' => $timeBonus,
            'total_score' => $session['score'],
            'total_xp' => $session['xp_earned'],
            'total_coins' => $session['coins_earned'],
            'streak_milestone' => $streakMilestone,
            'correct_text' => $sentence['correct_text'],
            'correct_word' => $sentence['correct_word'] ?? null,
            'explanation' => $sentence['explanation'],
            'explanation_uz' => $sentence['explanation_uz'],
            'error_type' => $sentence['error_type'],
            'progress' => [
                'completed' => $session['completed_sentences'],
                'total' => $session['total_sentences'],
            ],
            'is_complete' => $isComplete,
            'next_sentence' => $nextSentence,
        ];
    }

    /**
     * Handle wrong answer
     */
    protected function handleWrongAnswer(array $session, array $sentence): array
    {
        $session['streak'] = 0;
        $session['wrong_answers']++;
        $session['is_perfect'] = false;

        Cache::put("error_hunter.session.{$session['id']}", $session, now()->addHours(2));

        return [
            'success' => true,
            'correct' => false,
            'message' => 'Noto\'g\'ri javob!',
            'streak' => 0,
            'error_word' => $sentence['error_word'] ?? null,
            'error_index' => $sentence['error_index'] ?? null,
            'error_type' => $sentence['error_type'],
        ];
    }

    /**
     * Use a powerup
     */
    public function usePowerup(string $sessionId, string $powerupType): array
    {
        $session = Cache::get("error_hunter.session.{$sessionId}");
        if (!$session) {
            return ['success' => false, 'error' => 'Session not found'];
        }

        $config = $this->dataService->getConfig();
        $powerups = $config['powerups'] ?? [];
        $powerup = $powerups[$powerupType] ?? null;

        if (!$powerup) {
            return ['success' => false, 'error' => 'Invalid powerup type'];
        }

        // Check usage limit
        $usedCount = count(array_filter($session['powerups_used'], fn($p) => $p === $powerupType));
        if ($usedCount >= ($powerup['max_uses'] ?? 1)) {
            return ['success' => false, 'error' => 'Powerup limit reached'];
        }

        $session['powerups_used'][] = $powerupType;
        $session['is_perfect'] = false;

        $powerupData = [];
        $currentSentence = $session['sentences'][$session['current_sentence_index']] ?? null;

        switch ($powerupType) {
            case 'highlight':
                // Highlight the error word
                if ($currentSentence) {
                    $powerupData = [
                        'type' => 'highlight',
                        'error_index' => $currentSentence['error_index'],
                    ];
                }
                break;

            case 'hint':
                // Show error type hint
                if ($currentSentence) {
                    $errorTypes = $this->dataService->getErrorTypes();
                    $errorType = $currentSentence['error_type'];
                    $powerupData = [
                        'type' => 'hint',
                        'error_type' => $errorType,
                        'error_type_info' => $errorTypes[$errorType] ?? null,
                    ];
                    $session['hints_used']++;
                }
                break;

            case 'skip':
                // Skip current sentence
                $session['current_sentence_index']++;
                $session['completed_sentences']++;
                $nextSentence = $session['sentences'][$session['current_sentence_index']] ?? null;
                $powerupData = [
                    'type' => 'skip',
                    'next_sentence' => $nextSentence,
                    'is_complete' => $session['completed_sentences'] >= $session['total_sentences'],
                ];
                break;

            case 'extra_time':
                $session['time_limit'] += 30;
                $powerupData = [
                    'type' => 'extra_time',
                    'added_seconds' => 30,
                    'new_time_limit' => $session['time_limit'],
                ];
                break;
        }

        Cache::put("error_hunter.session.{$session['id']}", $session, now()->addHours(2));

        return [
            'success' => true,
            'powerup' => $powerupData,
            'coin_cost' => $powerup['cost_coins'] ?? 0,
        ];
    }

    /**
     * Complete the session
     */
    public function completeSession(string $sessionId, mixed $userId): array
    {
        $session = Cache::get("error_hunter.session.{$sessionId}");
        if (!$session) {
            return ['success' => false, 'error' => 'Session not found'];
        }

        if ($session['user_id'] !== $userId) {
            return ['success' => false, 'error' => 'Unauthorized'];
        }

        $config = $this->dataService->getConfig();
        $rewards = $config['rewards'] ?? [];
        $starsConfig = $config['stars'] ?? [];
        $level = $session['level'];

        // Calculate final stats
        $totalSentences = $session['total_sentences'];
        $correctAnswers = $session['correct_answers'];
        $accuracy = $totalSentences > 0 ? round(($correctAnswers / $totalSentences) * 100, 1) : 0;

        // Calculate stars
        $stars = 0;
        $starsRequirement = $level['stars_requirement'] ?? ['one' => 50, 'two' => 75, 'three' => 90];

        if ($accuracy >= $starsRequirement['three']) {
            $stars = 3;
        } elseif ($accuracy >= $starsRequirement['two']) {
            $stars = 2;
        } elseif ($accuracy >= $starsRequirement['one']) {
            $stars = 1;
        }

        // Bonus XP and coins based on stars
        $bonusXp = 0;
        $bonusCoins = 0;

        if ($stars === 3) {
            $bonusXp = $starsConfig['three_stars']['xp_reward'] ?? 100;
            $bonusCoins = $starsConfig['three_stars']['coin_reward'] ?? 30;
            $bonusCoins += $rewards['coins']['three_star_bonus'] ?? 25;
        } elseif ($stars === 2) {
            $bonusXp = $starsConfig['two_stars']['xp_reward'] ?? 60;
            $bonusCoins = $starsConfig['two_stars']['coin_reward'] ?? 15;
        } elseif ($stars === 1) {
            $bonusXp = $starsConfig['one_star']['xp_reward'] ?? 30;
            $bonusCoins = $starsConfig['one_star']['coin_reward'] ?? 8;
        }

        // Level complete bonus
        if ($stars > 0) {
            $bonusXp += $rewards['xp']['level_complete_bonus'] ?? 100;
            $bonusCoins += $rewards['coins']['per_level_complete'] ?? 20;
        }

        // Perfect session bonus
        if ($session['is_perfect'] && $accuracy === 100) {
            $bonusXp += $rewards['xp']['perfect_session_bonus'] ?? 150;
            $bonusCoins += $rewards['coins']['perfect_session_bonus'] ?? 35;
        }

        // Calculate total time
        $totalTime = array_sum($session['answer_times']);

        // Final totals
        $totalXp = $session['xp_earned'] + $bonusXp;
        $totalCoins = $session['coins_earned'] + $bonusCoins;

        // Save progress
        if ($stars > 0) {
            $this->dataService->saveUserProgress($userId, $level['number'], [
                'stars' => $stars,
                'score' => $session['score'],
            ]);
        }

        // Update user stats
        $this->dataService->updateUserStats($userId, [
            'errors_found' => $correctAnswers,
            'xp_earned' => $totalXp,
            'coins_earned' => $totalCoins,
            'best_streak' => $session['best_streak'],
            'is_perfect' => $session['is_perfect'] && $accuracy === 100,
            'total_time' => $totalTime,
            'total_attempts' => $totalSentences,
            'correct_attempts' => $correctAnswers,
            'game_mode' => $session['game_mode'],
            'error_types_found' => array_unique($session['error_types_found']),
        ]);

        // Check achievements
        $newAchievements = $this->checkAchievements($userId, $session, $stars);

        // Mark session as completed
        $session['status'] = 'completed';
        Cache::put("error_hunter.session.{$sessionId}", $session, now()->addHours(1));

        return [
            'success' => true,
            'results' => [
                'score' => $session['score'],
                'xp_earned' => $totalXp,
                'coins_earned' => $totalCoins,
                'stars' => $stars,
                'accuracy' => $accuracy,
                'correct_answers' => $correctAnswers,
                'total_sentences' => $totalSentences,
                'best_streak' => $session['best_streak'],
                'wrong_answers' => $session['wrong_answers'],
                'total_time' => $totalTime,
                'average_time' => $totalSentences > 0 ? round($totalTime / $totalSentences, 1) : 0,
                'is_perfect' => $session['is_perfect'] && $accuracy === 100,
                'bonus_xp' => $bonusXp,
                'bonus_coins' => $bonusCoins,
                'game_mode' => $session['game_mode'],
                'error_types_found' => array_unique($session['error_types_found']),
            ],
            'level' => $level,
            'new_achievements' => $newAchievements,
            'next_level_unlocked' => $stars >= ($level['unlock_requirement']['stars'] ?? 1),
        ];
    }

    /**
     * Check and award achievements
     */
    protected function checkAchievements(mixed $userId, array $session, int $stars): array
    {
        $achievements = $this->dataService->getAchievements();
        $userStats = $this->dataService->getUserStats($userId);
        $newAchievements = [];

        foreach ($achievements as $achievement) {
            // Check if already unlocked
            if (in_array($achievement['id'], $this->dataService->getUserAchievements($userId))) {
                continue;
            }

            $requirement = $achievement['requirement'] ?? [];
            $type = $requirement['type'] ?? '';
            $value = $requirement['value'] ?? 0;

            $unlocked = false;

            switch ($type) {
                case 'errors_found':
                    $unlocked = $userStats['total_errors_found'] >= $value;
                    break;

                case 'fast_find':
                    foreach ($session['answer_times'] as $time) {
                        if ($time <= $value) {
                            $unlocked = true;
                            break;
                        }
                    }
                    break;

                case 'perfect_grammar':
                    // Check if all grammar errors in session were found correctly
                    $grammarErrors = array_filter($session['error_types_found'], fn($t) => $t === 'grammar');
                    $unlocked = count($grammarErrors) > 0 && $session['is_perfect'];
                    break;

                case 'perfect_session':
                    $unlocked = $session['is_perfect'] && $session['correct_answers'] === $session['total_sentences'];
                    break;

                case 'streak':
                    $unlocked = $session['best_streak'] >= $value;
                    break;

                case 'levels_completed':
                    $unlocked = $userStats['levels_completed'] >= $value;
                    break;
            }

            if ($unlocked && $this->dataService->unlockAchievement($userId, $achievement['id'])) {
                $newAchievements[] = $achievement;
            }
        }

        return $newAchievements;
    }

    /**
     * Get session state
     */
    public function getSessionState(string $sessionId): array
    {
        $session = Cache::get("error_hunter.session.{$sessionId}");
        if (!$session) {
            return ['success' => false, 'error' => 'Session not found'];
        }

        return [
            'success' => true,
            'session' => [
                'id' => $session['id'],
                'level_number' => $session['level_number'],
                'game_mode' => $session['game_mode'],
                'score' => $session['score'],
                'xp_earned' => $session['xp_earned'],
                'coins_earned' => $session['coins_earned'],
                'streak' => $session['streak'],
                'best_streak' => $session['best_streak'],
                'correct_answers' => $session['correct_answers'],
                'wrong_answers' => $session['wrong_answers'],
                'progress' => [
                    'completed' => $session['completed_sentences'],
                    'total' => $session['total_sentences'],
                ],
                'status' => $session['status'],
                'time_limit' => $session['time_limit'],
                'current_sentence_index' => $session['current_sentence_index'],
            ],
            'current_sentence' => $session['sentences'][$session['current_sentence_index']] ?? null,
        ];
    }
}
