<?php

namespace App\Notifications;

use App\Models\CourseAnnouncement;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CourseAnnouncementNotification extends Notification
{
    use Queueable;
    
    public function __construct(
        public CourseAnnouncement $announcement
    ) {}
    
    public function via($notifiable): array
    {
        return ['database'];
    }
    
    public function toArray($notifiable): array
    {
        return [
            'type' => 'course_announcement',
            'announcement_id' => $this->announcement->id,
            'course_id' => $this->announcement->course_id,
            'course_title' => $this->announcement->course->title,
            'title' => $this->announcement->title,
            'announcement_type' => $this->announcement->type,
        ];
    }
}
