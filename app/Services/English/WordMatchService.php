<?php

namespace App\Services\English;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class WordMatchService
{
    protected WordMatchDataService $dataService;

    public function __construct(WordMatchDataService $dataService)
    {
        $this->dataService = $dataService;
    }

    /**
     * Start a new game session
     */
    public function startSession(mixed $userId, int $levelNumber, string $gameMode = 'classic_match'): array
    {
        $level = $this->dataService->getLevel($levelNumber);
        if (!$level) {
            return ['success' => false, 'error' => 'Level not found'];
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
            'classic_match' => $this->prepareClassicMatchPairs($pairs, $level),
            'memory_flip' => $this->prepareMemoryFlipCards($pairs, $level),
            'speed_match' => $this->prepareSpeedMatchPairs($pairs, $level),
            default => $this->prepareClassicMatchPairs($pairs, $level),
        };

        $sessionId = Str::uuid()->toString();
        $timeLimit = $level['time_limit'][$gameMode] ?? 120;

        $session = [
            'id' => $sessionId,
            'user_id' => $userId,
            'level_number' => $levelNumber,
            'level' => $level,
            'game_mode' => $gameMode,
            'pairs' => $preparedData['pairs'],
            'cards' => $preparedData['cards'] ?? [],
            'total_pairs' => count($pairs),
            'matched_pairs' => 0,
            'score' => 0,
            'xp_earned' => 0,
            'coins_earned' => 0,
            'streak' => 0,
            'best_streak' => 0,
            'correct_matches' => 0,
            'wrong_matches' => 0,
            'powerups_used' => [],
            'time_started' => now()->toDateTimeString(),
            'time_limit' => $timeLimit,
            'match_times' => [],
            'is_perfect' => true,
            'status' => 'active',
            'flipped_cards' => [], // For memory mode
            'current_pair_index' => 0, // For speed mode
        ];

        // Store session in cache for 2 hours
        Cache::put("word_match.session.{$sessionId}", $session, now()->addHours(2));

        return [
            'success' => true,
            'session_id' => $sessionId,
            'level' => $level,
            'game_mode' => $gameMode,
            'total_pairs' => count($pairs),
            'time_limit' => $timeLimit,
            'cards' => $preparedData['cards'] ?? [],
            'left_column' => $preparedData['left_column'] ?? [],
            'right_column' => $preparedData['right_column'] ?? [],
            'current_pair' => $preparedData['current_pair'] ?? null,
            'powerups' => $this->dataService->getPowerups(),
        ];
    }

    /**
     * Prepare pairs for Classic Match mode
     */
    protected function prepareClassicMatchPairs(array $pairs, array $level): array
    {
        $matchTypes = $level['match_types'] ?? ['word_translation'];

        $leftColumn = [];
        $rightColumn = [];

        foreach ($pairs as $index => $pair) {
            $matchType = $matchTypes[array_rand($matchTypes)];
            $cardId = Str::uuid()->toString();
            $matchId = Str::uuid()->toString();

            // Left side is always the word
            $leftColumn[] = [
                'id' => $cardId,
                'pair_id' => $pair['id'],
                'match_id' => $matchId,
                'content' => $pair['word'],
                'type' => 'word',
            ];

            // Right side depends on match type
            $rightContent = match ($matchType) {
                'word_translation' => $pair['translation_uz'],
                'word_definition' => $pair['definition'],
                'synonym' => $pair['synonyms'][0] ?? $pair['translation_uz'],
                'antonym' => $pair['antonyms'][0] ?? $pair['translation_uz'],
                default => $pair['translation_uz'],
            };

            $rightColumn[] = [
                'id' => Str::uuid()->toString(),
                'pair_id' => $pair['id'],
                'match_id' => $matchId,
                'content' => $rightContent,
                'type' => $matchType,
            ];
        }

        // Shuffle right column
        shuffle($rightColumn);

        return [
            'pairs' => $pairs,
            'left_column' => $leftColumn,
            'right_column' => $rightColumn,
        ];
    }

    /**
     * Prepare cards for Memory Flip mode
     */
    protected function prepareMemoryFlipCards(array $pairs, array $level): array
    {
        $cards = [];

        foreach ($pairs as $pair) {
            $matchId = Str::uuid()->toString();

            // Word card
            $cards[] = [
                'id' => Str::uuid()->toString(),
                'pair_id' => $pair['id'],
                'match_id' => $matchId,
                'content' => $pair['word'],
                'type' => 'word',
                'flipped' => false,
                'matched' => false,
            ];

            // Translation card
            $cards[] = [
                'id' => Str::uuid()->toString(),
                'pair_id' => $pair['id'],
                'match_id' => $matchId,
                'content' => $pair['translation_uz'],
                'type' => 'translation',
                'flipped' => false,
                'matched' => false,
            ];
        }

        // Shuffle cards
        shuffle($cards);

        return [
            'pairs' => $pairs,
            'cards' => $cards,
        ];
    }

    /**
     * Prepare pairs for Speed Match mode
     */
    protected function prepareSpeedMatchPairs(array $pairs, array $level): array
    {
        $preparedPairs = [];

        foreach ($pairs as $pair) {
            // Create options (1 correct + 3 wrong)
            $options = [$pair['translation_uz']];

            // Get random wrong options from other pairs
            $otherPairs = array_filter($pairs, fn($p) => $p['id'] !== $pair['id']);
            shuffle($otherPairs);
            $wrongOptions = array_slice($otherPairs, 0, 3);

            foreach ($wrongOptions as $wrong) {
                $options[] = $wrong['translation_uz'];
            }

            shuffle($options);

            $preparedPairs[] = [
                'id' => Str::uuid()->toString(),
                'pair_id' => $pair['id'],
                'word' => $pair['word'],
                'correct_answer' => $pair['translation_uz'],
                'options' => $options,
            ];
        }

        return [
            'pairs' => $pairs,
            'current_pair' => $preparedPairs[0] ?? null,
            'all_pairs' => $preparedPairs,
        ];
    }

    /**
     * Check a match (for Classic and Memory modes)
     */
    public function checkMatch(string $sessionId, string $card1Id, string $card2Id, int $timeSpent): array
    {
        $session = Cache::get("word_match.session.{$sessionId}");
        if (!$session) {
            return ['success' => false, 'error' => 'Session not found'];
        }

        if ($session['status'] !== 'active') {
            return ['success' => false, 'error' => 'Session is not active'];
        }

        $gameMode = $session['game_mode'];

        if ($gameMode === 'memory_flip') {
            return $this->checkMemoryMatch($session, $card1Id, $card2Id, $timeSpent);
        }

        return $this->checkClassicMatch($session, $card1Id, $card2Id, $timeSpent);
    }

    /**
     * Check Classic Match
     */
    protected function checkClassicMatch(array $session, string $leftId, string $rightId, int $timeSpent): array
    {
        // Find cards
        $leftCard = null;
        $rightCard = null;

        foreach ($session['pairs'] as $pair) {
            // This is simplified - in real implementation, you'd track cards separately
        }

        // For now, we'll pass match_id from frontend
        // The frontend will send the match_ids and we compare them
        $isCorrect = $leftId === $rightId; // Frontend sends match_ids

        if ($isCorrect) {
            return $this->handleCorrectMatch($session, $timeSpent);
        } else {
            return $this->handleWrongMatch($session);
        }
    }

    /**
     * Check Memory Flip Match
     */
    protected function checkMemoryMatch(array $session, string $card1Id, string $card2Id, int $timeSpent): array
    {
        $cards = $session['cards'];
        $card1 = null;
        $card2 = null;

        foreach ($cards as &$card) {
            if ($card['id'] === $card1Id) {
                $card1 = $card;
            }
            if ($card['id'] === $card2Id) {
                $card2 = $card;
            }
        }

        if (!$card1 || !$card2) {
            return ['success' => false, 'error' => 'Cards not found'];
        }

        $isCorrect = $card1['match_id'] === $card2['match_id'];

        if ($isCorrect) {
            // Mark cards as matched
            foreach ($cards as &$card) {
                if ($card['id'] === $card1Id || $card['id'] === $card2Id) {
                    $card['matched'] = true;
                }
            }
            $session['cards'] = $cards;

            return $this->handleCorrectMatch($session, $timeSpent, true);
        } else {
            return $this->handleWrongMatch($session, true);
        }
    }

    /**
     * Check Speed Match answer
     */
    public function checkSpeedAnswer(string $sessionId, string $selectedAnswer, int $timeSpent): array
    {
        $session = Cache::get("word_match.session.{$sessionId}");
        if (!$session) {
            return ['success' => false, 'error' => 'Session not found'];
        }

        if ($session['status'] !== 'active') {
            return ['success' => false, 'error' => 'Session is not active'];
        }

        $currentIndex = $session['current_pair_index'];
        $pairs = $session['pairs'];

        if ($currentIndex >= count($pairs)) {
            return ['success' => false, 'error' => 'No more pairs'];
        }

        $currentPair = $pairs[$currentIndex];
        $isCorrect = $selectedAnswer === $currentPair['translation_uz'];

        if ($isCorrect) {
            $session['current_pair_index']++;
            $result = $this->handleCorrectMatch($session, $timeSpent);

            // Add next pair info
            if ($session['current_pair_index'] < count($pairs)) {
                $nextPair = $pairs[$session['current_pair_index']];
                $options = [$nextPair['translation_uz']];

                $otherPairs = array_filter($pairs, fn($p, $i) => $i !== $session['current_pair_index'], ARRAY_FILTER_USE_BOTH);
                shuffle($otherPairs);
                $wrongOptions = array_slice(array_values($otherPairs), 0, 3);

                foreach ($wrongOptions as $wrong) {
                    $options[] = $wrong['translation_uz'];
                }
                shuffle($options);

                $result['next_pair'] = [
                    'word' => $nextPair['word'],
                    'options' => $options,
                ];
            }

            return $result;
        } else {
            return $this->handleWrongMatch($session);
        }
    }

    /**
     * Handle correct match
     */
    protected function handleCorrectMatch(array $session, int $timeSpent, bool $isMemoryMode = false): array
    {
        $config = $this->dataService->getConfig();
        $scoring = $config['scoring'] ?? [];
        $rewards = $config['rewards'] ?? [];
        $level = $session['level'];
        $gameMode = $session['game_mode'];

        // Calculate score
        $basePoints = $scoring['base_points_per_match'] ?? 50;

        // Time bonus
        $timeBonus = 0;
        if (isset($scoring['time_bonus']['enabled']) && $scoring['time_bonus']['enabled']) {
            $fastThreshold = $scoring['time_bonus']['fast_threshold_seconds'] ?? 3;
            if ($timeSpent <= $fastThreshold) {
                $maxBonusPercent = $scoring['time_bonus']['max_bonus_percent'] ?? 100;
                $timeBonus = round($basePoints * ($maxBonusPercent / 100) * (1 - $timeSpent / $fastThreshold));
            }
        }

        // Update streak
        $session['streak']++;
        $session['best_streak'] = max($session['best_streak'], $session['streak']);

        // Streak multiplier
        $streakMultiplier = 1.0;
        if (isset($scoring['streak_multiplier']['enabled']) && $scoring['streak_multiplier']['enabled']) {
            $increment = $scoring['streak_multiplier']['increment_per_streak'] ?? 0.15;
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
        $xpPerMatch = $rewards['xp']['per_match_base'] ?? 15;
        $xpPerStreak = $rewards['xp']['per_streak_match'] ?? 5;
        $speedBonusMax = $rewards['xp']['speed_bonus_max'] ?? 30;

        $xpEarned = $xpPerMatch;

        if ($session['streak'] > 1) {
            $xpEarned += $xpPerStreak * ($session['streak'] - 1);
        }

        // Speed bonus XP
        if ($timeSpent <= 2) {
            $xpEarned += $speedBonusMax;
        } elseif ($timeSpent <= 4) {
            $xpEarned += round($speedBonusMax * 0.5);
        }

        // Memory mode bonus
        if ($isMemoryMode) {
            $xpEarned += $rewards['xp']['memory_mode_bonus'] ?? 25;
        }

        // Apply difficulty multiplier to XP
        $xpEarned = round($xpEarned * $difficultyMultiplier);

        // Calculate coins
        $coinsEarned = 0;
        if ($session['wrong_matches'] === 0) {
            $coinsEarned = $rewards['coins']['per_perfect_match'] ?? 3;
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
        $session['correct_matches']++;
        $session['matched_pairs']++;
        $session['match_times'][] = $timeSpent;

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
        $isComplete = $session['matched_pairs'] >= $session['total_pairs'];

        // Save session
        Cache::put("word_match.session.{$session['id']}", $session, now()->addHours(2));

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
            'progress' => [
                'matched' => $session['matched_pairs'],
                'total' => $session['total_pairs'],
            ],
            'is_complete' => $isComplete,
            'cards' => $session['cards'] ?? null,
        ];
    }

    /**
     * Handle wrong match
     */
    protected function handleWrongMatch(array $session, bool $isMemoryMode = false): array
    {
        $session['streak'] = 0;
        $session['wrong_matches']++;
        $session['is_perfect'] = false;

        Cache::put("word_match.session.{$session['id']}", $session, now()->addHours(2));

        return [
            'success' => true,
            'correct' => false,
            'message' => $isMemoryMode ? 'Kartalar mos kelmadi!' : 'Noto\'g\'ri juftlik!',
            'streak' => 0,
            'cards' => $session['cards'] ?? null,
        ];
    }

    /**
     * Use a powerup
     */
    public function usePowerup(string $sessionId, string $powerupType): array
    {
        $session = Cache::get("word_match.session.{$sessionId}");
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

        switch ($powerupType) {
            case 'reveal':
                // Find an unmatched pair and reveal it
                if ($session['game_mode'] === 'memory_flip') {
                    $unmatchedCards = array_filter($session['cards'], fn($c) => !$c['matched']);
                    if (!empty($unmatchedCards)) {
                        $card = reset($unmatchedCards);
                        $matchId = $card['match_id'];
                        $matchingCards = array_filter($session['cards'], fn($c) => $c['match_id'] === $matchId);
                        $powerupData = [
                            'type' => 'reveal',
                            'card_ids' => array_column($matchingCards, 'id'),
                        ];
                    }
                }
                break;

            case 'shuffle':
                if ($session['game_mode'] === 'classic_match') {
                    // Reshuffle would be handled on frontend
                    $powerupData = ['type' => 'shuffle'];
                }
                break;

            case 'freeze_time':
                $powerupData = [
                    'type' => 'freeze_time',
                    'duration' => 10,
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

        Cache::put("word_match.session.{$session['id']}", $session, now()->addHours(2));

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
        $session = Cache::get("word_match.session.{$sessionId}");
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
        $correctMatches = $session['correct_matches'];
        $accuracy = $totalPairs > 0 ? round(($correctMatches / $totalPairs) * 100, 1) : 0;

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
            $bonusXp = $starsConfig['three_stars']['xp_reward'] ?? 80;
            $bonusCoins = $starsConfig['three_stars']['coin_reward'] ?? 25;
            $bonusCoins += $rewards['coins']['three_star_bonus'] ?? 20;
        } elseif ($stars === 2) {
            $bonusXp = $starsConfig['two_stars']['xp_reward'] ?? 50;
            $bonusCoins = $starsConfig['two_stars']['coin_reward'] ?? 12;
        } elseif ($stars === 1) {
            $bonusXp = $starsConfig['one_star']['xp_reward'] ?? 25;
            $bonusCoins = $starsConfig['one_star']['coin_reward'] ?? 5;
        }

        // Level complete bonus
        if ($stars > 0) {
            $bonusXp += $rewards['xp']['level_complete_bonus'] ?? 75;
            $bonusCoins += $rewards['coins']['per_level_complete'] ?? 15;
        }

        // Perfect session bonus
        if ($session['is_perfect'] && $accuracy === 100) {
            $bonusXp += $rewards['xp']['perfect_session_bonus'] ?? 150;
            $bonusCoins += $rewards['coins']['perfect_session_bonus'] ?? 30;
        }

        // Calculate total time
        $totalTime = array_sum($session['match_times']);

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
            'matches_completed' => $correctMatches,
            'xp_earned' => $totalXp,
            'coins_earned' => $totalCoins,
            'best_streak' => $session['best_streak'],
            'is_perfect' => $session['is_perfect'] && $accuracy === 100,
            'total_time' => $totalTime,
            'total_attempts' => $totalPairs,
            'correct_attempts' => $correctMatches,
            'game_mode' => $session['game_mode'],
        ]);

        // Check achievements
        $newAchievements = $this->checkAchievements($userId, $session, $stars);

        // Mark session as completed
        $session['status'] = 'completed';
        Cache::put("word_match.session.{$sessionId}", $session, now()->addHours(1));

        return [
            'success' => true,
            'results' => [
                'score' => $session['score'],
                'xp_earned' => $totalXp,
                'coins_earned' => $totalCoins,
                'stars' => $stars,
                'accuracy' => $accuracy,
                'correct_matches' => $correctMatches,
                'total_pairs' => $totalPairs,
                'best_streak' => $session['best_streak'],
                'wrong_matches' => $session['wrong_matches'],
                'total_time' => $totalTime,
                'average_time' => $totalPairs > 0 ? round($totalTime / $totalPairs, 1) : 0,
                'is_perfect' => $session['is_perfect'] && $accuracy === 100,
                'bonus_xp' => $bonusXp,
                'bonus_coins' => $bonusCoins,
                'game_mode' => $session['game_mode'],
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
                case 'matches_completed':
                    $unlocked = $userStats['total_matches_completed'] >= $value;
                    break;

                case 'streak':
                    $unlocked = $session['best_streak'] >= $value;
                    break;

                case 'perfect_sessions':
                    $unlocked = $userStats['perfect_sessions'] >= $value;
                    break;

                case 'match_time':
                    foreach ($session['match_times'] as $time) {
                        if ($time <= $value) {
                            $unlocked = true;
                            break;
                        }
                    }
                    break;

                case 'perfect_memory_mode':
                    $unlocked = $session['game_mode'] === 'memory_flip' &&
                               $session['is_perfect'] &&
                               $session['correct_matches'] === $session['total_pairs'];
                    break;

                case 'level_complete':
                    $unlocked = $session['level']['number'] >= $value && $stars > 0;
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
        $session = Cache::get("word_match.session.{$sessionId}");
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
                'correct_matches' => $session['correct_matches'],
                'wrong_matches' => $session['wrong_matches'],
                'progress' => [
                    'matched' => $session['matched_pairs'],
                    'total' => $session['total_pairs'],
                ],
                'status' => $session['status'],
                'time_limit' => $session['time_limit'],
            ],
            'cards' => $session['cards'] ?? null,
        ];
    }
}
