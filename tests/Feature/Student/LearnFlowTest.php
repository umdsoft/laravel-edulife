<?php

namespace Tests\Feature\Student;

use App\Models\CourseQuestion;
use App\Models\Enrollment;
use App\Models\LessonProgress;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

/**
 * QA-S-05 / QA-S-06: an enrolled student could open neither the course
 * player nor any lesson (500).
 */
class LearnFlowTest extends TestCase
{
    use RefreshDatabase, CreatesCourses;

    public function test_enrolled_student_opens_the_course_and_a_text_lesson(): void
    {
        $course = $this->course($this->teacher());
        $lesson = $this->lesson($this->module($course), ['type' => 'text']);
        $student = $this->studentUser();
        Enrollment::forceCreate(['user_id' => $student->id, 'course_id' => $course->id, 'access_type' => 'free']);

        CourseQuestion::forceCreate(['course_id' => $course->id, 'user_id' => $student->id, 'content' => 'Javobsiz savol']);
        CourseQuestion::forceCreate(['course_id' => $course->id, 'user_id' => $student->id, 'content' => 'Javobli', 'is_answered' => true]);

        $this->actingAs($student)->get(route('student.learn.course', $course))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Student/Learn/Course')->where('qnaCount', 1));

        $this->actingAs($student)->get(route('student.learn.lesson', [$course, $lesson]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Student/Learn/Lesson'));

        $this->assertSame(0, LessonProgress::where('lesson_id', $lesson->id)->value('total_duration'));

        // finishing the only lesson completes the course
        $this->actingAs($student)->postJson(route('student.learn.progress.complete', $lesson))->assertOk();
        $enrollment = Enrollment::where('user_id', $student->id)->firstOrFail();
        $this->assertEquals(100, $enrollment->progress);
        $this->assertSame('completed', $enrollment->status);
    }
}
