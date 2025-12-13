<?php

namespace App\Services\English;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

class FlashcardService
{
    private array $config;
    private array $levels;
    private array $categories;
    
    public function __construct()
    {
        $this->loadData();
    }
    
    /**
     * Load game data from JSON files
     */
    private function loadData(): void
    {
        $basePath = base_path('data/english/games/flashcard');
        
        $this->config = Cache::remember('flashcard_config', 3600, function () use ($basePath) {
            $path = $basePath . '/config.json';
            if (file_exists($path)) {
                return json_decode(file_get_contents($path), true);
            }
            return [];
        });
        
        $this->levels = Cache::remember('flashcard_levels', 3600, function () use ($basePath) {
            $path = $basePath . '/levels.json';
            if (file_exists($path)) {
                $data = json_decode(file_get_contents($path), true);
                return $data['levels'] ?? [];
            }
            return [];
        });
        
        $this->categories = Cache::remember('flashcard_categories', 3600, function () use ($basePath) {
            $path = $basePath . '/categories.json';
            if (file_exists($path)) {
                $data = json_decode(file_get_contents($path), true);
                return $data['categories'] ?? [];
            }
            return [];
        });
    }
    
    /**
     * Get words for a category
     */
    private function getWordsForCategory(string $category): array
    {
        $cacheKey = "flashcard_words_{$category}";
        
        return Cache::remember($cacheKey, 3600, function () use ($category) {
            $basePath = base_path('data/english/games/flashcard/words');
            $path = $basePath . "/{$category}.json";
            
            if (file_exists($path)) {
                $data = json_decode(file_get_contents($path), true);
                return $data['words'] ?? [];
            }
            return [];
        });
    }
    
    /**
     * Get user progress from cache
     */
    private function getUserProgress(string $userId): array
    {
        $key = "flashcard_progress_{$userId}";
        return Cache::get($key, []);
    }
    
    /**
     * Save user progress to cache
     */
    private function saveUserProgress(string $userId, array $progress): void
    {
        $key = "flashcard_progress_{$userId}";
        Cache::put($key, $progress, 86400 * 30); // 30 days
    }
    
    /**
     * Get all levels with user progress
     */
    public function getLevelsWithProgress(string $userId): array
    {
        $userProgress = $this->getUserProgress($userId);
        
        // Calculate total stars
        $totalStars = collect($userProgress)->sum('stars_earned');
        
        return collect($this->levels)->map(function ($level) use ($userProgress, $totalStars) {
            $levelNum = $level['number'];
            $progress = $userProgress[$levelNum] ?? [];
            
            $isUnlocked = $levelNum === 1 || $totalStars >= $level['required_stars_to_unlock'];
            
            return [
                'number' => $levelNum,
                'name' => $level['name'],
                'name_uz' => $level['name_uz'],
                'cefr_level' => $level['cefr_level'],
                'description' => $level['description'],
                'description_uz' => $level['description_uz'] ?? $level['description'],
                'accent_color' => $level['accent_color'],
                'icon' => $level['icon'],
                'categories' => $level['categories'],
                'cards_to_master' => $level['cards_to_master'],
                
                'is_unlocked' => $isUnlocked,
                'required_stars' => $level['required_stars_to_unlock'],
                
                'is_completed' => $progress['is_completed'] ?? false,
                'cards_learned' => $progress['cards_learned'] ?? 0,
                'stars_earned' => $progress['stars_earned'] ?? 0,
                'sessions_count' => $progress['sessions_count'] ?? 0,
                'rewards_claimed' => $progress['rewards_claimed'] ?? false,
                
                'xp_reward' => $level['rewards']['xp_completion'] ?? 10,
                'coin_reward' => $level['rewards']['coins_completion'] ?? 3,
            ];
        })->toArray();
    }
    
    /**
     * Get total stars earned
     */
    public function getTotalStars(string $userId): int
    {
        $userProgress = $this->getUserProgress($userId);
        return collect($userProgress)->sum('stars_earned');
    }
    
    /**
     * Get max stars possible
     */
    public function getMaxStars(): int
    {
        return count($this->levels) * 3;
    }
    
    /**
     * Get level details
     */
    public function getLevel(int $levelNumber): ?array
    {
        return collect($this->levels)->firstWhere('number', $levelNumber);
    }
    
    /**
     * Start a flashcard session
     */
    public function startSession(string $userId, int $levelNumber, ?string $category = null): array
    {
        $level = $this->getLevel($levelNumber);
        
        if (!$level) {
            throw new \Exception('Level topilmadi');
        }
        
        // Check unlock status
        $totalStars = $this->getTotalStars($userId);
        if ($levelNumber > 1 && $totalStars < $level['required_stars_to_unlock']) {
            throw new \Exception('Level qulflangan');
        }
        
        // Get cards for session
        $cards = $this->getCardsForSession($level, $category);
        
        if (empty($cards)) {
            throw new \Exception('Kartalar topilmadi');
        }
        
        // Shuffle cards
        shuffle($cards);
        
        // Limit to cards_per_session
        $cardsPerSession = $this->config['settings']['cards_per_session'] ?? 15;
        $cards = array_slice($cards, 0, $cardsPerSession);
        
        // Create session
        $sessionId = Str::uuid()->toString();
        
        $sessionData = [
            'session_id' => $sessionId,
            'user_id' => $userId,
            'level_number' => $levelNumber,
            'category' => $category,
            'cards' => $cards,
            'total_cards' => count($cards),
            'current_index' => 0,
            'correct_count' => 0,
            'wrong_count' => 0,
            'responses' => [],
            'started_at' => now()->toDateTimeString(),
        ];
        
        // Save session
        Cache::put("flashcard_session_{$sessionId}", $sessionData, 3600);
        
        // Prepare cards for frontend (remove answers if needed)
        $frontendCards = $this->prepareCardsForFrontend($cards, $level);
        
        return [
            'session_id' => $sessionId,
            'level' => [
                'number' => $level['number'],
                'name' => $level['name'],
                'name_uz' => $level['name_uz'],
                'cefr_level' => $level['cefr_level'],
                'accent_color' => $level['accent_color'],
                'icon' => $level['icon'],
            ],
            'features' => $level['features'],
            'cards' => $frontendCards,
            'total_cards' => count($cards),
            'cards_per_session' => $cardsPerSession,
        ];
    }
    
    /**
     * Get cards for a session
     */
    private function getCardsForSession(array $level, ?string $category): array
    {
        $categories = $category ? [$category] : ($level['categories'] ?? []);
        $allCards = [];
        
        foreach ($categories as $cat) {
            $words = $this->getWordsForCategory($cat);
            $allCards = array_merge($allCards, $words);
        }
        
        return $allCards;
    }
    
    /**
     * Prepare cards for frontend
     */
    private function prepareCardsForFrontend(array $cards, array $level): array
    {
        $features = $level['features'] ?? [];
        
        return collect($cards)->map(function ($card) use ($features) {
            $frontendCard = [
                'id' => $card['id'],
                'word' => $card['word'],
                'phonetic' => $card['phonetic'] ?? null,
                'part_of_speech' => $card['part_of_speech'] ?? null,
            ];
            
            // Add fields based on level features
            if ($features['show_translation'] ?? true) {
                $frontendCard['translation_uz'] = $card['translation_uz'] ?? null;
            }
            
            if ($features['show_definition'] ?? false) {
                $frontendCard['definition'] = $card['definition'] ?? null;
            }
            
            if ($features['show_example'] ?? false) {
                $frontendCard['example'] = $card['example'] ?? null;
                $frontendCard['example_uz'] = $card['example_uz'] ?? null;
            }
            
            if ($features['show_synonyms'] ?? false) {
                $frontendCard['synonyms'] = $card['synonyms'] ?? [];
            }
            
            if ($features['show_usage_notes'] ?? false) {
                $frontendCard['usage_notes'] = $card['usage_notes'] ?? null;
            }
            
            return $frontendCard;
        })->toArray();
    }
    
    /**
     * Record card response
     */
    public function recordResponse(string $sessionId, string $cardId, string $response, int $responseTimeMs): array
    {
        $session = Cache::get("flashcard_session_{$sessionId}");
        
        if (!$session) {
            throw new \Exception('Sessiya topilmadi');
        }
        
        // Record response
        $session['responses'][] = [
            'card_id' => $cardId,
            'response' => $response,
            'time_ms' => $responseTimeMs,
        ];
        
        // Update counts
        if (in_array($response, ['good', 'easy'])) {
            $session['correct_count']++;
        } else {
            $session['wrong_count']++;
        }
        
        $session['current_index']++;
        
        // Check if session is complete
        $isComplete = $session['current_index'] >= $session['total_cards'];
        
        // Save updated session
        Cache::put("flashcard_session_{$sessionId}", $session, 3600);
        
        return [
            'success' => true,
            'current_index' => $session['current_index'],
            'total_cards' => $session['total_cards'],
            'correct_count' => $session['correct_count'],
            'is_complete' => $isComplete,
        ];
    }
    
    /**
     * Complete session and calculate results
     */
    public function completeSession(string $sessionId): array
    {
        $session = Cache::get("flashcard_session_{$sessionId}");
        
        if (!$session) {
            throw new \Exception('Sessiya topilmadi');
        }
        
        $totalCards = $session['total_cards'];
        $correctCount = $session['correct_count'];
        $wrongCount = $session['wrong_count'];
        
        // Calculate accuracy
        $accuracy = $totalCards > 0 ? round(($correctCount / $totalCards) * 100) : 0;
        
        // Calculate stars
        $thresholds = $this->config['star_thresholds'] ?? ['one_star' => 50, 'two_stars' => 75, 'three_stars' => 90];
        $stars = 0;
        if ($accuracy >= $thresholds['one_star']) $stars = 1;
        if ($accuracy >= $thresholds['two_stars']) $stars = 2;
        if ($accuracy >= $thresholds['three_stars']) $stars = 3;
        
        // Update user progress
        $userId = $session['user_id'];
        $levelNumber = $session['level_number'];
        $userProgress = $this->getUserProgress($userId);
        
        $levelProgress = $userProgress[$levelNumber] ?? [
            'is_completed' => false,
            'cards_learned' => 0,
            'stars_earned' => 0,
            'sessions_count' => 0,
            'rewards_claimed' => false,
        ];
        
        $levelProgress['sessions_count']++;
        $levelProgress['cards_learned'] += $correctCount;
        
        // Update stars if better
        if ($stars > $levelProgress['stars_earned']) {
            $levelProgress['stars_earned'] = $stars;
        }
        
        // Check completion
        $level = $this->getLevel($levelNumber);
        $requiredCards = $level['cards_to_master'] ?? 20;
        
        $xpEarned = 0;
        $coinsEarned = 0;
        $isNewRecord = false;
        
        if ($levelProgress['cards_learned'] >= $requiredCards && !$levelProgress['is_completed']) {
            $levelProgress['is_completed'] = true;
            
            // Claim rewards only once!
            if (!$levelProgress['rewards_claimed']) {
                $levelProgress['rewards_claimed'] = true;
                $xpEarned = $level['rewards']['xp_completion'] ?? 10;
                $coinsEarned = $level['rewards']['coins_completion'] ?? 3;
                $isNewRecord = true;
                
                // Update user's balance
                $user = \App\Models\User::find($userId);
                if ($user) {
                    $user->increment('xp', $xpEarned);
                    $user->increment('coins', $coinsEarned);
                }
            }
        }
        
        // Save progress
        $userProgress[$levelNumber] = $levelProgress;
        $this->saveUserProgress($userId, $userProgress);
        
        // Clean up session
        Cache::forget("flashcard_session_{$sessionId}");
        
        // Check next level unlock
        $nextLevelUnlocked = $this->checkNextLevelUnlock($userId, $levelNumber);
        
        return [
            'session_id' => $sessionId,
            'status' => 'completed',
            
            'total_cards' => $totalCards,
            'correct_count' => $correctCount,
            'wrong_count' => $wrongCount,
            'accuracy' => $accuracy,
            
            'stars' => $stars,
            'is_new_record' => $isNewRecord,
            
            'level_progress' => [
                'cards_learned' => $levelProgress['cards_learned'],
                'cards_required' => $requiredCards,
                'is_completed' => $levelProgress['is_completed'],
            ],
            
            'xp_earned' => $xpEarned,
            'coins_earned' => $coinsEarned,
            
            'next_level_unlocked' => $nextLevelUnlocked,
        ];
    }
    
    /**
     * Check if next level is now unlocked
     */
    private function checkNextLevelUnlock(string $userId, int $currentLevel): bool
    {
        $totalStars = $this->getTotalStars($userId);
        $nextLevel = $this->getLevel($currentLevel + 1);
        
        if (!$nextLevel) {
            return false;
        }
        
        return $totalStars >= $nextLevel['required_stars_to_unlock'];
    }
    
    /**
     * Abandon session
     */
    public function abandonSession(string $sessionId): array
    {
        Cache::forget("flashcard_session_{$sessionId}");
        return ['status' => 'abandoned'];
    }
    
    /**
     * Get categories for a level
     */
    public function getCategoriesForLevel(int $levelNumber): array
    {
        $level = $this->getLevel($levelNumber);
        if (!$level) {
            return [];
        }
        
        $levelCategories = $level['categories'] ?? [];
        
        return collect($this->categories)
            ->filter(fn($cat) => in_array($cat['id'], $levelCategories))
            ->map(function ($cat) {
                $words = $this->getWordsForCategory($cat['id']);
                return array_merge($cat, [
                    'word_count' => count($words),
                ]);
            })
            ->values()
            ->toArray();
    }
}
