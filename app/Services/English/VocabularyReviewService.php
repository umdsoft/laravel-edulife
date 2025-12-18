<?php

namespace App\Services\English;

class VocabularyReviewService
{
    protected VocabularyReviewDataService $dataService;
    protected GameScoringService $scoringService;

    public function __construct(VocabularyReviewDataService $dataService, GameScoringService $scoringService)
    {
        $this->dataService = $dataService;
        $this->scoringService = $scoringService;
    }

    public function startReviewSession(string $mode = 'mixed', ?string $categoryId = null, int $wordCount = 20): array
    {
        $progress = $this->dataService->getUserProgress();

        // Get words for review
        if ($categoryId) {
            $words = $this->dataService->getWordsByCategory($categoryId);
            shuffle($words);
            $words = array_slice($words, 0, $wordCount);
        } else {
            $words = $this->dataService->getWordsForReview($wordCount);
        }

        // Add status to each word
        foreach ($words as &$word) {
            $wordProgress = $progress['words_learned'][$word['id']] ?? null;
            if (!$wordProgress) {
                $word['status'] = 'new';
            } elseif (in_array($word['id'], $progress['words_mastered'] ?? [])) {
                $word['status'] = 'mastered';
            } elseif (in_array($word['id'], $progress['words_difficult'] ?? [])) {
                $word['status'] = 'difficult';
            } else {
                $word['status'] = 'learning';
            }
        }

        // Generate review items based on mode
        $reviewItems = $this->generateReviewItems($words, $mode);

        $sessionId = uniqid('vr_session_');

        return [
            'session_id' => $sessionId,
            'mode' => $mode,
            'category_id' => $categoryId,
            'words' => $words,
            'review_items' => $reviewItems,
            'total_items' => count($reviewItems),
            'current_index' => 0,
        ];
    }

    protected function generateReviewItems(array $words, string $mode): array
    {
        $items = [];

        foreach ($words as $word) {
            $reviewType = $this->getReviewTypeForMode($mode);

            $item = [
                'word_id' => $word['id'],
                'word' => $word['word'],
                'translation' => $word['translation'],
                'pronunciation' => $word['pronunciation'] ?? '',
                'example' => $word['example'] ?? '',
                'example_uz' => $word['example_uz'] ?? '',
                'audio_url' => $word['audio_url'] ?? '',
                'review_type' => $reviewType,
                'status' => $word['status'] ?? 'new',
            ];

            // Add type-specific data
            switch ($reviewType) {
                case 'multiple_choice':
                    $item['options'] = $this->generateOptions($word, $words);
                    $item['question'] = "'{$word['word']}' so'zining tarjimasi qaysi?";
                    break;

                case 'typing':
                    $item['question'] = "'{$word['translation']}' so'zini inglizcha yozing";
                    $item['hint'] = $this->generateTypingHint($word['word']);
                    break;

                case 'listening':
                    $item['question'] = "Eshitgan so'zingizni tanlang";
                    $item['options'] = $this->generateOptions($word, $words, 'word');
                    break;

                case 'flashcard':
                    $item['front'] = $word['word'];
                    $item['back'] = $word['translation'];
                    $item['definition'] = $word['definition_uz'] ?? $word['definition'] ?? '';
                    break;

                case 'reverse':
                    $item['options'] = $this->generateOptions($word, $words, 'word');
                    $item['question'] = "'{$word['translation']}' tarjimasi qaysi so'z?";
                    break;
            }

            $items[] = $item;
        }

        return $items;
    }

    protected function getReviewTypeForMode(string $mode): string
    {
        $types = match ($mode) {
            'flashcard' => ['flashcard'],
            'typing' => ['typing'],
            'listening' => ['listening'],
            'multiple_choice' => ['multiple_choice', 'reverse'],
            'mixed' => ['flashcard', 'typing', 'multiple_choice', 'reverse'],
            default => ['multiple_choice'],
        };

        return $types[array_rand($types)];
    }

    protected function generateOptions(array $correctWord, array $allWords, string $field = 'translation'): array
    {
        $options = [];
        $correctValue = $correctWord[$field];
        $options[] = ['value' => $correctValue, 'is_correct' => true];

        // Get wrong options
        $wrongWords = array_filter($allWords, fn($w) => $w['id'] !== $correctWord['id']);
        shuffle($wrongWords);

        $wrongCount = 0;
        foreach ($wrongWords as $word) {
            if ($wrongCount >= 3) break;
            if ($word[$field] !== $correctValue) {
                $options[] = ['value' => $word[$field], 'is_correct' => false];
                $wrongCount++;
            }
        }

        shuffle($options);
        return $options;
    }

    protected function generateTypingHint(string $word): string
    {
        $length = strlen($word);
        if ($length <= 3) {
            return $word[0] . str_repeat('_', $length - 1);
        }
        return $word[0] . str_repeat('_', $length - 2) . $word[$length - 1];
    }

    public function checkAnswer(string $wordId, $answer, string $reviewType): array
    {
        $word = $this->dataService->getWordById($wordId);
        if (!$word) {
            return ['correct' => false, 'error' => 'Word not found'];
        }

        $correct = false;
        $correctAnswer = '';

        switch ($reviewType) {
            case 'multiple_choice':
                $correctAnswer = $word['translation'];
                $correct = strtolower(trim($answer)) === strtolower(trim($correctAnswer));
                break;

            case 'reverse':
                $correctAnswer = $word['word'];
                $correct = strtolower(trim($answer)) === strtolower(trim($correctAnswer));
                break;

            case 'typing':
                $correctAnswer = $word['word'];
                $correct = $this->checkTypingAnswer($answer, $correctAnswer);
                break;

            case 'listening':
                $correctAnswer = $word['word'];
                $correct = strtolower(trim($answer)) === strtolower(trim($correctAnswer));
                break;

            case 'flashcard':
                // For flashcard, user rates themselves
                $correct = $answer === 'good' || $answer === 'easy';
                $correctAnswer = $word['translation'];
                break;
        }

        // Update progress
        $this->updateWordProgress($wordId, $correct, $answer);

        return [
            'correct' => $correct,
            'correct_answer' => $correctAnswer,
            'word' => $word['word'],
            'translation' => $word['translation'],
            'example' => $word['example'] ?? '',
            'pronunciation' => $word['pronunciation'] ?? '',
        ];
    }

    protected function checkTypingAnswer(string $answer, string $correct): bool
    {
        $answer = strtolower(trim($answer));
        $correct = strtolower(trim($correct));

        // Exact match
        if ($answer === $correct) {
            return true;
        }

        // Allow minor typos (1-2 characters)
        $distance = levenshtein($answer, $correct);
        $maxDistance = max(1, floor(strlen($correct) / 5));

        return $distance <= $maxDistance;
    }

    protected function updateWordProgress(string $wordId, bool $correct, $answer): void
    {
        $progress = $this->dataService->getUserProgress();
        $scoringConfig = $this->dataService->getScoringConfig();
        $srConfig = $this->dataService->getSpacedRepetitionConfig();

        // Initialize word progress if not exists
        if (!isset($progress['words_learned'][$wordId])) {
            $progress['words_learned'][$wordId] = [
                'ease_factor' => 2.5,
                'interval' => 1,
                'repetitions' => 0,
                'last_review' => null,
                'correct_count' => 0,
                'incorrect_count' => 0,
            ];
        }

        $wordProgress = &$progress['words_learned'][$wordId];

        // Update spaced repetition data
        if ($correct) {
            $wordProgress['correct_count']++;
            $wordProgress['repetitions']++;
            $progress['correct_answers'] = ($progress['correct_answers'] ?? 0) + 1;

            // Calculate new interval using SM-2 algorithm
            if ($wordProgress['repetitions'] === 1) {
                $wordProgress['interval'] = 1;
            } elseif ($wordProgress['repetitions'] === 2) {
                $wordProgress['interval'] = 3;
            } else {
                $wordProgress['interval'] = round($wordProgress['interval'] * $wordProgress['ease_factor']);
            }

            // Adjust ease factor based on rating
            $easeAdjustment = $srConfig['ease_factor']['good'] ?? 1.0;
            if ($answer === 'easy') {
                $easeAdjustment = $srConfig['ease_factor']['easy'] ?? 1.3;
            }
            $wordProgress['ease_factor'] = max(1.3, $wordProgress['ease_factor'] + (0.1 - (5 - 4) * (0.08 + (5 - 4) * 0.02)));

            // Check if word is mastered (5+ correct in a row with interval > 30 days)
            if ($wordProgress['correct_count'] >= 5 && $wordProgress['interval'] >= 30) {
                if (!in_array($wordId, $progress['words_mastered'] ?? [])) {
                    $progress['words_mastered'][] = $wordId;
                    $progress['total_xp'] = ($progress['total_xp'] ?? 0) + ($scoringConfig['mastery_bonus'] ?? 50);
                }
            }

            // Remove from difficult if was there
            $progress['words_difficult'] = array_filter($progress['words_difficult'] ?? [], fn($id) => $id !== $wordId);

            // Award XP
            $progress['total_xp'] = ($progress['total_xp'] ?? 0) + ($scoringConfig['correct_answer'] ?? 10);

        } else {
            $wordProgress['incorrect_count']++;
            $wordProgress['repetitions'] = 0;
            $wordProgress['interval'] = 1;

            // Decrease ease factor
            $wordProgress['ease_factor'] = max(1.3, $wordProgress['ease_factor'] * ($srConfig['ease_factor']['again'] ?? 0.5));

            // Mark as difficult if multiple mistakes
            if ($wordProgress['incorrect_count'] >= 3) {
                if (!in_array($wordId, $progress['words_difficult'] ?? [])) {
                    $progress['words_difficult'][] = $wordId;
                }
            }
        }

        $wordProgress['last_review'] = date('Y-m-d H:i:s');
        $progress['total_reviews'] = ($progress['total_reviews'] ?? 0) + 1;
        $progress['today_words_reviewed'] = ($progress['today_words_reviewed'] ?? 0) + 1;

        // Update daily streak
        $today = date('Y-m-d');
        $lastReviewDate = $progress['last_review_date'] ?? null;

        if ($lastReviewDate !== $today) {
            if ($lastReviewDate === date('Y-m-d', strtotime('-1 day'))) {
                $progress['daily_streak'] = ($progress['daily_streak'] ?? 0) + 1;
            } else {
                $progress['daily_streak'] = 1;
            }
            $progress['last_review_date'] = $today;
            $progress['today_words_reviewed'] = 1;
            $progress['today_new_words'] = 0;
        }

        $this->dataService->saveUserProgress($progress);
    }

    public function completeSession(array $sessionData): array
    {
        $progress = $this->dataService->getUserProgress();

        $correct = $sessionData['correct'] ?? 0;
        $total = $sessionData['total'] ?? 0;
        $currentStreak = $sessionData['streak'] ?? 0;

        // Update best streak in progress
        if ($currentStreak > ($progress['best_streak'] ?? 0)) {
            $progress['best_streak'] = $currentStreak;
        }

        // Use unified GameScoringService for reward calculation
        $user = auth()->user();
        $rewards = $this->scoringService->calculateSessionRewards([
            'correct' => $correct,
            'total' => $total,
            'difficulty' => 'medium',
            'streak' => $currentStreak,
            'time_taken' => $sessionData['time_taken'] ?? 0,
            'is_perfect' => $correct === $total && $total > 0,
            'is_first_completion' => true,
        ], $user);

        // Update local progress tracking
        $progress['total_xp'] = ($progress['total_xp'] ?? 0) + $rewards['xp_earned'];
        $progress['total_coins'] = ($progress['total_coins'] ?? 0) + $rewards['coins_earned'];

        $this->dataService->saveUserProgress($progress);

        // Check for achievements
        $newAchievements = $this->dataService->checkAchievements([
            'accuracy' => $rewards['accuracy'],
            'total' => $total,
            'correct' => $correct,
            'time_taken' => $sessionData['time_taken'] ?? 0,
        ]);

        // Add achievement rewards
        foreach ($newAchievements as $achievement) {
            $achievementXp = $achievement['xp_reward'] ?? 0;
            $achievementCoins = $achievement['coin_reward'] ?? 0;

            if ($achievementXp > 0 || $achievementCoins > 0) {
                $progress['total_xp'] += $achievementXp;
                $progress['total_coins'] += $achievementCoins;

                // Also add to user profile
                if ($user) {
                    $this->scoringService->addRewardsToUser($user, $achievementXp, $achievementCoins);
                }
            }
        }

        if (!empty($newAchievements)) {
            $this->dataService->saveUserProgress($progress);
        }

        return [
            'correct' => $correct,
            'total' => $total,
            'accuracy' => $rewards['accuracy'],
            'xp_earned' => $rewards['xp_earned'],
            'coins_earned' => $rewards['coins_earned'],
            'stars' => $rewards['stars'],
            'is_perfect' => $rewards['is_perfect'],
            'best_streak' => $progress['best_streak'],
            'daily_streak' => $progress['daily_streak'] ?? 0,
            'new_achievements' => $newAchievements,
            'stats' => $this->dataService->getUserStats(),
        ];
    }

    public function getReviewSummary(): array
    {
        $progress = $this->dataService->getUserProgress();
        $wordsForReview = $this->dataService->getWordsForReview(100);

        $newWords = array_filter($wordsForReview, fn($w) => ($w['status'] ?? '') === 'new');
        $reviewWords = array_filter($wordsForReview, fn($w) => ($w['status'] ?? '') === 'review');

        $dailyGoals = $this->dataService->getDailyGoals();
        $currentGoal = null;
        $goalId = $progress['daily_goal'] ?? 'regular';

        foreach ($dailyGoals as $goal) {
            if ($goal['id'] === $goalId) {
                $currentGoal = $goal;
                break;
            }
        }

        return [
            'new_words_available' => count($newWords),
            'words_to_review' => count($reviewWords),
            'total_available' => count($wordsForReview),
            'today_reviewed' => $progress['today_words_reviewed'] ?? 0,
            'today_new' => $progress['today_new_words'] ?? 0,
            'daily_goal' => $currentGoal,
            'goal_progress' => $currentGoal ? min(100, round((($progress['today_words_reviewed'] ?? 0) / $currentGoal['words']) * 100)) : 0,
        ];
    }
}
