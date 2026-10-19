<?php

namespace App\Notifications;

use App\Models\CourseQuestion;
use App\Notifications\Channels\AppDatabaseChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

class QuestionAnsweredNotification extends Notification
{
    use Queueable;

    public function __construct(
        public CourseQuestion $question,
        public CourseQuestion $reply
    ) {}

    public function via($notifiable): array
    {
        return [AppDatabaseChannel::class];
    }

    public function toApp($notifiable): array
    {
        $course = $this->question->course;

        return [
            'type' => 'question_answered',
            'title' => 'Savolingizga javob berildi',
            'body' => ($course?->title ? $course->title.': ' : '').Str::limit(strip_tags((string) $this->reply->content), 160),
            'action_url' => $course && Route::has('student.learn.qna.index')
                ? route('student.learn.qna.index', $course, false)
                : null,
            'icon' => 'chat',
            'data' => [
                'question_id' => $this->question->id,
                'course_id' => $this->question->course_id,
            ],
        ];
    }

    public function toArray($notifiable): array
    {
        return $this->toApp($notifiable);
    }
}
