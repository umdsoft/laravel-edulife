<?php

namespace App\Jobs;

use App\Models\CourseAnnouncement;
use App\Notifications\CourseAnnouncementNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendAnnouncementNotificationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public function __construct(
        public CourseAnnouncement $announcement
    ) {}
    
    public function handle(): void
    {
        $enrollments = $this->announcement->course->enrollments()
            ->with('user')
            ->get();
        
        foreach ($enrollments as $enrollment) {
            $enrollment->user->notify(
                new CourseAnnouncementNotification($this->announcement)
            );
        }
    }
}
