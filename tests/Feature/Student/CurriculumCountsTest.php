<?php

namespace Tests\Feature\Student;

use App\Models\Enrollment;
use App\Models\LessonProgress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

/**
 * QA-S-19: the course said "6 ta dars" after a lesson was deleted (5 left), and
 * the notes / Q&A / announcements sidebar showed "0/5" with every lesson
 * "Boshlanmagan" because those pages never got the student's progress.
 */
class CurriculumCountsTest extends TestCase
{
    use RefreshDatabase, CreatesCourses;

    public function test_lesson_counters_follow_created_and_deleted_lessons(): void
    {
        $course = $this->course($this->teacher());
        $module = $this->module($course);
        $first = $this->lesson($module);
        $this->lesson($module, ['title' => 'Ikkinchi', 'sort_order' => 2]);

        $this->assertSame(2, $course->fresh()->lessons_count);
        $this->assertSame(1, $course->fresh()->modules_count);
        $this->assertSame(2, $module->fresh()->lessons_count);

        $first->delete();

        $this->assertSame(1, $course->fresh()->lessons_count);
        $this->assertSame(1, $module->fresh()->lessons_count);
    }

    public function test_notes_qna_and_announcements_pages_carry_the_students_progress(): void
    {
        $course = $this->course($this->teacher());
        $module = $this->module($course);
        $done = $this->lesson($module);
        $this->lesson($module, ['title' => 'Ikkinchi', 'sort_order' => 2]);
        $student = $this->studentUser();
        $enrollment = Enrollment::forceCreate(['user_id' => $student->id, 'course_id' => $course->id, 'access_type' => 'free']);
        LessonProgress::forceCreate([
            'user_id' => $student->id, 'lesson_id' => $done->id, 'course_id' => $course->id,
            'enrollment_id' => $enrollment->id, 'is_completed' => true, 'total_duration' => 0,
        ]);

        foreach (['student.learn.notes.index', 'student.learn.qna.index', 'student.learn.announcements.index'] as $name) {
            $this->actingAs($student)->get(route($name, $course->id))
                ->assertOk()
                ->assertInertia(fn (Assert $page) => $page
                    ->where("lessonProgresses.{$done->id}.is_completed", true)
                    ->where("moduleProgress.{$module->id}.completed", 1)
                    ->where("moduleProgress.{$module->id}.total", 2)
                    ->has('course.modules.0.lessons', 2));
        }
    }
}
