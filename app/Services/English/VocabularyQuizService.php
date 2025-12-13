<?php

namespace App\Services\English;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class VocabularyQuizService
{
    protected VocabularyQuizDataService $dataService;
    protected array $config;

    public function __construct(VocabularyQuizDataService $dataService)
    {
        $this->dataService = $dataService;
        $this->config = $dataService->getConfig();
    }

    /**
     * Start a new game session
     */
    public function startSession($userId, int $levelNumber): array
    {
        $level = $this->dataService->getLevel($levelNumber);

        if (!$level) {
            return ['success' => false, 'error' => 'Level not found'];
        }

        $questionsCount = $level['questions_count'] ?? $this->config['settings']['questions_per_session'] ?? 10;
        $words = $this->dataService->getLevelVocabulary($levelNumber, $questionsCount);

        if (empty($words)) {
            return ['success' => false, 'error' => 'No vocabulary available for this level'];
        }

        // Generate questions from words
        $questions = $this->generateQuestions($words, $level);

        $sessionId = Str::uuid()->toString();

        // Initialize powerups
        $powerups = $this->initializePowerups();

        $sessionData = [
            'session_id' => $sessionId,
            'user_id' => $userId,
            'level_number' => $levelNumber,
            'level' => $level,
            'questions' => $questions,
            'current_index' => 0,
            'total_questions' => count($questions),
            'score' => 0,
            'correct_count' => 0,
            'streak' => 0,
            'max_streak' => 0,
            'streak_multiplier' => 1.0,
            'powerups' => $powerups,
            'powerups_used' => [],
            'time_limit' => $level['time_limit'] ?? $this->config['settings']['base_time_limit'] ?? 20,
            'results' => [],
            'started_at' => now()->toDateTimeString(),
            'completed' => false,
            'double_points_active' => false,
            'hint_used_current' => false,
        ];

        // Store session in cache
        Cache::put("vocabulary_quiz_session_{$sessionId}", $sessionData, now()->addHours(2));

        return [
            'success' => true,
            'session_id' => $sessionId,
            'level' => $level,
            'total_questions' => count($questions),
            'time_limit' => $sessionData['time_limit'],
            'current_question' => $this->prepareQuestionForClient($questions[0]),
            'current_index' => 0,
            'powerups' => $powerups,
        ];
    }

    /**
     * Generate questions from vocabulary words
     */
    protected function generateQuestions(array $words, array $level): array
    {
        $questions = [];
        $questionTypes = $level['question_types'] ?? ['word_to_definition'];
        $allWords = $words; // Keep reference to all words for distractors

        foreach ($words as $word) {
            // Pick a random question type from allowed types for this level
            $questionType = $questionTypes[array_rand($questionTypes)];

            $question = $this->createQuestion($word, $questionType, $allWords);
            if ($question) {
                $questions[] = $question;
            }
        }

        shuffle($questions);
        return $questions;
    }

    /**
     * Create a single question
     */
    protected function createQuestion(array $word, string $questionType, array $allWords): ?array
    {
        $question = [
            'id' => $word['id'] . '_' . $questionType,
            'word_id' => $word['id'],
            'type' => $questionType,
            'word' => $word['word'],
            'word_data' => $word,
        ];

        switch ($questionType) {
            case 'word_to_definition':
                $question['prompt'] = $word['word'];
                $question['prompt_label'] = "So'zning ta'rifini toping";
                $question['correct_answer'] = $word['definition'];
                $question['options'] = $this->generateDefinitionOptions($word, $allWords);
                break;

            case 'definition_to_word':
                $question['prompt'] = $word['definition'];
                $question['prompt_label'] = "Ta'rifga mos so'zni toping";
                $question['correct_answer'] = $word['word'];
                $question['options'] = $this->generateWordOptions($word, $allWords);
                break;

            case 'word_to_translation':
                $question['prompt'] = $word['word'];
                $question['prompt_label'] = "So'zning tarjimasini toping";
                $question['correct_answer'] = $word['translation_uz'];
                $question['options'] = $this->generateTranslationOptions($word, $allWords);
                break;

            case 'translation_to_word':
                $question['prompt'] = $word['translation_uz'];
                $question['prompt_label'] = "Tarjimaga mos inglizcha so'zni toping";
                $question['correct_answer'] = $word['word'];
                $question['options'] = $this->generateWordOptions($word, $allWords);
                break;

            case 'synonym_match':
                if (empty($word['synonyms'])) {
                    return null;
                }
                $question['prompt'] = $word['word'];
                $question['prompt_label'] = "Sinonimini toping";
                $question['correct_answer'] = $word['synonyms'][0];
                $question['options'] = $this->generateSynonymOptions($word, $allWords);
                break;

            case 'antonym_match':
                if (empty($word['antonyms'])) {
                    return null;
                }
                $question['prompt'] = $word['word'];
                $question['prompt_label'] = "Antonimini toping";
                $question['correct_answer'] = $word['antonyms'][0];
                $question['options'] = $this->generateAntonymOptions($word, $allWords);
                break;

            case 'context_fill':
                $question['prompt'] = $word['example_sentence'];
                $question['prompt_label'] = "Bo'sh joyni to'ldiring";
                $question['correct_answer'] = $word['word'];
                $question['options'] = $this->generateWordOptions($word, $allWords);
                $question['context_translation'] = $word['example_translation'] ?? null;
                break;

            default:
                return null;
        }

        // Shuffle options
        if (!empty($question['options'])) {
            shuffle($question['options']);
        }

        return $question;
    }

    /**
     * Generate definition options (distractors)
     */
    protected function generateDefinitionOptions(array $word, array $allWords): array
    {
        $options = [$word['definition']];

        // Get distractors from other words
        $otherWords = array_filter($allWords, fn($w) => $w['id'] !== $word['id']);
        shuffle($otherWords);

        foreach (array_slice($otherWords, 0, 3) as $otherWord) {
            $options[] = $otherWord['definition'];
        }

        return $options;
    }

    /**
     * Generate word options (distractors)
     */
    protected function generateWordOptions(array $word, array $allWords): array
    {
        $options = [$word['word']];

        // First try to use predefined distractors
        if (!empty($word['distractors'])) {
            $options = array_merge($options, array_slice($word['distractors'], 0, 3));
        } else {
            // Fall back to other words
            $otherWords = array_filter($allWords, fn($w) => $w['id'] !== $word['id']);
            shuffle($otherWords);

            foreach (array_slice($otherWords, 0, 3) as $otherWord) {
                $options[] = $otherWord['word'];
            }
        }

        return array_unique($options);
    }

    /**
     * Generate translation options (distractors)
     */
    protected function generateTranslationOptions(array $word, array $allWords): array
    {
        $options = [$word['translation_uz']];

        $otherWords = array_filter($allWords, fn($w) => $w['id'] !== $word['id']);
        shuffle($otherWords);

        foreach (array_slice($otherWords, 0, 3) as $otherWord) {
            if (isset($otherWord['translation_uz'])) {
                $options[] = $otherWord['translation_uz'];
            }
        }

        return array_unique($options);
    }

    /**
     * Generate synonym options (distractors)
     */
    protected function generateSynonymOptions(array $word, array $allWords): array
    {
        $options = [$word['synonyms'][0]];

        // Add other words as distractors (not synonyms)
        $otherWords = array_filter($allWords, fn($w) => $w['id'] !== $word['id']);
        shuffle($otherWords);

        foreach (array_slice($otherWords, 0, 3) as $otherWord) {
            $options[] = $otherWord['word'];
        }

        return array_unique($options);
    }

    /**
     * Generate antonym options (distractors)
     */
    protected function generateAntonymOptions(array $word, array $allWords): array
    {
        $options = [$word['antonyms'][0]];

        // Add synonyms as distractors (opposite of antonyms)
        if (!empty($word['synonyms'])) {
            $options = array_merge($options, array_slice($word['synonyms'], 0, 2));
        }

        // Fill remaining with other words
        $otherWords = array_filter($allWords, fn($w) => $w['id'] !== $word['id']);
        shuffle($otherWords);

        while (count($options) < 4 && !empty($otherWords)) {
            $otherWord = array_shift($otherWords);
            $options[] = $otherWord['word'];
        }

        return array_unique($options);
    }

    /**
     * Initialize powerups for session
     */
    protected function initializePowerups(): array
    {
        $powerupsConfig = $this->dataService->getPowerups();
        $powerups = [];

        foreach ($powerupsConfig as $powerup) {
            $powerups[$powerup['id']] = [
                'id' => $powerup['id'],
                'name' => $powerup['name'],
                'name_uz' => $powerup['name_uz'],
                'remaining' => $powerup['max_uses'],
                'cooldown' => 0,
                'max_uses' => $powerup['max_uses'],
                'cooldown_questions' => $powerup['cooldown'],
            ];
        }

        return $powerups;
    }

    /**
     * Get current session state
     */
    public function getSession(string $sessionId): ?array
    {
        return Cache::get("vocabulary_quiz_session_{$sessionId}");
    }

    /**
     * Check user's answer
     */
    public function checkAnswer(string $sessionId, string $userAnswer, int $timeSpent): array
    {
        $session = $this->getSession($sessionId);

        if (!$session) {
            return ['success' => false, 'error' => 'Session not found'];
        }

        if ($session['completed']) {
            return ['success' => false, 'error' => 'Session already completed'];
        }

        $currentIndex = $session['current_index'];
        $question = $session['questions'][$currentIndex];
        $correctAnswer = $question['correct_answer'];
        $timeLimit = $session['time_limit'];

        // Check if answer is correct
        $isCorrect = strtolower(trim($userAnswer)) === strtolower(trim($correctAnswer));

        // Calculate points
        $points = $this->calculatePoints($isCorrect, $timeSpent, $timeLimit, $session);

        // Update streak
        if ($isCorrect) {
            $session['streak']++;
            $session['max_streak'] = max($session['max_streak'], $session['streak']);
            $session['correct_count']++;

            // Update streak multiplier
            $streakStep = $this->config['settings']['streak_multiplier_step'] ?? 0.1;
            $maxMultiplier = $this->config['settings']['max_streak_multiplier'] ?? 2.0;
            $session['streak_multiplier'] = min($maxMultiplier, 1.0 + ($session['streak'] * $streakStep));
        } else {
            $session['streak'] = 0;
            $session['streak_multiplier'] = 1.0;
        }

        // Apply double points if active
        if ($session['double_points_active'] && $isCorrect) {
            $points *= 2;
            $session['double_points_active'] = false;
        }

        // Update score
        $session['score'] += $points;

        // Record result
        $session['results'][] = [
            'question_id' => $question['id'],
            'word' => $question['word'],
            'type' => $question['type'],
            'user_answer' => $userAnswer,
            'correct_answer' => $correctAnswer,
            'is_correct' => $isCorrect,
            'points' => $points,
            'time_spent' => $timeSpent,
            'streak' => $session['streak'],
            'hint_used' => $session['hint_used_current'],
        ];

        // Reset hint flag
        $session['hint_used_current'] = false;

        // Update powerup cooldowns
        foreach ($session['powerups'] as &$powerup) {
            if ($powerup['cooldown'] > 0) {
                $powerup['cooldown']--;
            }
        }

        // Move to next question
        $session['current_index']++;
        $isLastQuestion = $session['current_index'] >= $session['total_questions'];

        if ($isLastQuestion) {
            $session['completed'] = true;
            $session['completed_at'] = now()->toDateTimeString();
        }

        Cache::put("vocabulary_quiz_session_{$sessionId}", $session, now()->addHours(2));

        $response = [
            'success' => true,
            'is_correct' => $isCorrect,
            'correct_answer' => $correctAnswer,
            'points' => $points,
            'current_score' => $session['score'],
            'streak' => $session['streak'],
            'streak_multiplier' => $session['streak_multiplier'],
            'is_last_question' => $isLastQuestion,
            'powerups' => $session['powerups'],
        ];

        if (!$isLastQuestion) {
            $response['next_question'] = $this->prepareQuestionForClient($session['questions'][$session['current_index']]);
            $response['current_index'] = $session['current_index'];
        }

        return $response;
    }

    /**
     * Calculate points for an answer
     */
    protected function calculatePoints(bool $isCorrect, int $timeSpent, int $timeLimit, array $session): int
    {
        if (!$isCorrect) {
            return 0;
        }

        $scoring = $this->config['scoring'] ?? [];
        $basePoints = $scoring['base_points'] ?? 100;

        // Apply streak multiplier
        $points = (int) ($basePoints * $session['streak_multiplier']);

        // Apply time bonus
        if ($scoring['time_bonus_enabled'] ?? true) {
            $timeRatio = max(0, 1 - ($timeSpent / $timeLimit));
            if ($timeRatio >= ($scoring['time_bonus_threshold'] ?? 0.5)) {
                $timeBonus = (int) (($scoring['time_bonus_max'] ?? 50) * $timeRatio);
                $points += $timeBonus;
            }
        }

        // Apply streak bonus milestones
        $streakBonuses = $scoring['streak_bonus'] ?? [];
        foreach ($streakBonuses as $streakCount => $bonus) {
            if ($session['streak'] == (int) $streakCount) {
                $points += $bonus;
            }
        }

        // Apply hint penalty if used
        if ($session['hint_used_current']) {
            $hintPenalty = $this->config['powerups']['hint']['points_penalty_percent'] ?? 25;
            $points = (int) ($points * (1 - $hintPenalty / 100));
        }

        // Get question type multiplier
        $currentQuestion = $session['questions'][$session['current_index']];
        $questionType = $currentQuestion['type'];
        $typeMultiplier = $this->config['question_types'][$questionType]['points_multiplier'] ?? 1.0;
        $points = (int) ($points * $typeMultiplier);

        return $points;
    }

    /**
     * Use a powerup
     */
    public function usePowerup(string $sessionId, string $powerupId): array
    {
        $session = $this->getSession($sessionId);

        if (!$session || $session['completed']) {
            return ['success' => false, 'error' => 'Invalid session'];
        }

        if (!isset($session['powerups'][$powerupId])) {
            return ['success' => false, 'error' => 'Powerup not found'];
        }

        $powerup = $session['powerups'][$powerupId];

        if ($powerup['remaining'] <= 0) {
            return ['success' => false, 'error' => 'No uses remaining'];
        }

        if ($powerup['cooldown'] > 0) {
            return ['success' => false, 'error' => 'Powerup is on cooldown', 'cooldown' => $powerup['cooldown']];
        }

        $currentQuestion = $session['questions'][$session['current_index']];
        $result = ['success' => true, 'powerup_id' => $powerupId];

        switch ($powerupId) {
            case 'fifty_fifty':
                // Remove 2 wrong options
                $options = $currentQuestion['options'];
                $correctAnswer = $currentQuestion['correct_answer'];
                $wrongOptions = array_filter($options, fn($o) => $o !== $correctAnswer);
                shuffle($wrongOptions);
                $toRemove = array_slice($wrongOptions, 0, 2);
                $result['removed_options'] = $toRemove;
                break;

            case 'skip':
                // Skip without penalty
                $session['results'][] = [
                    'question_id' => $currentQuestion['id'],
                    'word' => $currentQuestion['word'],
                    'type' => $currentQuestion['type'],
                    'user_answer' => '[SKIPPED]',
                    'correct_answer' => $currentQuestion['correct_answer'],
                    'is_correct' => false,
                    'points' => 0,
                    'time_spent' => 0,
                    'streak' => $session['streak'],
                    'skipped' => true,
                ];

                // Don't break streak for skip
                $session['current_index']++;
                $isLastQuestion = $session['current_index'] >= $session['total_questions'];

                if ($isLastQuestion) {
                    $session['completed'] = true;
                    $session['completed_at'] = now()->toDateTimeString();
                } else {
                    $result['next_question'] = $this->prepareQuestionForClient($session['questions'][$session['current_index']]);
                    $result['current_index'] = $session['current_index'];
                }
                $result['is_last_question'] = $isLastQuestion;
                $result['correct_answer'] = $currentQuestion['correct_answer'];
                break;

            case 'extra_time':
                $bonusSeconds = $this->config['powerups']['extra_time']['bonus_seconds'] ?? 10;
                $result['bonus_seconds'] = $bonusSeconds;
                break;

            case 'hint':
                // Generate hint based on question type
                $hint = $this->generateHint($currentQuestion);
                $result['hint'] = $hint;
                $session['hint_used_current'] = true;
                break;

            case 'double_points':
                $session['double_points_active'] = true;
                $result['message'] = 'Keyingi to\'g\'ri javob uchun ball ikki barobar!';
                break;
        }

        // Update powerup usage
        $session['powerups'][$powerupId]['remaining']--;
        $session['powerups'][$powerupId]['cooldown'] = $powerup['cooldown_questions'];
        $session['powerups_used'][] = [
            'powerup_id' => $powerupId,
            'question_index' => $session['current_index'],
            'used_at' => now()->toDateTimeString(),
        ];

        Cache::put("vocabulary_quiz_session_{$sessionId}", $session, now()->addHours(2));

        $result['powerups'] = $session['powerups'];
        return $result;
    }

    /**
     * Generate hint for current question
     */
    protected function generateHint(array $question): string
    {
        $wordData = $question['word_data'];

        switch ($question['type']) {
            case 'word_to_definition':
            case 'definition_to_word':
                // Show first letter and word length
                $word = $question['type'] === 'definition_to_word' ? $wordData['word'] : '';
                if ($word) {
                    return "So'z " . strlen($word) . " ta harfdan iborat va '" . strtoupper($word[0]) . "' harfi bilan boshlanadi.";
                }
                return "Tarjimasi: " . $wordData['translation_uz'];

            case 'word_to_translation':
            case 'translation_to_word':
                // Show definition
                return "Ta'rifi: " . $wordData['definition'];

            case 'synonym_match':
                // Show word usage
                return "Misol: " . $wordData['example_sentence'];

            case 'antonym_match':
                // Show synonyms to help understand meaning
                if (!empty($wordData['synonyms'])) {
                    return "Bu so'zning sinonimlari: " . implode(', ', array_slice($wordData['synonyms'], 0, 2));
                }
                return "Misol: " . $wordData['example_sentence'];

            case 'context_fill':
                // Show translation
                return "Gap tarjimasi: " . ($wordData['example_translation'] ?? 'Mavjud emas');

            default:
                return "Tarjimasi: " . $wordData['translation_uz'];
        }
    }

    /**
     * Complete session and calculate final results
     */
    public function completeSession(string $sessionId, $userId): array
    {
        $session = $this->getSession($sessionId);

        if (!$session) {
            return ['success' => false, 'error' => 'Session not found'];
        }

        // Calculate final statistics
        $totalQuestions = $session['total_questions'];
        $correctCount = $session['correct_count'];
        $accuracy = $totalQuestions > 0 ? round(($correctCount / $totalQuestions) * 100) : 0;

        // Calculate stars
        $stars = $this->calculateStars($accuracy);

        // Calculate rewards
        $rewards = $this->calculateRewards($session, $accuracy, $stars);

        // Check achievements
        $newAchievements = $this->checkAchievements($session, $userId);

        $result = [
            'session_id' => $sessionId,
            'level_number' => $session['level_number'],
            'total_questions' => $totalQuestions,
            'correct_count' => $correctCount,
            'accuracy' => $accuracy,
            'total_score' => $session['score'],
            'max_streak' => $session['max_streak'],
            'stars' => $stars,
            'rewards' => $rewards,
            'results' => $session['results'],
            'powerups_used' => $session['powerups_used'],
            'duration' => $this->calculateDuration($session['started_at'], $session['completed_at'] ?? now()->toDateTimeString()),
            'new_achievements' => $newAchievements,
        ];

        // Save progress
        $this->dataService->saveUserProgress($userId, $session['level_number'], [
            'score' => $session['score'],
            'stars' => $stars,
            'accuracy' => $accuracy,
        ]);

        // Clean up session
        Cache::forget("vocabulary_quiz_session_{$sessionId}");

        return [
            'success' => true,
            'result' => $result,
        ];
    }

    /**
     * Calculate stars based on accuracy
     */
    protected function calculateStars(int $accuracy): int
    {
        $thresholds = $this->config['star_thresholds'] ?? [
            'one_star' => 50,
            'two_stars' => 75,
            'three_stars' => 90,
        ];

        if ($accuracy >= $thresholds['three_stars']) {
            return 3;
        } elseif ($accuracy >= $thresholds['two_stars']) {
            return 2;
        } elseif ($accuracy >= $thresholds['one_star']) {
            return 1;
        }

        return 0;
    }

    /**
     * Calculate rewards (XP and coins)
     */
    protected function calculateRewards(array $session, int $accuracy, int $stars): array
    {
        $rewardsConfig = $this->config['rewards'] ?? [];

        $xpPerCorrect = $rewardsConfig['xp_per_correct'] ?? 5;
        $xpPerPerfect = $rewardsConfig['xp_per_perfect_answer'] ?? 10;
        $xpCompletionBonus = $rewardsConfig['xp_completion_bonus'] ?? 25;
        $xpPerfectSession = $rewardsConfig['xp_perfect_session'] ?? 100;
        $coinsPerSession = $rewardsConfig['coins_per_session'] ?? 5;
        $coinsPerfectSession = $rewardsConfig['coins_perfect_session'] ?? 15;
        $coinsPerStar = $rewardsConfig['coins_per_star'] ?? 3;

        // Calculate XP
        $xp = $xpCompletionBonus;
        $xp += $session['correct_count'] * $xpPerCorrect;

        // Bonus XP for fast correct answers
        foreach ($session['results'] as $result) {
            if ($result['is_correct'] && ($result['time_spent'] ?? 999) < 5) {
                $xp += $xpPerPerfect;
            }
        }

        // Bonus XP for stars
        $xp += $stars * 10;

        // Perfect session bonus
        if ($accuracy === 100) {
            $xp += $xpPerfectSession;
        }

        // Calculate coins
        $coins = $coinsPerSession;
        $coins += $stars * $coinsPerStar;

        if ($accuracy === 100) {
            $coins += $coinsPerfectSession;
        }

        return [
            'xp_earned' => $xp,
            'xp_completion' => $xpCompletionBonus,
            'xp_correct' => $session['correct_count'] * $xpPerCorrect,
            'coins_earned' => $coins,
            'coins_completion' => $coinsPerSession,
            'coins_stars' => $stars * $coinsPerStar,
        ];
    }

    /**
     * Check and award achievements
     */
    protected function checkAchievements(array $session, $userId): array
    {
        $achievements = $this->dataService->getAchievements();
        $userAchievements = $this->dataService->getUserAchievements($userId);
        $newAchievements = [];

        foreach ($achievements as $achievement) {
            if (in_array($achievement['id'], $userAchievements)) {
                continue; // Already earned
            }

            $earned = false;

            switch ($achievement['requirement']['type']) {
                case 'sessions_completed':
                    // This would need cumulative tracking
                    break;

                case 'perfect_session':
                    $earned = $session['correct_count'] === $session['total_questions'];
                    break;

                case 'streak':
                    $earned = $session['max_streak'] >= $achievement['requirement']['value'];
                    break;

                case 'fast_answers':
                    $fastCount = 0;
                    foreach ($session['results'] as $result) {
                        if ($result['is_correct'] && ($result['time_spent'] ?? 999) < $achievement['requirement']['threshold']) {
                            $fastCount++;
                        }
                    }
                    $earned = $fastCount >= $achievement['requirement']['value'];
                    break;

                case 'session_no_powerups':
                    $earned = empty($session['powerups_used']);
                    break;
            }

            if ($earned) {
                $this->dataService->saveUserAchievement($userId, $achievement['id']);
                $newAchievements[] = $achievement;
            }
        }

        return $newAchievements;
    }

    /**
     * Calculate duration between two timestamps
     */
    protected function calculateDuration(string $start, string $end): int
    {
        $startTime = strtotime($start);
        $endTime = strtotime($end);
        return max(0, $endTime - $startTime);
    }

    /**
     * Prepare question for client (hide correct answer from options order hint)
     */
    protected function prepareQuestionForClient(array $question): array
    {
        $options = $question['options'];
        shuffle($options);

        return [
            'id' => $question['id'],
            'type' => $question['type'],
            'prompt' => $question['prompt'],
            'prompt_label' => $question['prompt_label'],
            'options' => $options,
            'word' => $question['word'],
            'pronunciation' => $question['word_data']['pronunciation'] ?? null,
            'context_translation' => $question['context_translation'] ?? null,
        ];
    }
}
