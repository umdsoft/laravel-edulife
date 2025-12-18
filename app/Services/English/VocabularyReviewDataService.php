<?php

namespace App\Services\English;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class VocabularyReviewDataService
{
    protected string $dataPath;
    protected int $cacheTTL = 3600;

    public function __construct()
    {
        $this->dataPath = base_path('data/english/vocabulary-review');
    }

    public function getConfig(): array
    {
        return Cache::remember('vocabulary_review_config', $this->cacheTTL, function () {
            $path = $this->dataPath . '/config.json';
            if (File::exists($path)) {
                return json_decode(File::get($path), true);
            }
            return [];
        });
    }

    public function getReviewModes(): array
    {
        $config = $this->getConfig();
        return $config['review_modes'] ?? [];
    }

    public function getDifficultyLevels(): array
    {
        $config = $this->getConfig();
        return $config['difficulty_levels'] ?? [];
    }

    public function getWordCategories(): array
    {
        $config = $this->getConfig();
        return $config['word_categories'] ?? [];
    }

    public function getSpacedRepetitionConfig(): array
    {
        $config = $this->getConfig();
        return $config['spaced_repetition'] ?? [];
    }

    public function getScoringConfig(): array
    {
        $config = $this->getConfig();
        return $config['scoring'] ?? [];
    }

    public function getVocabularyRanks(): array
    {
        $config = $this->getConfig();
        return $config['vocabulary_ranks'] ?? [];
    }

    public function getDailyGoals(): array
    {
        $config = $this->getConfig();
        return $config['daily_goals'] ?? [];
    }

    public function getRewardsConfig(): array
    {
        $config = $this->getConfig();
        return $config['rewards'] ?? [];
    }

    public function getTips(): array
    {
        $config = $this->getConfig();
        return $config['tips'] ?? [];
    }

    public function getAchievements(): array
    {
        return Cache::remember('vocabulary_review_achievements', $this->cacheTTL, function () {
            $path = $this->dataPath . '/achievements.json';
            if (File::exists($path)) {
                $data = json_decode(File::get($path), true);
                return $data['achievements'] ?? [];
            }
            return [];
        });
    }

    public function getWordsByCategory(string $categoryId, ?string $level = null): array
    {
        $words = Cache::remember("vocabulary_review_words_{$categoryId}", $this->cacheTTL, function () use ($categoryId) {
            $path = $this->dataPath . "/words/{$categoryId}.json";
            if (File::exists($path)) {
                $data = json_decode(File::get($path), true);
                return $data['words'] ?? [];
            }
            return [];
        });

        // Filter by level if specified
        if ($level !== null) {
            $words = array_filter($words, function ($word) use ($level) {
                return ($word['level'] ?? 'beginner') === $level;
            });
            $words = array_values($words);
        }

        return $words;
    }

    public function getCategoryLevels(string $categoryId): array
    {
        $path = $this->dataPath . "/words/{$categoryId}.json";
        if (File::exists($path)) {
            $data = json_decode(File::get($path), true);
            return $data['levels'] ?? [];
        }
        return [];
    }

    public function getWordsByLevel(string $level): array
    {
        $allWords = $this->getAllWords();
        return array_values(array_filter($allWords, function ($word) use ($level) {
            return ($word['level'] ?? 'beginner') === $level;
        }));
    }

    public function getAllWords(): array
    {
        return Cache::remember('vocabulary_review_all_words', $this->cacheTTL, function () {
            $wordsPath = $this->dataPath . '/words';
            $allWords = [];

            if (File::isDirectory($wordsPath)) {
                $files = File::files($wordsPath);
                foreach ($files as $file) {
                    if ($file->getExtension() === 'json') {
                        $data = json_decode(File::get($file->getPathname()), true);
                        if (isset($data['words'])) {
                            foreach ($data['words'] as $word) {
                                $word['category_id'] = $data['category_id'] ?? basename($file->getFilename(), '.json');
                                $allWords[] = $word;
                            }
                        }
                    }
                }
            }

            return $allWords;
        });
    }

    public function getWordById(string $wordId): ?array
    {
        $allWords = $this->getAllWords();
        foreach ($allWords as $word) {
            if (isset($word['id']) && $word['id'] === $wordId) {
                return $word;
            }
        }
        return null;
    }

    public function getWordsByDifficulty(string $difficulty): array
    {
        $allWords = $this->getAllWords();
        return array_filter($allWords, function ($word) use ($difficulty) {
            return ($word['difficulty'] ?? 'A1') === $difficulty;
        });
    }

    public function getCategoryStats(string $categoryId): array
    {
        $words = $this->getWordsByCategory($categoryId);
        $levels = [];
        $difficulties = [];

        foreach ($words as $word) {
            $level = $word['level'] ?? 'beginner';
            $levels[$level] = ($levels[$level] ?? 0) + 1;

            $diff = $word['difficulty'] ?? 'A1';
            $difficulties[$diff] = ($difficulties[$diff] ?? 0) + 1;
        }

        return [
            'total_words' => count($words),
            'by_level' => $levels,
            'by_difficulty' => $difficulties,
        ];
    }

    public function getUserProgress(): array
    {
        $userId = auth()->id() ?? 'guest';
        return session("vocabulary_review_progress_{$userId}", [
            'words_learned' => [],
            'words_mastered' => [],
            'words_difficult' => [],
            'total_xp' => 0,
            'total_coins' => 0,
            'total_reviews' => 0,
            'correct_answers' => 0,
            'best_streak' => 0,
            'current_streak' => 0,
            'daily_streak' => 0,
            'last_review_date' => null,
            'achievements' => [],
            'category_progress' => [],
            'daily_goal' => 'regular',
            'today_words_reviewed' => 0,
            'today_new_words' => 0,
        ]);
    }

    public function saveUserProgress(array $progress): void
    {
        $userId = auth()->id() ?? 'guest';
        session(["vocabulary_review_progress_{$userId}" => $progress]);
    }

    public function getUserStats(): array
    {
        $progress = $this->getUserProgress();
        $ranks = $this->getVocabularyRanks();

        $wordsLearned = count($progress['words_learned'] ?? []);
        $wordsMastered = count($progress['words_mastered'] ?? []);

        $currentRank = null;
        $nextRank = null;

        foreach ($ranks as $index => $rank) {
            if ($wordsLearned >= $rank['min_words']) {
                $currentRank = $rank;
                $nextRank = $ranks[$index + 1] ?? null;
            }
        }

        $accuracy = 0;
        if (($progress['total_reviews'] ?? 0) > 0) {
            $accuracy = round(($progress['correct_answers'] / $progress['total_reviews']) * 100);
        }

        return [
            'words_learned' => $wordsLearned,
            'words_mastered' => $wordsMastered,
            'words_difficult' => count($progress['words_difficult'] ?? []),
            'total_xp' => $progress['total_xp'] ?? 0,
            'total_coins' => $progress['total_coins'] ?? 0,
            'total_reviews' => $progress['total_reviews'] ?? 0,
            'accuracy' => $accuracy,
            'best_streak' => $progress['best_streak'] ?? 0,
            'daily_streak' => $progress['daily_streak'] ?? 0,
            'vocabulary_rank' => [
                'current' => $currentRank,
                'next' => $nextRank,
                'words_to_next' => $nextRank ? ($nextRank['min_words'] - $wordsLearned) : 0,
                'progress' => $nextRank ? min(100, (($wordsLearned - ($currentRank['min_words'] ?? 0)) / ($nextRank['min_words'] - ($currentRank['min_words'] ?? 0))) * 100) : 100,
            ],
            'today_words_reviewed' => $progress['today_words_reviewed'] ?? 0,
            'today_new_words' => $progress['today_new_words'] ?? 0,
        ];
    }

    public function getWordsForReview(int $limit = 20): array
    {
        $progress = $this->getUserProgress();
        $allWords = $this->getAllWords();
        $srConfig = $this->getSpacedRepetitionConfig();

        $wordsForReview = [];
        $newWords = [];
        $reviewWords = [];

        foreach ($allWords as $word) {
            // Skip words without id
            if (!isset($word['id'])) {
                continue;
            }
            $wordId = $word['id'];
            $wordProgress = $progress['words_learned'][$wordId] ?? null;

            if (!$wordProgress) {
                // New word
                $word['status'] = 'new';
                $word['ease_factor'] = 2.5;
                $word['interval'] = 0;
                $word['repetitions'] = 0;
                $newWords[] = $word;
            } else {
                // Check if needs review
                $lastReview = $wordProgress['last_review'] ?? null;
                $interval = $wordProgress['interval'] ?? 1;

                if ($lastReview) {
                    $nextReview = strtotime($lastReview) + ($interval * 86400);
                    if (time() >= $nextReview) {
                        $word['status'] = 'review';
                        $word['ease_factor'] = $wordProgress['ease_factor'] ?? 2.5;
                        $word['interval'] = $interval;
                        $word['repetitions'] = $wordProgress['repetitions'] ?? 0;
                        $word['last_review'] = $lastReview;
                        $reviewWords[] = $word;
                    }
                }
            }
        }

        // Prioritize difficult words
        $difficultIds = $progress['words_difficult'] ?? [];
        usort($reviewWords, function ($a, $b) use ($difficultIds) {
            $aIsDifficult = in_array($a['id'], $difficultIds);
            $bIsDifficult = in_array($b['id'], $difficultIds);
            if ($aIsDifficult && !$bIsDifficult) return -1;
            if (!$aIsDifficult && $bIsDifficult) return 1;
            return 0;
        });

        // Mix review words and new words
        $newWordsLimit = min($srConfig['new_words_per_day'] ?? 20, count($newWords));
        $reviewLimit = $limit - $newWordsLimit;

        $wordsForReview = array_merge(
            array_slice($reviewWords, 0, $reviewLimit),
            array_slice($newWords, 0, $newWordsLimit)
        );

        shuffle($wordsForReview);

        return array_slice($wordsForReview, 0, $limit);
    }

    public function getCategoryProgress(): array
    {
        $progress = $this->getUserProgress();
        $categories = $this->getWordCategories();
        $result = [];

        foreach ($categories as $category) {
            $categoryWords = $this->getWordsByCategory($category['id']);
            $totalWords = count($categoryWords);
            $learnedCount = 0;
            $masteredCount = 0;

            foreach ($categoryWords as $word) {
                if (!isset($word['id'])) {
                    continue;
                }
                if (isset($progress['words_learned'][$word['id']])) {
                    $learnedCount++;
                }
                if (in_array($word['id'], $progress['words_mastered'] ?? [])) {
                    $masteredCount++;
                }
            }

            $result[] = [
                'id' => $category['id'],
                'name' => $category['name'],
                'name_uz' => $category['name_uz'],
                'icon' => $category['icon'],
                'color' => $category['color'],
                'total_words' => $totalWords,
                'learned' => $learnedCount,
                'mastered' => $masteredCount,
                'progress' => $totalWords > 0 ? round(($learnedCount / $totalWords) * 100) : 0,
            ];
        }

        return $result;
    }

    public function getUserAchievements(): array
    {
        $progress = $this->getUserProgress();
        $allAchievements = $this->getAchievements();
        $earnedIds = $progress['achievements'] ?? [];

        $earned = [];
        $locked = [];

        foreach ($allAchievements as $achievement) {
            if (in_array($achievement['id'], $earnedIds)) {
                $achievement['earned'] = true;
                $earned[] = $achievement;
            } else {
                $achievement['earned'] = false;
                $locked[] = $achievement;
            }
        }

        return [
            'earned' => $earned,
            'locked' => $locked,
            'total_earned' => count($earned),
            'total' => count($allAchievements),
        ];
    }

    public function checkAchievements(array $sessionData): array
    {
        $progress = $this->getUserProgress();
        $achievements = $this->getAchievements();
        $newAchievements = [];

        $wordsLearned = count($progress['words_learned'] ?? []);
        $wordsMastered = count($progress['words_mastered'] ?? []);
        $dailyStreak = $progress['daily_streak'] ?? 0;

        foreach ($achievements as $achievement) {
            if (in_array($achievement['id'], $progress['achievements'] ?? [])) {
                continue;
            }

            $earned = false;
            $condition = $achievement['condition'];

            switch ($condition['type']) {
                case 'words_learned':
                    $earned = $wordsLearned >= $condition['value'];
                    break;

                case 'words_mastered':
                    $earned = $wordsMastered >= $condition['value'];
                    break;

                case 'daily_streak':
                    $earned = $dailyStreak >= $condition['value'];
                    break;

                case 'perfect_session':
                    if (($sessionData['accuracy'] ?? 0) === 100 && ($sessionData['total'] ?? 0) >= 5) {
                        $earned = true;
                    }
                    break;

                case 'category_mastered':
                    $categoryProgress = $this->getCategoryProgress();
                    foreach ($categoryProgress as $cat) {
                        if ($cat['mastered'] === $cat['total_words'] && $cat['total_words'] > 0) {
                            $earned = true;
                            break;
                        }
                    }
                    break;

                case 'speed_review':
                    if (isset($sessionData['time_taken']) && isset($sessionData['correct'])) {
                        if ($sessionData['correct'] >= $condition['value'] && $sessionData['time_taken'] <= $condition['time']) {
                            $earned = true;
                        }
                    }
                    break;
            }

            if ($earned) {
                $newAchievements[] = $achievement;
                $progress['achievements'][] = $achievement['id'];
            }
        }

        if (!empty($newAchievements)) {
            $this->saveUserProgress($progress);
        }

        return $newAchievements;
    }

    public function clearCache(): void
    {
        Cache::forget('vocabulary_review_config');
        Cache::forget('vocabulary_review_achievements');
        Cache::forget('vocabulary_review_all_words');

        $categories = $this->getWordCategories();
        foreach ($categories as $category) {
            Cache::forget("vocabulary_review_words_{$category['id']}");
        }
    }
}
