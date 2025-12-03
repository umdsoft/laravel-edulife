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
        
        // Generate certificate
        $this->certificateService->generate($event->enrollment);
    }
}
