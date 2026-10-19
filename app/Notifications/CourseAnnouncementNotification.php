<?php

namespace App\Notifications;

use App\Models\CourseAnnouncement;
use App\Notifications\Channels\AppDatabaseChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class CourseAnnouncementNotification extends Notification
{
    use Queueable;

    public function __construct(
        public CourseAnnouncement $announcement
    ) {}

    public function via($notifiable): array
    {
        return [AppDatabaseChannel::class];
    }

    public function toApp($notifiable): array
    {
        $course = $this->announcement->course;

        return [
            'type' => 'course_announcement',
            'title' => $this->announcement->title,
            'body' => $course?->title.': '.Str::limit(strip_tags((string) $this->announcement->content), 160),
            'action_url' => $course && Route::has('student.learn.announcements.index')
                ? route('student.learn.announcements.index', $course, false)
                : null,
            'icon' => 'megaphone',
            'priority' => $this->announcement->type === 'important' ? 'high' : 'normal',
            'data' => [
                'announcement_id' => $this->announcement->id,
                'course_id' => $this->announcement->course_id,
                'announcement_type' => $this->announcement->type,
            ],
        ];
    }

    public function toArray($notifiable): array
    {
        return $this->toApp($notifiable);
    }
}
