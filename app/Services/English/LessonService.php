<?php

namespace App\Services\English;

use App\Models\English\EnglishLesson;
use App\Models\English\UserLessonProgress;
use App\Models\User;
use Illuminate\Support\Str;

class LessonService
{
    public function __construct(
        private LevelService $levelService,
        private UnitService $unitService
    ) {
    }

    /**
     * Get lesson with full content
     */
    public function getLessonWithContent(string $lessonId, User $user): ?EnglishLesson
    {
        $lesson = EnglishLesson::with([
            'unit.topic.level',
            'vocabulary' => fn($q) => $q->where('is_active', true)->orderBy('order_number'),
            'grammarPoints',
            'exercises' => fn($q) => $q->where('is_active', true)->orderBy('order_number'),
        ])->find($lessonId);

        if (!$lesson)
            return null;

        $lesson->user_progress = $this->getOrCreateProgress($lesson, $user);

        $lesson->vocabulary->each(function ($vocab) use ($user) {
            $vocab->user_status = $vocab->userVocabulary()
                ->where('user_id', $user->id)
                ->first();
        });

        return $lesson;
    }

    /**
     * Start a lesson
     */
    public function startLesson(EnglishLesson $lesson, User $user): UserLessonProgress
    {
        $progress = $this->getOrCreateProgress($lesson, $user);

        if ($progress->status === 'not_started') {
            $progress->status = 'in_progress';
            $progress->started_at = now();
            $progress->save();
        }

        return $progress;
    }

    /**
     * Update lesson step progress
     */
    public function updateStepProgress(EnglishLesson $lesson, User $user, string $step): UserLessonProgress
    {
        $progress = $this->getOrCreateProgress($lesson, $user);

        $completedSteps = $progress->completed_steps ?? [];

        if (!in_array($step, $completedSteps)) {
            $completedSteps[] = $step;
            $progress->completed_steps = $completedSteps;
            $progress->current_step = $step;
            $progress->save();
        }

        return $progress;
    }

    /**
     * Complete lesson
     */
    public function completeLesson(EnglishLesson $lesson, User $user, array $quizResults = []): array
    {
        $progress = $this->getOrCreateProgress($lesson, $user);
        $profile = $this->levelService->getOrCreateProfile($user);

        $isFirstCompletion = $progress->status !== 'completed';

        $stars = $this->calculateStars($quizResults);

        $progress->status = 'completed';
        $progress->completed_at = now();
        $progress->stars_earned = max($progress->stars_earned ?? 0, $stars);
        $progress->quiz_results = $quizResults;
        $progress->times_completed = ($progress->times_completed ?? 0) + 1;
        $progress->save();

        $xpEarned = $this->calculateXpReward($lesson, $stars, $isFirstCompletion);
        $coinsEarned = $isFirstCompletion ? ($stars * 5) : 0;

        $profile->addXp($xpEarned);
        if ($coinsEarned > 0) {
            $profile->addCoins($coinsEarned);
        }

        $this->unitService->updateUnitProgress($lesson->unit, $user);
        $this->updateStreak($user);

        return [
            'stars' => $stars,
            'xp_earned' => $xpEarned,
            'coins_earned' => $coinsEarned,
            'is_first_completion' => $isFirstCompletion,
            'streak' => $profile->current_streak,
        ];
    }

    /**
     * Calculate stars from quiz results
     */
    private function calculateStars(array $quizResults): int
    {
        if (empty($quizResults))
            return 1;

        $percentage = $quizResults['percentage'] ?? 0;

        return match (true) {
            $percentage >= 95 => 3,
            $percentage >= 80 => 2,
            $percentage >= 60 => 1,
            default => 0,
        };
    }

    /**
     * Calculate XP reward
     */
    private function calculateXpReward(EnglishLesson $lesson, int $stars, bool $isFirst): int
    {
        $baseXp = $lesson->xp_reward ?? 20;

        $starMultiplier = match ($stars) {
            3 => 1.5,
            2 => 1.2,
            1 => 1.0,
            default => 0.5,
        };

        $firstTimeBonus = $isFirst ? 1.0 : 0.3;

        return (int) round($baseXp * $starMultiplier * $firstTimeBonus);
    }

    /**
     * Update user streak
     */
    private function updateStreak(User $user): void
    {
        $profile = $this->levelService->getOrCreateProfile($user);

        $today = now()->toDateString();
        $lastStudyDate = $profile->last_study_date?->toDateString();

        if ($lastStudyDate === $today) {
            return;
        }

        $yesterday = now()->subDay()->toDateString();

        if ($lastStudyDate === $yesterday) {
            $profile->current_streak++;
        } else {
            if ($profile->streak_freezes > 0 && $lastStudyDate === now()->subDays(2)->toDateString()) {
                $profile->streak_freezes--;
                $profile->current_streak++;
            } else {
                $profile->current_streak = 1;
            }
        }

        $profile->longest_streak = max($profile->longest_streak, $profile->current_streak);
        $profile->last_study_date = $today;
        $profile->total_study_days++;
        $profile->save();
    }

    /**
     * Get or create lesson progress
     */
    public function getOrCreateProgress(EnglishLesson $lesson, User $user): UserLessonProgress
    {
        return UserLessonProgress::firstOrCreate(
            ['user_id' => $user->id, 'lesson_id' => $lesson->id],
            [
                'id' => Str::uuid(),
                'status' => 'not_started',
                'current_step' => 'intro',
                'completed_steps' => [],
                'stars_earned' => 0,
            ]
        );
    }
}
