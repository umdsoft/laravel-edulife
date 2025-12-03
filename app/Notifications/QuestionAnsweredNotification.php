<?php

namespace App\Notifications;

use App\Models\CourseQuestion;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class QuestionAnsweredNotification extends Notification
{
    use Queueable;
    
    public function __construct(
        public CourseQuestion $question,
        public CourseQuestion $reply
    ) {}
    
    public function via($notifiable): array
    {
        return ['database'];
    }
    
    public function toArray($notifiable): array
    {
        return [
            'type' => 'question_answered',
            'question_id' => $this->question->id,
            'course_id' => $this->question->course_id,
            'course_title' => $this->question->course->title,
            'message' => 'Savolingizga javob berildi',
        ];
    }
}
