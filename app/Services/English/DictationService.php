<?php

namespace App\Services\English;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class DictationService
{
    protected DictationDataService $dataService;
    protected array $config;

    public function __construct(DictationDataService $dataService)
    {
        $this->dataService = $dataService;
        $this->config = $dataService->getConfig();
    }

    /**
     * Start a new game session
     */
    public function startSession($userId, int $levelNumber): array
    {
        $level = $this->dataService->getLevel($levelNumber);

        if (!$level) {
            return ['success' => false, 'error' => 'Level not found'];
        }

        $itemCount = $this->config['settings']['items_per_session'] ?? 10;
        $items = $this->dataService->getLevelContent($levelNumber, $itemCount);

        if (empty($items)) {
            return ['success' => false, 'error' => 'No content available for this level'];
        }

        $sessionId = Str::uuid()->toString();
        $sessionData = [
            'session_id' => $sessionId,
            'user_id' => $userId,
            'level_number' => $levelNumber,
            'level' => $level,
            'items' => $items,
            'current_index' => 0,
            'total_items' => count($items),
            'score' => 0,
            'correct_count' => 0,
            'streak' => 0,
            'max_streak' => 0,
            'hints_used' => 0,
            'replays_used' => [],
            'results' => [],
            'started_at' => now()->toDateTimeString(),
            'completed' => false,
        ];

        // Store session in cache
        Cache::put("dictation_session_{$sessionId}", $sessionData, now()->addHours(2));

        return [
            'success' => true,
            'session_id' => $sessionId,
            'level' => $level,
            'total_items' => count($items),
            'current_item' => $this->prepareItemForClient($items[0]),
            'current_index' => 0,
        ];
    }

    /**
     * Get current session state
     */
    public function getSession(string $sessionId): ?array
    {
        return Cache::get("dictation_session_{$sessionId}");
    }

    /**
     * Get current item for playback
     */
    public function getCurrentItem(string $sessionId): ?array
    {
        $session = $this->getSession($sessionId);

        if (!$session || $session['completed']) {
            return null;
        }

        $currentIndex = $session['current_index'];
        if ($currentIndex >= count($session['items'])) {
            return null;
        }

        $item = $session['items'][$currentIndex];
        return $this->prepareItemForClient($item);
    }

    /**
     * Check user's answer
     */
    public function checkAnswer(string $sessionId, string $userAnswer): array
    {
        $session = $this->getSession($sessionId);

        if (!$session) {
            return ['success' => false, 'error' => 'Session not found'];
        }

        if ($session['completed']) {
            return ['success' => false, 'error' => 'Session already completed'];
        }

        $currentIndex = $session['current_index'];
        $item = $session['items'][$currentIndex];
        $correctAnswer = $item['text'];

        // Normalize answers for comparison
        $normalizedUser = $this->normalizeText($userAnswer);
        $normalizedCorrect = $this->normalizeText($correctAnswer);

        // Calculate accuracy
        $accuracy = $this->calculateAccuracy($normalizedUser, $normalizedCorrect);
        $isCorrect = $accuracy >= 90; // 90% threshold for "correct"
        $isPerfect = $accuracy === 100;

        // Calculate points
        $points = $this->calculatePoints($accuracy, $session['streak'], $isPerfect);

        // Apply hint penalty if hints were used for this item
        $hintsForItem = $session['hints_used_current'] ?? 0;
        if ($hintsForItem > 0) {
            $penalty = ($this->config['settings']['hint_penalty_percent'] ?? 15) / 100;
            $points = (int) ($points * (1 - ($penalty * $hintsForItem)));
        }

        // Update session
        $session['score'] += $points;
        $session['results'][] = [
            'item_id' => $item['id'],
            'text' => $correctAnswer,
            'user_answer' => $userAnswer,
            'accuracy' => $accuracy,
            'is_correct' => $isCorrect,
            'is_perfect' => $isPerfect,
            'points' => $points,
            'hints_used' => $hintsForItem,
        ];

        if ($isCorrect) {
            $session['correct_count']++;
            $session['streak']++;
            $session['max_streak'] = max($session['max_streak'], $session['streak']);
        } else {
            $session['streak'] = 0;
        }

        // Move to next item or complete
        $session['current_index']++;
        $session['hints_used_current'] = 0;

        $isLastItem = $session['current_index'] >= $session['total_items'];

        if ($isLastItem) {
            $session['completed'] = true;
            $session['completed_at'] = now()->toDateTimeString();
        }

        Cache::put("dictation_session_{$sessionId}", $session, now()->addHours(2));

        $response = [
            'success' => true,
            'is_correct' => $isCorrect,
            'is_perfect' => $isPerfect,
            'accuracy' => $accuracy,
            'points' => $points,
            'correct_answer' => $correctAnswer,
            'current_score' => $session['score'],
            'streak' => $session['streak'],
            'is_last_item' => $isLastItem,
        ];

        if (!$isLastItem) {
            $response['next_item'] = $this->prepareItemForClient($session['items'][$session['current_index']]);
            $response['current_index'] = $session['current_index'];
        }

        return $response;
    }

    /**
     * Use hint (reveal partial answer)
     */
    public function useHint(string $sessionId): array
    {
        $session = $this->getSession($sessionId);

        if (!$session || $session['completed']) {
            return ['success' => false, 'error' => 'Invalid session'];
        }

        $maxHints = $this->config['settings']['max_hints_per_item'] ?? 2;
        $currentHints = $session['hints_used_current'] ?? 0;

        if ($currentHints >= $maxHints) {
            return ['success' => false, 'error' => 'No hints remaining'];
        }

        $item = $session['items'][$session['current_index']];
        $text = $item['text'];
        $words = explode(' ', $text);

        // Reveal more words based on hint number
        $revealCount = min($currentHints + 1, count($words));
        $hint = implode(' ', array_slice($words, 0, $revealCount)) . '...';

        $session['hints_used']++;
        $session['hints_used_current'] = $currentHints + 1;

        Cache::put("dictation_session_{$sessionId}", $session, now()->addHours(2));

        return [
            'success' => true,
            'hint' => $hint,
            'hints_remaining' => $maxHints - ($currentHints + 1),
        ];
    }

    /**
     * Record replay usage
     */
    public function recordReplay(string $sessionId): array
    {
        $session = $this->getSession($sessionId);

        if (!$session || $session['completed']) {
            return ['success' => false, 'error' => 'Invalid session'];
        }

        $currentIndex = $session['current_index'];
        $maxReplays = $this->config['settings']['max_replays'] ?? 5;

        if (!isset($session['replays_used'][$currentIndex])) {
            $session['replays_used'][$currentIndex] = 0;
        }

        if ($session['replays_used'][$currentIndex] >= $maxReplays) {
            return ['success' => false, 'error' => 'No replays remaining', 'replays_remaining' => 0];
        }

        $session['replays_used'][$currentIndex]++;
        Cache::put("dictation_session_{$sessionId}", $session, now()->addHours(2));

        return [
            'success' => true,
            'replays_remaining' => $maxReplays - $session['replays_used'][$currentIndex],
        ];
    }

    /**
     * Complete session and calculate final results
     */
    public function completeSession(string $sessionId, $userId): array
    {
        $session = $this->getSession($sessionId);

        if (!$session) {
            return ['success' => false, 'error' => 'Session not found'];
        }

        // Calculate final statistics
        $totalItems = $session['total_items'];
        $correctCount = $session['correct_count'];
        $accuracy = $totalItems > 0 ? round(($correctCount / $totalItems) * 100) : 0;

        // Calculate stars
        $stars = $this->calculateStars($accuracy);

        // Calculate rewards
        $rewards = $this->calculateRewards($session, $accuracy, $stars);

        $result = [
            'session_id' => $sessionId,
            'level_number' => $session['level_number'],
            'total_items' => $totalItems,
            'correct_count' => $correctCount,
            'accuracy' => $accuracy,
            'total_score' => $session['score'],
            'max_streak' => $session['max_streak'],
            'stars' => $stars,
            'rewards' => $rewards,
            'results' => $session['results'],
            'duration' => $this->calculateDuration($session['started_at'], $session['completed_at'] ?? now()->toDateTimeString()),
        ];

        // Save progress
        $this->dataService->saveUserProgress($userId, $session['level_number'], [
            'score' => $session['score'],
            'stars' => $stars,
            'accuracy' => $accuracy,
        ]);

        // Clean up session
        Cache::forget("dictation_session_{$sessionId}");

        return [
            'success' => true,
            'result' => $result,
        ];
    }

    /**
     * Skip current item
     */
    public function skipItem(string $sessionId): array
    {
        $session = $this->getSession($sessionId);

        if (!$session || $session['completed']) {
            return ['success' => false, 'error' => 'Invalid session'];
        }

        $currentIndex = $session['current_index'];
        $item = $session['items'][$currentIndex];

        // Record as incorrect
        $session['results'][] = [
            'item_id' => $item['id'],
            'text' => $item['text'],
            'user_answer' => '',
            'accuracy' => 0,
            'is_correct' => false,
            'is_perfect' => false,
            'points' => 0,
            'skipped' => true,
        ];

        $session['streak'] = 0;
        $session['current_index']++;
        $session['hints_used_current'] = 0;

        $isLastItem = $session['current_index'] >= $session['total_items'];

        if ($isLastItem) {
            $session['completed'] = true;
            $session['completed_at'] = now()->toDateTimeString();
        }

        Cache::put("dictation_session_{$sessionId}", $session, now()->addHours(2));

        $response = [
            'success' => true,
            'correct_answer' => $item['text'],
            'is_last_item' => $isLastItem,
        ];

        if (!$isLastItem) {
            $response['next_item'] = $this->prepareItemForClient($session['items'][$session['current_index']]);
            $response['current_index'] = $session['current_index'];
        }

        return $response;
    }

    /**
     * Normalize text for comparison
     */
    protected function normalizeText(string $text): string
    {
        // Convert to lowercase
        $text = mb_strtolower($text);

        // Remove extra spaces
        $text = preg_replace('/\s+/', ' ', $text);

        // Trim
        $text = trim($text);

        return $text;
    }

    /**
     * Calculate accuracy between two strings
     */
    protected function calculateAccuracy(string $userAnswer, string $correctAnswer): int
    {
        if (empty($correctAnswer)) {
            return 0;
        }

        if ($userAnswer === $correctAnswer) {
            return 100;
        }

        // Use Levenshtein distance for similarity
        $maxLen = max(strlen($userAnswer), strlen($correctAnswer));

        if ($maxLen === 0) {
            return 100;
        }

        $distance = levenshtein($userAnswer, $correctAnswer);
        $similarity = (1 - ($distance / $maxLen)) * 100;

        return max(0, (int) round($similarity));
    }

    /**
     * Calculate points for an answer
     */
    protected function calculatePoints(int $accuracy, int $streak, bool $isPerfect): int
    {
        $scoring = $this->config['scoring'] ?? [];
        $basePoints = $scoring['base_points_per_word'] ?? 10;

        // Base points scaled by accuracy
        $points = (int) ($basePoints * ($accuracy / 100));

        // Perfect bonus
        if ($isPerfect) {
            $points += $scoring['perfect_bonus'] ?? 5;
        }

        // Streak bonus
        $streakMultiplier = $scoring['streak_multiplier'] ?? 0.1;
        $maxStreakBonus = $scoring['max_streak_bonus'] ?? 50;
        $streakBonus = min($streak * $streakMultiplier * $basePoints, $maxStreakBonus);
        $points += (int) $streakBonus;

        return $points;
    }

    /**
     * Calculate stars based on accuracy
     */
    protected function calculateStars(int $accuracy): int
    {
        $thresholds = $this->config['star_thresholds'] ?? [
            'one_star' => 50,
            'two_stars' => 75,
            'three_stars' => 90,
        ];

        if ($accuracy >= $thresholds['three_stars']) {
            return 3;
        } elseif ($accuracy >= $thresholds['two_stars']) {
            return 2;
        } elseif ($accuracy >= $thresholds['one_star']) {
            return 1;
        }

        return 0;
    }

    /**
     * Calculate rewards (XP and coins)
     */
    protected function calculateRewards(array $session, int $accuracy, int $stars): array
    {
        $rewards = $this->config['rewards'] ?? [];

        $xpPerCorrect = $rewards['xp_per_correct'] ?? 2;
        $xpPerPerfect = $rewards['xp_per_perfect'] ?? 5;
        $coinsPerComplete = $rewards['coins_per_session_complete'] ?? 3;
        $coinsPerfect = $rewards['coins_perfect_session'] ?? 5;

        // Calculate XP
        $xp = 0;
        foreach ($session['results'] as $result) {
            if ($result['is_correct'] ?? false) {
                $xp += $xpPerCorrect;
            }
            if ($result['is_perfect'] ?? false) {
                $xp += $xpPerPerfect;
            }
        }

        // Bonus XP for stars
        $xp += $stars * 5;

        // Calculate coins
        $coins = $coinsPerComplete;
        if ($accuracy === 100) {
            $coins += $coinsPerfect;
        }

        return [
            'xp_earned' => $xp,
            'xp_completion' => $xp,
            'coins_earned' => $coins,
            'coins_completion' => $coins,
        ];
    }

    /**
     * Calculate duration between two timestamps
     */
    protected function calculateDuration(string $start, string $end): int
    {
        $startTime = strtotime($start);
        $endTime = strtotime($end);
        return max(0, $endTime - $startTime);
    }

    /**
     * Prepare item for client (hide answer)
     */
    protected function prepareItemForClient(array $item): array
    {
        return [
            'id' => $item['id'],
            'text' => $item['text'], // Text for TTS
            'translation_uz' => $item['translation_uz'] ?? null,
            'word_count' => $item['word_count'] ?? str_word_count($item['text']),
            'category' => $item['category'] ?? 'general',
            'content_type' => $item['content_type'] ?? 'words',
        ];
    }
}
