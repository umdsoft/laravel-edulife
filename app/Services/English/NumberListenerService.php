<?php

namespace App\Services\English;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class NumberListenerService
{
    protected NumberListenerDataService $dataService;
    protected GameScoringService $scoringService;
    protected string $sessionsPath;

    public function __construct(NumberListenerDataService $dataService, GameScoringService $scoringService)
    {
        $this->dataService = $dataService;
        $this->scoringService = $scoringService;
        $this->sessionsPath = storage_path('app/game_sessions/number_listener');

        if (!File::exists($this->sessionsPath)) {
            File::makeDirectory($this->sessionsPath, 0755, true);
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

        $config = $this->dataService->getConfig();
        $gameModes = $config['game_modes'] ?? [];
        $selectedMode = null;

        foreach ($gameModes as $gm) {
            if ($gm['id'] === $mode) {
                $selectedMode = $gm;
                break;
            }
        }

        if (!$selectedMode) {
            $selectedMode = $gameModes[0] ?? ['id' => 'classic', 'time_per_number' => 10, 'replays_allowed' => 2];
        }

        // Get numbers for this level
        $numbers = $this->dataService->getNumbersForLevel($level);

        // If not enough numbers from file, generate some
        if (count($numbers) < ($level['numbers_count'] ?? 10)) {
            $numbers = $this->dataService->generateRandomNumbers($level);
        }

        $sessionId = Str::uuid()->toString();
        $session = [
            'id' => $sessionId,
            'user_id' => Auth::id(),
            'level_number' => $levelNumber,
            'level' => $level,
            'mode' => $selectedMode,
            'numbers' => $numbers,
            'current_index' => 0,
            'score' => 0,
            'correct_count' => 0,
            'wrong_count' => 0,
            'streak' => 0,
            'best_streak' => 0,
            'replays_used' => 0,
            'replays_remaining' => $selectedMode['replays_allowed'] ?? $level['replays_allowed'] ?? 2,
            'time_per_number' => $selectedMode['time_per_number'] ?? $level['time_per_number'] ?? 10,
            'answers' => [],
            'powerups_used' => [],
            'double_xp_remaining' => 0,
            'started_at' => now()->toISOString(),
            'completed' => false,
        ];

        $this->saveSession($session);

        return [
            'session_id' => $sessionId,
            'level' => $level,
            'mode' => $selectedMode,
            'total_numbers' => count($numbers),
            'current_index' => 0,
            'current_number' => $this->prepareNumberForClient($numbers[0], $level['category'] ?? 'basic'),
            'time_per_number' => $session['time_per_number'],
            'replays_remaining' => $session['replays_remaining'],
        ];
    }

    /**
     * Prepare number data for client (hide the answer)
     */
    protected function prepareNumberForClient(array $number, string $category): array
    {
        return [
            'word' => $number['word'],
            'category' => $category,
            'display' => $number['display'] ?? null,
        ];
    }

    /**
     * Check an answer
     */
    public function checkAnswer(string $sessionId, string $answer, float $timeSpent): array
    {
        $session = $this->getSession($sessionId);

        if (!$session) {
            throw new \Exception('Session not found');
        }

        if ($session['completed']) {
            throw new \Exception('Session already completed');
        }

        $currentIndex = $session['current_index'];
        $currentNumber = $session['numbers'][$currentIndex];
        $level = $session['level'];
        $config = $this->dataService->getConfig();
        $scoring = $config['scoring'] ?? [];

        // Get the correct answer based on category
        $correctAnswer = $this->getCorrectAnswer($currentNumber, $level['category'] ?? 'basic');

        // Check if answer is correct
        $isCorrect = $this->compareAnswers($answer, $correctAnswer, $level['category'] ?? 'basic');

        $points = 0;
        $xpEarned = 0;
        $coinsEarned = 0;

        if ($isCorrect) {
            $session['correct_count']++;
            $session['streak']++;

            if ($session['streak'] > $session['best_streak']) {
                $session['best_streak'] = $session['streak'];
            }

            // Calculate points
            $basePoints = $scoring['base_points'] ?? 100;
            $timeBonus = max(0, ($scoring['time_bonus_max'] ?? 50) * (1 - $timeSpent / $session['time_per_number']));
            $streakMultiplier = 1 + ($session['streak'] * ($scoring['streak_multiplier'] ?? 0.1));

            // Category difficulty multiplier
            $category = $level['category'] ?? 'basic';
            $difficultyMultiplier = $scoring['difficulty_multipliers'][$category] ?? 1.0;

            // No replay bonus
            $noReplayBonus = $session['replays_used'] === 0 ? ($scoring['no_replay_bonus'] ?? 25) : 0;

            $points = (int)(($basePoints + $timeBonus + $noReplayBonus) * $streakMultiplier * $difficultyMultiplier);

            // XP rewards
            $xpRewards = $config['xp_rewards'] ?? [];
            $xpEarned = $xpRewards['correct_answer'] ?? 10;

            if ($session['streak'] >= 5) {
                $xpEarned += $xpRewards['streak_bonus'] ?? 5;
            }

            // Double XP powerup
            if ($session['double_xp_remaining'] > 0) {
                $xpEarned *= 2;
                $session['double_xp_remaining']--;
            }

            // Coin rewards
            $coinRewards = $config['coin_rewards'] ?? [];
            $coinsEarned = $coinRewards['correct_answer'] ?? 2;

            if ($session['streak'] >= 5) {
                $coinsEarned += $coinRewards['streak_bonus'] ?? 1;
            }

            $session['score'] += $points;
        } else {
            $session['wrong_count']++;
            $session['streak'] = 0;
        }

        // Record the answer
        $session['answers'][] = [
            'index' => $currentIndex,
            'number' => $currentNumber,
            'user_answer' => $answer,
            'correct_answer' => $correctAnswer,
            'is_correct' => $isCorrect,
            'time_spent' => $timeSpent,
            'points_earned' => $points,
            'replays_used' => $session['replays_used'],
        ];

        // Reset replays for next number
        $session['replays_used'] = 0;

        // Move to next number
        $session['current_index']++;
        $hasMore = $session['current_index'] < count($session['numbers']);

        $this->saveSession($session);

        $response = [
            'is_correct' => $isCorrect,
            'correct_answer' => $correctAnswer,
            'points_earned' => $points,
            'xp_earned' => $xpEarned,
            'coins_earned' => $coinsEarned,
            'current_score' => $session['score'],
            'streak' => $session['streak'],
            'correct_count' => $session['correct_count'],
            'wrong_count' => $session['wrong_count'],
            'has_more' => $hasMore,
            'progress' => [
                'current' => $session['current_index'],
                'total' => count($session['numbers']),
            ],
        ];

        if ($hasMore) {
            $nextNumber = $session['numbers'][$session['current_index']];
            $response['next_number'] = $this->prepareNumberForClient($nextNumber, $level['category'] ?? 'basic');
            $response['replays_remaining'] = $session['mode']['replays_allowed'] ?? $level['replays_allowed'] ?? 2;
        }

        return $response;
    }

    /**
     * Get correct answer from number data
     */
    protected function getCorrectAnswer(array $number, string $category): string
    {
        if (isset($number['number'])) {
            return (string)$number['number'];
        }

        if (isset($number['amount'])) {
            return number_format($number['amount'], 2, '.', '');
        }

        if (isset($number['time'])) {
            return $number['time'];
        }

        return '';
    }

    /**
     * Compare user answer with correct answer
     */
    protected function compareAnswers(string $userAnswer, string $correctAnswer, string $category): bool
    {
        // Normalize both answers
        $userAnswer = trim(strtolower($userAnswer));
        $correctAnswer = trim(strtolower($correctAnswer));

        // Remove common prefixes
        $userAnswer = preg_replace('/^\$/', '', $userAnswer);
        $correctAnswer = preg_replace('/^\$/', '', $correctAnswer);

        // For time, allow flexibility
        if ($category === 'time') {
            // Remove colons and compare
            $userClean = preg_replace('/[:\s]/', '', $userAnswer);
            $correctClean = preg_replace('/[:\s]/', '', $correctAnswer);
            return $userClean === $correctClean;
        }

        // For money, allow flexibility with decimal places
        if ($category === 'money') {
            $userNum = (float)preg_replace('/[^\d.]/', '', $userAnswer);
            $correctNum = (float)preg_replace('/[^\d.]/', '', $correctAnswer);
            return abs($userNum - $correctNum) < 0.01;
        }

        // For regular numbers
        return $userAnswer === $correctAnswer;
    }

    /**
     * Use a replay
     */
    public function useReplay(string $sessionId): array
    {
        $session = $this->getSession($sessionId);

        if (!$session) {
            throw new \Exception('Session not found');
        }

        $replaysRemaining = $session['replays_remaining'] ?? 0;

        if ($replaysRemaining <= 0) {
            throw new \Exception('No replays remaining');
        }

        $session['replays_remaining']--;
        $session['replays_used']++;

        $this->saveSession($session);

        $currentNumber = $session['numbers'][$session['current_index']];

        return [
            'replays_remaining' => $session['replays_remaining'],
            'current_number' => $this->prepareNumberForClient($currentNumber, $session['level']['category'] ?? 'basic'),
        ];
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

        $config = $this->dataService->getConfig();
        $powerups = $config['powerups'] ?? [];

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

        // Check usage limits
        $usedCount = $session['powerups_used'][$powerupId] ?? 0;
        $maxUses = $powerup['uses_per_game'] ?? 1;

        if ($usedCount >= $maxUses) {
            throw new \Exception('Powerup usage limit reached');
        }

        // Apply powerup effect
        $result = [
            'powerup_id' => $powerupId,
            'effect' => $powerup['effect'],
        ];

        switch ($powerup['effect']) {
            case 'add_replay':
                $session['replays_remaining']++;
                $result['replays_remaining'] = $session['replays_remaining'];
                break;

            case 'show_first_digit':
                $currentNumber = $session['numbers'][$session['current_index']];
                $correctAnswer = $this->getCorrectAnswer($currentNumber, $session['level']['category'] ?? 'basic');
                $result['hint'] = substr($correctAnswer, 0, 1) . str_repeat('_', strlen($correctAnswer) - 1);
                break;

            case 'skip_number':
                $session['current_index']++;
                $hasMore = $session['current_index'] < count($session['numbers']);
                $result['skipped'] = true;
                $result['has_more'] = $hasMore;

                if ($hasMore) {
                    $nextNumber = $session['numbers'][$session['current_index']];
                    $result['next_number'] = $this->prepareNumberForClient($nextNumber, $session['level']['category'] ?? 'basic');
                    $result['replays_remaining'] = $session['mode']['replays_allowed'] ?? 2;
                }
                break;

            case 'double_xp':
                $duration = $powerup['duration'] ?? 5;
                $session['double_xp_remaining'] = $duration;
                $result['double_xp_remaining'] = $duration;
                break;

            case 'slow_playback':
                $result['playback_speed'] = 0.7;
                break;
        }

        // Record usage
        $session['powerups_used'][$powerupId] = $usedCount + 1;

        $this->saveSession($session);

        return $result;
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

        $session['completed'] = true;
        $session['completed_at'] = now()->toISOString();

        $config = $this->dataService->getConfig();
        $starThresholds = $config['star_thresholds'] ?? [];
        $xpRewards = $config['xp_rewards'] ?? [];
        $coinRewards = $config['coin_rewards'] ?? [];

        $totalNumbers = count($session['numbers']);
        $accuracy = $totalNumbers > 0 ? round(($session['correct_count'] / $totalNumbers) * 100) : 0;

        // Calculate stars
        $stars = 0;
        if ($accuracy >= ($starThresholds['three_stars'] ?? 95)) {
            $stars = 3;
        } elseif ($accuracy >= ($starThresholds['two_stars'] ?? 80)) {
            $stars = 2;
        } elseif ($accuracy >= ($starThresholds['one_star'] ?? 60)) {
            $stars = 1;
        }

        // Calculate XP
        $xpEarned = $xpRewards['level_complete'] ?? 100;

        if ($stars === 3) {
            $xpEarned += $xpRewards['three_stars'] ?? 150;
        }

        if ($accuracy === 100) {
            $xpEarned += $xpRewards['no_mistakes'] ?? 100;
        }

        // Calculate coins
        $coinsEarned = $coinRewards['level_complete'] ?? 25;

        if ($stars === 3) {
            $coinsEarned += $coinRewards['three_stars'] ?? 50;
        }

        // Update user progress
        $userProgress = $this->dataService->getUserProgress();
        $levelNumber = $session['level_number'];
        $isFirstComplete = !isset($userProgress['levels'][$levelNumber]) || !$userProgress['levels'][$levelNumber]['completed'];

        if ($isFirstComplete) {
            $xpEarned += $xpRewards['first_time_complete'] ?? 200;
        }

        // Update level progress
        $existingProgress = $userProgress['levels'][$levelNumber] ?? [];
        $userProgress['levels'][$levelNumber] = [
            'completed' => true,
            'stars' => max($stars, $existingProgress['stars'] ?? 0),
            'best_score' => max($session['score'], $existingProgress['best_score'] ?? 0),
            'best_accuracy' => max($accuracy, $existingProgress['best_accuracy'] ?? 0),
            'attempts' => ($existingProgress['attempts'] ?? 0) + 1,
            'last_played' => now()->toISOString(),
        ];

        // Update overall stats
        $userProgress['total_xp'] = ($userProgress['total_xp'] ?? 0) + $xpEarned;
        $userProgress['total_coins'] = ($userProgress['total_coins'] ?? 0) + $coinsEarned;
        $userProgress['total_numbers_correct'] = ($userProgress['total_numbers_correct'] ?? 0) + $session['correct_count'];
        $userProgress['total_numbers_attempted'] = ($userProgress['total_numbers_attempted'] ?? 0) + $totalNumbers;
        $userProgress['games_played'] = ($userProgress['games_played'] ?? 0) + 1;

        if ($session['best_streak'] > ($userProgress['best_streak'] ?? 0)) {
            $userProgress['best_streak'] = $session['best_streak'];
        }

        // Update category stats
        $category = $session['level']['category'] ?? 'basic';
        if (!isset($userProgress['category_stats'][$category])) {
            $userProgress['category_stats'][$category] = ['correct' => 0, 'attempted' => 0];
        }
        $userProgress['category_stats'][$category]['correct'] += $session['correct_count'];
        $userProgress['category_stats'][$category]['attempted'] += $totalNumbers;

        // Update daily streak
        $lastPlayed = $userProgress['last_played'] ?? null;
        $today = now()->toDateString();

        if ($lastPlayed) {
            $lastPlayedDate = date('Y-m-d', strtotime($lastPlayed));
            $yesterday = now()->subDay()->toDateString();

            if ($lastPlayedDate === $yesterday) {
                $userProgress['daily_streak'] = ($userProgress['daily_streak'] ?? 0) + 1;
            } elseif ($lastPlayedDate !== $today) {
                $userProgress['daily_streak'] = 1;
            }
        } else {
            $userProgress['daily_streak'] = 1;
        }

        $userProgress['last_played'] = now()->toISOString();

        // Check achievements
        $newAchievements = $this->checkAchievements($userProgress, $session);

        foreach ($newAchievements as $achievement) {
            if (!in_array($achievement['id'], $userProgress['achievements'] ?? [])) {
                $userProgress['achievements'][] = $achievement['id'];
                $xpEarned += $achievement['xp_reward'] ?? 0;
                $coinsEarned += $achievement['coin_reward'] ?? 0;
            }
        }

        $this->dataService->saveUserProgress($userProgress);
        $this->saveSession($session);

        // Determine current rank
        $ranks = $this->dataService->getNumberMasterRanks();
        $currentRank = null;
        foreach ($ranks as $rank) {
            if ($userProgress['total_xp'] >= $rank['xp_required']) {
                $currentRank = $rank;
            }
        }

        return [
            'score' => $session['score'],
            'correct_count' => $session['correct_count'],
            'wrong_count' => $session['wrong_count'],
            'accuracy' => $accuracy,
            'stars' => $stars,
            'best_streak' => $session['best_streak'],
            'xp_earned' => $xpEarned,
            'coins_earned' => $coinsEarned,
            'total_xp' => $userProgress['total_xp'],
            'total_coins' => $userProgress['total_coins'],
            'is_new_best' => $session['score'] === $userProgress['levels'][$levelNumber]['best_score'],
            'is_first_complete' => $isFirstComplete,
            'new_achievements' => $newAchievements,
            'current_rank' => $currentRank,
            'answers' => $session['answers'],
        ];
    }

    /**
     * Check for new achievements
     */
    protected function checkAchievements(array $userProgress, array $session): array
    {
        $allAchievements = $this->dataService->getAllAchievements();
        $unlockedIds = $userProgress['achievements'] ?? [];
        $newAchievements = [];

        foreach ($allAchievements as $achievement) {
            if (in_array($achievement['id'], $unlockedIds)) {
                continue;
            }

            if ($this->isAchievementUnlocked($achievement, $userProgress, $session)) {
                $newAchievements[] = $achievement;
            }
        }

        return $newAchievements;
    }

    /**
     * Check if an achievement is unlocked
     */
    protected function isAchievementUnlocked(array $achievement, array $userProgress, array $session): bool
    {
        $condition = $achievement['condition'] ?? '';

        if (preg_match('/(\w+)\s*>=\s*(\d+)/', $condition, $matches)) {
            $field = $matches[1];
            $target = (int)$matches[2];

            $current = 0;
            switch ($field) {
                case 'numbers_heard':
                case 'total_correct':
                    $current = $userProgress['total_numbers_correct'] ?? 0;
                    break;
                case 'streak':
                    $current = max($userProgress['best_streak'] ?? 0, $session['best_streak'] ?? 0);
                    break;
                case 'levels_completed':
                    $current = count(array_filter($userProgress['levels'] ?? [], fn($l) => $l['completed'] ?? false));
                    break;
                case 'three_star_levels':
                    $current = count(array_filter($userProgress['levels'] ?? [], fn($l) => ($l['stars'] ?? 0) >= 3));
                    break;
                case 'daily_streak':
                    $current = $userProgress['daily_streak'] ?? 0;
                    break;
                case 'perfect_levels':
                    $current = count(array_filter($userProgress['levels'] ?? [], fn($l) => ($l['best_accuracy'] ?? 0) >= 100));
                    break;
            }

            return $current >= $target;
        }

        return false;
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
            'session_id' => $sessionId,
            'level' => $session['level'],
            'mode' => $session['mode'],
            'current_index' => $session['current_index'],
            'total_numbers' => count($session['numbers']),
            'score' => $session['score'],
            'correct_count' => $session['correct_count'],
            'wrong_count' => $session['wrong_count'],
            'streak' => $session['streak'],
            'replays_remaining' => $session['replays_remaining'],
            'completed' => $session['completed'],
        ];
    }

    /**
     * Save session to file
     */
    protected function saveSession(array $session): void
    {
        $sessionPath = $this->sessionsPath . '/' . $session['id'] . '.json';
        File::put($sessionPath, json_encode($session, JSON_PRETTY_PRINT));
    }

    /**
     * Get session from file
     */
    protected function getSession(string $sessionId): ?array
    {
        $sessionPath = $this->sessionsPath . '/' . $sessionId . '.json';

        if (!File::exists($sessionPath)) {
            return null;
        }

        return json_decode(File::get($sessionPath), true);
    }
}
