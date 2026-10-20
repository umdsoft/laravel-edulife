<?php

namespace Tests\Feature\Smoke;

use App\Models\CourseAnnouncement;
use App\Models\Enrollment;
use App\Models\LessonAttachment;
use App\Models\Notification;
use App\Models\Question;
use App\Models\Review;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Models\LessonProgress;
use App\Models\Video;
use App\Models\VideoChapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCourses;
use Tests\Concerns\SmokesRoutes;
use Tests\TestCase;

/**
 * Every teacher-panel GET page renders (no 5xx, no missing Vue page) with
 * realistic data in place.
 */
class TeacherPagesSmokeTest extends TestCase
{
    use RefreshDatabase, CreatesCourses, SmokesRoutes;

    public function test_every_teacher_page_renders(): void
    {
        $teacher = $this->teacher();
        $student = $this->studentUser();
        $course = $this->course($teacher);
        $module = $this->module($course);
        $lesson = $this->lesson($module, ['type' => 'video']);

        $enrollment = Enrollment::forceCreate([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'access_type' => 'free',
            'status' => 'active',
            'last_activity_at' => now(),
        ]);

        LessonProgress::forceCreate([
            'user_id' => $student->id,
            'lesson_id' => $lesson->id,
            'enrollment_id' => $enrollment->id,
            'course_id' => $course->id,
            'watch_time' => 120,
            'watch_percentage' => 50,
        ]);

        $test = Test::forceCreate([
            'course_id' => $course->id,
            'type' => 'final',
            'title' => 'Yakuniy test',
            'questions_count' => 1,
        ]);
        $question = Question::forceCreate([
            'course_id' => $course->id,
            'test_id' => $test->id,
            'type' => 'single_choice',
            'question_text' => '2 + 2 = ?',
        ]);
        $question->options()->create(['option_text' => '4', 'is_correct' => true]);

        TestAttempt::forceCreate([
            'user_id' => $student->id,
            'test_id' => $test->id,
            'course_id' => $course->id,
            'enrollment_id' => $enrollment->id,
            'status' => 'submitted',
            'started_at' => now()->subMinutes(10),
            'submitted_at' => now(),
            'expires_at' => now()->addMinutes(20),
            'score' => 100,
            'is_passed' => true,
        ]);

        Review::forceCreate([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'enrollment_id' => $enrollment->id,
            'rating' => 5,
            'review_text' => 'Zo\'r kurs',
            'status' => 'approved',
        ]);

        $video = Video::forceCreate([
            'lesson_id' => $lesson->id,
            'original_path' => 'videos/a.mp4',
            'original_name' => 'a.mp4',
            'size' => 100,
            'mime_type' => 'video/mp4',
            'status' => 'completed',
        ]);
        $chapter = VideoChapter::forceCreate([
            'video_id' => $video->id,
            'lesson_id' => $lesson->id,
            'title' => 'Kirish',
            'timestamp' => 0,
        ]);
        $attachment = LessonAttachment::forceCreate([
            'lesson_id' => $lesson->id,
            'title' => 'Konspekt',
            'file_name' => 'k.pdf',
            'file_path' => 'attachments/k.pdf',
            'file_type' => 'pdf',
            'mime_type' => 'application/pdf',
            'file_size' => 100,
        ]);
        $announcement = CourseAnnouncement::forceCreate([
            'course_id' => $course->id,
            'teacher_id' => $teacher->id,
            'title' => 'E\'lon',
            'content' => 'Matn',
        ]);
        Notification::forceCreate([
            'user_id' => $teacher->id,
            'type' => 'system',
            'title' => 'Salom',
        ]);

        // --- exam: stage 5 fixtures (blueprints / sessions) ---
        $examBlueprint = \App\Models\Exam\ExamBlueprint::factory()->create();
        $examSession = \App\Models\Exam\ExamSession::factory()->create([
            'subject_id' => $examBlueprint->subject_id, 'blueprint_id' => $examBlueprint->id,
        ]);
        // --- end exam: stage 5 ---

        $this->smokeGetRoutes($teacher, 'teacher.', [
            'blueprint' => $examBlueprint->id, // exam stage 5
            'session' => $examSession->id,     // exam stage 5
            'course' => $course->id,
            'module' => $module->id,
            'lesson' => $lesson->id,
            'test' => $test->id,
            'question' => $question->id,
            'enrollment' => $enrollment->id,
            'attachment' => $attachment->id,
            'chapter' => $chapter->id,
            'announcement' => $announcement->id,
        ]);
    }
}
