<?php

namespace App\Services\English;

use App\Models\English\EnglishUnit;
use App\Models\English\UserUnitProgress;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class UnitService
{
    public function __construct(
        private LevelService $levelService
    ) {
    }

    /**
     * Get units for a topic with progress
     */
    public function getUnitsForTopic(string $topicId, User $user): Collection
    {
        $profile = $this->levelService->getOrCreateProfile($user);

        $units = EnglishUnit::with(['lessons', 'vocabulary'])
            ->where('topic_id', $topicId)
            ->where('is_active', true)
            ->orderBy('order_number')
            ->get();

        return $units->map(function ($unit, $index) use ($profile, $units, $user) {
            $unit->is_unlocked = $this->isUnitUnlocked($unit, $index, $units, $profile);
            $unit->progress = $this->getUnitProgress($unit, $user);
            return $unit;
        });
    }

    /**
     * Get single unit with full details
     */
    public function getUnitWithDetails(string $unitId, User $user): ?EnglishUnit
    {
        $unit = EnglishUnit::with([
            'lessons' => fn($q) => $q->where('is_active', true)->orderBy('order_number'),
            'vocabulary' => fn($q) => $q->where('is_active', true)->orderBy('order_number'),
            'topic.level',
        ])->find($unitId);

        if (!$unit)
            return null;

        $unit->progress = $this->getUnitProgress($unit, $user);
        $unit->lessons->each(function ($lesson) use ($user) {
            $lesson->user_progress = $lesson->userProgress()->where('user_id', $user->id)->first();
        });

        return $unit;
    }

    /**
     * Check if unit is unlocked
     */
    public function isUnitUnlocked(EnglishUnit $unit, int $index, Collection $allUnits, $profile): bool
    {
        if ($index === 0)
            return true;

        $previousUnit = $allUnits[$index - 1] ?? null;
        if (!$previousUnit)
            return true;

        $progress = $this->getUnitProgress($previousUnit, $profile->user);
        return ($progress->completion_percentage ?? 0) >= 80;
    }

    /**
     * Get or create unit progress
     */
    public function getUnitProgress(EnglishUnit $unit, User $user): ?UserUnitProgress
    {
        return UserUnitProgress::firstOrCreate(
            ['user_id' => $user->id, 'unit_id' => $unit->id],
            [
                'id' => Str::uuid(),
                'completion_percentage' => 0,
                'status' => 'not_started',
            ]
        );
    }

    /**
     * Update unit progress after lesson completion
     */
    public function updateUnitProgress(EnglishUnit $unit, User $user): UserUnitProgress
    {
        $progress = $this->getUnitProgress($unit, $user);

        $totalLessons = $unit->lessons()->count();
        $completedLessons = $unit->lessons()
            ->whereHas('userProgress', fn($q) => $q->where('user_id', $user->id)->where('status', 'completed'))
            ->count();

        $percentage = $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100, 2) : 0;

        $progress->completion_percentage = $percentage;
        $progress->status = match (true) {
            $percentage >= 100 => 'completed',
            $percentage > 0 => 'in_progress',
            default => 'not_started',
        };
        $progress->save();

        return $progress;
    }

    /**
     * Complete unit test
     */
    public function completeUnitTest(EnglishUnit $unit, User $user, int $score): array
    {
        $progress = $this->getUnitProgress($unit, $user);

        $passed = $score >= 90;

        $progress->unit_test_score = $score;
        $progress->unit_test_passed = $passed;
        $progress->unit_test_completed_at = now();

        if ($passed && !$progress->badge_earned) {
            $progress->badge_earned = true;
            $progress->badge_earned_at = now();
        }

        $progress->save();

        $xpEarned = $passed ? 100 + ($score - 90) * 2 : 25;
        $profile = $this->levelService->getOrCreateProfile($user);
        $profile->addXp($xpEarned);

        return [
            'passed' => $passed,
            'score' => $score,
            'xp_earned' => $xpEarned,
            'badge_earned' => $progress->badge_earned,
        ];
    }
}
