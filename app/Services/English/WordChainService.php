<?php

namespace App\Services\English;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class WordChainService
{
    protected WordChainDataService $dataService;
    protected GameScoringService $scoringService;
    protected string $sessionPath;

    public function __construct(WordChainDataService $dataService, GameScoringService $scoringService)
    {
        $this->dataService = $dataService;
        $this->scoringService = $scoringService;
        $this->sessionPath = storage_path('app/game-sessions/word-chain');

        if (!File::exists($this->sessionPath)) {
            File::makeDirectory($this->sessionPath, 0755, true);
        }
    }

    public function startSession(int $levelNumber): array
    {
        $level = $this->dataService->getLevel($levelNumber);

        if (!$level) {
            throw new \Exception('Level not found');
        }

        if (!$this->dataService->isLevelUnlocked($levelNumber)) {
            throw new \Exception('Level is locked');
        }

        $availableWords = $this->dataService->getWordsForLevel($level);
        $sessionId = Str::uuid()->toString();

        $session = [
            'id' => $sessionId,
            'user_id' => Auth::id() ?? 'guest',
            'level_id' => $level['id'],
            'level_number' => $levelNumber,
            'game_mode' => $level['game_mode'],
            'available_words' => $availableWords,
            'chain' => [],
            'used_words' => [],
            'current_letter' => strtoupper($level['starting_letter']),
            'target_chain_length' => $level['target_chain_length'],
            'score' => 0,
            'combo' => 0,
            'max_combo' => 0,
            'hints_used' => 0,
            'skips_used' => 0,
            'powerups_used' => [],
            'time_limit' => $level['time_limit'],
            'time_remaining' => $level['time_limit'],
            'started_at' => now()->toIso8601String(),
            'completed' => false,
            'success' => false,
        ];

        $this->saveSession($session);

        return [
            'session_id' => $sessionId,
            'level' => $level,
            'starting_letter' => $session['current_letter'],
            'target_chain_length' => $level['target_chain_length'],
            'time_limit' => $level['time_limit'],
            'available_words_count' => count($availableWords),
        ];
    }

    public function submitWord(string $sessionId, string $word, float $timeSpent): array
    {
        $session = $this->getSession($sessionId);

        if (!$session) {
            throw new \Exception('Session not found');
        }

        if ($session['completed']) {
            throw new \Exception('Session already completed');
        }

        $word = trim($word);
        $wordLower = strtolower($word);
        $wordUpper = strtoupper($word);

        // Validate word starts with correct letter
        if (strtoupper($word[0]) !== $session['current_letter']) {
            return [
                'valid' => false,
                'error' => 'wrong_letter',
                'message' => "So'z '{$session['current_letter']}' harfi bilan boshlanishi kerak",
                'current_letter' => $session['current_letter'],
            ];
        }

        // Check if word was already used
        if (in_array($wordLower, $session['used_words'])) {
            return [
                'valid' => false,
                'error' => 'already_used',
                'message' => "Bu so'z allaqachon ishlatilgan",
                'current_letter' => $session['current_letter'],
            ];
        }

        // Check if word exists in available words
        if (!$this->dataService->isValidWord($word, $session['available_words'])) {
            return [
                'valid' => false,
                'error' => 'invalid_word',
                'message' => "Bu so'z ro'yxatda mavjud emas",
                'current_letter' => $session['current_letter'],
            ];
        }

        // Word is valid - add to chain
        $translation = $this->dataService->getWordTranslation($word, $session['available_words']);
        $scoringConfig = $this->dataService->getScoringConfig();

        // Calculate points
        $basePoints = $scoringConfig['base_points'];
        $lengthBonus = strlen($word) * $scoringConfig['length_bonus_per_letter'];

        $session['combo']++;
        $comboBonus = $session['combo'] * $scoringConfig['combo_bonus_per_word'];

        // Long word bonus (7+ letters)
        $longWordBonus = strlen($word) >= 7 ? $scoringConfig['long_word_bonus'] : 0;

        // Time bonus for quick answers
        $timeBonus = 0;
        if ($timeSpent < 5) {
            $timeBonus = $scoringConfig['speed_bonus'];
        }

        $totalPoints = $basePoints + $lengthBonus + $comboBonus + $longWordBonus + $timeBonus;

        // Update session
        $session['chain'][] = [
            'word' => $word,
            'translation' => $translation,
            'points' => $totalPoints,
            'time_spent' => $timeSpent,
        ];
        $session['used_words'][] = $wordLower;
        $session['score'] += $totalPoints;
        $session['current_letter'] = strtoupper(substr($word, -1));

        if ($session['combo'] > $session['max_combo']) {
            $session['max_combo'] = $session['combo'];
        }

        // Check if target reached
        $targetReached = count($session['chain']) >= $session['target_chain_length'];

        if ($targetReached) {
            $session['completed'] = true;
            $session['success'] = true;
            $session['completed_at'] = now()->toIso8601String();
        }

        $this->saveSession($session);

        $result = [
            'valid' => true,
            'word' => $word,
            'translation' => $translation,
            'points_earned' => $totalPoints,
            'breakdown' => [
                'base' => $basePoints,
                'length' => $lengthBonus,
                'combo' => $comboBonus,
                'long_word' => $longWordBonus,
                'speed' => $timeBonus,
            ],
            'score' => $session['score'],
            'chain_length' => count($session['chain']),
            'combo' => $session['combo'],
            'next_letter' => $session['current_letter'],
            'target_reached' => $targetReached,
        ];

        if ($targetReached) {
            $result['summary'] = $this->completeSession($sessionId);
        }

        return $result;
    }

    public function getHint(string $sessionId): array
    {
        $session = $this->getSession($sessionId);

        if (!$session) {
            throw new \Exception('Session not found');
        }

        if ($session['completed']) {
            throw new \Exception('Session already completed');
        }

        $currentLetter = $session['current_letter'];
        $usedWords = $session['used_words'];

        // Find available words starting with current letter
        $availableWords = $this->dataService->getWordsByStartingLetter(
            $session['available_words'],
            $currentLetter
        );

        // Filter out used words
        $availableWords = array_filter($availableWords, function ($word) use ($usedWords) {
            return !in_array(strtolower($word['word']), $usedWords);
        });

        if (empty($availableWords)) {
            return [
                'hint' => null,
                'message' => "'{$currentLetter}' harfi bilan boshlanadigan so'z qolmadi",
            ];
        }

        // Get random word for hint
        $hintWord = array_values($availableWords)[array_rand($availableWords)];

        // Show first 2 letters as hint
        $hintText = substr($hintWord['word'], 0, 2) . str_repeat('_', strlen($hintWord['word']) - 2);

        $session['hints_used']++;
        $session['combo'] = 0; // Reset combo when using hint
        $this->saveSession($session);

        return [
            'hint' => $hintText,
            'word_length' => strlen($hintWord['word']),
            'translation' => $hintWord['translation'],
            'hints_used' => $session['hints_used'],
        ];
    }

    public function skipLetter(string $sessionId): array
    {
        $session = $this->getSession($sessionId);

        if (!$session) {
            throw new \Exception('Session not found');
        }

        if ($session['completed']) {
            throw new \Exception('Session already completed');
        }

        $powerups = $this->dataService->getPowerups();
        $skipPowerup = null;
        foreach ($powerups as $p) {
            if ($p['id'] === 'skip_letter') {
                $skipPowerup = $p;
                break;
            }
        }

        $usedCount = $session['powerups_used']['skip_letter'] ?? 0;
        $maxUses = $skipPowerup['uses_per_game'] ?? 2;

        if ($usedCount >= $maxUses) {
            throw new \Exception('Skip limit reached');
        }

        // Find a new random letter that has available words
        $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $attempts = 0;
        $newLetter = $session['current_letter'];

        while ($attempts < 26) {
            $randomLetter = $letters[rand(0, 25)];
            if ($randomLetter !== $session['current_letter']) {
                $wordsWithLetter = $this->dataService->getWordsByStartingLetter(
                    $session['available_words'],
                    $randomLetter
                );
                $unusedWords = array_filter($wordsWithLetter, function ($word) use ($session) {
                    return !in_array(strtolower($word['word']), $session['used_words']);
                });

                if (!empty($unusedWords)) {
                    $newLetter = $randomLetter;
                    break;
                }
            }
            $attempts++;
        }

        $session['current_letter'] = $newLetter;
        $session['skips_used']++;
        $session['powerups_used']['skip_letter'] = $usedCount + 1;
        $session['combo'] = 0; // Reset combo

        $this->saveSession($session);

        return [
            'new_letter' => $newLetter,
            'skips_remaining' => $maxUses - $usedCount - 1,
        ];
    }

    public function usePowerup(string $sessionId, string $powerupId): array
    {
        $session = $this->getSession($sessionId);

        if (!$session) {
            throw new \Exception('Session not found');
        }

        if ($session['completed']) {
            throw new \Exception('Session already completed');
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

        $usedCount = $session['powerups_used'][$powerupId] ?? 0;
        $maxUses = $powerup['uses_per_game'] ?? 1;

        if ($usedCount >= $maxUses) {
            throw new \Exception('Powerup limit reached');
        }

        $session['powerups_used'][$powerupId] = $usedCount + 1;

        $result = [
            'powerup_id' => $powerupId,
            'uses_remaining' => $maxUses - $usedCount - 1,
        ];

        switch ($powerupId) {
            case 'hint':
                return $this->getHint($sessionId);

            case 'skip_letter':
                return $this->skipLetter($sessionId);

            case 'extra_time':
                $timeBonus = $powerup['time_bonus'] ?? 30;
                if ($session['time_remaining'] !== null) {
                    $session['time_remaining'] += $timeBonus;
                }
                $result['time_bonus'] = $timeBonus;
                $result['time_remaining'] = $session['time_remaining'];
                break;

            case 'double_points':
                $session['double_points_active'] = true;
                $session['double_points_words'] = 3;
                $result['words_remaining'] = 3;
                break;

            case 'word_reveal':
                $currentLetter = $session['current_letter'];
                $availableWords = $this->dataService->getWordsByStartingLetter(
                    $session['available_words'],
                    $currentLetter
                );
                $unusedWords = array_filter($availableWords, function ($word) use ($session) {
                    return !in_array(strtolower($word['word']), $session['used_words']);
                });

                if (!empty($unusedWords)) {
                    $randomWord = array_values($unusedWords)[array_rand($unusedWords)];
                    $result['revealed_word'] = $randomWord['word'];
                    $result['translation'] = $randomWord['translation'];
                }
                break;
        }

        $this->saveSession($session);

        return $result;
    }

    public function updateTimeRemaining(string $sessionId, int $timeRemaining): array
    {
        $session = $this->getSession($sessionId);

        if (!$session) {
            throw new \Exception('Session not found');
        }

        $session['time_remaining'] = $timeRemaining;

        if ($timeRemaining <= 0 && !$session['completed']) {
            $session['completed'] = true;
            $session['completed_at'] = now()->toIso8601String();
            $session['time_expired'] = true;
            $session['success'] = count($session['chain']) >= $session['target_chain_length'];
        }

        $this->saveSession($session);

        $result = [
            'time_remaining' => $session['time_remaining'],
            'completed' => $session['completed'],
        ];

        if ($session['completed']) {
            $result['summary'] = $this->generateSummary($session);
            $this->dataService->updateLevelProgress($session['level_id'], $result['summary']);
        }

        return $result;
    }

    public function completeSession(string $sessionId): array
    {
        $session = $this->getSession($sessionId);

        if (!$session) {
            throw new \Exception('Session not found');
        }

        if (!$session['completed']) {
            $session['completed'] = true;
            $session['completed_at'] = now()->toIso8601String();
            $session['success'] = count($session['chain']) >= $session['target_chain_length'];
            $this->saveSession($session);
        }

        $summary = $this->generateSummary($session);

        $this->dataService->updateLevelProgress($session['level_id'], $summary);

        return $summary;
    }

    protected function generateSummary(array $session): array
    {
        $chainLength = count($session['chain']);
        $targetLength = $session['target_chain_length'];
        $completionRate = min(100, round(($chainLength / $targetLength) * 100));

        // Calculate stars based on completion and score
        $stars = 0;
        if ($chainLength >= $targetLength) {
            $stars = 1;
            if ($chainLength >= $targetLength + 3) {
                $stars = 2;
            }
            if ($chainLength >= $targetLength + 5 && $session['hints_used'] === 0) {
                $stars = 3;
            }
        }

        // XP calculation
        $xpEarned = $session['score'];
        if ($stars === 3) {
            $xpEarned += 50;
        }
        if ($session['hints_used'] === 0 && $chainLength >= $targetLength) {
            $xpEarned += 25;
        }

        // Coins calculation
        $coinsEarned = (int)($session['score'] / 10);
        $coinsEarned += $stars * 5;

        // Get words used for tracking
        $wordsUsed = array_map(function ($item) {
            return $item['word'];
        }, $session['chain']);

        return [
            'completed' => true,
            'success' => $session['success'] ?? ($chainLength >= $targetLength),
            'level_number' => $session['level_number'],
            'score' => $session['score'],
            'chain_length' => $chainLength,
            'target_chain_length' => $targetLength,
            'completion_rate' => $completionRate,
            'stars' => $stars,
            'max_combo' => $session['max_combo'],
            'hints_used' => $session['hints_used'],
            'skips_used' => $session['skips_used'],
            'xp_earned' => $xpEarned,
            'coins_earned' => $coinsEarned,
            'time_expired' => $session['time_expired'] ?? false,
            'words_used' => $wordsUsed,
            'chain' => $session['chain'],
        ];
    }

    public function getSessionState(string $sessionId): ?array
    {
        $session = $this->getSession($sessionId);

        if (!$session) {
            return null;
        }

        return [
            'session_id' => $session['id'],
            'current_letter' => $session['current_letter'],
            'chain_length' => count($session['chain']),
            'target_chain_length' => $session['target_chain_length'],
            'score' => $session['score'],
            'combo' => $session['combo'],
            'time_remaining' => $session['time_remaining'],
            'completed' => $session['completed'],
            'chain' => $session['chain'],
            'powerups_used' => $session['powerups_used'],
            'hints_used' => $session['hints_used'],
        ];
    }

    public function getAvailableWordsCount(string $sessionId): array
    {
        $session = $this->getSession($sessionId);

        if (!$session) {
            throw new \Exception('Session not found');
        }

        $currentLetter = $session['current_letter'];
        $availableWords = $this->dataService->getWordsByStartingLetter(
            $session['available_words'],
            $currentLetter
        );

        $unusedWords = array_filter($availableWords, function ($word) use ($session) {
            return !in_array(strtolower($word['word']), $session['used_words']);
        });

        return [
            'current_letter' => $currentLetter,
            'available_count' => count($unusedWords),
            'total_unused' => count($session['available_words']) - count($session['used_words']),
        ];
    }

    protected function getSession(string $sessionId): ?array
    {
        $sessionFile = $this->sessionPath . "/{$sessionId}.json";

        if (File::exists($sessionFile)) {
            return json_decode(File::get($sessionFile), true);
        }

        return null;
    }

    protected function saveSession(array $session): void
    {
        $sessionFile = $this->sessionPath . "/{$session['id']}.json";
        File::put($sessionFile, json_encode($session, JSON_PRETTY_PRINT));
    }
}
