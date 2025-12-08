<?php

namespace App\Services\English;

use App\Models\English\EnglishVocabulary;
use App\Models\English\UserVocabulary;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Carbon\Carbon;

class VocabularyService
{
    public function __construct(
        private LevelService $levelService
    ) {
    }

    /**
     * Get vocabulary for level with user progress
     */
    public function getVocabularyForLevel(string $levelId, User $user, array $filters = []): Collection
    {
        $query = EnglishVocabulary::with(['category', 'unit'])
            ->where('level_id', $levelId)
            ->where('is_active', true);

        if (!empty($filters['category'])) {
            $query->where('category_id', $filters['category']);
        }
        if (!empty($filters['unit'])) {
            $query->where('unit_id', $filters['unit']);
        }
        if (!empty($filters['difficulty'])) {
            $query->where('difficulty', $filters['difficulty']);
        }

        $vocabulary = $query->orderBy('order_number')->get();

        $vocabulary->each(function ($vocab) use ($user) {
            $vocab->user_progress = $this->getUserVocabularyProgress($vocab, $user);
        });

        return $vocabulary;
    }

    /**
     * Get words due for review (SM-2)
     */
    public function getWordsForReview(User $user, int $limit = 20): Collection
    {
        return UserVocabulary::with('vocabulary')
            ->where('user_id', $user->id)
            ->where('next_review_date', '<=', now())
            ->whereIn('status', ['learning', 'reviewing'])
            ->orderBy('next_review_date')
            ->limit($limit)
            ->get();
    }

    /**
     * Get new words to learn
     */
    public function getNewWordsToLearn(User $user, string $levelId, int $limit = 10): Collection
    {
        return EnglishVocabulary::where('level_id', $levelId)
            ->whereDoesntHave('userVocabulary', fn($q) => $q->where('user_id', $user->id))
            ->where('is_active', true)
            ->orderBy('order_number')
            ->limit($limit)
            ->get();
    }

    /**
     * Learn new word
     */
    public function learnWord(EnglishVocabulary $vocabulary, User $user): UserVocabulary
    {
        $userVocab = UserVocabulary::firstOrCreate(
            ['user_id' => $user->id, 'vocabulary_id' => $vocabulary->id],
            [
                'id' => Str::uuid(),
                'status' => 'new',
                'ease_factor' => 2.5,
                'interval_days' => 0,
                'repetitions' => 0,
                'next_review_date' => now(),
                'mastery_level' => 0,
            ]
        );

        if ($userVocab->status === 'new') {
            $userVocab->status = 'learning';
            $userVocab->first_learned_at = now();
            $userVocab->save();

            $profile = $this->levelService->getOrCreateProfile($user);
            $profile->addXp(5);
        }

        return $userVocab;
    }

    /**
     * Process vocabulary review (SM-2 Algorithm)
     * Quality: 0-5 (0=blackout, 5=perfect)
     */
    public function processReview(UserVocabulary $userVocab, int $quality): array
    {
        $oldInterval = $userVocab->interval_days;
        $oldEaseFactor = $userVocab->ease_factor;

        $qualityHistory = $userVocab->quality_history ?? [];
        $qualityHistory[] = [
            'quality' => $quality,
            'date' => now()->toDateString(),
            'interval' => $oldInterval,
        ];
        $userVocab->quality_history = array_slice($qualityHistory, -20);

        // SM-2 Algorithm
        if ($quality >= 3) {
            if ($userVocab->repetitions === 0) {
                $userVocab->interval_days = 1;
            } elseif ($userVocab->repetitions === 1) {
                $userVocab->interval_days = 6;
            } else {
                $userVocab->interval_days = (int) round($oldInterval * $userVocab->ease_factor);
            }
            $userVocab->repetitions++;
            $userVocab->consecutive_correct = ($userVocab->consecutive_correct ?? 0) + 1;
        } else {
            $userVocab->repetitions = 0;
            $userVocab->interval_days = 1;
            $userVocab->consecutive_correct = 0;
        }

        // Update ease factor
        $userVocab->ease_factor = max(
            1.3,
            $oldEaseFactor + (0.1 - (5 - $quality) * (0.08 + (5 - $quality) * 0.02))
        );

        $userVocab->next_review_date = now()->addDays($userVocab->interval_days);
        $userVocab->last_reviewed_at = now();
        $userVocab->times_reviewed = ($userVocab->times_reviewed ?? 0) + 1;

        $this->updateStatusAndMastery($userVocab);
        $userVocab->save();

        $xpEarned = $this->calculateReviewXp($quality, $userVocab->consecutive_correct);
        $profile = $this->levelService->getOrCreateProfile($userVocab->user);
        $profile->addXp($xpEarned);

        return [
            'quality' => $quality,
            'new_interval' => $userVocab->interval_days,
            'next_review' => $userVocab->next_review_date->format('Y-m-d'),
            'mastery_level' => $userVocab->mastery_level,
            'status' => $userVocab->status,
            'xp_earned' => $xpEarned,
            'streak' => $userVocab->consecutive_correct,
        ];
    }

    /**
     * Update vocabulary status and mastery level
     */
    private function updateStatusAndMastery(UserVocabulary $userVocab): void
    {
        $userVocab->status = match (true) {
            $userVocab->consecutive_correct >= 5 && $userVocab->interval_days >= 32 => 'mastered',
            $userVocab->interval_days >= 8 => 'reviewing',
            $userVocab->repetitions >= 1 => 'learning',
            default => 'new',
        };

        $userVocab->mastery_level = match (true) {
            $userVocab->interval_days >= 90 && $userVocab->consecutive_correct >= 10 => 5,
            $userVocab->interval_days >= 30 && $userVocab->consecutive_correct >= 5 => 4,
            $userVocab->interval_days >= 14 && $userVocab->consecutive_correct >= 3 => 3,
            $userVocab->interval_days >= 6 => 2,
            $userVocab->repetitions >= 1 => 1,
            default => 0,
        };
    }

    /**
     * Calculate XP reward for review
     */
    private function calculateReviewXp(int $quality, int $streak): int
    {
        $baseXp = match ($quality) {
            5 => 10, 4 => 8, 3 => 5, 2 => 2, 1 => 1, default => 0,
        };

        $streakMultiplier = min(2.0, 1 + ($streak * 0.1));
        return (int) round($baseXp * $streakMultiplier);
    }

    /**
     * Get vocabulary statistics for user
     */
    public function getVocabularyStats(User $user, ?string $levelId = null): array
    {
        $query = UserVocabulary::where('user_id', $user->id);

        if ($levelId) {
            $query->whereHas('vocabulary', fn($q) => $q->where('level_id', $levelId));
        }

        $total = $query->count();
        $byStatus = $query->get()->groupBy('status');

        return [
            'total_learned' => $total,
            'new' => $byStatus->get('new', collect())->count(),
            'learning' => $byStatus->get('learning', collect())->count(),
            'reviewing' => $byStatus->get('reviewing', collect())->count(),
            'mastered' => $byStatus->get('mastered', collect())->count(),
            'due_for_review' => UserVocabulary::where('user_id', $user->id)
                ->where('next_review_date', '<=', now())->count(),
            'average_mastery' => $query->avg('mastery_level') ?? 0,
        ];
    }

    private function getUserVocabularyProgress(EnglishVocabulary $vocab, User $user): ?UserVocabulary
    {
        return UserVocabulary::where('user_id', $user->id)
            ->where('vocabulary_id', $vocab->id)->first();
    }

    /**
     * Search vocabulary
     */
    public function searchVocabulary(string $query, User $user, ?string $levelId = null): Collection
    {
        $vocabQuery = EnglishVocabulary::where(function ($q) use ($query) {
            $q->where('word', 'LIKE', "%{$query}%")
                ->orWhere('translation_uz', 'LIKE', "%{$query}%");
        })->where('is_active', true);

        if ($levelId) {
            $vocabQuery->where('level_id', $levelId);
        }

        return $vocabQuery->limit(50)->get()->map(function ($vocab) use ($user) {
            $vocab->user_progress = $this->getUserVocabularyProgress($vocab, $user);
            return $vocab;
        });
    }
}
