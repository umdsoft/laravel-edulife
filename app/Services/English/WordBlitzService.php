<?php

namespace App\Services\English;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class WordBlitzService
{
    private array $config;
    private array $words;
    
    public function __construct()
    {
        $this->loadData();
    }
    
    /**
     * Load game data from JSON files
     */
    private function loadData(): void
    {
        $basePath = base_path('data/english/games/word_blitz');
        
        $this->config = Cache::remember('word_blitz_config', 3600, function () use ($basePath) {
            $configPath = $basePath . '/config.json';
            if (file_exists($configPath)) {
                return json_decode(file_get_contents($configPath), true);
            }
            return ['levels' => []];
        });
        
        $this->words = Cache::remember('word_blitz_words', 3600, function () use ($basePath) {
            $wordsPath = $basePath . '/words.json';
            if (file_exists($wordsPath)) {
                return json_decode(file_get_contents($wordsPath), true);
            }
            return [];
        });
    }
    
    /**
     * Get user progress from cache
     */
    private function getUserProgress(string $userId): array
    {
        $key = "word_blitz_progress_{$userId}";
        return Cache::get($key, []);
    }
    
    /**
     * Save user progress to cache
     */
    private function saveUserProgress(string $userId, array $progress): void
    {
        $key = "word_blitz_progress_{$userId}";
        Cache::put($key, $progress, 86400 * 30); // 30 days
    }
    
    /**
     * Get all levels with user progress
     */
    public function getLevelsWithProgress(string $userId): array
    {
        $levels = $this->config['levels'] ?? [];
        $userProgress = $this->getUserProgress($userId);
        
        // Calculate total stars
        $totalStars = collect($userProgress)->sum('stars_earned');
        
        return collect($levels)->map(function ($level) use ($userProgress, $totalStars) {
            $levelNum = $level['number'];
            $progress = $userProgress[$levelNum] ?? [];
            
            $isUnlocked = $levelNum === 1 || $totalStars >= $level['stars_required'];
            
            return [
                'number' => $levelNum,
                'name' => $level['name'],
                'name_uz' => $level['name_uz'],
                'cefr' => $level['cefr'],
                'time_limit' => $level['time_limit'],
                'words_required' => $level['words_required'],
                'color' => $level['color'],
                'modes' => $level['modes'],
                
                'is_unlocked' => $isUnlocked,
                'stars_required' => $level['stars_required'],
                
                'is_completed' => $progress['is_completed'] ?? false,
                'best_score' => $progress['best_score'] ?? 0,
                'stars_earned' => $progress['stars_earned'] ?? 0,
                'attempts_count' => $progress['attempts_count'] ?? 0,
                'rewards_claimed' => $progress['rewards_claimed'] ?? false,
                
                'xp_reward' => $level['xp_reward'],
                'coin_reward' => $level['coin_reward'],
            ];
        })->toArray();
    }
    
    /**
     * Get total stars for user
     */
    public function getTotalStars(string $userId): int
    {
        $userProgress = $this->getUserProgress($userId);
        return collect($userProgress)->sum('stars_earned');
    }
    
    /**
     * Start a new game
     */
    public function startGame(string $userId, int $levelNumber): array
    {
        $level = collect($this->config['levels'])->firstWhere('number', $levelNumber);
        
        if (!$level) {
            throw new \Exception('Level not found');
        }
        
        $totalStars = $this->getTotalStars($userId);
        if ($levelNumber > 1 && $totalStars < $level['stars_required']) {
            throw new \Exception('Level is locked. Need ' . $level['stars_required'] . ' stars.');
        }
        
        $words = $this->getWordsForLevel($level);
        $sessionId = Str::uuid()->toString();
        
        Cache::put("word_blitz_session_{$sessionId}", [
            'user_id' => $userId,
            'level_number' => $levelNumber,
            'level' => $level,
            'words' => $words,
            'answers' => [],
            'score' => 0,
            'words_correct' => 0,
            'words_wrong' => 0,
            'words_skipped' => 0,
            'started_at' => now()->toISOString(),
            'status' => 'in_progress',
        ], 600);
        
        $userProgress = $this->getUserProgress($userId);
        if (!isset($userProgress[$levelNumber])) {
            $userProgress[$levelNumber] = [
                'is_completed' => false,
                'best_score' => 0,
                'stars_earned' => 0,
                'attempts_count' => 0,
                'rewards_claimed' => false,
            ];
        }
        $userProgress[$levelNumber]['attempts_count']++;
        $this->saveUserProgress($userId, $userProgress);
        
        return [
            'session_id' => $sessionId,
            'level' => [
                'number' => $level['number'],
                'name' => $level['name'],
                'name_uz' => $level['name_uz'],
                'cefr' => $level['cefr'],
                'color' => $level['color'],
            ],
            'settings' => [
                'time_limit' => $level['time_limit'],
                'words_required' => $level['words_required'],
                'time_bonus_threshold' => $level['time_bonus_threshold'],
                'modes' => $level['modes'],
            ],
            'words' => $words,
            'rewards' => [
                'xp_base' => $level['xp_reward'],
                'xp_per_word' => $level['xp_per_word'],
                'coin_base' => $level['coin_reward'],
                'coin_bonus' => $level['coin_bonus_perfect'],
            ],
        ];
    }
    
    private function getWordsForLevel(array $level): array
    {
        $cefr = $level['cefr'];
        $minLen = $level['word_length_min'];
        $maxLen = $level['word_length_max'];
        $modes = $level['modes'];
        $count = $level['words_required'] + 10;
        
        $allWords = $this->words[$cefr] ?? [];
        
        $filteredWords = collect($allWords)->filter(function ($word) use ($minLen, $maxLen) {
            $len = strlen($word['word']);
            return $len >= $minLen && $len <= $maxLen;
        });
        
        $selectedWords = $filteredWords->shuffle()->take($count)->values();
        
        return $selectedWords->map(function ($word, $index) use ($modes) {
            $mode = $modes[array_rand($modes)];
            $wordStr = $word['word'];
            
            $data = [
                'id' => $index + 1,
                'mode' => $mode,
                'length' => strlen($wordStr),
                'answer' => $wordStr,
            ];
            
            switch ($mode) {
                case 'type_word':
                    $data['display'] = $wordStr;
                    $data['hint'] = $word['uz'];
                    break;
                case 'listen_type':
                    $data['tts_text'] = $wordStr;
                    $data['hint'] = "So'zni eshiting va yozing";
                    break;
                case 'unscramble':
                    $data['scrambled'] = $this->scrambleWord($wordStr);
                    $data['hint'] = $word['uz'];
                    break;
                case 'translation':
                    $data['display'] = $word['uz'];
                    $data['hint'] = "Inglizcha tarjima (" . strlen($wordStr) . " harf)";
                    break;
            }
            
            return $data;
        })->toArray();
    }
    
    private function scrambleWord(string $word): string
    {
        $letters = str_split($word);
        $original = $letters;
        $attempts = 0;
        do {
            shuffle($letters);
            $attempts++;
        } while ($letters === $original && $attempts < 50 && strlen($word) > 1);
        return implode('', $letters);
    }
    
    public function checkAnswer(string $sessionId, int $wordId, string $userAnswer, int $responseTimeMs): array
    {
        $session = Cache::get("word_blitz_session_{$sessionId}");
        
        if (!$session) {
            throw new \Exception('Session not found or expired');
        }
        
        if ($session['status'] !== 'in_progress') {
            throw new \Exception('Game is not in progress');
        }
        
        $word = collect($session['words'])->firstWhere('id', $wordId);
        
        if (!$word) {
            throw new \Exception('Word not found');
        }
        
        $isCorrect = strtoupper(trim($userAnswer)) === strtoupper($word['answer']);
        $level = $session['level'];
        $points = 0;
        $timeBonus = 0;
        
        if ($isCorrect) {
            $points = 10;
            $responseSeconds = $responseTimeMs / 1000;
            if ($responseSeconds <= $level['time_bonus_threshold']) {
                $timeBonus = 5;
                $points += $timeBonus;
            }
            $session['words_correct']++;
            $session['score'] += $points;
        } else {
            $session['words_wrong']++;
        }
        
        $session['answers'][] = [
            'word_id' => $wordId,
            'word' => $word['answer'],
            'user_answer' => $userAnswer,
            'is_correct' => $isCorrect,
            'response_time_ms' => $responseTimeMs,
            'points' => $points,
        ];
        
        Cache::put("word_blitz_session_{$sessionId}", $session, 600);
        
        return [
            'is_correct' => $isCorrect,
            'correct_answer' => $word['answer'],
            'translation' => $this->getTranslation($word['answer'], $session['level']['cefr']),
            'points_earned' => $points,
            'time_bonus' => $timeBonus,
            'current_score' => $session['score'],
            'words_correct' => $session['words_correct'],
        ];
    }
    
    private function getTranslation(string $word, string $cefr): string
    {
        $words = $this->words[$cefr] ?? [];
        $found = collect($words)->firstWhere('word', strtoupper($word));
        return $found['uz'] ?? '';
    }
    
    public function skipWord(string $sessionId, int $wordId): array
    {
        $session = Cache::get("word_blitz_session_{$sessionId}");
        
        if (!$session) {
            throw new \Exception('Session not found');
        }
        
        $word = collect($session['words'])->firstWhere('id', $wordId);
        
        $session['words_skipped']++;
        $session['answers'][] = [
            'word_id' => $wordId,
            'word' => $word['answer'] ?? '',
            'skipped' => true,
            'points' => 0,
        ];
        
        Cache::put("word_blitz_session_{$sessionId}", $session, 600);
        
        return [
            'skipped' => true,
            'correct_answer' => $word['answer'] ?? '',
            'translation' => $word ? $this->getTranslation($word['answer'], $session['level']['cefr']) : '',
        ];
    }
    
    public function completeGame(string $sessionId, int $timeSpent): array
    {
        $session = Cache::get("word_blitz_session_{$sessionId}");
        
        if (!$session) {
            throw new \Exception('Session not found');
        }
        
        if ($session['status'] === 'completed') {
            throw new \Exception('Game already completed');
        }
        
        $level = $session['level'];
        $levelNumber = $session['level_number'];
        $userId = $session['user_id'];
        $wordsCorrect = $session['words_correct'];
        $totalAnswers = count($session['answers']);
        
        $accuracy = $totalAnswers > 0 ? ($wordsCorrect / $totalAnswers) * 100 : 0;
        
        $stars = 0;
        if ($wordsCorrect >= $level['words_required']) {
            $stars = 1;
            if ($accuracy >= 80) $stars = 2;
            if ($accuracy >= 95) $stars = 3;
        }
        
        $userProgress = $this->getUserProgress($userId);
        if (!isset($userProgress[$levelNumber])) {
            $userProgress[$levelNumber] = [
                'is_completed' => false,
                'best_score' => 0,
                'stars_earned' => 0,
                'attempts_count' => 1,
                'rewards_claimed' => false,
            ];
        }
        
        $levelProgress = $userProgress[$levelNumber];
        
        $isNewRecord = false;
        if ($session['score'] > $levelProgress['best_score']) {
            $userProgress[$levelNumber]['best_score'] = $session['score'];
            $isNewRecord = true;
        }
        
        if ($stars > $levelProgress['stars_earned']) {
            $userProgress[$levelNumber]['stars_earned'] = $stars;
        }
        
        $xpEarned = 0;
        $coinsEarned = 0;
        $isFirstCompletion = false;
        
        if ($stars > 0 && !$levelProgress['rewards_claimed']) {
            $isFirstCompletion = true;
            $userProgress[$levelNumber]['is_completed'] = true;
            $userProgress[$levelNumber]['rewards_claimed'] = true;
            
            $xpEarned = $level['xp_reward'] + ($wordsCorrect * $level['xp_per_word']);
            $coinsEarned = $level['coin_reward'];
            
            if ($accuracy >= 95) {
                $coinsEarned += $level['coin_bonus_perfect'];
            }
            
            $user = User::find($userId);
            if ($user && $user->englishProfile) {
                $user->englishProfile->increment('total_xp', $xpEarned);
            }
        }
        
        $this->saveUserProgress($userId, $userProgress);
        
        $session['status'] = 'completed';
        Cache::put("word_blitz_session_{$sessionId}", $session, 300);
        
        $nextLevelUnlocked = $this->checkNextLevelUnlock($userId, $levelNumber);
        
        return [
            'session_id' => $sessionId,
            'status' => 'completed',
            'passed' => $stars > 0,
            'score' => $session['score'],
            'words_correct' => $wordsCorrect,
            'words_wrong' => $session['words_wrong'],
            'words_skipped' => $session['words_skipped'],
            'total_words' => $totalAnswers,
            'accuracy' => round($accuracy, 1),
            'time_spent' => $timeSpent,
            'stars' => $stars,
            'words_required' => $level['words_required'],
            'is_first_completion' => $isFirstCompletion,
            'xp_earned' => $xpEarned,
            'coins_earned' => $coinsEarned,
            'is_new_record' => $isNewRecord,
            'best_score' => $userProgress[$levelNumber]['best_score'],
            'next_level_unlocked' => $nextLevelUnlocked,
        ];
    }
    
    private function checkNextLevelUnlock(string $userId, int $currentLevel): bool
    {
        $levels = $this->config['levels'] ?? [];
        $nextLevel = collect($levels)->firstWhere('number', $currentLevel + 1);
        
        if (!$nextLevel) {
            return false;
        }
        
        $totalStars = $this->getTotalStars($userId);
        return $totalStars >= $nextLevel['stars_required'];
    }
    
    public function abandonGame(string $sessionId): array
    {
        $session = Cache::get("word_blitz_session_{$sessionId}");
        
        if ($session && $session['status'] === 'in_progress') {
            $session['status'] = 'abandoned';
            Cache::put("word_blitz_session_{$sessionId}", $session, 60);
        }
        
        return ['status' => 'abandoned'];
    }
}
