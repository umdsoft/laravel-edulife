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
        $event->progress->user->studentProfile->increment('lessons_completed');
    }
}
