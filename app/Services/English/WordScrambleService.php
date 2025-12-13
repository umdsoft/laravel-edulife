<?php

namespace App\Services\English;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class WordScrambleService
{
    protected WordScrambleDataService $dataService;

    public function __construct(WordScrambleDataService $dataService)
    {
        $this->dataService = $dataService;
    }

    /**
     * Start a new game session
     */
    public function startSession(mixed $userId, int $levelNumber): array
    {
        $level = $this->dataService->getLevel($levelNumber);
        if (!$level) {
            return ['success' => false, 'error' => 'Level not found'];
        }

        $words = $this->dataService->getWordsForLevel($levelNumber);
        if (empty($words)) {
            return ['success' => false, 'error' => 'No words available for this level'];
        }

        // Prepare words with scrambled letters
        $preparedWords = array_map(function ($word, $index) use ($level) {
            return [
                'index' => $index,
                'word' => strtoupper($word['word']),
                'scrambled' => $this->scrambleWord($word['word']),
                'definition' => $word['definition'],
                'translation_uz' => $word['translation_uz'],
                'category' => $word['category'],
                'difficulty' => $word['difficulty'],
                'image' => $word['image'] ?? null,
                'time_limit' => $level['time_per_word'] ?? 60,
            ];
        }, $words, array_keys($words));

        $sessionId = Str::uuid()->toString();
        $config = $this->dataService->getConfig();

        $session = [
            'id' => $sessionId,
            'user_id' => $userId,
            'level_number' => $levelNumber,
            'level' => $level,
            'words' => $preparedWords,
            'current_word_index' => 0,
            'total_words' => count($preparedWords),
            'score' => 0,
            'xp_earned' => 0,
            'coins_earned' => 0,
            'streak' => 0,
            'best_streak' => 0,
            'hints_used' => 0,
            'hints_used_per_word' => [],
            'correct_answers' => 0,
            'wrong_attempts' => 0,
            'skipped_words' => 0,
            'time_started' => now()->toDateTimeString(),
            'word_times' => [],
            'is_perfect' => true,
            'revealed_letters' => [],
            'status' => 'active',
        ];

        // Store session in cache for 2 hours
        Cache::put("word_scramble.session.{$sessionId}", $session, now()->addHours(2));

        return [
            'success' => true,
            'session_id' => $sessionId,
            'level' => $level,
            'total_words' => count($preparedWords),
            'current_word' => $this->getCurrentWordData($session),
            'hints_allowed' => $level['hints_allowed'] ?? 3,
            'config' => [
                'time_per_word' => $level['time_per_word'] ?? 60,
                'hints' => $this->dataService->getHints(),
            ],
        ];
    }

    /**
     * Scramble a word ensuring it's different from original
     */
    protected function scrambleWord(string $word): array
    {
        $letters = str_split(strtoupper($word));
        $original = $letters;
        $attempts = 0;
        $maxAttempts = 10;

        // Shuffle until different from original (or max attempts reached)
        do {
            shuffle($letters);
            $attempts++;
        } while ($letters === $original && $attempts < $maxAttempts && strlen($word) > 2);

        // Add unique IDs to each letter for drag-drop
        return array_map(function ($letter, $index) {
            return [
                'id' => Str::uuid()->toString(),
                'letter' => $letter,
                'originalIndex' => $index,
            ];
        }, $letters, array_keys($letters));
    }

    /**
     * Get current word data for frontend (without revealing answer)
     */
    protected function getCurrentWordData(array $session): ?array
    {
        if ($session['current_word_index'] >= count($session['words'])) {
            return null;
        }

        $word = $session['words'][$session['current_word_index']];
        $wordKey = $session['current_word_index'];

        // Get revealed letters for this word
        $revealedLetters = $session['revealed_letters'][$wordKey] ?? [];

        return [
            'index' => $word['index'],
            'scrambled' => $word['scrambled'],
            'word_length' => strlen($word['word']),
            'category' => $word['category'],
            'time_limit' => $word['time_limit'],
            'revealed_letters' => $revealedLetters,
            'image' => $word['image'],
        ];
    }

    /**
     * Check the user's answer
     */
    public function checkAnswer(string $sessionId, string $answer, int $timeSpent): array
    {
        $session = Cache::get("word_scramble.session.{$sessionId}");
        if (!$session) {
            return ['success' => false, 'error' => 'Session not found'];
        }

        if ($session['status'] !== 'active') {
            return ['success' => false, 'error' => 'Session is not active'];
        }

        $currentWordIndex = $session['current_word_index'];
        if ($currentWordIndex >= count($session['words'])) {
            return ['success' => false, 'error' => 'No more words'];
        }

        $currentWord = $session['words'][$currentWordIndex];
        $correctWord = $currentWord['word'];
        $userAnswer = strtoupper(trim($answer));

        $isCorrect = $userAnswer === $correctWord;

        if ($isCorrect) {
            return $this->handleCorrectAnswer($session, $currentWord, $timeSpent);
        } else {
            return $this->handleWrongAnswer($session, $correctWord, $userAnswer);
        }
    }

    /**
     * Handle correct answer
     */
    protected function handleCorrectAnswer(array $session, array $word, int $timeSpent): array
    {
        $config = $this->dataService->getConfig();
        $scoring = $config['scoring'] ?? [];
        $rewards = $config['rewards'] ?? [];
        $level = $session['level'];

        // Calculate score
        $wordLength = strlen($word['word']);
        $basePoints = ($scoring['base_points_per_letter'] ?? 10) * $wordLength;

        // Time bonus
        $timeLimit = $word['time_limit'];
        $timeBonus = 0;
        if (isset($scoring['time_bonus']['enabled']) && $scoring['time_bonus']['enabled']) {
            $remainingTimePercent = max(0, ($timeLimit - $timeSpent) / $timeLimit * 100);
            $maxBonusPercent = $scoring['time_bonus']['max_bonus_percent'] ?? 50;
            $timeBonus = round($basePoints * ($remainingTimePercent * $maxBonusPercent / 10000));
        }

        // Length bonus
        $lengthBonus = 0;
        if (isset($scoring['length_bonus']['enabled']) && $scoring['length_bonus']['enabled']) {
            $baseLength = $scoring['length_bonus']['base_length'] ?? 4;
            $bonusPerLetter = $scoring['length_bonus']['bonus_per_extra_letter'] ?? 15;
            if ($wordLength > $baseLength) {
                $lengthBonus = ($wordLength - $baseLength) * $bonusPerLetter;
            }
        }

        // Update streak
        $session['streak']++;
        $session['best_streak'] = max($session['best_streak'], $session['streak']);

        // Streak multiplier
        $streakMultiplier = 1.0;
        if (isset($scoring['streak_multiplier']['enabled']) && $scoring['streak_multiplier']['enabled']) {
            $increment = $scoring['streak_multiplier']['increment_per_streak'] ?? 0.1;
            $maxMultiplier = $scoring['streak_multiplier']['max_multiplier'] ?? 3.0;
            $streakMultiplier = min($maxMultiplier, 1.0 + (($session['streak'] - 1) * $increment));
        }

        // Difficulty multiplier
        $difficultyMultiplier = $scoring['difficulty_multiplier'][$level['difficulty']] ?? 1.0;

        // Calculate hints penalty
        $hintsPenalty = 1.0;
        $hintsUsedForWord = $session['hints_used_per_word'][$session['current_word_index']] ?? [];
        $hints = $config['hint_system']['hints'] ?? [];
        foreach ($hintsUsedForWord as $hintId) {
            $hintPenalty = $hints[$hintId]['penalty_percent'] ?? 0;
            $hintsPenalty -= ($hintPenalty / 100);
        }
        $hintsPenalty = max(0.1, $hintsPenalty); // Minimum 10% score

        // Calculate total score
        $totalScore = round(($basePoints + $timeBonus + $lengthBonus) * $streakMultiplier * $difficultyMultiplier * $hintsPenalty);

        // Calculate XP
        $xpPerWord = $rewards['xp']['per_word_base'] ?? 10;
        $xpPerLetter = $rewards['xp']['per_letter'] ?? 2;
        $xpPerStreak = $rewards['xp']['per_streak_word'] ?? 5;
        $speedBonusMax = $rewards['xp']['speed_bonus_max'] ?? 25;

        $xpEarned = $xpPerWord + ($xpPerLetter * $wordLength);

        if ($session['streak'] > 1) {
            $xpEarned += $xpPerStreak * ($session['streak'] - 1);
        }

        // Speed bonus XP
        if ($timeSpent < $timeLimit * 0.3) {
            $xpEarned += $speedBonusMax;
        } elseif ($timeSpent < $timeLimit * 0.5) {
            $xpEarned += round($speedBonusMax * 0.5);
        }

        // First try bonus
        if (empty($hintsUsedForWord) && $session['wrong_attempts'] === 0) {
            $xpEarned += $rewards['xp']['first_try_bonus'] ?? 15;
        }

        // Apply difficulty multiplier to XP
        $xpEarned = round($xpEarned * $difficultyMultiplier);

        // Calculate coins
        $coinsEarned = 0;
        if (empty($hintsUsedForWord)) {
            $coinsEarned = $rewards['coins']['per_perfect_word'] ?? 2;
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
        $session['word_times'][$session['current_word_index']] = $timeSpent;

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

        // Move to next word
        $session['current_word_index']++;

        // Check if session complete
        $isComplete = $session['current_word_index'] >= count($session['words']);

        // Save session
        Cache::put("word_scramble.session.{$session['id']}", $session, now()->addHours(2));

        $response = [
            'success' => true,
            'correct' => true,
            'correct_word' => $word['word'],
            'definition' => $word['definition'],
            'translation_uz' => $word['translation_uz'],
            'score_earned' => $totalScore,
            'xp_earned' => $xpEarned,
            'coins_earned' => $coinsEarned,
            'streak' => $session['streak'],
            'streak_multiplier' => round($streakMultiplier, 2),
            'time_bonus' => $timeBonus,
            'length_bonus' => $lengthBonus,
            'total_score' => $session['score'],
            'total_xp' => $session['xp_earned'],
            'total_coins' => $session['coins_earned'],
            'streak_milestone' => $streakMilestone,
            'progress' => [
                'current' => $session['current_word_index'],
                'total' => count($session['words']),
            ],
            'is_complete' => $isComplete,
        ];

        if (!$isComplete) {
            $response['next_word'] = $this->getCurrentWordData($session);
        }

        return $response;
    }

    /**
     * Handle wrong answer
     */
    protected function handleWrongAnswer(array $session, string $correctWord, string $userAnswer): array
    {
        $session['streak'] = 0;
        $session['wrong_attempts']++;
        $session['is_perfect'] = false;

        Cache::put("word_scramble.session.{$session['id']}", $session, now()->addHours(2));

        return [
            'success' => true,
            'correct' => false,
            'message' => 'Incorrect! Try again.',
            'streak' => 0,
            'hint' => $this->getAutoHint($correctWord, $userAnswer),
        ];
    }

    /**
     * Get automatic hint based on user's wrong answer
     */
    protected function getAutoHint(string $correctWord, string $userAnswer): ?string
    {
        $correctLetters = str_split($correctWord);
        $userLetters = str_split($userAnswer);

        // Count correct positions
        $correctPositions = 0;
        for ($i = 0; $i < min(count($correctLetters), count($userLetters)); $i++) {
            if ($correctLetters[$i] === $userLetters[$i]) {
                $correctPositions++;
            }
        }

        if ($correctPositions === 0) {
            return "No letters are in correct position. Try rearranging!";
        } elseif ($correctPositions < strlen($correctWord) / 2) {
            return "{$correctPositions} letter(s) in correct position. Keep trying!";
        } else {
            return "Almost there! {$correctPositions} letter(s) correct.";
        }
    }

    /**
     * Use a hint
     */
    public function useHint(string $sessionId, string $hintType): array
    {
        $session = Cache::get("word_scramble.session.{$sessionId}");
        if (!$session) {
            return ['success' => false, 'error' => 'Session not found'];
        }

        $currentWordIndex = $session['current_word_index'];
        $currentWord = $session['words'][$currentWordIndex] ?? null;

        if (!$currentWord) {
            return ['success' => false, 'error' => 'No current word'];
        }

        $config = $this->dataService->getConfig();
        $hints = $config['hint_system']['hints'] ?? [];
        $hint = $hints[$hintType] ?? null;

        if (!$hint) {
            return ['success' => false, 'error' => 'Invalid hint type'];
        }

        // Check if hint already used for this word
        $hintsUsedForWord = $session['hints_used_per_word'][$currentWordIndex] ?? [];
        if (in_array($hintType, $hintsUsedForWord)) {
            return ['success' => false, 'error' => 'Hint already used for this word'];
        }

        // Check max hints per word
        $maxHints = $config['hint_system']['max_hints_per_word'] ?? 3;
        if (count($hintsUsedForWord) >= $maxHints) {
            return ['success' => false, 'error' => 'Maximum hints reached for this word'];
        }

        // Check coin cost
        $coinCost = $hint['cost_coins'] ?? 0;
        // Note: In a real app, you'd check user's coin balance here

        // Mark hint as used
        $session['hints_used']++;
        $session['hints_used_per_word'][$currentWordIndex][] = $hintType;
        $session['is_perfect'] = false;

        $hintData = [];

        switch ($hintType) {
            case 'definition':
                $hintData = [
                    'type' => 'definition',
                    'content' => $currentWord['definition'],
                ];
                break;

            case 'translation':
                $hintData = [
                    'type' => 'translation',
                    'content' => $currentWord['translation_uz'],
                ];
                break;

            case 'first_letter':
                $hintData = [
                    'type' => 'first_letter',
                    'content' => substr($currentWord['word'], 0, 1),
                    'position' => 0,
                ];

                // Add to revealed letters
                if (!isset($session['revealed_letters'][$currentWordIndex])) {
                    $session['revealed_letters'][$currentWordIndex] = [];
                }
                $session['revealed_letters'][$currentWordIndex][0] = substr($currentWord['word'], 0, 1);
                break;

            case 'reveal_letter':
                $word = $currentWord['word'];
                $revealed = $session['revealed_letters'][$currentWordIndex] ?? [];

                // Find unrevealed positions
                $unrevealedPositions = [];
                for ($i = 0; $i < strlen($word); $i++) {
                    if (!isset($revealed[$i])) {
                        $unrevealedPositions[] = $i;
                    }
                }

                if (empty($unrevealedPositions)) {
                    return ['success' => false, 'error' => 'All letters already revealed'];
                }

                // Pick random position
                $randomPosition = $unrevealedPositions[array_rand($unrevealedPositions)];
                $revealedLetter = substr($word, $randomPosition, 1);

                if (!isset($session['revealed_letters'][$currentWordIndex])) {
                    $session['revealed_letters'][$currentWordIndex] = [];
                }
                $session['revealed_letters'][$currentWordIndex][$randomPosition] = $revealedLetter;

                $hintData = [
                    'type' => 'reveal_letter',
                    'content' => $revealedLetter,
                    'position' => $randomPosition,
                ];
                break;

            case 'image':
                if ($currentWord['image']) {
                    $hintData = [
                        'type' => 'image',
                        'content' => $currentWord['image'],
                    ];
                } else {
                    return ['success' => false, 'error' => 'No image available for this word'];
                }
                break;
        }

        // Save session
        Cache::put("word_scramble.session.{$session['id']}", $session, now()->addHours(2));

        return [
            'success' => true,
            'hint' => $hintData,
            'coin_cost' => $coinCost,
            'penalty_percent' => $hint['penalty_percent'] ?? 0,
            'hints_remaining' => $maxHints - count($session['hints_used_per_word'][$currentWordIndex]),
            'revealed_letters' => $session['revealed_letters'][$currentWordIndex] ?? [],
        ];
    }

    /**
     * Skip current word
     */
    public function skipWord(string $sessionId): array
    {
        $session = Cache::get("word_scramble.session.{$sessionId}");
        if (!$session) {
            return ['success' => false, 'error' => 'Session not found'];
        }

        $currentWordIndex = $session['current_word_index'];
        $currentWord = $session['words'][$currentWordIndex] ?? null;

        if (!$currentWord) {
            return ['success' => false, 'error' => 'No current word'];
        }

        // Apply skip penalty
        $session['streak'] = 0;
        $session['skipped_words']++;
        $session['is_perfect'] = false;
        $session['current_word_index']++;

        $isComplete = $session['current_word_index'] >= count($session['words']);

        Cache::put("word_scramble.session.{$session['id']}", $session, now()->addHours(2));

        $response = [
            'success' => true,
            'skipped_word' => $currentWord['word'],
            'definition' => $currentWord['definition'],
            'translation_uz' => $currentWord['translation_uz'],
            'progress' => [
                'current' => $session['current_word_index'],
                'total' => count($session['words']),
            ],
            'is_complete' => $isComplete,
        ];

        if (!$isComplete) {
            $response['next_word'] = $this->getCurrentWordData($session);
        }

        return $response;
    }

    /**
     * Complete the session
     */
    public function completeSession(string $sessionId, mixed $userId): array
    {
        $session = Cache::get("word_scramble.session.{$sessionId}");
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
        $totalWords = count($session['words']);
        $correctAnswers = $session['correct_answers'];
        $accuracy = $totalWords > 0 ? round(($correctAnswers / $totalWords) * 100, 1) : 0;

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
            $bonusXp = $starsConfig['three_stars']['xp_reward'] ?? 60;
            $bonusCoins = $starsConfig['three_stars']['coin_reward'] ?? 20;
            $bonusCoins += $rewards['coins']['three_star_bonus'] ?? 15;
        } elseif ($stars === 2) {
            $bonusXp = $starsConfig['two_stars']['xp_reward'] ?? 40;
            $bonusCoins = $starsConfig['two_stars']['coin_reward'] ?? 10;
        } elseif ($stars === 1) {
            $bonusXp = $starsConfig['one_star']['xp_reward'] ?? 20;
            $bonusCoins = $starsConfig['one_star']['coin_reward'] ?? 5;
        }

        // Level complete bonus
        if ($stars > 0) {
            $bonusXp += $rewards['xp']['level_complete_bonus'] ?? 50;
            $bonusCoins += $rewards['coins']['per_level_complete'] ?? 10;
        }

        // Perfect session bonus
        if ($session['is_perfect'] && $accuracy === 100) {
            $bonusXp += $rewards['xp']['perfect_session_bonus'] ?? 100;
            $bonusCoins += $rewards['coins']['perfect_session_bonus'] ?? 25;
        }

        // Calculate total time
        $totalTime = array_sum($session['word_times']);

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
            'words_completed' => $correctAnswers,
            'xp_earned' => $totalXp,
            'coins_earned' => $totalCoins,
            'best_streak' => $session['best_streak'],
            'is_perfect' => $session['is_perfect'] && $accuracy === 100,
            'total_time' => $totalTime,
            'total_attempts' => $totalWords,
            'correct_attempts' => $correctAnswers,
        ]);

        // Check achievements
        $newAchievements = $this->checkAchievements($userId, $session, $stars);

        // Mark session as completed
        $session['status'] = 'completed';
        Cache::put("word_scramble.session.{$sessionId}", $session, now()->addHours(1));

        return [
            'success' => true,
            'results' => [
                'score' => $session['score'],
                'xp_earned' => $totalXp,
                'coins_earned' => $totalCoins,
                'stars' => $stars,
                'accuracy' => $accuracy,
                'correct_answers' => $correctAnswers,
                'total_words' => $totalWords,
                'best_streak' => $session['best_streak'],
                'hints_used' => $session['hints_used'],
                'skipped_words' => $session['skipped_words'],
                'total_time' => $totalTime,
                'average_time' => $totalWords > 0 ? round($totalTime / $totalWords, 1) : 0,
                'is_perfect' => $session['is_perfect'] && $accuracy === 100,
                'bonus_xp' => $bonusXp,
                'bonus_coins' => $bonusCoins,
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
                case 'words_completed':
                    $unlocked = $userStats['total_words_completed'] >= $value;
                    break;

                case 'streak':
                    $unlocked = $session['best_streak'] >= $value;
                    break;

                case 'perfect_sessions':
                    $unlocked = $userStats['perfect_sessions'] >= $value;
                    break;

                case 'sessions_no_hints':
                    $unlocked = $session['hints_used'] === 0 && $session['correct_answers'] === count($session['words']);
                    break;

                case 'word_length':
                    foreach ($session['words'] as $word) {
                        if (strlen($word['word']) >= $value) {
                            $unlocked = true;
                            break;
                        }
                    }
                    break;

                case 'level_complete':
                    $unlocked = $session['level']['number'] >= $value && $stars > 0;
                    break;

                case 'three_star_levels':
                    $unlocked = $userStats['three_star_levels'] >= $value;
                    break;

                case 'daily_streak':
                    $unlocked = $userStats['daily_streak'] >= $value;
                    break;

                case 'total_xp':
                    $unlocked = $userStats['total_xp_earned'] >= $value;
                    break;

                case 'total_coins':
                    $unlocked = $userStats['total_coins_earned'] >= $value;
                    break;

                case 'word_time':
                    foreach ($session['word_times'] as $time) {
                        if ($time <= $value) {
                            $unlocked = true;
                            break;
                        }
                    }
                    break;

                case 'play_time':
                    $currentHour = (int)now()->format('H');
                    if ($value === 'before_7am' && $currentHour < 7) {
                        $unlocked = true;
                    } elseif ($value === 'after_midnight' && $currentHour >= 0 && $currentHour < 5) {
                        $unlocked = true;
                    }
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
        $session = Cache::get("word_scramble.session.{$sessionId}");
        if (!$session) {
            return ['success' => false, 'error' => 'Session not found'];
        }

        return [
            'success' => true,
            'session' => [
                'id' => $session['id'],
                'level_number' => $session['level_number'],
                'score' => $session['score'],
                'xp_earned' => $session['xp_earned'],
                'coins_earned' => $session['coins_earned'],
                'streak' => $session['streak'],
                'best_streak' => $session['best_streak'],
                'correct_answers' => $session['correct_answers'],
                'hints_used' => $session['hints_used'],
                'progress' => [
                    'current' => $session['current_word_index'],
                    'total' => count($session['words']),
                ],
                'status' => $session['status'],
            ],
            'current_word' => $this->getCurrentWordData($session),
        ];
    }
}
