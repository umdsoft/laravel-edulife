<?php

namespace App\Services\English;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class HangmanService
{
    protected HangmanDataService $dataService;

    public function __construct(HangmanDataService $dataService)
    {
        $this->dataService = $dataService;
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

        $words = $this->dataService->getWordsForLevel($levelNumber);
        if (empty($words)) {
            throw new \Exception('No words available for this level');
        }

        $config = $this->dataService->getConfig();
        $gameModes = $config['game_modes'] ?? [];
        $modeConfig = null;

        foreach ($gameModes as $gm) {
            if ($gm['id'] === $mode) {
                $modeConfig = $gm;
                break;
            }
        }

        $maxWrong = $modeConfig['max_wrong'] ?? $level['max_wrong'] ?? 6;

        $sessionId = Str::uuid()->toString();
        $session = [
            'id' => $sessionId,
            'user_id' => Auth::id(),
            'level' => $levelNumber,
            'mode' => $mode,
            'mode_config' => $modeConfig,
            'words' => $words,
            'current_word_index' => 0,
            'current_word' => $words[0],
            'guessed_letters' => [],
            'wrong_guesses' => 0,
            'max_wrong' => $maxWrong,
            'score' => 0,
            'words_solved' => 0,
            'words_failed' => 0,
            'streak' => 0,
            'best_streak' => 0,
            'perfect_words' => 0,
            'used_powerups' => [],
            'hints_used' => 0,
            'started_at' => now()->toIso8601String(),
            'word_start_time' => now()->toIso8601String(),
            'category_stats' => [],
            'time_limit' => $modeConfig['time_limit'] ?? null,
            'time_remaining' => $modeConfig['time_limit'] ?? null,
        ];

        $this->saveSession($session);

        return [
            'session_id' => $sessionId,
            'level' => $level,
            'mode' => $mode,
            'mode_config' => $modeConfig,
            'total_words' => count($words),
            'current_word' => $this->formatWordState($session),
            'max_wrong' => $maxWrong,
            'time_limit' => $session['time_limit'],
        ];
    }

    /**
     * Format word state for frontend (hide unguessed letters)
     */
    protected function formatWordState(array $session): array
    {
        $word = strtoupper($session['current_word']['word']);
        $guessed = array_map('strtoupper', $session['guessed_letters']);

        $display = [];
        for ($i = 0; $i < strlen($word); $i++) {
            $char = $word[$i];
            $display[] = in_array($char, $guessed) ? $char : '_';
        }

        return [
            'display' => $display,
            'length' => strlen($word),
            'guessed_letters' => $guessed,
            'wrong_guesses' => $session['wrong_guesses'],
            'max_wrong' => $session['max_wrong'],
            'lives_remaining' => $session['max_wrong'] - $session['wrong_guesses'],
            'category' => $session['current_word']['category'] ?? null,
            'word_index' => $session['current_word_index'],
            'total_words' => count($session['words']),
        ];
    }

    /**
     * Guess a letter
     */
    public function guessLetter(string $sessionId, string $letter): array
    {
        $session = $this->getSession($sessionId);
        if (!$session) {
            throw new \Exception('Session not found');
        }

        $letter = strtoupper($letter);
        $word = strtoupper($session['current_word']['word']);

        // Check if already guessed
        if (in_array($letter, array_map('strtoupper', $session['guessed_letters']))) {
            return [
                'already_guessed' => true,
                'current_word' => $this->formatWordState($session),
            ];
        }

        $session['guessed_letters'][] = $letter;
        $isCorrect = str_contains($word, $letter);
        $config = $this->dataService->getConfig();
        $scoring = $config['scoring'] ?? [];

        $points = 0;
        if ($isCorrect) {
            // Count occurrences
            $occurrences = substr_count($word, $letter);
            $points = ($scoring['letter_guess_bonus'] ?? 10) * $occurrences;
        } else {
            $session['wrong_guesses']++;
        }

        $session['score'] += $points;

        // Check if word is complete
        $allLettersGuessed = $this->isWordComplete($word, $session['guessed_letters']);
        $isGameOver = $session['wrong_guesses'] >= $session['max_wrong'];

        $response = [
            'is_correct' => $isCorrect,
            'letter' => $letter,
            'points_earned' => $points,
            'current_word' => $this->formatWordState($session),
            'score' => $session['score'],
        ];

        if ($allLettersGuessed) {
            $response = array_merge($response, $this->handleWordSolved($session, $scoring));
        } elseif ($isGameOver) {
            $response = array_merge($response, $this->handleWordFailed($session));
        }

        $this->saveSession($session);
        return $response;
    }

    /**
     * Check if all letters are guessed
     */
    protected function isWordComplete(string $word, array $guessedLetters): bool
    {
        $guessed = array_map('strtoupper', $guessedLetters);
        for ($i = 0; $i < strlen($word); $i++) {
            if (!in_array($word[$i], $guessed)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Handle word solved
     */
    protected function handleWordSolved(array &$session, array $scoring): array
    {
        $config = $this->dataService->getConfig();
        $isPerfect = $session['wrong_guesses'] === 0;
        $word = $session['current_word']['word'];

        // Base points for solving
        $points = $scoring['base_points_per_word'] ?? 100;

        // Lives remaining bonus
        $livesRemaining = $session['max_wrong'] - $session['wrong_guesses'];
        $points += $livesRemaining * ($scoring['lives_remaining_multiplier'] ?? 20);

        // Perfect word bonus
        if ($isPerfect) {
            $points += $scoring['no_wrong_guess_bonus'] ?? 50;
            $session['perfect_words']++;
        }

        // Streak bonus
        $session['streak']++;
        if ($session['streak'] > $session['best_streak']) {
            $session['best_streak'] = $session['streak'];
        }

        $streakMultipliers = $scoring['streak_multipliers'] ?? [];
        $multiplier = 1.0;
        foreach ($streakMultipliers as $threshold => $mult) {
            if ($session['streak'] >= (int)$threshold) {
                $multiplier = $mult;
            }
        }
        $points = (int)($points * $multiplier);

        // Mode bonus
        $modeMultiplier = $session['mode_config']['bonus_multiplier'] ?? 1.0;
        $points = (int)($points * $modeMultiplier);

        $session['score'] += $points;
        $session['words_solved']++;

        // Track category stats
        $category = $session['current_word']['category'] ?? 'unknown';
        if (!isset($session['category_stats'][$category])) {
            $session['category_stats'][$category] = ['solved' => 0, 'failed' => 0];
        }
        $session['category_stats'][$category]['solved']++;

        $result = [
            'word_solved' => true,
            'word' => $word,
            'is_perfect' => $isPerfect,
            'points_earned' => $points,
            'streak' => $session['streak'],
            'words_solved' => $session['words_solved'],
            'score' => $session['score'],
        ];

        // Check if more words
        if ($session['current_word_index'] < count($session['words']) - 1) {
            $result['has_next_word'] = true;
        } else {
            $result['level_complete'] = true;
        }

        return $result;
    }

    /**
     * Handle word failed
     */
    protected function handleWordFailed(array &$session): array
    {
        $word = $session['current_word']['word'];

        $session['words_failed']++;
        $session['streak'] = 0;

        // Track category stats
        $category = $session['current_word']['category'] ?? 'unknown';
        if (!isset($session['category_stats'][$category])) {
            $session['category_stats'][$category] = ['solved' => 0, 'failed' => 0];
        }
        $session['category_stats'][$category]['failed']++;

        $result = [
            'word_failed' => true,
            'word' => $word,
            'hint' => $session['current_word']['hint'] ?? null,
            'words_failed' => $session['words_failed'],
        ];

        // Check if more words
        if ($session['current_word_index'] < count($session['words']) - 1) {
            $result['has_next_word'] = true;
        } else {
            $result['level_complete'] = true;
        }

        return $result;
    }

    /**
     * Get next word
     */
    public function nextWord(string $sessionId): array
    {
        $session = $this->getSession($sessionId);
        if (!$session) {
            throw new \Exception('Session not found');
        }

        $session['current_word_index']++;
        if ($session['current_word_index'] >= count($session['words'])) {
            throw new \Exception('No more words');
        }

        $session['current_word'] = $session['words'][$session['current_word_index']];
        $session['guessed_letters'] = [];
        $session['wrong_guesses'] = 0;
        $session['word_start_time'] = now()->toIso8601String();

        $this->saveSession($session);

        return [
            'current_word' => $this->formatWordState($session),
            'word_index' => $session['current_word_index'],
            'total_words' => count($session['words']),
            'score' => $session['score'],
            'streak' => $session['streak'],
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
        $usedCount = count(array_filter($session['used_powerups'], fn($id) => $id === $powerupId));
        $maxUses = $powerup['uses_per_game'] ?? 1;

        if ($usedCount >= $maxUses) {
            throw new \Exception('Powerup limit reached');
        }

        $session['used_powerups'][] = $powerupId;
        $result = ['powerup_used' => $powerupId];

        $word = strtoupper($session['current_word']['word']);
        $guessed = array_map('strtoupper', $session['guessed_letters']);

        switch ($powerupId) {
            case 'reveal_letter':
                // Find an unguessed letter
                $unguessed = [];
                for ($i = 0; $i < strlen($word); $i++) {
                    if (!in_array($word[$i], $guessed)) {
                        $unguessed[] = $word[$i];
                    }
                }
                if (!empty($unguessed)) {
                    $revealLetter = $unguessed[array_rand($unguessed)];
                    $session['guessed_letters'][] = $revealLetter;
                    $result['revealed_letter'] = $revealLetter;
                }
                break;

            case 'reveal_vowels':
                $vowels = ['A', 'E', 'I', 'O', 'U'];
                foreach ($vowels as $vowel) {
                    if (str_contains($word, $vowel) && !in_array($vowel, $guessed)) {
                        $session['guessed_letters'][] = $vowel;
                    }
                }
                $result['revealed_vowels'] = true;
                break;

            case 'extra_life':
                $session['max_wrong']++;
                $result['max_wrong'] = $session['max_wrong'];
                break;

            case 'hint':
                $session['hints_used']++;
                $result['hint'] = $session['current_word']['hint'] ?? 'No hint available';
                $result['hint_uz'] = $session['current_word']['hint_uz'] ?? null;
                break;

            case 'category_hint':
                $result['category'] = $session['current_word']['category'] ?? 'unknown';
                break;

            case 'extra_time':
                if ($session['time_remaining'] !== null) {
                    $session['time_remaining'] += 30;
                    $result['time_remaining'] = $session['time_remaining'];
                }
                break;
        }

        // Check if word is now complete
        if (isset($result['revealed_letter']) || isset($result['revealed_vowels'])) {
            $allLettersGuessed = $this->isWordComplete($word, $session['guessed_letters']);
            if ($allLettersGuessed) {
                $config = $this->dataService->getConfig();
                $scoring = $config['scoring'] ?? [];
                $result = array_merge($result, $this->handleWordSolved($session, $scoring));
            }
        }

        $result['current_word'] = $this->formatWordState($session);
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

        $config = $this->dataService->getConfig();
        $level = $this->dataService->getLevel($session['level']);

        $totalWords = count($session['words']);
        $wordsSolved = $session['words_solved'];

        // Calculate stars based on words solved
        $starThresholds = $level['star_thresholds'] ?? ['one' => 3, 'two' => 4, 'three' => 5];
        $stars = 0;
        if ($wordsSolved >= $starThresholds['three']) {
            $stars = 3;
        } elseif ($wordsSolved >= $starThresholds['two']) {
            $stars = 2;
        } elseif ($wordsSolved >= $starThresholds['one']) {
            $stars = 1;
        }

        // Calculate XP
        $xpRewards = $config['xp_rewards'] ?? [];
        $xpEarned = ($wordsSolved * ($xpRewards['per_word_solved'] ?? 15));
        $xpEarned += $xpRewards['completion_bonus'] ?? 50;
        $xpEarned += ($session['perfect_words'] * ($xpRewards['perfect_word_bonus'] ?? 25));

        if ($session['best_streak'] >= 3) {
            $xpEarned += $xpRewards['streak_3_bonus'] ?? 20;
        }
        if ($session['best_streak'] >= 5) {
            $xpEarned += $xpRewards['streak_5_bonus'] ?? 40;
        }
        if ($session['hints_used'] === 0 && $wordsSolved > 0) {
            $xpEarned += $xpRewards['no_hints_bonus'] ?? 30;
        }

        // Calculate coins
        $coinRewards = $config['coin_rewards'] ?? [];
        $coinsEarned = ($wordsSolved * ($coinRewards['per_word_solved'] ?? 5));
        $coinsEarned += $coinRewards['completion_bonus'] ?? 15;
        $coinsEarned += ($session['perfect_words'] * ($coinRewards['perfect_word_bonus'] ?? 10));

        if ($session['best_streak'] >= 5) {
            $coinsEarned += $coinRewards['streak_5_bonus'] ?? 20;
        }

        // Update user progress
        $progress = $this->dataService->getUserProgress();

        // Update level progress
        if (!isset($progress['levels'][$session['level']])) {
            $progress['levels'][$session['level']] = [
                'completed' => false,
                'stars' => 0,
                'best_score' => 0,
                'words_solved' => 0,
                'attempts' => 0,
            ];
        }

        $levelProgress = &$progress['levels'][$session['level']];
        $levelProgress['completed'] = $stars > 0;
        $levelProgress['attempts']++;

        if ($session['score'] > $levelProgress['best_score']) {
            $levelProgress['best_score'] = $session['score'];
        }
        if ($wordsSolved > $levelProgress['words_solved']) {
            $levelProgress['words_solved'] = $wordsSolved;
        }
        if ($stars > $levelProgress['stars']) {
            $levelProgress['stars'] = $stars;
        }

        // Update global stats
        $progress['stats']['games_played'] = ($progress['stats']['games_played'] ?? 0) + 1;
        $progress['stats']['total_words_solved'] = ($progress['stats']['total_words_solved'] ?? 0) + $wordsSolved;
        $progress['stats']['total_words_failed'] = ($progress['stats']['total_words_failed'] ?? 0) + $session['words_failed'];
        $progress['stats']['perfect_words'] = ($progress['stats']['perfect_words'] ?? 0) + $session['perfect_words'];

        if ($session['best_streak'] > ($progress['stats']['best_streak'] ?? 0)) {
            $progress['stats']['best_streak'] = $session['best_streak'];
        }

        $progress['stats']['total_xp'] = ($progress['stats']['total_xp'] ?? 0) + $xpEarned;
        $progress['stats']['total_coins'] = ($progress['stats']['total_coins'] ?? 0) + $coinsEarned;

        // Update category stats
        foreach ($session['category_stats'] as $category => $catStats) {
            if (!isset($progress['category_stats'][$category])) {
                $progress['category_stats'][$category] = ['solved' => 0, 'failed' => 0];
            }
            $progress['category_stats'][$category]['solved'] += $catStats['solved'];
            $progress['category_stats'][$category]['failed'] += $catStats['failed'];
        }

        // Check achievements
        $newAchievements = $this->checkAchievements($progress, $session);
        foreach ($newAchievements as $achievement) {
            if (!in_array($achievement['id'], $progress['achievements'] ?? [])) {
                $progress['achievements'][] = $achievement['id'];
                $xpEarned += $achievement['xp_reward'] ?? 0;
                $coinsEarned += $achievement['coin_reward'] ?? 0;
            }
        }

        $this->dataService->saveUserProgress($progress);
        $this->deleteSession($sessionId);

        return [
            'total_score' => $session['score'],
            'words_solved' => $wordsSolved,
            'words_failed' => $session['words_failed'],
            'total_words' => $totalWords,
            'perfect_words' => $session['perfect_words'],
            'best_streak' => $session['best_streak'],
            'stars' => $stars,
            'xp_earned' => $xpEarned,
            'coins_earned' => $coinsEarned,
            'new_achievements' => $newAchievements,
            'level_complete' => true,
        ];
    }

    /**
     * Check achievements
     */
    protected function checkAchievements(array $progress, array $session): array
    {
        $achievements = $this->dataService->getAchievements();
        $unlockedIds = $progress['achievements'] ?? [];
        $newAchievements = [];

        foreach ($achievements as $achievement) {
            if (in_array($achievement['id'], $unlockedIds)) {
                continue;
            }

            if ($this->isAchievementUnlocked($achievement, $progress, $session)) {
                $newAchievements[] = $achievement;
            }
        }

        return $newAchievements;
    }

    /**
     * Check if achievement is unlocked
     */
    protected function isAchievementUnlocked(array $achievement, array $progress, array $session): bool
    {
        $condition = $achievement['condition'] ?? [];
        $type = $condition['type'] ?? '';
        $value = $condition['value'] ?? 1;

        switch ($type) {
            case 'words_solved':
                return ($progress['stats']['total_words_solved'] ?? 0) >= $value;

            case 'perfect_words':
                return ($progress['stats']['perfect_words'] ?? 0) >= $value;

            case 'streak':
                return ($progress['stats']['best_streak'] ?? 0) >= $value;

            case 'category_words':
                $category = $condition['category'] ?? '';
                return ($progress['category_stats'][$category]['solved'] ?? 0) >= $value;

            case 'all_categories':
                $categories = ['animals', 'food', 'countries', 'professions', 'nature', 'household', 'verbs', 'adjectives'];
                foreach ($categories as $cat) {
                    if (($progress['category_stats'][$cat]['solved'] ?? 0) < 1) {
                        return false;
                    }
                }
                return true;

            case 'no_hints_level':
                return $session['hints_used'] === 0 && $session['words_solved'] > 0;

            default:
                return false;
        }
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
            'session_id' => $session['id'],
            'level' => $session['level'],
            'mode' => $session['mode'],
            'score' => $session['score'],
            'words_solved' => $session['words_solved'],
            'words_failed' => $session['words_failed'],
            'streak' => $session['streak'],
            'current_word' => $this->formatWordState($session),
            'word_index' => $session['current_word_index'],
            'total_words' => count($session['words']),
        ];
    }

    /**
     * Session management
     */
    protected function getSession(string $sessionId): ?array
    {
        $path = storage_path("app/game_sessions/hangman/{$sessionId}.json");
        if (file_exists($path)) {
            return json_decode(file_get_contents($path), true);
        }
        return null;
    }

    protected function saveSession(array $session): void
    {
        $dir = storage_path('app/game_sessions/hangman');
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $path = "{$dir}/{$session['id']}.json";
        file_put_contents($path, json_encode($session));
    }

    protected function deleteSession(string $sessionId): void
    {
        $path = storage_path("app/game_sessions/hangman/{$sessionId}.json");
        if (file_exists($path)) {
            unlink($path);
        }
    }
}
