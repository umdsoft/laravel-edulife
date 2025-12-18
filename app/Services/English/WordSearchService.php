<?php

namespace App\Services\English;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class WordSearchService
{
    protected WordSearchDataService $dataService;
    protected GameScoringService $scoringService;
    protected string $sessionPath;

    public function __construct(WordSearchDataService $dataService, GameScoringService $scoringService)
    {
        $this->dataService = $dataService;
        $this->scoringService = $scoringService;
        $this->sessionPath = storage_path('app/game-sessions/word-search');

        if (!File::exists($this->sessionPath)) {
            File::makeDirectory($this->sessionPath, 0755, true);
        }
    }

    public function startSession(int $levelNumber, string $mode = 'classic'): array
    {
        $level = $this->dataService->getLevel($levelNumber);

        if (!$level) {
            throw new \Exception('Level not found');
        }

        if (!$this->dataService->isLevelUnlocked($levelNumber)) {
            throw new \Exception('Level is locked');
        }

        $gridSize = $this->dataService->getGridSize($level['grid_size']);
        $words = $this->selectWordsForLevel($level);
        $grid = $this->generateGrid($gridSize['rows'], $gridSize['cols'], $words, $level['directions']);

        $sessionId = Str::uuid()->toString();

        $session = [
            'id' => $sessionId,
            'user_id' => Auth::id() ?? 'guest',
            'level_id' => $level['id'],
            'level_number' => $levelNumber,
            'mode' => $mode,
            'grid' => $grid['grid'],
            'words' => $grid['placed_words'],
            'word_positions' => $grid['word_positions'],
            'rows' => $gridSize['rows'],
            'cols' => $gridSize['cols'],
            'found_words' => [],
            'score' => 0,
            'hints_used' => 0,
            'time_limit' => $level['time_limit'],
            'time_remaining' => $level['time_limit'],
            'started_at' => now()->toIso8601String(),
            'completed' => false,
            'powerups_used' => [],
        ];

        $this->saveSession($session);

        return [
            'session_id' => $sessionId,
            'grid' => $grid['grid'],
            'words' => array_map(function ($w) {
                return ['word' => $w['word'], 'translation' => $w['translation'], 'found' => false];
            }, $grid['placed_words']),
            'rows' => $gridSize['rows'],
            'cols' => $gridSize['cols'],
            'time_limit' => $level['time_limit'],
            'level' => $level,
        ];
    }

    protected function selectWordsForLevel(array $level): array
    {
        $allWords = $this->dataService->getWordsByCategories($level['categories']);
        shuffle($allWords);

        $wordCount = $level['word_count'] ?? 8;
        return array_slice($allWords, 0, $wordCount);
    }

    protected function generateGrid(int $rows, int $cols, array $words, array $allowedDirections): array
    {
        $grid = array_fill(0, $rows, array_fill(0, $cols, ''));
        $placedWords = [];
        $wordPositions = [];

        $allDirections = $this->dataService->getDirections();
        $directions = array_filter($allDirections, fn($d) => in_array($d['id'], $allowedDirections));

        usort($words, fn($a, $b) => strlen($b['word']) - strlen($a['word']));

        foreach ($words as $wordData) {
            $word = strtoupper($wordData['word']);
            $placed = false;

            for ($attempt = 0; $attempt < 100 && !$placed; $attempt++) {
                $direction = $directions[array_rand($directions)];
                $startRow = rand(0, $rows - 1);
                $startCol = rand(0, $cols - 1);

                if ($this->canPlaceWord($grid, $word, $startRow, $startCol, $direction, $rows, $cols)) {
                    $positions = $this->placeWord($grid, $word, $startRow, $startCol, $direction);
                    $placedWords[] = $wordData;
                    $wordPositions[$word] = [
                        'start' => ['row' => $startRow, 'col' => $startCol],
                        'direction' => $direction['id'],
                        'positions' => $positions,
                    ];
                    $placed = true;
                }
            }
        }

        // Fill empty cells with random letters
        for ($r = 0; $r < $rows; $r++) {
            for ($c = 0; $c < $cols; $c++) {
                if ($grid[$r][$c] === '') {
                    $grid[$r][$c] = chr(rand(65, 90));
                }
            }
        }

        return [
            'grid' => $grid,
            'placed_words' => $placedWords,
            'word_positions' => $wordPositions,
        ];
    }

    protected function canPlaceWord(array &$grid, string $word, int $row, int $col, array $direction, int $rows, int $cols): bool
    {
        $length = strlen($word);
        $dx = $direction['dx'];
        $dy = $direction['dy'];

        $endRow = $row + ($length - 1) * $dy;
        $endCol = $col + ($length - 1) * $dx;

        if ($endRow < 0 || $endRow >= $rows || $endCol < 0 || $endCol >= $cols) {
            return false;
        }

        for ($i = 0; $i < $length; $i++) {
            $r = $row + $i * $dy;
            $c = $col + $i * $dx;

            if ($grid[$r][$c] !== '' && $grid[$r][$c] !== $word[$i]) {
                return false;
            }
        }

        return true;
    }

    protected function placeWord(array &$grid, string $word, int $row, int $col, array $direction): array
    {
        $positions = [];
        $dx = $direction['dx'];
        $dy = $direction['dy'];

        for ($i = 0; $i < strlen($word); $i++) {
            $r = $row + $i * $dy;
            $c = $col + $i * $dx;
            $grid[$r][$c] = $word[$i];
            $positions[] = ['row' => $r, 'col' => $c];
        }

        return $positions;
    }

    public function checkWord(string $sessionId, string $word, array $selectedPositions): array
    {
        $session = $this->getSession($sessionId);

        if (!$session) {
            throw new \Exception('Session not found');
        }

        if ($session['completed']) {
            throw new \Exception('Session already completed');
        }

        $word = strtoupper($word);

        if (in_array($word, $session['found_words'])) {
            return ['valid' => false, 'already_found' => true];
        }

        if (!isset($session['word_positions'][$word])) {
            return ['valid' => false, 'not_in_puzzle' => true];
        }

        $correctPositions = $session['word_positions'][$word]['positions'];
        $isCorrect = $this->validatePositions($selectedPositions, $correctPositions);

        if ($isCorrect) {
            $session['found_words'][] = $word;

            $scoringConfig = $this->dataService->getScoringConfig();
            $points = $scoringConfig['base_points_per_word'] ?? 10;
            $points += strlen($word) * ($scoringConfig['length_bonus_per_letter'] ?? 2);
            $points += count($session['found_words']) * ($scoringConfig['streak_bonus'] ?? 5);

            $session['score'] += $points;

            $isComplete = count($session['found_words']) >= count($session['words']);

            if ($isComplete) {
                $session['completed'] = true;
                $session['completed_at'] = now()->toIso8601String();

                if ($session['hints_used'] === 0) {
                    $session['score'] += $scoringConfig['no_hints_bonus'] ?? 25;
                }
                $session['score'] += $scoringConfig['all_words_bonus'] ?? 50;
            }

            $this->saveSession($session);

            $result = [
                'valid' => true,
                'word' => $word,
                'points' => $points,
                'score' => $session['score'],
                'found_count' => count($session['found_words']),
                'total_words' => count($session['words']),
                'is_complete' => $isComplete,
            ];

            if ($isComplete) {
                $result['summary'] = $this->generateSummary($session);
            }

            return $result;
        }

        return ['valid' => false, 'wrong_positions' => true];
    }

    protected function validatePositions(array $selected, array $correct): bool
    {
        if (count($selected) !== count($correct)) {
            return false;
        }

        foreach ($correct as $i => $pos) {
            if (!isset($selected[$i]) ||
                $selected[$i]['row'] !== $pos['row'] ||
                $selected[$i]['col'] !== $pos['col']) {
                return false;
            }
        }

        return true;
    }

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

        $usedCount = $session['powerups_used'][$powerupId] ?? 0;
        if ($usedCount >= ($powerup['uses_per_game'] ?? 1)) {
            throw new \Exception('Powerup limit reached');
        }

        $session['powerups_used'][$powerupId] = $usedCount + 1;
        $result = ['powerup_id' => $powerupId];

        switch ($powerupId) {
            case 'hint':
                $session['hints_used']++;
                $unfoundWords = array_filter($session['words'], function ($w) use ($session) {
                    return !in_array(strtoupper($w['word']), $session['found_words']);
                });

                if (!empty($unfoundWords)) {
                    $randomWord = array_values($unfoundWords)[array_rand($unfoundWords)];
                    $word = strtoupper($randomWord['word']);
                    $positions = $session['word_positions'][$word]['positions'];
                    $result['hint'] = [
                        'word' => $word,
                        'first_letter_position' => $positions[0],
                    ];
                }
                break;

            case 'reveal_word':
                $unfoundWords = array_filter($session['words'], function ($w) use ($session) {
                    return !in_array(strtoupper($w['word']), $session['found_words']);
                });

                if (!empty($unfoundWords)) {
                    $randomWord = array_values($unfoundWords)[array_rand($unfoundWords)];
                    $word = strtoupper($randomWord['word']);
                    $session['found_words'][] = $word;
                    $result['revealed_word'] = [
                        'word' => $word,
                        'positions' => $session['word_positions'][$word]['positions'],
                    ];
                }
                break;

            case 'extra_time':
                $timeBonus = $powerup['time_bonus'] ?? 60;
                if ($session['time_remaining'] !== null) {
                    $session['time_remaining'] += $timeBonus;
                }
                $result['time_bonus'] = $timeBonus;
                break;

            case 'clear_letters':
                $result['clear_duration'] = 10;
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
        }

        $this->saveSession($session);

        return [
            'time_remaining' => $session['time_remaining'],
            'completed' => $session['completed'],
        ];
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
        $foundWords = count($session['found_words']);
        $percentage = $totalWords > 0 ? round(($foundWords / $totalWords) * 100) : 0;

        $stars = 0;
        if ($percentage >= 60) $stars = 1;
        if ($percentage >= 80) $stars = 2;
        if ($percentage >= 100) $stars = 3;

        $xpEarned = $session['score'];
        if ($stars === 3) $xpEarned += 50;

        $coinsEarned = (int)($session['score'] / 10) + $stars * 5;

        $startTime = strtotime($session['started_at']);
        $endTime = isset($session['completed_at']) ? strtotime($session['completed_at']) : time();
        $timeTaken = $endTime - $startTime;

        return [
            'completed' => $session['completed'],
            'level_number' => $session['level_number'],
            'score' => $session['score'],
            'words_found' => $foundWords,
            'total_words' => $totalWords,
            'percentage' => $percentage,
            'stars' => $stars,
            'hints_used' => $session['hints_used'],
            'time_taken' => $timeTaken,
            'time_expired' => $session['time_expired'] ?? false,
            'xp_earned' => $xpEarned,
            'coins_earned' => $coinsEarned,
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
            'grid' => $session['grid'],
            'words' => array_map(function ($w) use ($session) {
                return [
                    'word' => $w['word'],
                    'translation' => $w['translation'],
                    'found' => in_array(strtoupper($w['word']), $session['found_words']),
                ];
            }, $session['words']),
            'found_words' => $session['found_words'],
            'score' => $session['score'],
            'time_remaining' => $session['time_remaining'],
            'completed' => $session['completed'],
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
