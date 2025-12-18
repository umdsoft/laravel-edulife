<?php

namespace App\Services\English;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class SpellingBeeService
{
    protected SpellingBeeDataService $dataService;
    protected GameScoringService $scoringService;
    protected string $sessionsPath;

    public function __construct(SpellingBeeDataService $dataService, GameScoringService $scoringService)
    {
        $this->dataService = $dataService;
        $this->scoringService = $scoringService;
        $this->sessionsPath = storage_path('app/game_sessions/spelling_bee');

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
            $selectedMode = $gameModes[0] ?? ['id' => 'classic', 'time_per_word' => 30, 'hints_allowed' => 2, 'lives' => 3];
        }

        // Get words for this level
        if ($level['is_final'] ?? false) {
            $words = $this->dataService->getMixedWords($level['words_count'] ?? 20);
        } else {
            $words = $this->dataService->getWordsForLevel($level);
        }

        $sessionId = Str::uuid()->toString();
        $session = [
            'id' => $sessionId,
            'user_id' => Auth::id(),
            'level_number' => $levelNumber,
            'level' => $level,
            'mode' => $selectedMode,
            'words' => $words,
            'current_index' => 0,
            'score' => 0,
            'correct_count' => 0,
            'wrong_count' => 0,
            'streak' => 0,
            'best_streak' => 0,
            'lives' => $selectedMode['lives'] ?? $level['lives'] ?? 3,
            'hints_remaining' => $selectedMode['hints_allowed'] ?? $level['hints_allowed'] ?? 2,
            'time_per_word' => $selectedMode['time_per_word'] ?? $level['time_per_word'] ?? 30,
            'answers' => [],
            'powerups_used' => [],
            'double_points_remaining' => 0,
            'revealed_letters' => [],
            'started_at' => now()->toISOString(),
            'completed' => false,
        ];

        $this->saveSession($session);

        return [
            'session_id' => $sessionId,
            'level' => $level,
            'mode' => $selectedMode,
            'total_words' => count($words),
            'current_index' => 0,
            'current_word' => $this->prepareWordForClient($words[0], $selectedMode),
            'time_per_word' => $session['time_per_word'],
            'lives' => $session['lives'],
            'hints_remaining' => $session['hints_remaining'],
        ];
    }

    /**
     * Prepare word for client (hide the spelling)
     */
    protected function prepareWordForClient(array $word, array $mode): array
    {
        return [
            'audio_text' => $word['word'],
            'definition' => $word['definition'] ?? null,
            'example' => $word['example'] ?? null,
            'pronunciation' => $word['pronunciation'] ?? null,
            'length' => strlen($word['word']),
            'hide_input' => $mode['hide_input'] ?? false,
        ];
    }

    /**
     * Check spelling answer
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
        $currentWord = $session['words'][$currentIndex];
        $level = $session['level'];
        $config = $this->dataService->getConfig();
        $scoring = $config['scoring'] ?? [];

        $correctSpelling = strtolower(trim($currentWord['word']));
        $userSpelling = strtolower(trim($answer));

        $isCorrect = $correctSpelling === $userSpelling;

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
            $timeBonus = max(0, ($scoring['time_bonus_max'] ?? 50) * (1 - $timeSpent / $session['time_per_word']));
            $streakMultiplier = 1 + ($session['streak'] * ($scoring['streak_multiplier'] ?? 0.15));

            // Word length bonus
            $lengthBonus = strlen($correctSpelling) * ($scoring['length_bonus_per_letter'] ?? 10);

            // Category difficulty multiplier
            $category = $level['category'] ?? 'basic';
            $difficultyMultiplier = $scoring['difficulty_multipliers'][$category] ?? 1.0;

            // First try bonus (no hints used for this word)
            $noHintBonus = empty($session['revealed_letters']) ? ($scoring['no_hint_bonus'] ?? 30) : 0;

            $points = (int)(($basePoints + $timeBonus + $lengthBonus + $noHintBonus) * $streakMultiplier * $difficultyMultiplier);

            // Double points powerup
            if ($session['double_points_remaining'] > 0) {
                $points *= 2;
                $session['double_points_remaining']--;
            }

            // XP rewards
            $xpRewards = $config['xp_rewards'] ?? [];
            $xpEarned = $xpRewards['correct_word'] ?? 15;

            if ($session['streak'] >= 5) {
                $xpEarned += $xpRewards['streak_bonus'] ?? 10;
            }

            if ($timeSpent < 5) {
                $xpEarned += $xpRewards['speed_bonus'] ?? 50;
            }

            // Coin rewards
            $coinRewards = $config['coin_rewards'] ?? [];
            $coinsEarned = $coinRewards['correct_word'] ?? 3;

            if ($session['streak'] >= 5) {
                $coinsEarned += $coinRewards['streak_bonus'] ?? 2;
            }

            $session['score'] += $points;
        } else {
            $session['wrong_count']++;
            $session['streak'] = 0;
            $session['lives']--;
        }

        // Record the answer
        $session['answers'][] = [
            'index' => $currentIndex,
            'word' => $currentWord['word'],
            'user_answer' => $answer,
            'is_correct' => $isCorrect,
            'time_spent' => $timeSpent,
            'points_earned' => $points,
            'hints_used' => count($session['revealed_letters']),
        ];

        // Reset revealed letters for next word
        $session['revealed_letters'] = [];

        // Move to next word
        $session['current_index']++;
        $hasMore = $session['current_index'] < count($session['words']) && $session['lives'] > 0;
        $gameOver = $session['lives'] <= 0;

        $this->saveSession($session);

        $response = [
            'is_correct' => $isCorrect,
            'correct_spelling' => $currentWord['word'],
            'points_earned' => $points,
            'xp_earned' => $xpEarned,
            'coins_earned' => $coinsEarned,
            'current_score' => $session['score'],
            'streak' => $session['streak'],
            'lives' => $session['lives'],
            'correct_count' => $session['correct_count'],
            'wrong_count' => $session['wrong_count'],
            'has_more' => $hasMore,
            'game_over' => $gameOver,
            'progress' => [
                'current' => $session['current_index'],
                'total' => count($session['words']),
            ],
        ];

        if ($hasMore) {
            $nextWord = $session['words'][$session['current_index']];
            $response['next_word'] = $this->prepareWordForClient($nextWord, $session['mode']);
            $response['hints_remaining'] = $session['level']['hints_allowed'] ?? 2;
        }

        return $response;
    }

    /**
     * Use a hint to reveal a letter
     */
    public function useHint(string $sessionId): array
    {
        $session = $this->getSession($sessionId);

        if (!$session) {
            throw new \Exception('Session not found');
        }

        if ($session['hints_remaining'] <= 0) {
            throw new \Exception('No hints remaining');
        }

        $currentWord = $session['words'][$session['current_index']];
        $word = $currentWord['word'];
        $revealedPositions = $session['revealed_letters'];

        // Find a position to reveal
        $availablePositions = [];
        for ($i = 0; $i < strlen($word); $i++) {
            if (!in_array($i, $revealedPositions)) {
                $availablePositions[] = $i;
            }
        }

        if (empty($availablePositions)) {
            throw new \Exception('All letters already revealed');
        }

        // Reveal a random position
        $positionToReveal = $availablePositions[array_rand($availablePositions)];
        $session['revealed_letters'][] = $positionToReveal;
        $session['hints_remaining']--;

        $this->saveSession($session);

        // Build hint display
        $hintDisplay = '';
        for ($i = 0; $i < strlen($word); $i++) {
            if (in_array($i, $session['revealed_letters'])) {
                $hintDisplay .= $word[$i];
            } else {
                $hintDisplay .= '_';
            }
        }

        return [
            'hint_display' => $hintDisplay,
            'revealed_position' => $positionToReveal,
            'revealed_letter' => $word[$positionToReveal],
            'hints_remaining' => $session['hints_remaining'],
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

        $usedCount = $session['powerups_used'][$powerupId] ?? 0;
        $maxUses = $powerup['uses_per_game'] ?? 1;

        if ($usedCount >= $maxUses) {
            throw new \Exception('Powerup usage limit reached');
        }

        $result = [
            'powerup_id' => $powerupId,
            'effect' => $powerup['effect'],
        ];

        $currentWord = $session['words'][$session['current_index']];

        switch ($powerup['effect']) {
            case 'reveal_letter':
                $word = $currentWord['word'];
                $revealedPositions = $session['revealed_letters'];
                $availablePositions = [];
                for ($i = 0; $i < strlen($word); $i++) {
                    if (!in_array($i, $revealedPositions)) {
                        $availablePositions[] = $i;
                    }
                }
                if (!empty($availablePositions)) {
                    $pos = $availablePositions[array_rand($availablePositions)];
                    $session['revealed_letters'][] = $pos;
                    $result['revealed_position'] = $pos;
                    $result['revealed_letter'] = $word[$pos];
                }
                break;

            case 'slow_audio':
                $result['playback_speed'] = 0.6;
                break;

            case 'add_time':
                $result['extra_time'] = $powerup['value'] ?? 15;
                break;

            case 'skip':
                $session['current_index']++;
                $hasMore = $session['current_index'] < count($session['words']) && $session['lives'] > 0;
                $result['skipped'] = true;
                $result['has_more'] = $hasMore;

                if ($hasMore) {
                    $nextWord = $session['words'][$session['current_index']];
                    $result['next_word'] = $this->prepareWordForClient($nextWord, $session['mode']);
                }
                $session['revealed_letters'] = [];
                break;

            case 'show_definition':
                $result['definition'] = $currentWord['definition'] ?? 'No definition available';
                $result['example'] = $currentWord['example'] ?? null;
                break;

            case 'double_points':
                $duration = $powerup['duration'] ?? 3;
                $session['double_points_remaining'] = $duration;
                $result['double_points_remaining'] = $duration;
                break;
        }

        $session['powerups_used'][$powerupId] = $usedCount + 1;
        $this->saveSession($session);

        return $result;
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

        $session['completed'] = true;
        $session['completed_at'] = now()->toISOString();

        $config = $this->dataService->getConfig();
        $starThresholds = $config['star_thresholds'] ?? [];
        $xpRewards = $config['xp_rewards'] ?? [];
        $coinRewards = $config['coin_rewards'] ?? [];

        $totalWords = count($session['words']);
        $accuracy = $totalWords > 0 ? round(($session['correct_count'] / $totalWords) * 100) : 0;

        // Calculate stars
        $stars = 0;
        if ($accuracy >= ($starThresholds['three_stars'] ?? 95)) {
            $stars = 3;
        } elseif ($accuracy >= ($starThresholds['two_stars'] ?? 80)) {
            $stars = 2;
        } elseif ($accuracy >= ($starThresholds['one_star'] ?? 60)) {
            $stars = 1;
        }

        // XP calculation
        $xpEarned = $xpRewards['level_complete'] ?? 150;
        if ($stars === 3) {
            $xpEarned += $xpRewards['three_stars'] ?? 200;
        }
        if ($accuracy === 100) {
            $xpEarned += $xpRewards['no_mistakes'] ?? 150;
        }

        // Coins calculation
        $coinsEarned = $coinRewards['level_complete'] ?? 40;
        if ($stars === 3) {
            $coinsEarned += $coinRewards['three_stars'] ?? 75;
        }

        // Update user progress
        $userProgress = $this->dataService->getUserProgress();
        $levelId = $session['level']['id'];
        $isFirstComplete = !isset($userProgress['levels'][$levelId]) || !$userProgress['levels'][$levelId]['completed'];

        if ($isFirstComplete) {
            $xpEarned += $xpRewards['first_time_complete'] ?? 300;
        }

        // Update level progress
        $existingProgress = $userProgress['levels'][$levelId] ?? [];
        $userProgress['levels'][$levelId] = [
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
        $userProgress['total_words_correct'] = ($userProgress['total_words_correct'] ?? 0) + $session['correct_count'];
        $userProgress['total_words_attempted'] = ($userProgress['total_words_attempted'] ?? 0) + count($session['answers']);
        $userProgress['games_played'] = ($userProgress['games_played'] ?? 0) + 1;

        if ($session['best_streak'] > ($userProgress['best_streak'] ?? 0)) {
            $userProgress['best_streak'] = $session['best_streak'];
        }

        // Update longest word spelled
        foreach ($session['answers'] as $answer) {
            if ($answer['is_correct']) {
                $wordLen = strlen($answer['word']);
                if ($wordLen > ($userProgress['longest_word_spelled'] ?? 0)) {
                    $userProgress['longest_word_spelled'] = $wordLen;
                }
            }
        }

        // Daily streak
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

        // Current rank
        $ranks = $this->dataService->getSpellingMasterRanks();
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
            'is_new_best' => $session['score'] === $userProgress['levels'][$levelId]['best_score'],
            'is_first_complete' => $isFirstComplete,
            'new_achievements' => $newAchievements,
            'current_rank' => $currentRank,
            'answers' => $session['answers'],
        ];
    }

    /**
     * Check achievements
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
     * Check if achievement is unlocked
     */
    protected function isAchievementUnlocked(array $achievement, array $userProgress, array $session): bool
    {
        $condition = $achievement['condition'] ?? '';

        if (preg_match('/(\w+)\s*>=\s*(\d+)/', $condition, $matches)) {
            $field = $matches[1];
            $target = (int)$matches[2];

            $current = 0;
            switch ($field) {
                case 'words_spelled':
                case 'total_correct':
                    $current = $userProgress['total_words_correct'] ?? 0;
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
                case 'longest_word':
                    $current = $userProgress['longest_word_spelled'] ?? 0;
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
            'total_words' => count($session['words']),
            'score' => $session['score'],
            'correct_count' => $session['correct_count'],
            'wrong_count' => $session['wrong_count'],
            'streak' => $session['streak'],
            'lives' => $session['lives'],
            'hints_remaining' => $session['hints_remaining'],
            'completed' => $session['completed'],
        ];
    }

    protected function saveSession(array $session): void
    {
        $path = $this->sessionsPath . '/' . $session['id'] . '.json';
        File::put($path, json_encode($session, JSON_PRETTY_PRINT));
    }

    protected function getSession(string $sessionId): ?array
    {
        $path = $this->sessionsPath . '/' . $sessionId . '.json';

        if (!File::exists($path)) {
            return null;
        }

        return json_decode(File::get($path), true);
    }
}
