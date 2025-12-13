<?php

namespace App\Services\English;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class SentenceBuilderService
{
    public function __construct(
        private SentenceBuilderDataService $dataService
    ) {}
    
    /**
     * Get levels with user progress
     */
    public function getLevelsWithProgress(string $userId): array
    {
        $levels = $this->dataService->getLevels();
        $userProgress = $this->getUserProgress($userId);
        $totalStars = collect($userProgress)->sum('stars');
        
        return collect($levels)->map(function ($level) use ($userProgress, $totalStars) {
            $levelNum = $level['number'];
            $progress = $userProgress[$levelNum] ?? [];
            
            // Check unlock status
            $isUnlocked = $levelNum === 1 || 
                          $totalStars >= ($level['required_stars_to_unlock'] ?? 0);
            
            return array_merge($level, [
                'is_unlocked' => $isUnlocked,
                'is_completed' => $progress['is_completed'] ?? false,
                'stars_earned' => $progress['stars'] ?? 0,
                'sentences_completed' => $progress['sentences_completed'] ?? 0,
                'rewards_claimed' => $progress['rewards_claimed'] ?? false,
            ]);
        })->toArray();
    }
    
    /**
     * Start a game session
     */
    public function startSession(string $userId, int $levelNumber): array 
    {
        $level = $this->dataService->getLevel($levelNumber);
        if (!$level) {
            throw new \Exception('Level not found');
        }
        
        // Get sentences
        $allSentences = $this->dataService->getSentencesForLevel($levelNumber);
        if (empty($allSentences)) {
            throw new \Exception('No sentences available');
        }
        
        $config = $this->dataService->getConfig();
        $count = $config['settings']['sentences_per_session'] ?? 10;
        
        // Shuffle and pick
        shuffle($allSentences);
        $sentences = array_slice($allSentences, 0, $count);
        
        // Create session ID
        $sessionId = (string) Str::uuid();
        
        // Prepare session data for Cache (active session)
        $sessionData = [
            'id' => $sessionId,
            'user_id' => $userId,
            'level_number' => $levelNumber,
            'sentences' => collect($sentences)->map(fn($s) => $s['id'])->toArray(),
            'current_index' => 0,
            'answers' => [], // To store user results
            'score' => 0,
            'streak' => 0,
            'started_at' => time(),
        ];
        
        Cache::put("sb_session_{$sessionId}", $sessionData, 3600); // 1 hour
        
        // Prepare frontend data (scramble words)
        $preparedSentences = collect($sentences)->map(function ($sentence) use ($level) {
            $scrambled = $this->dataService->scrambleWords($sentence['words']);
            
            return [
                'id' => $sentence['id'],
                'scrambled_words' => $scrambled['scrambled_words'],
                'punctuation' => $scrambled['punctuation'],
                'translation_uz' => $sentence['translation_uz'],
                'grammar_tip' => ($level['features']['show_grammar_tip'] ?? true) 
                    ? ($sentence['grammar_tip'] ?? null) 
                    : null,
            ];
        })->toArray();
        
        return [
            'session_id' => $sessionId,
            'level' => $level,
            'sentences' => $preparedSentences,
            'config' => $config['settings'] ?? [],
        ];
    }
    
    /**
     * Check user's answer
     */
    public function checkAnswer(
        string $sessionId,
        string $sentenceId,
        array $userAnswer,
        int $hintsUsed
    ): array {
        $session = Cache::get("sb_session_{$sessionId}");
        if (!$session) {
            throw new \Exception('Session expired');
        }
        
        $sentence = $this->dataService->getSentence($sentenceId);
        if (!$sentence) {
            throw new \Exception('Sentence not found');
        }
        
        // Validate
        $correctWords = $this->dataService->scrambleWords($sentence['words'])['scrambled_words'];
        
        // Clean up punctuation from comparison if needed, or assume exact match required
        // We will do a simple exact match of words array (excluding punctuation keys if separate)
        
        // If user answer contains punctuation, careful. 
        // For simplicity, we assume exact array match of "words" in JSON.
        // But wait, JSON words includes punctuation like "." at the end often.
        // We need to compare specific content.
        
        $isCorrect = $this->compareArrays($userAnswer, $sentence['words']);
        $isPerfect = $isCorrect && $hintsUsed === 0;
        
        // Scoring
        $points = 0;
        if ($isCorrect) {
            $session['streak']++;
            $points = 10 + ($isPerfect ? 5 : 0) + ($session['streak'] > 1 ? 2 : 0);
            $session['score'] += $points;
        } else {
            $session['streak'] = 0;
        }
        
        // Record result
        $session['answers'][$sentenceId] = [
            'is_correct' => $isCorrect,
            'is_perfect' => $isPerfect,
            'points' => $points
        ];
        
        Cache::put("sb_session_{$sessionId}", $session, 3600);
        
        return [
            'is_correct' => $isCorrect,
            'correct_sentence' => $sentence['sentence'], // Full string
            'points_earned' => $points,
            'streak' => $session['streak'],
            'total_score' => $session['score']
        ];
    }
    
    /**
     * Get a hint (next correct word)
     */
    public function getHint(string $sessionId, string $sentenceId, array $currentAnswer): array
    {
        $session = Cache::get("sb_session_{$sessionId}");
        if (!$session) {
            throw new \Exception('Session expired');
        }
        
        $sentence = $this->dataService->getSentence($sentenceId);
        if (!$sentence) {
            throw new \Exception('Sentence not found');
        }
        
        $correctWords = $sentence['words'];
        
        // Find the first mismatch or the next missing word
        $nextIndex = 0;
        foreach ($currentAnswer as $index => $word) {
            if (isset($correctWords[$index]) && trim(strtolower($word)) === trim(strtolower($correctWords[$index]))) {
                $nextIndex = $index + 1;
            } else {
                // Found a mistake at this index, so the hint is for this index
                $nextIndex = $index;
                break;
            }
        }
        
        if ($nextIndex >= count($correctWords)) {
             return ['message' => 'Already correct or complete'];
        }
        
        $hintWord = $correctWords[$nextIndex];
        
        return [
            'hint_word' => $hintWord,
            'index' => $nextIndex
        ];
    }

    /**
     * Complete session
     */
    public function completeSession(string $sessionId): array
    {
        $session = Cache::get("sb_session_{$sessionId}");
        if (!$session) {
            throw new \Exception('Session expired');
        }
        
        $stats = $this->calculateStats($session);
        
        // Update user progress permanently
        $this->updateUserLevelProgress($session['user_id'], $session['level_number'], $stats);
        
        Cache::forget("sb_session_{$sessionId}");
        
        return $stats;
    }
    
    /**
     * Calculate session stats
     */
    private function calculateStats(array $session): array
    {
        $total = count($session['sentences']);
        $correct = collect($session['answers'])->where('is_correct', true)->count();
        $accuracy = $total > 0 ? round(($correct / $total) * 100) : 0;
        
        $stars = 0;
        if ($accuracy >= 50) $stars = 1;
        if ($accuracy >= 75) $stars = 2;
        if ($accuracy >= 90) $stars = 3;
        
        return [
            'accuracy' => $accuracy,
            'stars' => $stars,
            'correct_count' => $correct,
            'total_score' => $session['score'],
            'sentences_completed' => $correct // Progress is based on correct ones usually
        ];
    }
    
    /**
     * Update long-term user progress
     */
    private function updateUserLevelProgress(string $userId, int $levelNumber, array $stats): void
    {
        $progress = $this->getUserProgress($userId);
        
        if (!isset($progress[$levelNumber])) {
            $progress[$levelNumber] = [
                'stars' => 0,
                'sentences_completed' => 0,
                'is_completed' => false,
                'rewards_claimed' => false
            ];
        }
        
        // Update stars if better
        if ($stats['stars'] > $progress[$levelNumber]['stars']) {
            $progress[$levelNumber]['stars'] = $stats['stars'];
        }
        
        // Accumulate completed sentences
        $progress[$levelNumber]['sentences_completed'] += $stats['correct_count'];
        
        // Check level requirements
        $level = $this->dataService->getLevel($levelNumber);
        $required = $level['sentences_to_complete'] ?? 100;
        
        if ($progress[$levelNumber]['sentences_completed'] >= $required) {
            $progress[$levelNumber]['is_completed'] = true;
        }
        
        // Save
        $this->saveUserProgress($userId, $progress);
        
        // Handle rewards (XP/Coins)
        if ($progress[$levelNumber]['is_completed'] && !$progress[$levelNumber]['rewards_claimed']) {
            $this->awardLevelRewards($userId, $level);
            $progress[$levelNumber]['rewards_claimed'] = true;
            $this->saveUserProgress($userId, $progress);
            
            // Add to returned stats to notify frontend
            $stats['rewards'] = $level['rewards'] ?? [];
        }
    }
    
    private function awardLevelRewards(string $userId, array $level): void
    {
        $rewards = $level['rewards'] ?? [];
        $xp = $rewards['xp_completion'] ?? 0;
        $coins = $rewards['coins_completion'] ?? 0;
        
        $user = \App\Models\User::find($userId);
        if ($user) {
            $user->increment('xp', $xp);
            $user->increment('coins', $coins);
        }
    }
    
    private function getUserProgress(string $userId): array
    {
        return Cache::get("sb_progress_{$userId}", []);
    }
    
    private function saveUserProgress(string $userId, array $progress): void
    {
        Cache::put("sb_progress_{$userId}", $progress, 86400 * 365); // 1 year
    }
    
    private function compareArrays(array $a, array $b): bool
    {
        if (count($a) !== count($b)) return false;
        
        // Reset keys for comparison
        $a = array_values($a);
        $b = array_values($b);
        
        for ($i = 0; $i < count($a); $i++) {
            // Case insensitive strict comparison
            if (trim(strtolower($a[$i])) !== trim(strtolower($b[$i]))) {
                return false;
            }
        }
        return true;
    }
}
