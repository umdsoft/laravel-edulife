<?php

namespace App\Listeners\Exam;

use App\Events\Exam\ExamAttemptGraded;
use App\Services\Exam\LeaderboardService;
use App\Services\Exam\ReferralService;

/**
 * ExamAttemptGraded (stage 5):
 *  - a graded placement attempt moves the student's referral to placement_done;
 *  - any graded attempt invalidates the cached exam leaderboards.
 * Registered in AppServiceProvider (event discovery is off).
 */
class AdvanceReferralOnPlacement
{
    public function __construct(private readonly ReferralService $referrals) {}

    public function handle(ExamAttemptGraded $event): void
    {
        LeaderboardService::flush();

        if ($event->regraded) {
            return;
        }

        $this->referrals->onAttemptGraded($event->attempt);
    }
}
