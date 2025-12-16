<?php

namespace App\Services\English;

use Illuminate\Support\Str;

class TypingRaceService
{
    protected TypingRaceDataService $dataService;
    protected string $sessionsPath;

    public function __construct(TypingRaceDataService $dataService)
    {
        $this->dataService = $dataService;
        $this->sessionsPath = storage_path('app/game-sessions/typing-race');

        if (!file_exists($this->sessionsPath)) {
            mkdir($this->sessionsPath, 0755, true);
        }
    }

    /**
     * Start a new typing session
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

        // Get content for this level
        $content = $this->dataService->getContent($level);

        $sessionId = Str::uuid()->toString();
        $session = [
            'id' => $sessionId,
            'level_number' => $levelNumber,
            'mode' => $mode,
            'content' => $content,
            'current_index' => 0,
            'score' => 0,
            'total_characters' => 0,
            'correct_characters' => 0,
            'total_words' => 0,
            'correct_words' => 0,
            'wpm' => 0,
            'accuracy' => 100,
            'streak' => 0,
            'best_streak' => 0,
            'combo_multiplier' => 1.0,
            'time_limit' => $level['time_limit'] ?? 120,
            'time_elapsed' => 0,
            'started_at' => now()->toIso8601String(),
            'completed' => false,
            'powerups_used' => [],
            'errors' => [],
            'auto_corrections_left' => 0,
            'double_points_until' => null,
            'slow_motion_until' => null,
        ];

        $this->saveSession($sessionId, $session);

        return [
            'session_id' => $sessionId,
            'content' => $content,
            'current_index' => 0,
            'current_text' => $content[0] ?? '',
            'time_limit' => $session['time_limit'],
            'mode' => $mode,
            'total_items' => count($content),
        ];
    }

    /**
     * Submit typed text and check accuracy
     */
    public function submitText(string $sessionId, string $typedText, float $timeSpent): array
    {
        $session = $this->getSession($sessionId);

        if (!$session) {
            throw new \Exception('Session not found');
        }

        if ($session['completed']) {
            throw new \Exception('Session already completed');
        }

        $currentIndex = $session['current_index'];
        $originalText = $session['content'][$currentIndex] ?? '';

        // Calculate accuracy
        $result = $this->calculateTypingAccuracy($originalText, $typedText);

        // Update session stats
        $session['total_characters'] += strlen($originalText);
        $session['correct_characters'] += $result['correct_chars'];
        $session['total_words'] += $result['total_words'];
        $session['correct_words'] += $result['correct_words'];
        $session['time_elapsed'] += $timeSpent;

        // Calculate WPM
        $session['wpm'] = $this->calculateWPM($session['correct_characters'], $session['time_elapsed']);

        // Calculate overall accuracy
        $session['accuracy'] = $session['total_characters'] > 0
            ? round(($session['correct_characters'] / $session['total_characters']) * 100, 1)
            : 100;

        // Update streak
        if ($result['is_perfect']) {
            $session['streak']++;
            $session['best_streak'] = max($session['best_streak'], $session['streak']);

            // Increase combo multiplier
            $config = $this->dataService->getConfig();
            $increment = $config['scoring']['combo_multiplier_increment'] ?? 0.1;
            $maxMultiplier = $config['scoring']['max_combo_multiplier'] ?? 3.0;
            $session['combo_multiplier'] = min($maxMultiplier, $session['combo_multiplier'] + $increment);
        } else {
            $session['streak'] = 0;
            $session['combo_multiplier'] = 1.0;
        }

        // Calculate points
        $points = $this->calculatePoints($result, $session, $timeSpent);
        $session['score'] += $points;

        // Record errors
        if (!empty($result['errors'])) {
            $session['errors'][] = [
                'index' => $currentIndex,
                'errors' => $result['errors'],
            ];
        }

        // Move to next item
        $session['current_index']++;
        $isComplete = $session['current_index'] >= count($session['content']);

        if ($isComplete) {
            $session['completed'] = true;
        }

        $this->saveSession($sessionId, $session);

        return [
            'is_correct' => $result['is_perfect'],
            'accuracy' => $result['accuracy'],
            'correct_chars' => $result['correct_chars'],
            'total_chars' => strlen($originalText),
            'errors' => $result['errors'],
            'points_earned' => $points,
            'total_score' => $session['score'],
            'wpm' => $session['wpm'],
            'overall_accuracy' => $session['accuracy'],
            'streak' => $session['streak'],
            'combo_multiplier' => $session['combo_multiplier'],
            'current_index' => $session['current_index'],
            'next_text' => !$isComplete ? $session['content'][$session['current_index']] : null,
            'is_complete' => $isComplete,
            'items_remaining' => count($session['content']) - $session['current_index'],
        ];
    }

    /**
     * Calculate typing accuracy
     */
    protected function calculateTypingAccuracy(string $original, string $typed): array
    {
        $originalChars = mb_str_split($original);
        $typedChars = mb_str_split($typed);

        $correctChars = 0;
        $errors = [];

        $maxLength = max(count($originalChars), count($typedChars));

        for ($i = 0; $i < $maxLength; $i++) {
            $origChar = $originalChars[$i] ?? '';
            $typedChar = $typedChars[$i] ?? '';

            if ($origChar === $typedChar) {
                $correctChars++;
            } else {
                $errors[] = [
                    'position' => $i,
                    'expected' => $origChar,
                    'typed' => $typedChar,
                ];
            }
        }

        // Count words
        $originalWords = preg_split('/\s+/', trim($original));
        $typedWords = preg_split('/\s+/', trim($typed));

        $correctWords = 0;
        foreach ($originalWords as $index => $word) {
            if (isset($typedWords[$index]) && $typedWords[$index] === $word) {
                $correctWords++;
            }
        }

        $accuracy = count($originalChars) > 0
            ? round(($correctChars / count($originalChars)) * 100, 1)
            : 0;

        return [
            'correct_chars' => $correctChars,
            'total_chars' => count($originalChars),
            'correct_words' => $correctWords,
            'total_words' => count($originalWords),
            'accuracy' => $accuracy,
            'is_perfect' => empty($errors),
            'errors' => $errors,
        ];
    }

    /**
     * Calculate WPM (Words Per Minute)
     */
    protected function calculateWPM(int $characters, float $timeSeconds): int
    {
        if ($timeSeconds <= 0) {
            return 0;
        }

        // Standard WPM calculation: characters / 5 (average word length) / minutes
        $words = $characters / 5;
        $minutes = $timeSeconds / 60;

        return (int) round($words / $minutes);
    }

    /**
     * Calculate points for typed text
     */
    protected function calculatePoints(array $result, array $session, float $timeSpent): int
    {
        $config = $this->dataService->getConfig();
        $scoring = $config['scoring'] ?? [];

        // Base points per character
        $basePoints = $result['correct_chars'] * ($scoring['base_points_per_character'] ?? 1);

        // Accuracy bonus
        if ($result['accuracy'] >= 95) {
            $basePoints *= ($scoring['accuracy_bonus_multiplier'] ?? 1.5);
        }

        // Speed bonus
        $wpm = $this->calculateWPM($result['correct_chars'], $timeSpent);
        $speedThreshold = $scoring['speed_bonus_threshold_wpm'] ?? 60;
        if ($wpm >= $speedThreshold) {
            $basePoints += ($wpm - $speedThreshold) * ($scoring['speed_bonus_per_wpm'] ?? 2);
        }

        // Perfect word/sentence bonus
        if ($result['is_perfect']) {
            if ($result['total_words'] === 1) {
                $basePoints += $scoring['perfect_word_bonus'] ?? 5;
            } else {
                $basePoints += $scoring['perfect_sentence_bonus'] ?? 20;
            }
        }

        // Streak bonus
        $streakThreshold = $scoring['streak_bonus_threshold'] ?? 5;
        if ($session['streak'] >= $streakThreshold) {
            $basePoints += $scoring['streak_bonus'] ?? 10;
        }

        // Apply combo multiplier
        $basePoints *= $session['combo_multiplier'];

        // Double points powerup
        if ($session['double_points_until'] && now()->lt($session['double_points_until'])) {
            $basePoints *= 2;
        }

        return (int) round($basePoints);
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

        // Check usage limit
        $usedCount = count(array_filter($session['powerups_used'], fn($p) => $p === $powerupId));
        $maxUses = $powerup['uses_per_game'] ?? 1;

        if ($usedCount >= $maxUses) {
            throw new \Exception('Powerup usage limit reached');
        }

        // Record powerup usage
        $session['powerups_used'][] = $powerupId;

        $result = ['powerup_id' => $powerupId, 'effect' => null];

        // Apply powerup effect
        switch ($powerupId) {
            case 'slow_motion':
                $session['slow_motion_until'] = now()->addSeconds(5)->toIso8601String();
                $result['effect'] = 'slow_motion_active';
                $result['duration'] = 5;
                break;

            case 'auto_correct':
                $session['auto_corrections_left'] = 3;
                $result['effect'] = 'auto_correct_enabled';
                $result['corrections'] = 3;
                break;

            case 'skip_word':
                if ($session['current_index'] < count($session['content']) - 1) {
                    $session['current_index']++;
                    $result['effect'] = 'word_skipped';
                    $result['next_text'] = $session['content'][$session['current_index']];
                }
                break;

            case 'extra_time':
                $session['time_limit'] += 15;
                $result['effect'] = 'time_added';
                $result['seconds_added'] = 15;
                $result['new_time_limit'] = $session['time_limit'];
                break;

            case 'double_points':
                $session['double_points_until'] = now()->addSeconds(30)->toIso8601String();
                $result['effect'] = 'double_points_active';
                $result['duration'] = 30;
                break;

            case 'hint_letters':
                $currentText = $session['content'][$session['current_index']] ?? '';
                $hint = substr($currentText, 0, 3);
                $result['effect'] = 'hint_shown';
                $result['hint'] = $hint;
                break;
        }

        $this->saveSession($sessionId, $session);

        return array_merge($result, [
            'uses_left' => $maxUses - $usedCount - 1,
        ]);
    }

    /**
     * Complete session and calculate final results
     */
    public function completeSession(string $sessionId): array
    {
        $session = $this->getSession($sessionId);

        if (!$session) {
            throw new \Exception('Session not found');
        }

        $level = $this->dataService->getLevel($session['level_number']);

        // Calculate final stats
        $finalWpm = $session['wpm'];
        $finalAccuracy = $session['accuracy'];
        $finalScore = $session['score'];

        // Calculate stars based on accuracy and WPM
        $config = $this->dataService->getConfig();
        $starThresholds = $config['star_thresholds'] ?? [];

        $targetWpm = $level['target_wpm'] ?? 30;
        $targetAccuracy = $level['target_accuracy'] ?? 85;

        // Combined score based on accuracy and WPM
        $accuracyScore = $finalAccuracy;
        $wpmScore = min(100, ($finalWpm / $targetWpm) * 100);
        $combinedScore = ($accuracyScore * 0.6) + ($wpmScore * 0.4);

        $stars = 0;
        if ($combinedScore >= ($starThresholds['three_stars'] ?? 95)) {
            $stars = 3;
        } elseif ($combinedScore >= ($starThresholds['two_stars'] ?? 80)) {
            $stars = 2;
        } elseif ($combinedScore >= ($starThresholds['one_star'] ?? 60)) {
            $stars = 1;
        }

        // Calculate XP and coins
        $baseXp = $level['xp_reward'] ?? 50;
        $baseCoins = $level['coins_reward'] ?? 20;

        $xpMultiplier = 1 + ($stars * 0.25);
        $xpEarned = (int) round($baseXp * $xpMultiplier);
        $coinsEarned = (int) round($baseCoins * $xpMultiplier);

        // Update user progress
        $progress = $this->dataService->getUserProgress();
        $levelProgress = $progress['levels'][$session['level_number']] ?? [
            'completed' => false,
            'stars' => 0,
            'best_score' => 0,
            'best_wpm' => 0,
            'best_accuracy' => 0,
        ];

        // Update level progress
        $isNewBest = $finalScore > ($levelProgress['best_score'] ?? 0);
        $levelProgress['completed'] = true;
        $levelProgress['stars'] = max($levelProgress['stars'], $stars);
        $levelProgress['best_score'] = max($levelProgress['best_score'] ?? 0, $finalScore);
        $levelProgress['best_wpm'] = max($levelProgress['best_wpm'] ?? 0, $finalWpm);
        $levelProgress['best_accuracy'] = max($levelProgress['best_accuracy'] ?? 0, $finalAccuracy);
        $levelProgress['last_played'] = now()->toIso8601String();

        $progress['levels'][$session['level_number']] = $levelProgress;

        // Update global stats
        $stats = $progress['stats'] ?? [];
        $stats['games_played'] = ($stats['games_played'] ?? 0) + 1;
        $stats['total_characters'] = ($stats['total_characters'] ?? 0) + $session['total_characters'];
        $stats['total_words'] = ($stats['total_words'] ?? 0) + $session['total_words'];
        $stats['total_correct'] = ($stats['total_correct'] ?? 0) + $session['correct_characters'];
        $stats['best_wpm'] = max($stats['best_wpm'] ?? 0, $finalWpm);
        $stats['best_accuracy'] = max($stats['best_accuracy'] ?? 0, $finalAccuracy);
        $stats['best_streak'] = max($stats['best_streak'] ?? 0, $session['best_streak']);
        $stats['total_xp'] = ($stats['total_xp'] ?? 0) + $xpEarned;
        $stats['total_coins'] = ($stats['total_coins'] ?? 0) + $coinsEarned;
        $stats['total_time_seconds'] = ($stats['total_time_seconds'] ?? 0) + $session['time_elapsed'];

        if ($finalAccuracy >= 100) {
            $stats['perfect_games'] = ($stats['perfect_games'] ?? 0) + 1;
        }
        if ($finalAccuracy >= 95) {
            $stats['high_accuracy_games'] = ($stats['high_accuracy_games'] ?? 0) + 1;
        }

        // Calculate average WPM
        $totalGames = $stats['games_played'];
        $stats['average_wpm'] = (int) round(
            (($stats['average_wpm'] ?? 0) * ($totalGames - 1) + $finalWpm) / $totalGames
        );

        // Calculate average accuracy
        $stats['average_accuracy'] = round(
            (($stats['average_accuracy'] ?? 0) * ($totalGames - 1) + $finalAccuracy) / $totalGames,
            1
        );

        // Count levels completed
        $levelsCompleted = count(array_filter($progress['levels'], fn($l) => $l['completed'] ?? false));
        $stats['levels_completed'] = $levelsCompleted;

        // Count total stars
        $totalStars = array_sum(array_map(fn($l) => $l['stars'] ?? 0, $progress['levels']));
        $stats['total_stars'] = $totalStars;

        // Track content type stats
        $contentType = $level['content_type'] ?? 'words';
        $stats["{$contentType}_typed"] = ($stats["{$contentType}_typed"] ?? 0) + count($session['content']);

        $progress['stats'] = $stats;

        $this->dataService->saveUserProgress($progress);

        // Check for new achievements
        $newAchievements = $this->dataService->checkAchievements($stats);

        // Clean up session
        $this->deleteSession($sessionId);

        return [
            'score' => $finalScore,
            'wpm' => $finalWpm,
            'accuracy' => $finalAccuracy,
            'stars' => $stars,
            'xp_earned' => $xpEarned,
            'coins_earned' => $coinsEarned,
            'is_new_best' => $isNewBest,
            'total_characters' => $session['total_characters'],
            'correct_characters' => $session['correct_characters'],
            'total_words' => $session['total_words'],
            'correct_words' => $session['correct_words'],
            'best_streak' => $session['best_streak'],
            'time_elapsed' => $session['time_elapsed'],
            'errors_count' => count($session['errors']),
            'achievements' => $newAchievements,
            'level_progress' => $levelProgress,
        ];
    }

    /**
     * Get session state
     */
    public function getSessionState(string $sessionId): ?array
    {
        return $this->getSession($sessionId);
    }

    /**
     * Get session from storage
     */
    protected function getSession(string $sessionId): ?array
    {
        $sessionFile = $this->sessionsPath . "/{$sessionId}.json";

        if (!file_exists($sessionFile)) {
            return null;
        }

        return json_decode(file_get_contents($sessionFile), true);
    }

    /**
     * Save session to storage
     */
    protected function saveSession(string $sessionId, array $session): void
    {
        $sessionFile = $this->sessionsPath . "/{$sessionId}.json";
        file_put_contents($sessionFile, json_encode($session, JSON_PRETTY_PRINT));
    }

    /**
     * Delete session from storage
     */
    protected function deleteSession(string $sessionId): void
    {
        $sessionFile = $this->sessionsPath . "/{$sessionId}.json";

        if (file_exists($sessionFile)) {
            unlink($sessionFile);
        }
    }
}
