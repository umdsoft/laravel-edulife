<?php

namespace App\Services\English;

use App\Models\English\EnglishTopic;
use App\Models\English\UserEnglishProfile;
use App\Models\User;
use Illuminate\Support\Collection;

class TopicService
{
    public function __construct(
        private LevelService $levelService
    ) {
    }

    /**
     * Get topics for a level with progress
     */
    public function getTopicsForLevel(string $levelId, User $user): Collection
    {
        $profile = $this->levelService->getOrCreateProfile($user);

        $topics = EnglishTopic::with(['units.lessons'])
            ->where('level_id', $levelId)
            ->where('is_active', true)
            ->orderBy('order_number')
            ->get();

        return $topics->map(function ($topic, $index) use ($profile, $topics) {
            $topic->is_unlocked = $this->isTopicUnlocked($topic, $index, $topics, $profile);
            $topic->progress = $this->calculateTopicProgress($topic, $profile);
            $topic->stats = $this->getTopicStats($topic);
            return $topic;
        });
    }

    /**
     * Check if topic is unlocked
     */
    public function isTopicUnlocked(EnglishTopic $topic, int $index, Collection $allTopics, UserEnglishProfile $profile): bool
    {
        if ($index === 0)
            return true;

        $previousTopic = $allTopics[$index - 1] ?? null;

        if (!$previousTopic)
            return true;

        return $this->calculateTopicProgress($previousTopic, $profile) >= 80;
    }

    /**
     * Calculate topic progress
     */
    public function calculateTopicProgress(EnglishTopic $topic, UserEnglishProfile $profile): float
    {
        $totalLessons = 0;
        $completedLessons = 0;

        foreach ($topic->units as $unit) {
            $totalLessons += $unit->lessons->count();
            $completedLessons += $unit->lessons->filter(function ($lesson) use ($profile) {
                return $lesson->userProgress()
                    ->where('user_id', $profile->user_id)
                    ->where('status', 'completed')
                    ->exists();
            })->count();
        }

        return $totalLessons > 0 ? round(($completedLessons / $totalLessons) * 100, 2) : 0;
    }

    /**
     * Get topic statistics
     */
    public function getTopicStats(EnglishTopic $topic): array
    {
        return [
            'units_count' => $topic->units->count(),
            'lessons_count' => $topic->units->sum(fn($u) => $u->lessons->count()),
            'estimated_minutes' => $topic->units->sum(fn($u) => $u->lessons->sum('estimated_minutes')),
        ];
    }
}
