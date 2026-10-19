<?php

namespace Tests\Feature\Teacher;

use App\Models\Enrollment;
use App\Models\LessonProgress;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

class TeacherStudentsTest extends TestCase
{
    use RefreshDatabase, CreatesCourses;

    private function enroll(User $student, $course, array $attributes = []): Enrollment
    {
        return Enrollment::forceCreate(array_merge([
            'user_id' => $student->id,
            'course_id' => $course->id,
            'access_type' => 'free',
            'status' => 'active',
        ], $attributes));
    }

    public function test_students_list_is_filtered_by_the_backend(): void
    {
        $teacher = $this->teacher();
        $algebra = $this->course($teacher, ['title' => 'Algebra']);
        $physics = $this->course($teacher, ['title' => 'Fizika']);

        $ali = User::factory()->create(['role' => 'student', 'first_name' => 'Ali', 'last_name' => 'Valiyev']);
        $vali = User::factory()->create(['role' => 'student', 'first_name' => 'Vali', 'last_name' => 'Aliyev']);
        $sara = User::factory()->create(['role' => 'student', 'first_name' => 'Sara', 'last_name' => 'Karimova']);

        $this->enroll($ali, $algebra);
        $this->enroll($vali, $physics, ['status' => 'completed']);
        $this->enroll($sara, $algebra);

        // 25 more so the list spans two pages: filtering must not be page-local
        foreach (range(1, 25) as $i) {
            $this->enroll(User::factory()->create(['role' => 'student', 'first_name' => "Talaba{$i}", 'last_name' => 'X']), $physics);
        }

        $names = fn ($response) => collect($response->viewData('page')['props']['enrollments']['data'])
            ->map(fn ($e) => $e['user']['first_name'])->sort()->values()->all();

        $response = $this->actingAs($teacher)->get(route('teacher.students.index', ['search' => 'karimova sa']));
        $this->assertSame(['Sara'], $names($response));

        $response = $this->actingAs($teacher)->get(route('teacher.students.index', ['course' => $algebra->id]));
        $this->assertSame(['Ali', 'Sara'], $names($response));

        $response = $this->actingAs($teacher)->get(route('teacher.students.index', ['status' => 'completed']));
        $this->assertSame(['Vali'], $names($response));

        $this->actingAs($teacher)->get(route('teacher.students.index', ['status' => 'completed']))
            ->assertInertia(fn (Assert $page) => $page
                ->where('filters.status', 'completed')
                ->has('courses', 2));
    }

    public function test_teacher_sees_a_student_progress_page(): void
    {
        $teacher = $this->teacher();
        $course = $this->course($teacher);
        $module = $this->module($course);
        $done = $this->lesson($module, ['title' => 'Birinchi']);
        $this->lesson($module, ['title' => 'Ikkinchi', 'sort_order' => 2]);
        $student = $this->studentUser();
        $enrollment = $this->enroll($student, $course);

        LessonProgress::forceCreate([
            'user_id' => $student->id,
            'enrollment_id' => $enrollment->id,
            'course_id' => $course->id,
            'lesson_id' => $done->id,
            'is_completed' => true,
            'completed_at' => now(),
            'watch_time' => 600,
        ]);
        $test = Test::forceCreate(['course_id' => $course->id, 'type' => 'final', 'title' => 'Yakuniy']);
        TestAttempt::forceCreate([
            'user_id' => $student->id,
            'test_id' => $test->id,
            'course_id' => $course->id,
            'enrollment_id' => $enrollment->id,
            'status' => 'submitted',
            'started_at' => now()->subMinutes(5),
            'submitted_at' => now(),
            'expires_at' => now()->addMinutes(25),
            'score' => 90,
            'is_passed' => true,
        ]);

        $this->actingAs($teacher)->get(route('teacher.students.show', $enrollment))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Teacher/Students/Show')
                ->where('summary.total_lessons', 2)
                ->where('summary.completed_lessons', 1)
                ->where('summary.tests_passed', 1)
                ->where('enrollment.total_watch_time', 600)
                ->where('modules.0.lessons.0.is_completed', true)
                ->where('modules.0.lessons.1.is_completed', false)
                ->where('attempts.0.test_title', 'Yakuniy'));
    }

    public function test_teacher_cannot_open_another_teachers_student(): void
    {
        $owner = $this->teacher();
        $enrollment = $this->enroll($this->studentUser(), $this->course($owner));

        $this->actingAs($this->teacher())->get(route('teacher.students.show', $enrollment))->assertForbidden();
    }
}
