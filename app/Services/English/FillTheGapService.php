<?php

namespace App\Services\English;

use Illuminate\Support\Str;

class FillTheGapService
{
    protected FillTheGapDataService $dataService;
    protected string $sessionsPath;

    public function __construct(FillTheGapDataService $dataService)
    {
        $this->dataService = $dataService;
        $this->sessionsPath = storage_path('app/game-sessions/fill-the-gap');

        if (!file_exists($this->sessionsPath)) {
            mkdir($this->sessionsPath, 0755, true);
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

        // Get sentences for this level
        $sentences = $this->dataService->getSentences($level);

        // Prepare sentences with gap info
        $preparedSentences = array_map(function ($sentence) use ($level) {
            return $this->prepareSentence($sentence, $level);
        }, $sentences);

        $sessionId = Str::uuid()->toString();
        $session = [
            'id' => $sessionId,
            'level_number' => $levelNumber,
            'mode' => $mode,
            'sentences' => $preparedSentences,
            'current_index' => 0,
            'score' => 0,
            'correct_answers' => 0,
            'total_answers' => 0,
            'streak' => 0,
            'best_streak' => 0,
            'combo_multiplier' => 1.0,
            'time_limit' => $level['time_limit'] ?? 180,
            'time_elapsed' => 0,
            'started_at' => now()->toIso8601String(),
            'completed' => false,
            'powerups_used' => [],
            'category' => $level['category'] ?? 'vocabulary',
            'has_options' => $level['has_options'] ?? true,
            'double_points_active' => false,
        ];

        $this->saveSession($sessionId, $session);

        return [
            'session_id' => $sessionId,
            'current_sentence' => $this->getSentenceForClient($preparedSentences[0]),
            'current_index' => 0,
            'total_sentences' => count($preparedSentences),
            'time_limit' => $session['time_limit'],
            'mode' => $mode,
            'has_options' => $session['has_options'],
        ];
    }

    /**
     * Prepare sentence with gap markers
     */
    protected function prepareSentence(array $sentence, array $level): array
    {
        $gaps = $sentence['gaps'] ?? [];
        $options = $sentence['options'] ?? [];

        // Add distractors if needed
        $optionsCount = $level['options_count'] ?? 4;
        if ($level['has_options'] && count($options) < $optionsCount) {
            $options = $this->addDistractors($options, $optionsCount);
        }

        // Shuffle options
        shuffle($options);

        return [
            'sentence' => $sentence['sentence'],
            'gaps' => $gaps,
            'options' => $options,
            'hint' => $sentence['hint'] ?? null,
            'difficulty' => $sentence['difficulty'] ?? 'beginner',
        ];
    }

    /**
     * Add distractor options
     */
    protected function addDistractors(array $options, int $targetCount): array
    {
        $distractors = [
            'the', 'a', 'an', 'in', 'on', 'at', 'by', 'for', 'with', 'to',
            'is', 'are', 'was', 'were', 'be', 'been', 'being',
            'have', 'has', 'had', 'do', 'does', 'did',
            'will', 'would', 'could', 'should', 'might', 'must',
            'very', 'much', 'more', 'most', 'less', 'least',
        ];

        // Filter out existing options
        $available = array_diff($distractors, $options);
        shuffle($available);

        while (count($options) < $targetCount && !empty($available)) {
            $options[] = array_shift($available);
        }

        return $options;
    }

    /**
     * Get sentence data for client (without answers)
     */
    protected function getSentenceForClient(array $sentence): array
    {
        return [
            'sentence' => $sentence['sentence'],
            'options' => $sentence['options'] ?? [],
            'hint' => $sentence['hint'] ?? null,
            'gaps_count' => count($sentence['gaps'] ?? []),
        ];
    }

    /**
     * Check answer for current sentence
     */
    public function checkAnswer(string $sessionId, array $answers, float $timeSpent): array
    {
        $session = $this->getSession($sessionId);

        if (!$session) {
            throw new \Exception('Session not found');
        }

        if ($session['completed']) {
            throw new \Exception('Session already completed');
        }

        $currentIndex = $session['current_index'];
        $currentSentence = $session['sentences'][$currentIndex];
        $correctAnswers = $currentSentence['gaps'];

        // Check each gap
        $results = [];
        $allCorrect = true;
        $correctCount = 0;

        foreach ($correctAnswers as $index => $correctAnswer) {
            $userAnswer = $answers[$index] ?? '';
            $isCorrect = $this->normalizeAnswer($userAnswer) === $this->normalizeAnswer($correctAnswer);

            $results[] = [
                'index' => $index,
                'correct' => $isCorrect,
                'user_answer' => $userAnswer,
                'correct_answer' => $correctAnswer,
            ];

            if ($isCorrect) {
                $correctCount++;
            } else {
                $allCorrect = false;
            }
        }

        // Update session stats
        $session['total_answers'] += count($correctAnswers);
        $session['correct_answers'] += $correctCount;
        $session['time_elapsed'] += $timeSpent;

        // Update streak
        if ($allCorrect) {
            $session['streak']++;
            $session['best_streak'] = max($session['best_streak'], $session['streak']);

            // Increase combo
            $config = $this->dataService->getConfig();
            $increment = $config['scoring']['combo_increment'] ?? 0.1;
            $maxCombo = $config['scoring']['max_combo'] ?? 3.0;
            $session['combo_multiplier'] = min($maxCombo, $session['combo_multiplier'] + $increment);
        } else {
            $session['streak'] = 0;
            $session['combo_multiplier'] = 1.0;
        }

        // Calculate points
        $points = $this->calculatePoints($correctCount, count($correctAnswers), $session, $timeSpent, $allCorrect);
        $session['score'] += $points;

        // Move to next sentence
        $session['current_index']++;
        $isComplete = $session['current_index'] >= count($session['sentences']);

        if ($isComplete) {
            $session['completed'] = true;
        }

        // Reset double points
        $session['double_points_active'] = false;

        $this->saveSession($sessionId, $session);

        $response = [
            'results' => $results,
            'all_correct' => $allCorrect,
            'correct_count' => $correctCount,
            'total_gaps' => count($correctAnswers),
            'points_earned' => $points,
            'total_score' => $session['score'],
            'streak' => $session['streak'],
            'combo_multiplier' => $session['combo_multiplier'],
            'current_index' => $session['current_index'],
            'is_complete' => $isComplete,
        ];

        if (!$isComplete) {
            $response['next_sentence'] = $this->getSentenceForClient($session['sentences'][$session['current_index']]);
        }

        return $response;
    }

    /**
     * Normalize answer for comparison
     */
    protected function normalizeAnswer(string $answer): string
    {
        return strtolower(trim($answer));
    }

    /**
     * Calculate points for answer
     */
    protected function calculatePoints(int $correct, int $total, array $session, float $timeSpent, bool $allCorrect): int
    {
        $config = $this->dataService->getConfig();
        $scoring = $config['scoring'] ?? [];

        $basePoints = ($correct / $total) * ($scoring['base_points'] ?? 10) * $total;

        // Time bonus (faster = more points)
        $maxTimeBonus = $scoring['time_bonus_multiplier'] ?? 0.5;
        $timeRatio = max(0, 1 - ($timeSpent / 30)); // 30 seconds as baseline
        $basePoints += $basePoints * $timeRatio * $maxTimeBonus;

        // Streak bonus
        if ($session['streak'] >= 3) {
            $basePoints += $scoring['streak_bonus'] ?? 5;
        }

        // Perfect bonus
        if ($allCorrect) {
            $basePoints += $scoring['perfect_bonus'] ?? 20;
        }

        // Apply combo multiplier
        $basePoints *= $session['combo_multiplier'];

        // Double points powerup
        if ($session['double_points_active']) {
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

        $session['powerups_used'][] = $powerupId;

        $result = ['powerup_id' => $powerupId, 'effect' => null];

        $currentSentence = $session['sentences'][$session['current_index']] ?? null;

        switch ($powerupId) {
            case 'reveal_letter':
                if ($currentSentence) {
                    $firstGap = $currentSentence['gaps'][0] ?? '';
                    $result['effect'] = 'letter_revealed';
                    $result['letter'] = substr($firstGap, 0, 1);
                }
                break;

            case 'eliminate_option':
                if ($currentSentence && !empty($currentSentence['options'])) {
                    $correctAnswers = array_map([$this, 'normalizeAnswer'], $currentSentence['gaps']);
                    $wrongOptions = array_filter($currentSentence['options'], function ($opt) use ($correctAnswers) {
                        return !in_array($this->normalizeAnswer($opt), $correctAnswers);
                    });

                    if (!empty($wrongOptions)) {
                        $eliminated = array_values($wrongOptions)[0];
                        $result['effect'] = 'option_eliminated';
                        $result['eliminated'] = $eliminated;
                    }
                }
                break;

            case 'extra_time':
                $session['time_limit'] += 15;
                $result['effect'] = 'time_added';
                $result['seconds_added'] = 15;
                break;

            case 'skip_question':
                if ($session['current_index'] < count($session['sentences']) - 1) {
                    $session['current_index']++;
                    $result['effect'] = 'question_skipped';
                    $result['next_sentence'] = $this->getSentenceForClient($session['sentences'][$session['current_index']]);
                }
                break;

            case 'double_points':
                $session['double_points_active'] = true;
                $result['effect'] = 'double_points_activated';
                break;

            case 'hint':
                if ($currentSentence && isset($currentSentence['hint'])) {
                    $result['effect'] = 'hint_shown';
                    $result['hint'] = $currentSentence['hint'];
                }
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
        $accuracy = $session['total_answers'] > 0
            ? round(($session['correct_answers'] / $session['total_answers']) * 100, 1)
            : 0;

        // Calculate stars
        $config = $this->dataService->getConfig();
        $starThresholds = $config['star_thresholds'] ?? [];

        $stars = 0;
        if ($accuracy >= ($starThresholds['three_stars'] ?? 95)) {
            $stars = 3;
        } elseif ($accuracy >= ($starThresholds['two_stars'] ?? 80)) {
            $stars = 2;
        } elseif ($accuracy >= ($starThresholds['one_star'] ?? 60)) {
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
            'best_accuracy' => 0,
        ];

        $isNewBest = $session['score'] > ($levelProgress['best_score'] ?? 0);
        $levelProgress['completed'] = true;
        $levelProgress['stars'] = max($levelProgress['stars'], $stars);
        $levelProgress['best_score'] = max($levelProgress['best_score'] ?? 0, $session['score']);
        $levelProgress['best_accuracy'] = max($levelProgress['best_accuracy'] ?? 0, $accuracy);
        $levelProgress['last_played'] = now()->toIso8601String();

        $progress['levels'][$session['level_number']] = $levelProgress;

        // Update global stats
        $stats = $progress['stats'] ?? [];
        $stats['games_played'] = ($stats['games_played'] ?? 0) + 1;
        $stats['gaps_filled'] = ($stats['gaps_filled'] ?? 0) + $session['total_answers'];
        $stats['correct_answers'] = ($stats['correct_answers'] ?? 0) + $session['correct_answers'];
        $stats['total_answers'] = ($stats['total_answers'] ?? 0) + $session['total_answers'];
        $stats['best_streak'] = max($stats['best_streak'] ?? 0, $session['best_streak']);
        $stats['total_xp'] = ($stats['total_xp'] ?? 0) + $xpEarned;
        $stats['total_coins'] = ($stats['total_coins'] ?? 0) + $coinsEarned;

        // Calculate overall accuracy
        $stats['accuracy'] = $stats['total_answers'] > 0
            ? round(($stats['correct_answers'] / $stats['total_answers']) * 100, 1)
            : 0;

        // Track perfect games
        if ($accuracy >= 100) {
            $stats['perfect_games'] = ($stats['perfect_games'] ?? 0) + 1;
        }

        // Track category stats
        $category = $session['category'];
        $stats["{$category}_correct"] = ($stats["{$category}_correct"] ?? 0) + $session['correct_answers'];

        // Check if typing mode
        if (!$session['has_options']) {
            $stats['typing_correct'] = ($stats['typing_correct'] ?? 0) + $session['correct_answers'];
        }

        // Count completed levels
        $levelsCompleted = count(array_filter($progress['levels'], fn($l) => $l['completed'] ?? false));
        $stats['levels_completed'] = $levelsCompleted;

        // Count total stars
        $totalStars = array_sum(array_map(fn($l) => $l['stars'] ?? 0, $progress['levels']));
        $stats['total_stars'] = $totalStars;

        $progress['stats'] = $stats;

        $this->dataService->saveUserProgress($progress);

        // Check for new achievements
        $newAchievements = $this->dataService->checkAchievements($stats);

        // Clean up session
        $this->deleteSession($sessionId);

        return [
            'score' => $session['score'],
            'accuracy' => $accuracy,
            'stars' => $stars,
            'xp_earned' => $xpEarned,
            'coins_earned' => $coinsEarned,
            'is_new_best' => $isNewBest,
            'correct_answers' => $session['correct_answers'],
            'total_answers' => $session['total_answers'],
            'best_streak' => $session['best_streak'],
            'time_elapsed' => $session['time_elapsed'],
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
