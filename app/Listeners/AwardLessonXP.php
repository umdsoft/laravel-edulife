<?php

namespace App\Listeners;

use App\Events\LessonCompleted;
use App\Services\XPRewardService;

class AwardLessonXP
{
    public function __construct(
        protected XPRewardService $xpService
    ) {}
    
    public function handle(LessonCompleted $event): void
    {
        $this->xpService->awardLessonXP($event->progress);
        
        // Update student profile lessons count
        $user = $event->progress->user;
        $user->studentProfile->increment('lessons_completed');

        // daily missions ("1 ta dars tugating") and achievements ("Birinchi qadam")
        app(\App\Services\GamificationProgress::class)->record($user, 'lesson_complete');
    }
}
