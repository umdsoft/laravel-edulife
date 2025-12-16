<?php

namespace App\Services\English;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class AnagramSolverService
{
    protected AnagramSolverDataService $dataService;
    protected string $sessionPath;

    public function __construct(AnagramSolverDataService $dataService)
    {
        $this->dataService = $dataService;
        $this->sessionPath = storage_path('app/game-sessions/anagram-solver');

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

        $words = $this->dataService->getWordsForLevel($level);
        $sessionId = Str::uuid()->toString();

        // Prepare words with scrambled versions
        $preparedWords = [];
        foreach ($words as $index => $word) {
            $preparedWords[] = [
                'index' => $index,
                'word' => $word['word'],
                'translation' => $word['translation'],
                'scrambled' => $this->dataService->scrambleWord($word['word']),
                'category' => $word['category'] ?? null,
                'solved' => false,
            ];
        }

        $session = [
            'id' => $sessionId,
            'user_id' => Auth::id() ?? 'guest',
            'level_id' => $level['id'],
            'level_number' => $levelNumber,
            'game_mode' => $level['game_mode'],
            'words' => $preparedWords,
            'current_index' => 0,
            'score' => 0,
            'words_solved' => 0,
            'mistakes' => 0,
            'max_mistakes' => $level['max_mistakes'] ?? null,
            'streak' => 0,
            'best_streak' => 0,
            'hints_used' => 0,
            'skips_used' => 0,
            'reveals_used' => 0,
            'fast_solves' => 0,
            'long_words_solved' => 0,
            'powerups_used' => [],
            'time_limit' => $level['time_limit'],
            'time_remaining' => $level['time_limit'],
            'started_at' => now()->toIso8601String(),
            'completed' => false,
            'answers' => [],
        ];

        $this->saveSession($session);

        return [
            'session_id' => $sessionId,
            'level' => $level,
            'words' => $this->getWordsForClient($preparedWords),
            'word_count' => count($preparedWords),
            'time_limit' => $level['time_limit'],
        ];
    }

    protected function getWordsForClient(array $words): array
    {
        return array_map(function ($word) {
            return [
                'index' => $word['index'],
                'scrambled' => $word['scrambled'],
                'translation' => $word['translation'],
                'length' => strlen($word['word']),
                'category' => $word['category'],
                'solved' => $word['solved'],
            ];
        }, $words);
    }

    public function submitAnswer(string $sessionId, int $wordIndex, string $answer, float $timeSpent): array
    {
        $session = $this->getSession($sessionId);

        if (!$session) {
            throw new \Exception('Session not found');
        }

        if ($session['completed']) {
            throw new \Exception('Session already completed');
        }

        if ($wordIndex < 0 || $wordIndex >= count($session['words'])) {
            throw new \Exception('Invalid word index');
        }

        $word = $session['words'][$wordIndex];

        if ($word['solved']) {
            throw new \Exception('Word already solved');
        }

        $isCorrect = $this->dataService->isValidSolution($answer, $word['word']);
        $scoringConfig = $this->dataService->getScoringConfig();

        $pointsEarned = 0;
        if ($isCorrect) {
            $pointsEarned = $scoringConfig['base_points'];

            // Length bonus
            $pointsEarned += strlen($word['word']) * $scoringConfig['length_bonus_per_letter'];

            // Streak bonus
            $session['streak']++;
            $pointsEarned += $session['streak'] * $scoringConfig['streak_bonus_per_word'];

            // Speed bonus
            if ($timeSpent < $scoringConfig['time_bonus_threshold']) {
                $pointsEarned += $scoringConfig['speed_bonus'];
                $session['fast_solves']++;
            }

            // No hint bonus
            if (!isset($session['answers'][$wordIndex]) || !($session['answers'][$wordIndex]['hint_used'] ?? false)) {
                $pointsEarned += $scoringConfig['no_hint_bonus'];
            }

            // Long word bonus
            if (strlen($word['word']) >= 7) {
                $pointsEarned += $scoringConfig['perfect_solve_bonus'];
                $session['long_words_solved']++;
            }

            $session['words_solved']++;
            $session['words'][$wordIndex]['solved'] = true;

            if ($session['streak'] > $session['best_streak']) {
                $session['best_streak'] = $session['streak'];
            }
        } else {
            $session['streak'] = 0;
            $session['mistakes']++;

            // Check survival mode
            if ($session['max_mistakes'] !== null && $session['mistakes'] >= $session['max_mistakes']) {
                $session['completed'] = true;
                $session['completed_at'] = now()->toIso8601String();
                $session['game_over_reason'] = 'max_mistakes';
            }
        }

        $session['score'] += $pointsEarned;
        $session['answers'][$wordIndex] = [
            'answer' => $answer,
            'correct' => $isCorrect,
            'time_spent' => $timeSpent,
            'points' => $pointsEarned,
        ];

        // Check if all words are solved
        $allSolved = true;
        foreach ($session['words'] as $w) {
            if (!$w['solved']) {
                $allSolved = false;
                break;
            }
        }

        if ($allSolved) {
            $session['completed'] = true;
            $session['completed_at'] = now()->toIso8601String();
        }

        $this->saveSession($session);

        $result = [
            'correct' => $isCorrect,
            'correct_answer' => $word['word'],
            'translation' => $word['translation'],
            'points_earned' => $pointsEarned,
            'score' => $session['score'],
            'streak' => $session['streak'],
            'words_solved' => $session['words_solved'],
            'mistakes' => $session['mistakes'],
            'is_complete' => $session['completed'],
        ];

        if ($session['completed']) {
            $result['summary'] = $this->completeSession($sessionId);
        }

        return $result;
    }

    public function getHint(string $sessionId, int $wordIndex): array
    {
        $session = $this->getSession($sessionId);

        if (!$session) {
            throw new \Exception('Session not found');
        }

        if ($session['completed']) {
            throw new \Exception('Session already completed');
        }

        $powerups = $this->dataService->getPowerups();
        $hintPowerup = null;
        foreach ($powerups as $p) {
            if ($p['id'] === 'hint') {
                $hintPowerup = $p;
                break;
            }
        }

        $usedCount = $session['powerups_used']['hint'] ?? 0;
        $maxUses = $hintPowerup['uses_per_game'] ?? 3;

        if ($usedCount >= $maxUses) {
            throw new \Exception('Hint limit reached');
        }

        $word = $session['words'][$wordIndex];
        if ($word['solved']) {
            throw new \Exception('Word already solved');
        }

        // Reveal one random letter position
        $wordLetters = str_split($word['word']);
        $revealedPositions = $session['answers'][$wordIndex]['revealed_positions'] ?? [];
        $availablePositions = [];

        for ($i = 0; $i < count($wordLetters); $i++) {
            if (!in_array($i, $revealedPositions)) {
                $availablePositions[] = $i;
            }
        }

        if (empty($availablePositions)) {
            return [
                'hint' => $word['word'],
                'position' => null,
                'letter' => null,
                'hints_remaining' => $maxUses - $usedCount,
            ];
        }

        $randomPosition = $availablePositions[array_rand($availablePositions)];
        $revealedLetter = $wordLetters[$randomPosition];

        $session['hints_used']++;
        $session['powerups_used']['hint'] = $usedCount + 1;

        if (!isset($session['answers'][$wordIndex])) {
            $session['answers'][$wordIndex] = [];
        }
        $session['answers'][$wordIndex]['hint_used'] = true;
        $session['answers'][$wordIndex]['revealed_positions'] = array_merge($revealedPositions, [$randomPosition]);

        $this->saveSession($session);

        return [
            'position' => $randomPosition,
            'letter' => strtoupper($revealedLetter),
            'hints_remaining' => $maxUses - $usedCount - 1,
        ];
    }

    public function shuffleWord(string $sessionId, int $wordIndex): array
    {
        $session = $this->getSession($sessionId);

        if (!$session) {
            throw new \Exception('Session not found');
        }

        if ($session['completed']) {
            throw new \Exception('Session already completed');
        }

        $powerups = $this->dataService->getPowerups();
        $shufflePowerup = null;
        foreach ($powerups as $p) {
            if ($p['id'] === 'shuffle') {
                $shufflePowerup = $p;
                break;
            }
        }

        $usedCount = $session['powerups_used']['shuffle'] ?? 0;
        $maxUses = $shufflePowerup['uses_per_game'] ?? 3;

        if ($usedCount >= $maxUses) {
            throw new \Exception('Shuffle limit reached');
        }

        $word = $session['words'][$wordIndex];
        if ($word['solved']) {
            throw new \Exception('Word already solved');
        }

        // Reshuffle the word
        $newScrambled = $this->dataService->scrambleWord($word['word']);
        $session['words'][$wordIndex]['scrambled'] = $newScrambled;
        $session['powerups_used']['shuffle'] = $usedCount + 1;

        $this->saveSession($session);

        return [
            'scrambled' => $newScrambled,
            'shuffles_remaining' => $maxUses - $usedCount - 1,
        ];
    }

    public function skipWord(string $sessionId, int $wordIndex): array
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
            if ($p['id'] === 'skip') {
                $skipPowerup = $p;
                break;
            }
        }

        $usedCount = $session['powerups_used']['skip'] ?? 0;
        $maxUses = $skipPowerup['uses_per_game'] ?? 2;

        if ($usedCount >= $maxUses) {
            throw new \Exception('Skip limit reached');
        }

        $word = $session['words'][$wordIndex];
        if ($word['solved']) {
            throw new \Exception('Word already solved');
        }

        $session['skips_used']++;
        $session['powerups_used']['skip'] = $usedCount + 1;
        $session['words'][$wordIndex]['solved'] = true;
        $session['words'][$wordIndex]['skipped'] = true;
        $session['streak'] = 0;

        // Check if all words are solved/skipped
        $allDone = true;
        foreach ($session['words'] as $w) {
            if (!$w['solved']) {
                $allDone = false;
                break;
            }
        }

        if ($allDone) {
            $session['completed'] = true;
            $session['completed_at'] = now()->toIso8601String();
        }

        $this->saveSession($session);

        $result = [
            'skipped' => true,
            'correct_answer' => $word['word'],
            'translation' => $word['translation'],
            'skips_remaining' => $maxUses - $usedCount - 1,
            'is_complete' => $session['completed'],
        ];

        if ($session['completed']) {
            $result['summary'] = $this->completeSession($sessionId);
        }

        return $result;
    }

    public function revealWord(string $sessionId, int $wordIndex): array
    {
        $session = $this->getSession($sessionId);

        if (!$session) {
            throw new \Exception('Session not found');
        }

        if ($session['completed']) {
            throw new \Exception('Session already completed');
        }

        $powerups = $this->dataService->getPowerups();
        $revealPowerup = null;
        foreach ($powerups as $p) {
            if ($p['id'] === 'reveal_word') {
                $revealPowerup = $p;
                break;
            }
        }

        $usedCount = $session['powerups_used']['reveal_word'] ?? 0;
        $maxUses = $revealPowerup['uses_per_game'] ?? 1;

        if ($usedCount >= $maxUses) {
            throw new \Exception('Reveal limit reached');
        }

        $word = $session['words'][$wordIndex];
        if ($word['solved']) {
            throw new \Exception('Word already solved');
        }

        $session['reveals_used']++;
        $session['powerups_used']['reveal_word'] = $usedCount + 1;

        $this->saveSession($session);

        return [
            'word' => $word['word'],
            'translation' => $word['translation'],
            'reveals_remaining' => $maxUses - $usedCount - 1,
        ];
    }

    public function usePowerup(string $sessionId, string $powerupId, int $wordIndex = null): array
    {
        switch ($powerupId) {
            case 'hint':
                return $this->getHint($sessionId, $wordIndex);
            case 'shuffle':
                return $this->shuffleWord($sessionId, $wordIndex);
            case 'skip':
                return $this->skipWord($sessionId, $wordIndex);
            case 'reveal_word':
                return $this->revealWord($sessionId, $wordIndex);
            case 'extra_time':
                return $this->addExtraTime($sessionId);
            default:
                throw new \Exception('Unknown powerup');
        }
    }

    public function addExtraTime(string $sessionId): array
    {
        $session = $this->getSession($sessionId);

        if (!$session) {
            throw new \Exception('Session not found');
        }

        $powerups = $this->dataService->getPowerups();
        $timePowerup = null;
        foreach ($powerups as $p) {
            if ($p['id'] === 'extra_time') {
                $timePowerup = $p;
                break;
            }
        }

        $usedCount = $session['powerups_used']['extra_time'] ?? 0;
        $maxUses = $timePowerup['uses_per_game'] ?? 2;

        if ($usedCount >= $maxUses) {
            throw new \Exception('Extra time limit reached');
        }

        $timeBonus = $timePowerup['time_bonus'] ?? 15;
        if ($session['time_remaining'] !== null) {
            $session['time_remaining'] += $timeBonus;
        }

        $session['powerups_used']['extra_time'] = $usedCount + 1;
        $this->saveSession($session);

        return [
            'time_bonus' => $timeBonus,
            'time_remaining' => $session['time_remaining'],
            'extra_time_remaining' => $maxUses - $usedCount - 1,
        ];
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
            $this->saveSession($session);
        }

        $summary = $this->generateSummary($session);

        $this->dataService->updateLevelProgress($session['level_id'], $summary);

        return $summary;
    }

    protected function generateSummary(array $session): array
    {
        $totalWords = count($session['words']);
        $wordsSolved = $session['words_solved'];
        $accuracy = $totalWords > 0 ? round(($wordsSolved / $totalWords) * 100) : 0;

        // Calculate stars
        $stars = 0;
        if ($accuracy >= 60) $stars = 1;
        if ($accuracy >= 80) $stars = 2;
        if ($accuracy >= 95) $stars = 3;

        // XP calculation
        $xpEarned = $session['score'];
        if ($stars === 3) {
            $xpEarned += 50;
        }
        if ($session['hints_used'] === 0 && $accuracy >= 80) {
            $xpEarned += 25;
        }

        // Coins calculation
        $coinsEarned = (int)($session['score'] / 10);
        $coinsEarned += $stars * 5;

        return [
            'completed' => true,
            'level_number' => $session['level_number'],
            'score' => $session['score'],
            'words_solved' => $wordsSolved,
            'total_words' => $totalWords,
            'accuracy' => $accuracy,
            'stars' => $stars,
            'best_streak' => $session['best_streak'],
            'mistakes' => $session['mistakes'],
            'hints_used' => $session['hints_used'],
            'skips_used' => $session['skips_used'],
            'fast_solves' => $session['fast_solves'],
            'long_words_solved' => $session['long_words_solved'],
            'xp_earned' => $xpEarned,
            'coins_earned' => $coinsEarned,
            'time_expired' => $session['time_expired'] ?? false,
            'game_over_reason' => $session['game_over_reason'] ?? null,
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
            'current_index' => $session['current_index'],
            'score' => $session['score'],
            'words_solved' => $session['words_solved'],
            'mistakes' => $session['mistakes'],
            'streak' => $session['streak'],
            'time_remaining' => $session['time_remaining'],
            'completed' => $session['completed'],
            'words' => $this->getWordsForClient($session['words']),
            'powerups_used' => $session['powerups_used'],
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
