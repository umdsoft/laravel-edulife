<?php

namespace App\Services\English;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class MinimalPairsService
{
    protected MinimalPairsDataService $dataService;

    public function __construct(MinimalPairsDataService $dataService)
    {
        $this->dataService = $dataService;
    }

    /**
     * Start a new game session
     */
    public function startSession(mixed $userId, int $levelNumber, string $gameMode = 'listen_choose'): array
    {
        $level = $this->dataService->getLevel($levelNumber);
        if (!$level) {
            return ['success' => false, 'error' => 'Level not found'];
        }

        // Check if game mode is available for this level
        $availableModes = $level['available_modes'] ?? ['listen_choose'];
        if (!in_array($gameMode, $availableModes)) {
            return ['success' => false, 'error' => 'Game mode not available for this level'];
        }

        $pairs = $this->dataService->getPairsForLevel($levelNumber);
        if (empty($pairs)) {
            return ['success' => false, 'error' => 'No pairs available for this level'];
        }

        $config = $this->dataService->getConfig();
        $gameModes = $config['game_modes'] ?? [];
        $modeConfig = $gameModes[$gameMode] ?? null;

        if (!$modeConfig) {
            return ['success' => false, 'error' => 'Invalid game mode'];
        }

        // Prepare pairs based on game mode
        $preparedData = match ($gameMode) {
            'listen_choose' => $this->prepareListenChoosePairs($pairs, $level),
            'same_different' => $this->prepareSameDifferentPairs($pairs, $level),
            'odd_one_out' => $this->prepareOddOneOutPairs($pairs, $level),
            'speak_compare' => $this->prepareSpeakComparePairs($pairs, $level),
            default => $this->prepareListenChoosePairs($pairs, $level),
        };

        $sessionId = Str::uuid()->toString();
        $timeLimit = $level['time_limit'][$gameMode] ?? 15;

        $session = [
            'id' => $sessionId,
            'user_id' => $userId,
            'level_number' => $levelNumber,
            'level' => $level,
            'game_mode' => $gameMode,
            'pairs' => $preparedData['pairs'],
            'total_pairs' => count($preparedData['pairs']),
            'current_pair_index' => 0,
            'completed_pairs' => 0,
            'score' => 0,
            'xp_earned' => 0,
            'coins_earned' => 0,
            'streak' => 0,
            'best_streak' => 0,
            'correct_answers' => 0,
            'wrong_answers' => 0,
            'powerups_used' => [],
            'time_started' => now()->toDateTimeString(),
            'time_limit_per_pair' => $timeLimit,
            'total_time_limit' => $timeLimit * count($preparedData['pairs']),
            'answer_times' => [],
            'is_perfect' => true,
            'status' => 'active',
            'replays_used' => 0,
            'max_replays' => $level['max_replays'] ?? 3,
            'categories_encountered' => [],
        ];

        // Store session in cache for 2 hours
        Cache::put("minimal_pairs.session.{$sessionId}", $session, now()->addHours(2));

        return [
            'success' => true,
            'session_id' => $sessionId,
            'level' => $level,
            'game_mode' => $gameMode,
            'mode_config' => $modeConfig,
            'total_pairs' => count($preparedData['pairs']),
            'time_limit_per_pair' => $timeLimit,
            'current_pair' => $preparedData['pairs'][0] ?? null,
            'powerups' => $this->dataService->getPowerups(),
            'max_replays' => $level['max_replays'] ?? 3,
        ];
    }

    /**
     * Prepare pairs for Listen & Choose mode
     * Player listens to one word and chooses which word they heard
     */
    protected function prepareListenChoosePairs(array $pairs, array $level): array
    {
        $prepared = [];
        $optionsCount = $level['options_count'] ?? 2;

        foreach ($pairs as $pair) {
            // Randomly select which word to play
            $playWord1 = rand(0, 1) === 1;
            $correctWord = $playWord1 ? $pair['word_1'] : $pair['word_2'];
            $correctPhonetic = $playWord1 ? $pair['phonetic_1'] : $pair['phonetic_2'];
            $correctMeaning = $playWord1 ? $pair['meaning_1'] : $pair['meaning_2'];

            // Create options
            $options = [
                [
                    'word' => $pair['word_1'],
                    'phonetic' => $pair['phonetic_1'],
                    'meaning' => $pair['meaning_1'],
                    'is_correct' => $playWord1,
                ],
                [
                    'word' => $pair['word_2'],
                    'phonetic' => $pair['phonetic_2'],
                    'meaning' => $pair['meaning_2'],
                    'is_correct' => !$playWord1,
                ],
            ];

            // Shuffle options
            shuffle($options);

            $prepared[] = [
                'id' => $pair['id'],
                'category' => $pair['category'] ?? null,
                'audio_word' => $correctWord,
                'audio_phonetic' => $correctPhonetic,
                'correct_word' => $correctWord,
                'correct_meaning' => $correctMeaning,
                'options' => $options,
                'difficulty' => $pair['difficulty'] ?? 1,
                'sentence' => $playWord1 ? ($pair['sentence_1'] ?? null) : ($pair['sentence_2'] ?? null),
            ];
        }

        return ['pairs' => $prepared];
    }

    /**
     * Prepare pairs for Same or Different mode
     * Player listens to two sounds and decides if they are same or different
     */
    protected function prepareSameDifferentPairs(array $pairs, array $level): array
    {
        $prepared = [];

        foreach ($pairs as $pair) {
            // 50% chance of same word, 50% chance of different words
            $isSame = rand(0, 1) === 1;

            if ($isSame) {
                // Play the same word twice
                $playWord1 = rand(0, 1) === 1;
                $firstWord = $playWord1 ? $pair['word_1'] : $pair['word_2'];
                $secondWord = $firstWord;
                $firstPhonetic = $playWord1 ? $pair['phonetic_1'] : $pair['phonetic_2'];
                $secondPhonetic = $firstPhonetic;
            } else {
                // Play different words
                $firstWord = $pair['word_1'];
                $secondWord = $pair['word_2'];
                $firstPhonetic = $pair['phonetic_1'];
                $secondPhonetic = $pair['phonetic_2'];

                // Randomize order
                if (rand(0, 1) === 1) {
                    [$firstWord, $secondWord] = [$secondWord, $firstWord];
                    [$firstPhonetic, $secondPhonetic] = [$secondPhonetic, $firstPhonetic];
                }
            }

            $prepared[] = [
                'id' => $pair['id'] . '_' . ($isSame ? 'same' : 'diff'),
                'category' => $pair['category'] ?? null,
                'first_word' => $firstWord,
                'second_word' => $secondWord,
                'first_phonetic' => $firstPhonetic,
                'second_phonetic' => $secondPhonetic,
                'is_same' => $isSame,
                'word_1' => $pair['word_1'],
                'word_2' => $pair['word_2'],
                'phonetic_1' => $pair['phonetic_1'],
                'phonetic_2' => $pair['phonetic_2'],
                'meaning_1' => $pair['meaning_1'],
                'meaning_2' => $pair['meaning_2'],
                'difficulty' => $pair['difficulty'] ?? 1,
            ];
        }

        return ['pairs' => $prepared];
    }

    /**
     * Prepare pairs for Odd One Out mode
     * Player listens to 3-4 words and picks the one that's different
     */
    protected function prepareOddOneOutPairs(array $pairs, array $level): array
    {
        $prepared = [];
        $optionsCount = $level['options_count'] ?? 3;

        foreach ($pairs as $pair) {
            // Randomly select which word is the odd one
            $oddIsWord1 = rand(0, 1) === 1;
            $oddWord = $oddIsWord1 ? $pair['word_1'] : $pair['word_2'];
            $oddPhonetic = $oddIsWord1 ? $pair['phonetic_1'] : $pair['phonetic_2'];
            $sameWord = $oddIsWord1 ? $pair['word_2'] : $pair['word_1'];
            $samePhonetic = $oddIsWord1 ? $pair['phonetic_2'] : $pair['phonetic_1'];

            // Create options array with one odd and rest same
            $options = [];

            // Add the odd one at a random position
            $oddPosition = rand(0, $optionsCount - 1);

            for ($i = 0; $i < $optionsCount; $i++) {
                if ($i === $oddPosition) {
                    $options[] = [
                        'position' => $i,
                        'word' => $oddWord,
                        'phonetic' => $oddPhonetic,
                        'is_odd' => true,
                    ];
                } else {
                    $options[] = [
                        'position' => $i,
                        'word' => $sameWord,
                        'phonetic' => $samePhonetic,
                        'is_odd' => false,
                    ];
                }
            }

            $prepared[] = [
                'id' => $pair['id'],
                'category' => $pair['category'] ?? null,
                'options' => $options,
                'odd_position' => $oddPosition,
                'odd_word' => $oddWord,
                'odd_phonetic' => $oddPhonetic,
                'same_word' => $sameWord,
                'same_phonetic' => $samePhonetic,
                'word_1' => $pair['word_1'],
                'word_2' => $pair['word_2'],
                'meaning_1' => $pair['meaning_1'],
                'meaning_2' => $pair['meaning_2'],
                'difficulty' => $pair['difficulty'] ?? 1,
            ];
        }

        return ['pairs' => $prepared];
    }

    /**
     * Prepare pairs for Speak & Compare mode
     * Player records their pronunciation and compares with native
     */
    protected function prepareSpeakComparePairs(array $pairs, array $level): array
    {
        $prepared = [];

        foreach ($pairs as $pair) {
            $prepared[] = [
                'id' => $pair['id'],
                'category' => $pair['category'] ?? null,
                'word_1' => $pair['word_1'],
                'word_2' => $pair['word_2'],
                'phonetic_1' => $pair['phonetic_1'],
                'phonetic_2' => $pair['phonetic_2'],
                'meaning_1' => $pair['meaning_1'],
                'meaning_2' => $pair['meaning_2'],
                'sentence_1' => $pair['sentence_1'] ?? null,
                'sentence_2' => $pair['sentence_2'] ?? null,
                'difficulty' => $pair['difficulty'] ?? 1,
            ];
        }

        return ['pairs' => $prepared];
    }

    /**
     * Check answer for Listen & Choose mode
     */
    public function checkListenChooseAnswer(string $sessionId, string $pairId, string $selectedWord, int $timeSpent): array
    {
        $session = Cache::get("minimal_pairs.session.{$sessionId}");
        if (!$session) {
            return ['success' => false, 'error' => 'Session not found'];
        }

        $currentPair = $this->getCurrentPair($session);
        if (!$currentPair || $currentPair['id'] !== $pairId) {
            return ['success' => false, 'error' => 'Invalid pair'];
        }

        $isCorrect = strtolower(trim($selectedWord)) === strtolower(trim($currentPair['correct_word']));

        if ($isCorrect) {
            return $this->handleCorrectAnswer($session, $currentPair, $timeSpent);
        } else {
            return $this->handleWrongAnswer($session, $currentPair);
        }
    }

    /**
     * Check answer for Same or Different mode
     */
    public function checkSameDifferentAnswer(string $sessionId, string $pairId, bool $userSaidSame, int $timeSpent): array
    {
        $session = Cache::get("minimal_pairs.session.{$sessionId}");
        if (!$session) {
            return ['success' => false, 'error' => 'Session not found'];
        }

        $currentPair = $this->getCurrentPair($session);
        if (!$currentPair || !str_starts_with($currentPair['id'], explode('_', $pairId)[0])) {
            return ['success' => false, 'error' => 'Invalid pair'];
        }

        $isCorrect = $userSaidSame === $currentPair['is_same'];

        if ($isCorrect) {
            return $this->handleCorrectAnswer($session, $currentPair, $timeSpent);
        } else {
            return $this->handleWrongAnswer($session, $currentPair);
        }
    }

    /**
     * Check answer for Odd One Out mode
     */
    public function checkOddOneOutAnswer(string $sessionId, string $pairId, int $selectedPosition, int $timeSpent): array
    {
        $session = Cache::get("minimal_pairs.session.{$sessionId}");
        if (!$session) {
            return ['success' => false, 'error' => 'Session not found'];
        }

        $currentPair = $this->getCurrentPair($session);
        if (!$currentPair || $currentPair['id'] !== $pairId) {
            return ['success' => false, 'error' => 'Invalid pair'];
        }

        $isCorrect = $selectedPosition === $currentPair['odd_position'];

        if ($isCorrect) {
            return $this->handleCorrectAnswer($session, $currentPair, $timeSpent);
        } else {
            return $this->handleWrongAnswer($session, $currentPair);
        }
    }

    /**
     * Check answer for Speak & Compare mode
     * This is a simplified check - in production, you'd use speech recognition
     */
    public function checkSpeakCompareAnswer(string $sessionId, string $pairId, bool $userConfirmed, int $timeSpent): array
    {
        $session = Cache::get("minimal_pairs.session.{$sessionId}");
        if (!$session) {
            return ['success' => false, 'error' => 'Session not found'];
        }

        $currentPair = $this->getCurrentPair($session);
        if (!$currentPair || $currentPair['id'] !== $pairId) {
            return ['success' => false, 'error' => 'Invalid pair'];
        }

        // In speak & compare mode, we award points for completion
        // Real pronunciation scoring would need speech recognition API
        if ($userConfirmed) {
            return $this->handleCorrectAnswer($session, $currentPair, $timeSpent);
        } else {
            return $this->handleWrongAnswer($session, $currentPair);
        }
    }

    /**
     * Universal check answer method that routes to the correct mode handler
     */
    public function checkAnswer(string $sessionId, string $pairId, mixed $answer, int $timeSpent): array
    {
        $session = Cache::get("minimal_pairs.session.{$sessionId}");
        if (!$session) {
            return ['success' => false, 'error' => 'Session not found'];
        }

        $gameMode = $session['game_mode'];

        return match ($gameMode) {
            'listen_choose' => $this->checkListenChooseAnswer($sessionId, $pairId, (string)$answer, $timeSpent),
            'same_different' => $this->checkSameDifferentAnswer($sessionId, $pairId, (bool)$answer, $timeSpent),
            'odd_one_out' => $this->checkOddOneOutAnswer($sessionId, $pairId, (int)$answer, $timeSpent),
            'speak_compare' => $this->checkSpeakCompareAnswer($sessionId, $pairId, (bool)$answer, $timeSpent),
            default => ['success' => false, 'error' => 'Invalid game mode'],
        };
    }

    /**
     * Get current pair from session
     */
    protected function getCurrentPair(array $session): ?array
    {
        return $session['pairs'][$session['current_pair_index']] ?? null;
    }

    /**
     * Handle correct answer with professional scoring algorithm
     */
    protected function handleCorrectAnswer(array $session, array $pair, int $timeSpent): array
    {
        $config = $this->dataService->getConfig();
        $scoring = $config['scoring'] ?? [];
        $rewards = $config['rewards'] ?? [];
        $level = $session['level'];
        $gameMode = $session['game_mode'];

        // ============ SCORE CALCULATION ============

        // Base points
        $basePoints = $scoring['base_points_per_correct'] ?? 100;

        // Time bonus (faster answers = more points)
        $timeBonus = 0;
        $timeBonusConfig = $scoring['time_bonus'] ?? [];
        if ($timeBonusConfig['enabled'] ?? true) {
            $fastThreshold = $timeBonusConfig['fast_threshold_seconds'] ?? 3;
            $maxBonusPercent = $timeBonusConfig['max_bonus_percent'] ?? 50;

            if ($timeSpent <= $fastThreshold && $fastThreshold > 0) {
                $timeFactor = 1 - ($timeSpent / $fastThreshold);
                $timeBonus = round($basePoints * ($maxBonusPercent / 100) * $timeFactor);
            }
        }

        // No replay bonus (didn't use replay for this pair)
        $noReplayBonus = 0;
        if ($session['replays_used'] === 0) {
            $noReplayBonus = $scoring['no_replay_bonus'] ?? 25;
        }

        // Update streak
        $session['streak']++;
        $session['best_streak'] = max($session['best_streak'], $session['streak']);

        // Streak multiplier
        $streakMultiplier = 1.0;
        $streakConfig = $scoring['streak_multiplier'] ?? [];
        if ($streakConfig['enabled'] ?? true) {
            $increment = $streakConfig['increment_per_streak'] ?? 0.2;
            $maxMultiplier = $streakConfig['max_multiplier'] ?? 3.0;
            $streakMultiplier = min($maxMultiplier, 1.0 + (($session['streak'] - 1) * $increment));
        }

        // Difficulty multiplier
        $difficultyMultipliers = $scoring['difficulty_multiplier'] ?? [];
        $difficultyMultiplier = $difficultyMultipliers[(string)$level['difficulty']] ?? 1.0;

        // Mode multiplier
        $modeMultipliers = $scoring['mode_multiplier'] ?? [];
        $modeMultiplier = $modeMultipliers[$gameMode] ?? 1.0;

        // Calculate total score
        $totalScore = round(($basePoints + $timeBonus + $noReplayBonus) * $streakMultiplier * $difficultyMultiplier * $modeMultiplier);

        // ============ XP CALCULATION ============

        $xpRewards = $rewards['xp'] ?? [];
        $xpBase = $xpRewards['per_correct_base'] ?? 20;
        $xpPerStreak = $xpRewards['per_streak_correct'] ?? 5;
        $speedBonusMax = $xpRewards['speed_bonus_max'] ?? 30;

        $xpEarned = $xpBase;

        // Streak XP bonus
        if ($session['streak'] > 1) {
            $xpEarned += $xpPerStreak * min($session['streak'] - 1, 5);
        }

        // Speed bonus XP
        if ($timeSpent <= 2) {
            $xpEarned += $speedBonusMax;
        } elseif ($timeSpent <= 4) {
            $xpEarned += round($speedBonusMax * 0.5);
        }

        // First try bonus (no replays)
        if ($session['replays_used'] === 0) {
            $xpEarned += $xpRewards['first_try_bonus'] ?? 10;
        }

        // Mode-specific bonus
        if ($gameMode === 'speak_compare') {
            $xpEarned += $xpRewards['speak_compare_bonus'] ?? 15;
        } elseif ($gameMode === 'odd_one_out') {
            $xpEarned += round($xpBase * 0.25); // 25% bonus for harder mode
        }

        // Apply difficulty multiplier to XP
        $xpEarned = round($xpEarned * $difficultyMultiplier);

        // ============ COIN CALCULATION ============

        $coinRewards = $rewards['coins'] ?? [];
        $coinsEarned = 0;

        // Perfect pair (no wrong attempts for this pair)
        if ($session['wrong_answers'] === 0 || ($session['correct_answers'] / max(1, $session['correct_answers'] + $session['wrong_answers'])) === 1) {
            $coinsEarned = $coinRewards['per_perfect_pair'] ?? 3;
        }

        // Streak coin bonuses
        $streakCoinBonuses = $coinRewards['streak_bonus'] ?? [];
        foreach ($streakCoinBonuses as $streakLevel => $bonus) {
            if ($session['streak'] == (int)$streakLevel) {
                $coinsEarned += $bonus;
            }
        }

        // ============ UPDATE SESSION ============

        $session['score'] += $totalScore;
        $session['xp_earned'] += $xpEarned;
        $session['coins_earned'] += $coinsEarned;
        $session['correct_answers']++;
        $session['completed_pairs']++;
        $session['current_pair_index']++;
        $session['answer_times'][] = $timeSpent;

        // Track category
        if (isset($pair['category']) && !in_array($pair['category'], $session['categories_encountered'])) {
            $session['categories_encountered'][] = $pair['category'];
        }

        // Reset replays for next pair
        $pairReplaysUsed = $session['replays_used'];
        $session['replays_used'] = 0;

        // Check streak milestones
        $streakMilestone = null;
        $milestones = $streakConfig['milestones'] ?? [];
        foreach ($milestones as $milestone) {
            if ($session['streak'] === ($milestone['streak'] ?? 0)) {
                $streakMilestone = $milestone;
                $session['xp_earned'] += $milestone['bonus_xp'] ?? 0;
                break;
            }
        }

        // Check if session complete
        $isComplete = $session['completed_pairs'] >= $session['total_pairs'];

        // Get next pair
        $nextPair = null;
        if (!$isComplete && isset($session['pairs'][$session['current_pair_index']])) {
            $nextPair = $session['pairs'][$session['current_pair_index']];
        }

        // Save session
        Cache::put("minimal_pairs.session.{$session['id']}", $session, now()->addHours(2));

        return [
            'success' => true,
            'correct' => true,
            'score_earned' => $totalScore,
            'xp_earned' => $xpEarned,
            'coins_earned' => $coinsEarned,
            'streak' => $session['streak'],
            'streak_multiplier' => round($streakMultiplier, 2),
            'time_bonus' => $timeBonus,
            'no_replay_bonus' => $noReplayBonus,
            'total_score' => $session['score'],
            'total_xp' => $session['xp_earned'],
            'total_coins' => $session['coins_earned'],
            'streak_milestone' => $streakMilestone,
            'progress' => [
                'completed' => $session['completed_pairs'],
                'total' => $session['total_pairs'],
            ],
            'is_complete' => $isComplete,
            'next_pair' => $nextPair,
            'pair_info' => [
                'word_1' => $pair['word_1'] ?? $pair['options'][0]['word'] ?? null,
                'word_2' => $pair['word_2'] ?? $pair['options'][1]['word'] ?? null,
                'meaning_1' => $pair['meaning_1'] ?? null,
                'meaning_2' => $pair['meaning_2'] ?? null,
            ],
        ];
    }

    /**
     * Handle wrong answer
     */
    protected function handleWrongAnswer(array $session, array $pair): array
    {
        $session['streak'] = 0;
        $session['wrong_answers']++;
        $session['is_perfect'] = false;

        Cache::put("minimal_pairs.session.{$session['id']}", $session, now()->addHours(2));

        return [
            'success' => true,
            'correct' => false,
            'message' => 'Noto\'g\'ri javob! Qayta tinglang.',
            'streak' => 0,
            'correct_word' => $pair['correct_word'] ?? $pair['odd_word'] ?? null,
            'is_same' => $pair['is_same'] ?? null,
            'odd_position' => $pair['odd_position'] ?? null,
            'pair_info' => [
                'word_1' => $pair['word_1'] ?? null,
                'word_2' => $pair['word_2'] ?? null,
                'phonetic_1' => $pair['phonetic_1'] ?? null,
                'phonetic_2' => $pair['phonetic_2'] ?? null,
            ],
        ];
    }

    /**
     * Use replay (listen again)
     */
    public function useReplay(string $sessionId): array
    {
        $session = Cache::get("minimal_pairs.session.{$sessionId}");
        if (!$session) {
            return ['success' => false, 'error' => 'Session not found'];
        }

        $maxReplays = $session['max_replays'] ?? 3;
        if ($session['replays_used'] >= $maxReplays) {
            return ['success' => false, 'error' => 'Maximum replays reached for this pair'];
        }

        $session['replays_used']++;
        $session['is_perfect'] = false; // Using replay means not perfect

        Cache::put("minimal_pairs.session.{$session['id']}", $session, now()->addHours(2));

        return [
            'success' => true,
            'replays_remaining' => $maxReplays - $session['replays_used'],
            'current_pair' => $session['pairs'][$session['current_pair_index']] ?? null,
        ];
    }

    /**
     * Use a powerup
     */
    public function usePowerup(string $sessionId, string $powerupType): array
    {
        $session = Cache::get("minimal_pairs.session.{$sessionId}");
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
        $currentPair = $session['pairs'][$session['current_pair_index']] ?? null;

        switch ($powerupType) {
            case 'slow_audio':
                $powerupData = [
                    'type' => 'slow_audio',
                    'speed' => 0.7,
                    'message' => 'Audio will play at 70% speed',
                ];
                break;

            case 'show_phonetic':
                if ($currentPair) {
                    $powerupData = [
                        'type' => 'show_phonetic',
                        'phonetic_1' => $currentPair['phonetic_1'] ?? $currentPair['options'][0]['phonetic'] ?? null,
                        'phonetic_2' => $currentPair['phonetic_2'] ?? $currentPair['options'][1]['phonetic'] ?? null,
                    ];
                }
                break;

            case 'extra_replay':
                $session['max_replays'] += 2;
                $powerupData = [
                    'type' => 'extra_replay',
                    'added_replays' => 2,
                    'new_max_replays' => $session['max_replays'],
                ];
                break;

            case 'skip':
                $session['current_pair_index']++;
                $session['completed_pairs']++;
                $nextPair = $session['pairs'][$session['current_pair_index']] ?? null;
                $powerupData = [
                    'type' => 'skip',
                    'next_pair' => $nextPair,
                    'is_complete' => $session['completed_pairs'] >= $session['total_pairs'],
                ];
                break;

            case 'show_meaning':
                if ($currentPair) {
                    $powerupData = [
                        'type' => 'show_meaning',
                        'meaning_1' => $currentPair['meaning_1'] ?? $currentPair['options'][0]['meaning'] ?? null,
                        'meaning_2' => $currentPair['meaning_2'] ?? $currentPair['options'][1]['meaning'] ?? null,
                    ];
                }
                break;
        }

        Cache::put("minimal_pairs.session.{$session['id']}", $session, now()->addHours(2));

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
        $session = Cache::get("minimal_pairs.session.{$sessionId}");
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
        $totalPairs = $session['total_pairs'];
        $correctAnswers = $session['correct_answers'];
        $accuracy = $totalPairs > 0 ? round(($correctAnswers / $totalPairs) * 100, 1) : 0;

        // Calculate stars
        $stars = 0;
        $starsRequirement = $starsConfig;

        if ($accuracy >= ($starsRequirement['three_stars']['min_accuracy_percent'] ?? 90)) {
            $stars = 3;
        } elseif ($accuracy >= ($starsRequirement['two_stars']['min_accuracy_percent'] ?? 75)) {
            $stars = 2;
        } elseif ($accuracy >= ($starsRequirement['one_star']['min_accuracy_percent'] ?? 50)) {
            $stars = 1;
        }

        // Bonus XP and coins based on stars
        $bonusXp = 0;
        $bonusCoins = 0;

        if ($stars === 3) {
            $bonusXp = $starsRequirement['three_stars']['xp_reward'] ?? 100;
            $bonusCoins = $starsRequirement['three_stars']['coin_reward'] ?? 30;
            $bonusCoins += $rewards['coins']['three_star_bonus'] ?? 25;
        } elseif ($stars === 2) {
            $bonusXp = $starsRequirement['two_stars']['xp_reward'] ?? 60;
            $bonusCoins = $starsRequirement['two_stars']['coin_reward'] ?? 15;
        } elseif ($stars === 1) {
            $bonusXp = $starsRequirement['one_star']['xp_reward'] ?? 30;
            $bonusCoins = $starsRequirement['one_star']['coin_reward'] ?? 8;
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

        // No replay bonus for entire session
        $totalReplaysUsed = array_sum(array_map(fn($t) => 0, $session['answer_times'])); // Simplified

        // Calculate total time
        $totalTime = array_sum($session['answer_times']);
        $averageResponseTime = $correctAnswers > 0 ? round($totalTime / $correctAnswers, 2) : 0;

        // Final totals
        $totalXp = $session['xp_earned'] + $bonusXp;
        $totalCoins = $session['coins_earned'] + $bonusCoins;

        // Add level rewards
        $totalXp += $level['xp_reward'] ?? 0;
        $totalCoins += $level['coin_reward'] ?? 0;

        // Save progress
        if ($stars > 0) {
            $this->dataService->saveUserProgress($userId, $level['number'], [
                'stars' => $stars,
                'score' => $session['score'],
                'accuracy' => $accuracy,
            ]);
        }

        // Update user stats
        $this->dataService->updateUserStats($userId, [
            'pairs_completed' => $correctAnswers,
            'xp_earned' => $totalXp,
            'coins_earned' => $totalCoins,
            'best_streak' => $session['best_streak'],
            'is_perfect' => $session['is_perfect'] && $accuracy === 100,
            'total_time' => $totalTime,
            'total_attempts' => $totalPairs,
            'correct_attempts' => $correctAnswers,
            'total_responses' => $correctAnswers,
            'total_response_time' => $totalTime,
            'game_mode' => $session['game_mode'],
            'replays_used' => count($session['powerups_used']),
        ]);

        // Check achievements
        $newAchievements = $this->checkAchievements($userId, $session, $stars);

        // Mark session as completed
        $session['status'] = 'completed';
        Cache::put("minimal_pairs.session.{$sessionId}", $session, now()->addHours(1));

        // Calculate listening rank
        $userStats = $this->dataService->getUserStats($userId);

        return [
            'success' => true,
            'results' => [
                'score' => $session['score'],
                'xp_earned' => $totalXp,
                'coins_earned' => $totalCoins,
                'stars' => $stars,
                'accuracy' => $accuracy,
                'correct_answers' => $correctAnswers,
                'total_pairs' => $totalPairs,
                'best_streak' => $session['best_streak'],
                'wrong_answers' => $session['wrong_answers'],
                'total_time' => $totalTime,
                'average_response_time' => $averageResponseTime,
                'is_perfect' => $session['is_perfect'] && $accuracy === 100,
                'bonus_xp' => $bonusXp,
                'bonus_coins' => $bonusCoins,
                'game_mode' => $session['game_mode'],
                'categories_encountered' => $session['categories_encountered'],
            ],
            'level' => $level,
            'new_achievements' => $newAchievements,
            'next_level_unlocked' => $this->checkNextLevelUnlocked($level, $stars),
            'listening_rank' => $userStats['listening_rank'] ?? null,
        ];
    }

    /**
     * Check if next level is unlocked
     */
    protected function checkNextLevelUnlocked(array $currentLevel, int $starsEarned): bool
    {
        $levels = $this->dataService->getLevels();
        $nextLevelNumber = $currentLevel['number'] + 1;

        foreach ($levels as $level) {
            if ($level['number'] === $nextLevelNumber) {
                $requirement = $level['unlock_requirement'] ?? null;
                if (!$requirement) {
                    return true;
                }
                $requiredStars = $requirement['stars'] ?? 1;
                return $starsEarned >= $requiredStars;
            }
        }

        return false;
    }

    /**
     * Check and award achievements
     */
    protected function checkAchievements(mixed $userId, array $session, int $stars): array
    {
        $achievements = $this->dataService->getAchievements();
        $userStats = $this->dataService->getUserStats($userId);
        $userAchievements = $this->dataService->getUserAchievements($userId);
        $newAchievements = [];

        foreach ($achievements as $achievement) {
            // Check if already unlocked
            if (in_array($achievement['id'], $userAchievements)) {
                continue;
            }

            $requirement = $achievement['requirement'] ?? [];
            $type = $requirement['type'] ?? '';

            $unlocked = false;

            switch ($type) {
                case 'pairs_completed':
                    $unlocked = $userStats['total_pairs_completed'] >= ($requirement['count'] ?? 1);
                    break;

                case 'perfect_session':
                    $unlocked = $session['is_perfect'] && $session['correct_answers'] === $session['total_pairs'];
                    break;

                case 'streak':
                    $unlocked = $session['best_streak'] >= ($requirement['count'] ?? 1);
                    break;

                case 'fast_answers':
                    $fastCount = count(array_filter($session['answer_times'], fn($t) => $t <= ($requirement['time_limit'] ?? 2)));
                    $unlocked = $fastCount >= ($requirement['count'] ?? 1);
                    break;

                case 'levels_completed':
                    $unlocked = $userStats['levels_completed'] >= ($requirement['count'] ?? 1);
                    break;

                case 'all_three_stars':
                    $unlocked = $userStats['three_star_levels'] >= ($requirement['count'] ?? 10);
                    break;

                case 'category_mastery':
                    $requiredCategories = $requirement['categories'] ?? [];
                    $masteredCategories = $userStats['categories_mastered'] ?? [];
                    $allMastered = count(array_diff($requiredCategories, $masteredCategories)) === 0;
                    $unlocked = $allMastered;
                    break;

                case 'no_replay_level':
                    $unlocked = count($session['powerups_used']) === 0 && $stars > 0;
                    break;

                case 'mode_completed':
                    $modeKey = $requirement['mode'] . '_sessions';
                    $unlocked = ($userStats[$modeKey] ?? 0) >= ($requirement['count'] ?? 1);
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
        $session = Cache::get("minimal_pairs.session.{$sessionId}");
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
                    'completed' => $session['completed_pairs'],
                    'total' => $session['total_pairs'],
                ],
                'status' => $session['status'],
                'time_limit_per_pair' => $session['time_limit_per_pair'],
                'current_pair_index' => $session['current_pair_index'],
                'replays_remaining' => $session['max_replays'] - $session['replays_used'],
            ],
            'current_pair' => $session['pairs'][$session['current_pair_index']] ?? null,
        ];
    }
}
