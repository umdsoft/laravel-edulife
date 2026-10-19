<?php

namespace Tests\Feature\Student;

use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Question;
use App\Models\Test;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

class StudentTestsIndexTest extends TestCase
{
    use RefreshDatabase, CreatesCourses;

    private function makeTest($course, array $attributes): Test
    {
        $test = Test::forceCreate(array_merge(['course_id' => $course->id, 'title' => 'Test', 'is_active' => true, 'questions_count' => 1, 'passing_score' => 86], $attributes));
        Question::forceCreate(['course_id' => $course->id, 'test_id' => $test->id, 'type' => 'single_choice', 'question_text' => '1+1'])
            ->options()->create(['option_text' => '2', 'is_correct' => true]);

        return $test;
    }

    /** the page 500'd (module/lesson relations) and grouped by *_test types that the DB never stores */
    public function test_tests_are_listed_by_type(): void
    {
        $course = $this->course($this->teacher());
        $lesson = $this->lesson($this->module($course));
        $student = $this->studentUser();
        Enrollment::forceCreate(['user_id' => $student->id, 'course_id' => $course->id, 'access_type' => 'free']);

        $this->makeTest($course, ['type' => 'lesson', 'title' => 'Dars testi', 'testable_type' => Lesson::class, 'testable_id' => $lesson->id]);
        $this->makeTest($course, ['type' => 'final', 'title' => 'Yakuniy']);

        $this->actingAs($student)->get(route('student.tests.index', $course))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Student/Tests/Index')
                ->has('tests.lesson_tests', 1)
                ->where('tests.lesson_tests.0.title', 'Dars testi')
                ->where('tests.lesson_tests.0.pass_rate', 86)
                ->where('tests.final_test.title', 'Yakuniy'));
    }

    public function test_lesson_test_opens_after_the_lesson_is_completed(): void
    {
        $course = $this->course($this->teacher());
        $lesson = $this->lesson($this->module($course));
        $student = $this->studentUser();
        $enrollment = Enrollment::forceCreate(['user_id' => $student->id, 'course_id' => $course->id, 'access_type' => 'free']);
        $test = $this->makeTest($course, ['type' => 'lesson', 'testable_type' => Lesson::class, 'testable_id' => $lesson->id]);

        $this->actingAs($student)->get(route('student.tests.start', $test))
            ->assertInertia(fn (Assert $page) => $page->where('canStart', false)->where('prerequisiteMessage', 'Testni boshlashdan oldin darsni yakunlang.'));
        $this->actingAs($student)->post(route('student.tests.begin', $test), ['confirm' => true])
            ->assertSessionHas('error');

        LessonProgress::forceCreate([
            'user_id' => $student->id, 'enrollment_id' => $enrollment->id, 'course_id' => $course->id,
            'lesson_id' => $lesson->id, 'is_completed' => true, 'completed_at' => now(),
        ]);

        $this->actingAs($student)->get(route('student.tests.start', $test))
            ->assertInertia(fn (Assert $page) => $page->where('canStart', true));
        $this->actingAs($student)->post(route('student.tests.begin', $test), ['confirm' => true])
            ->assertRedirect();
        $this->assertSame(1, $test->attempts()->count());
    }
}
