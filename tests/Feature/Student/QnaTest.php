<?php

namespace Tests\Feature\Student;

use App\Models\CourseQuestion;
use App\Models\Enrollment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

class QnaTest extends TestCase
{
    use RefreshDatabase, CreatesCourses;

    public function test_enrolled_student_can_ask_and_teacher_is_notified(): void
    {
        $teacher = $this->teacher();
        $course = $this->course($teacher);
        $student = $this->studentUser();
        Enrollment::forceCreate(['user_id' => $student->id, 'course_id' => $course->id, 'access_type' => 'free', 'status' => 'active']);

        $this->actingAs($student)
            ->post(route('student.learn.qna.store', $course), ['content' => 'Bu darsda nima uchun shunday?'])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame(1, CourseQuestion::count());
        $this->assertDatabaseHas('notifications', ['user_id' => $teacher->id, 'type' => 'new_question']);
    }

    public function test_not_enrolled_student_cannot_ask(): void
    {
        $course = $this->course($this->teacher());

        $this->actingAs($this->studentUser())
            ->post(route('student.learn.qna.store', $course), ['content' => 'Savol matni uzunroq'])
            ->assertNotFound();

        $this->assertSame(0, CourseQuestion::count());
    }
}
