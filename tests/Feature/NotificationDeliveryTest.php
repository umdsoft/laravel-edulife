<?php

namespace Tests\Feature;

use App\Jobs\SendAnnouncementNotificationsJob;
use App\Models\CourseAnnouncement;
use App\Models\CourseQuestion;
use App\Models\Enrollment;
use App\Models\Notification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

/**
 * Laravel notifications are stored in the app's own notifications table
 * (user_id, title, body, action_url), not Laravel's notifiable/data schema.
 */
class NotificationDeliveryTest extends TestCase
{
    use RefreshDatabase, CreatesCourses;

    public function test_teacher_reply_notifies_the_student(): void
    {
        $teacher = $this->teacher();
        $student = $this->studentUser();
        $course = $this->course($teacher);
        Enrollment::forceCreate(['user_id' => $student->id, 'course_id' => $course->id, 'access_type' => 'free']);
        $question = CourseQuestion::forceCreate(['course_id' => $course->id, 'user_id' => $student->id, 'content' => 'Savolim bor']);

        $this->actingAs($teacher)->post(route('teacher.courses.qna.reply', [$course, $question]), [
            'content' => 'Mana javob',
        ])->assertSessionHasNoErrors();

        $notification = Notification::where('user_id', $student->id)->firstOrFail();
        $this->assertSame('question_answered', $notification->type);
        $this->assertNotEmpty($notification->title);
        $this->assertFalse($notification->is_read);
    }

    public function test_announcement_notifies_enrolled_students(): void
    {
        $teacher = $this->teacher();
        $course = $this->course($teacher);
        $students = [$this->studentUser(), $this->studentUser()];
        foreach ($students as $s) {
            Enrollment::forceCreate(['user_id' => $s->id, 'course_id' => $course->id, 'access_type' => 'free']);
        }
        $announcement = CourseAnnouncement::forceCreate([
            'course_id' => $course->id, 'teacher_id' => $teacher->id, 'title' => 'Shanba imtihoni', 'content' => 'Matn', 'published_at' => now(),
        ]);

        SendAnnouncementNotificationsJob::dispatchSync($announcement);

        $this->assertSame(2, Notification::where('type', 'course_announcement')->count());
        $this->assertSame('Shanba imtihoni', Notification::where('user_id', $students[0]->id)->value('title'));
        $this->assertStringContainsString('/announcements', Notification::where('user_id', $students[0]->id)->value('action_url'));
    }
}
