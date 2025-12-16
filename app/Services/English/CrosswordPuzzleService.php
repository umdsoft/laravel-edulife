<?php

namespace App\Services\English;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CrosswordPuzzleService
{
    protected CrosswordPuzzleDataService $dataService;
    protected string $sessionPath;

    public function __construct(CrosswordPuzzleDataService $dataService)
    {
        $this->dataService = $dataService;
        $this->sessionPath = storage_path('app/game-sessions/crossword-puzzle');

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
        $gridSizes = $this->dataService->getGridSizes();
        $gridSize = $gridSizes[$level['grid_size']];

        $crosswordData = $this->generateCrossword($words, $gridSize['rows'], $gridSize['cols']);

        $sessionId = Str::uuid()->toString();

        $session = [
            'id' => $sessionId,
            'user_id' => Auth::id() ?? 'guest',
            'level_id' => $level['id'],
            'level_number' => $levelNumber,
            'grid' => $crosswordData['grid'],
            'words' => $crosswordData['words'],
            'clues' => $this->generateClues($crosswordData['words'], $level['clue_types']),
            'rows' => $gridSize['rows'],
            'cols' => $gridSize['cols'],
            'user_grid' => $this->createEmptyGrid($crosswordData['grid']),
            'words_found' => [],
            'letters_revealed' => [],
            'score' => 0,
            'streak' => 0,
            'best_streak' => 0,
            'hints_used' => 0,
            'reveals_used' => 0,
            'checks_used' => 0,
            'powerups_used' => [],
            'time_limit' => $level['time_limit'],
            'time_remaining' => $level['time_limit'],
            'started_at' => now()->toIso8601String(),
            'completed' => false,
        ];

        $this->saveSession($session);

        return [
            'session_id' => $sessionId,
            'grid' => $this->getGridForClient($crosswordData['grid']),
            'clues' => $session['clues'],
            'words_count' => count($crosswordData['words']),
            'rows' => $gridSize['rows'],
            'cols' => $gridSize['cols'],
            'time_limit' => $level['time_limit'],
        ];
    }

    protected function generateCrossword(array $words, int $rows, int $cols): array
    {
        $grid = array_fill(0, $rows, array_fill(0, $cols, null));
        $placedWords = [];

        // Sort words by length (longer first for better placement)
        usort($words, function ($a, $b) {
            return strlen($b['word']) - strlen($a['word']);
        });

        $wordNumber = 1;

        foreach ($words as $word) {
            $placed = $this->placeWord($grid, $word, $placedWords, $rows, $cols, $wordNumber);
            if ($placed) {
                $placedWords[] = $placed;
                $wordNumber++;
            }

            // Stop if we have enough words
            if (count($placedWords) >= count($words)) {
                break;
            }
        }

        return [
            'grid' => $grid,
            'words' => $placedWords,
        ];
    }

    protected function placeWord(array &$grid, array $wordData, array $placedWords, int $rows, int $cols, int $wordNumber): ?array
    {
        $word = strtoupper($wordData['word']);
        $length = strlen($word);

        // Try to intersect with existing words first
        if (!empty($placedWords)) {
            for ($i = 0; $i < strlen($word); $i++) {
                $letter = $word[$i];

                foreach ($placedWords as $placed) {
                    $placedWord = strtoupper($placed['word']);
                    for ($j = 0; $j < strlen($placedWord); $j++) {
                        if ($placedWord[$j] === $letter) {
                            // Try to place perpendicular
                            if ($placed['direction'] === 'across') {
                                // Place down
                                $startRow = $placed['row'] - $i;
                                $startCol = $placed['col'] + $j;

                                if ($this->canPlaceWord($grid, $word, $startRow, $startCol, 'down', $rows, $cols)) {
                                    $this->writeWord($grid, $word, $startRow, $startCol, 'down');
                                    return [
                                        'word' => $wordData['word'],
                                        'translation' => $wordData['translation'],
                                        'definition' => $wordData['definition'] ?? '',
                                        'synonym' => $wordData['synonym'] ?? '',
                                        'antonym' => $wordData['antonym'] ?? '',
                                        'example' => $wordData['example'] ?? '',
                                        'category' => $wordData['category'],
                                        'row' => $startRow,
                                        'col' => $startCol,
                                        'direction' => 'down',
                                        'number' => $wordNumber,
                                        'length' => $length,
                                    ];
                                }
                            } else {
                                // Place across
                                $startRow = $placed['row'] + $j;
                                $startCol = $placed['col'] - $i;

                                if ($this->canPlaceWord($grid, $word, $startRow, $startCol, 'across', $rows, $cols)) {
                                    $this->writeWord($grid, $word, $startRow, $startCol, 'across');
                                    return [
                                        'word' => $wordData['word'],
                                        'translation' => $wordData['translation'],
                                        'definition' => $wordData['definition'] ?? '',
                                        'synonym' => $wordData['synonym'] ?? '',
                                        'antonym' => $wordData['antonym'] ?? '',
                                        'example' => $wordData['example'] ?? '',
                                        'category' => $wordData['category'],
                                        'row' => $startRow,
                                        'col' => $startCol,
                                        'direction' => 'across',
                                        'number' => $wordNumber,
                                        'length' => $length,
                                    ];
                                }
                            }
                        }
                    }
                }
            }
        }

        // Try random placement
        $directions = ['across', 'down'];
        shuffle($directions);

        for ($attempt = 0; $attempt < 100; $attempt++) {
            $direction = $directions[$attempt % 2];

            if ($direction === 'across') {
                $row = rand(0, $rows - 1);
                $col = rand(0, max(0, $cols - $length));
            } else {
                $row = rand(0, max(0, $rows - $length));
                $col = rand(0, $cols - 1);
            }

            if ($this->canPlaceWord($grid, $word, $row, $col, $direction, $rows, $cols)) {
                $this->writeWord($grid, $word, $row, $col, $direction);
                return [
                    'word' => $wordData['word'],
                    'translation' => $wordData['translation'],
                    'definition' => $wordData['definition'] ?? '',
                    'synonym' => $wordData['synonym'] ?? '',
                    'antonym' => $wordData['antonym'] ?? '',
                    'example' => $wordData['example'] ?? '',
                    'category' => $wordData['category'],
                    'row' => $row,
                    'col' => $col,
                    'direction' => $direction,
                    'number' => $wordNumber,
                    'length' => $length,
                ];
            }
        }

        return null;
    }

    protected function canPlaceWord(array $grid, string $word, int $row, int $col, string $direction, int $rows, int $cols): bool
    {
        $length = strlen($word);

        // Check bounds
        if ($direction === 'across') {
            if ($col < 0 || $col + $length > $cols || $row < 0 || $row >= $rows) {
                return false;
            }
        } else {
            if ($row < 0 || $row + $length > $rows || $col < 0 || $col >= $cols) {
                return false;
            }
        }

        // Check for conflicts
        for ($i = 0; $i < $length; $i++) {
            $r = $direction === 'across' ? $row : $row + $i;
            $c = $direction === 'across' ? $col + $i : $col;

            $cell = $grid[$r][$c];
            if ($cell !== null && $cell !== $word[$i]) {
                return false;
            }
        }

        // Check before and after the word
        if ($direction === 'across') {
            if ($col > 0 && $grid[$row][$col - 1] !== null) {
                return false;
            }
            if ($col + $length < $cols && $grid[$row][$col + $length] !== null) {
                return false;
            }
        } else {
            if ($row > 0 && $grid[$row - 1][$col] !== null) {
                return false;
            }
            if ($row + $length < $rows && $grid[$row + $length][$col] !== null) {
                return false;
            }
        }

        return true;
    }

    protected function writeWord(array &$grid, string $word, int $row, int $col, string $direction): void
    {
        for ($i = 0; $i < strlen($word); $i++) {
            $r = $direction === 'across' ? $row : $row + $i;
            $c = $direction === 'across' ? $col + $i : $col;
            $grid[$r][$c] = $word[$i];
        }
    }

    protected function generateClues(array $words, array $clueTypes): array
    {
        $across = [];
        $down = [];

        foreach ($words as $word) {
            $clueType = $clueTypes[array_rand($clueTypes)];
            $clue = $this->getClueForWord($word, $clueType);

            $clueData = [
                'number' => $word['number'],
                'clue' => $clue,
                'clue_type' => $clueType,
                'length' => $word['length'],
            ];

            if ($word['direction'] === 'across') {
                $across[] = $clueData;
            } else {
                $down[] = $clueData;
            }
        }

        usort($across, fn($a, $b) => $a['number'] - $b['number']);
        usort($down, fn($a, $b) => $a['number'] - $b['number']);

        return [
            'across' => $across,
            'down' => $down,
        ];
    }

    protected function getClueForWord(array $word, string $clueType): string
    {
        switch ($clueType) {
            case 'translation':
                return "O'zbek tilida: " . $word['translation'];
            case 'definition':
                return $word['definition'] ?: "O'zbek tilida: " . $word['translation'];
            case 'synonym':
                return $word['synonym'] ? "Synonym of: " . $word['synonym'] : "O'zbek tilida: " . $word['translation'];
            case 'antonym':
                return $word['antonym'] ? "Opposite of: " . $word['antonym'] : "O'zbek tilida: " . $word['translation'];
            case 'fill_blank':
                return $word['example'] ?: "O'zbek tilida: " . $word['translation'];
            default:
                return "O'zbek tilida: " . $word['translation'];
        }
    }

    protected function createEmptyGrid(array $grid): array
    {
        $emptyGrid = [];
        foreach ($grid as $row => $cols) {
            $emptyGrid[$row] = [];
            foreach ($cols as $col => $cell) {
                $emptyGrid[$row][$col] = $cell !== null ? '' : null;
            }
        }
        return $emptyGrid;
    }

    protected function getGridForClient(array $grid): array
    {
        $clientGrid = [];
        foreach ($grid as $row => $cols) {
            $clientGrid[$row] = [];
            foreach ($cols as $col => $cell) {
                $clientGrid[$row][$col] = $cell !== null ? '' : null;
            }
        }
        return $clientGrid;
    }

    public function submitWord(string $sessionId, int $wordNumber, string $answer): array
    {
        $session = $this->getSession($sessionId);

        if (!$session) {
            throw new \Exception('Session not found');
        }

        if ($session['completed']) {
            throw new \Exception('Session already completed');
        }

        $word = null;
        foreach ($session['words'] as $w) {
            if ($w['number'] === $wordNumber) {
                $word = $w;
                break;
            }
        }

        if (!$word) {
            throw new \Exception('Word not found');
        }

        if (in_array($wordNumber, $session['words_found'])) {
            return [
                'already_found' => true,
                'score' => $session['score'],
            ];
        }

        $isCorrect = strtoupper(trim($answer)) === strtoupper($word['word']);
        $scoringConfig = $this->dataService->getScoringConfig();

        $pointsEarned = 0;
        if ($isCorrect) {
            $pointsEarned = strlen($word['word']) * $scoringConfig['base_points_per_letter'];
            $pointsEarned += $scoringConfig['word_completion_bonus'];

            $session['streak']++;
            $pointsEarned += $session['streak'] * $scoringConfig['streak_bonus_per_word'];

            if ($session['streak'] > $session['best_streak']) {
                $session['best_streak'] = $session['streak'];
            }

            $session['words_found'][] = $wordNumber;
            $session['score'] += $pointsEarned;

            // Update user grid
            $this->fillWordInGrid($session, $word);

            // Check if puzzle is complete
            if (count($session['words_found']) >= count($session['words'])) {
                $session['completed'] = true;
                $session['completed_at'] = now()->toIso8601String();
            }
        } else {
            $session['streak'] = 0;
        }

        $this->saveSession($session);

        $result = [
            'correct' => $isCorrect,
            'word' => $isCorrect ? strtoupper($word['word']) : null,
            'points_earned' => $pointsEarned,
            'score' => $session['score'],
            'streak' => $session['streak'],
            'words_found' => count($session['words_found']),
            'total_words' => count($session['words']),
            'is_complete' => $session['completed'],
        ];

        if ($session['completed']) {
            $result['summary'] = $this->completeSession($sessionId);
        }

        return $result;
    }

    protected function fillWordInGrid(array &$session, array $word): void
    {
        $letters = str_split(strtoupper($word['word']));
        for ($i = 0; $i < count($letters); $i++) {
            $r = $word['direction'] === 'across' ? $word['row'] : $word['row'] + $i;
            $c = $word['direction'] === 'across' ? $word['col'] + $i : $word['col'];
            $session['user_grid'][$r][$c] = $letters[$i];
        }
    }

    public function usePowerup(string $sessionId, string $powerupId, ?int $wordNumber = null): array
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
            case 'reveal_letter':
                if ($wordNumber) {
                    $word = $this->findWord($session['words'], $wordNumber);
                    if ($word && !in_array($wordNumber, $session['words_found'])) {
                        $letters = str_split(strtoupper($word['word']));
                        $unrevealedIndices = [];

                        for ($i = 0; $i < count($letters); $i++) {
                            $r = $word['direction'] === 'across' ? $word['row'] : $word['row'] + $i;
                            $c = $word['direction'] === 'across' ? $word['col'] + $i : $word['col'];
                            $key = "{$r}_{$c}";

                            if (!in_array($key, $session['letters_revealed'])) {
                                $unrevealedIndices[] = ['index' => $i, 'row' => $r, 'col' => $c];
                            }
                        }

                        if (!empty($unrevealedIndices)) {
                            $reveal = $unrevealedIndices[array_rand($unrevealedIndices)];
                            $session['letters_revealed'][] = "{$reveal['row']}_{$reveal['col']}";
                            $session['user_grid'][$reveal['row']][$reveal['col']] = $letters[$reveal['index']];
                            $session['hints_used']++;

                            $result['revealed_letter'] = [
                                'row' => $reveal['row'],
                                'col' => $reveal['col'],
                                'letter' => $letters[$reveal['index']],
                            ];
                        }
                    }
                }
                break;

            case 'reveal_word':
                if ($wordNumber) {
                    $word = $this->findWord($session['words'], $wordNumber);
                    if ($word && !in_array($wordNumber, $session['words_found'])) {
                        $this->fillWordInGrid($session, $word);
                        $session['words_found'][] = $wordNumber;
                        $session['reveals_used']++;

                        $result['revealed_word'] = [
                            'number' => $wordNumber,
                            'word' => strtoupper($word['word']),
                            'row' => $word['row'],
                            'col' => $word['col'],
                            'direction' => $word['direction'],
                        ];

                        if (count($session['words_found']) >= count($session['words'])) {
                            $session['completed'] = true;
                            $session['completed_at'] = now()->toIso8601String();
                            $result['is_complete'] = true;
                        }
                    }
                }
                break;

            case 'check_word':
                if ($wordNumber) {
                    $word = $this->findWord($session['words'], $wordNumber);
                    if ($word) {
                        $currentAnswer = $this->getCurrentWordFromGrid($session['user_grid'], $word);
                        $isCorrect = strtoupper($currentAnswer) === strtoupper($word['word']);
                        $session['checks_used']++;

                        $result['check_result'] = [
                            'number' => $wordNumber,
                            'is_correct' => $isCorrect,
                        ];
                    }
                }
                break;

            case 'hint':
                if ($wordNumber) {
                    $word = $this->findWord($session['words'], $wordNumber);
                    if ($word) {
                        $session['hints_used']++;
                        $result['hint'] = [
                            'number' => $wordNumber,
                            'first_letter' => strtoupper($word['word'][0]),
                            'translation' => $word['translation'],
                        ];
                    }
                }
                break;

            case 'extra_time':
                $timeBonus = $powerup['time_bonus'] ?? 60;
                if ($session['time_remaining'] !== null) {
                    $session['time_remaining'] += $timeBonus;
                }
                $result['time_bonus'] = $timeBonus;
                $result['time_remaining'] = $session['time_remaining'];
                break;
        }

        $this->saveSession($session);

        return $result;
    }

    protected function findWord(array $words, int $wordNumber): ?array
    {
        foreach ($words as $word) {
            if ($word['number'] === $wordNumber) {
                return $word;
            }
        }
        return null;
    }

    protected function getCurrentWordFromGrid(array $grid, array $word): string
    {
        $result = '';
        for ($i = 0; $i < $word['length']; $i++) {
            $r = $word['direction'] === 'across' ? $word['row'] : $word['row'] + $i;
            $c = $word['direction'] === 'across' ? $word['col'] + $i : $word['col'];
            $result .= $grid[$r][$c] ?? '';
        }
        return $result;
    }

    public function updateCell(string $sessionId, int $row, int $col, string $letter): array
    {
        $session = $this->getSession($sessionId);

        if (!$session) {
            throw new \Exception('Session not found');
        }

        if ($session['completed']) {
            throw new \Exception('Session already completed');
        }

        if ($session['grid'][$row][$col] === null) {
            throw new \Exception('Invalid cell');
        }

        $session['user_grid'][$row][$col] = strtoupper($letter);
        $this->saveSession($session);

        return [
            'row' => $row,
            'col' => $col,
            'letter' => strtoupper($letter),
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
        $wordsFound = count($session['words_found']);
        $completionRate = $totalWords > 0 ? round(($wordsFound / $totalWords) * 100) : 0;

        $stars = 0;
        if ($completionRate >= 60) $stars = 1;
        if ($completionRate >= 80) $stars = 2;
        if ($completionRate >= 95) $stars = 3;

        $xpEarned = $session['score'];
        if ($stars === 3) {
            $xpEarned += 100;
        }
        if ($session['hints_used'] === 0 && $session['reveals_used'] === 0) {
            $xpEarned += 50;
        }

        $coinsEarned = (int)($session['score'] / 10);
        $coinsEarned += $stars * 5;

        $timeTaken = null;
        if ($session['started_at'] && $session['completed_at']) {
            $start = new \DateTime($session['started_at']);
            $end = new \DateTime($session['completed_at']);
            $timeTaken = $end->getTimestamp() - $start->getTimestamp();
        }

        return [
            'completed' => true,
            'level_number' => $session['level_number'],
            'score' => $session['score'],
            'words_found' => $wordsFound,
            'total_words' => $totalWords,
            'completion_rate' => $completionRate,
            'stars' => $stars,
            'best_streak' => $session['best_streak'],
            'hints_used' => $session['hints_used'],
            'reveals_used' => $session['reveals_used'],
            'checks_used' => $session['checks_used'],
            'xp_earned' => $xpEarned,
            'coins_earned' => $coinsEarned,
            'time_taken' => $timeTaken,
            'time_expired' => $session['time_remaining'] !== null && $session['time_remaining'] <= 0,
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
            'user_grid' => $session['user_grid'],
            'words_found' => $session['words_found'],
            'score' => $session['score'],
            'streak' => $session['streak'],
            'time_remaining' => $session['time_remaining'],
            'completed' => $session['completed'],
            'clues' => $session['clues'],
            'powerups_used' => $session['powerups_used'],
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
        }

        $this->saveSession($session);

        return [
            'time_remaining' => $session['time_remaining'],
            'completed' => $session['completed'],
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
