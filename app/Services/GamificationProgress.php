<?php

namespace App\Services;

use App\Models\User;

/**
 * One entry point for "the student did something": advances today's daily
 * missions and unlocks achievements. Called from lesson completion, test
 * passing, course completion and reviews (battles call the two services
 * directly). Never breaks the action that triggered it: a failure is logged.
 */
class GamificationProgress
{
    public function __construct(
        protected MissionService $missions,
        protected AchievementService $achievements,
    ) {}

    /**
     * @param string $event lesson_complete | test_pass | course_complete | review | ...
     */
    public function record(?User $user, string $event, array $data = []): void
    {
        if (!$user || $user->role !== 'student') {
            return;
        }

        try {
            $this->missions->updateProgress($user, $event);
            $this->achievements->checkAchievements($user, $event, $data);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
