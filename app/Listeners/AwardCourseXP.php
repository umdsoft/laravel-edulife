<?php

namespace App\Listeners;

use App\Events\CourseCompleted;
use App\Services\XPRewardService;
use App\Services\CertificateService;

class AwardCourseXP
{
    public function __construct(
        protected XPRewardService $xpService,
        protected CertificateService $certificateService
    ) {}
    
    public function handle(CourseCompleted $event): void
    {
        $this->xpService->awardCourseXP($event->enrollment);

        // "Kurs tugadi" and friends
        app(\App\Services\GamificationProgress::class)->record($event->enrollment->user, 'course_complete');
        
        // Generate certificate
        $this->certificateService->generate($event->enrollment);
    }
}
