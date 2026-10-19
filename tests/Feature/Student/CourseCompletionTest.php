<?php

namespace Tests\Feature\Student;

use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\Question;
use App\Models\Test;
use App\Models\TestAttempt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

/**
 * QA-S-20: quiz lessons and lessons without material could not be completed,
 * so courses stopped at 40% and no certificate was ever issued.
 */
class CourseCompletionTest extends TestCase
{
    use RefreshDatabase, CreatesCourses;

    public function test_passing_the_test_of_a_quiz_lesson_completes_it(): void
    {
        $course = $this->course($this->teacher());
        $module = $this->module($course);
        $quiz = $this->lesson($module, ['type' => 'quiz', 'title' => 'Kinematika testi', 'text_content' => null]);
        $student = $this->studentUser();
        $enrollment = Enrollment::forceCreate(['user_id' => $student->id, 'course_id' => $course->id, 'access_type' => 'free']);

        $test = Test::forceCreate([
            'course_id' => $course->id, 'type' => 'lesson', 'title' => 'Kinematika', 'testable_type' => Lesson::class,
            'testable_id' => $quiz->id, 'questions_count' => 1, 'passing_score' => 50, 'is_active' => true, 'max_attempts' => 0,
        ]);
        $question = Question::forceCreate(['course_id' => $course->id, 'test_id' => $test->id, 'type' => 'single_choice', 'question_text' => 'v = ?']);
        $correct = $question->options()->create(['option_text' => 's/t', 'is_correct' => true]);
        $question->options()->create(['option_text' => 's*t', 'is_correct' => false]);

        $this->actingAs($student)->get(route('student.learn.lesson', [$course, $quiz]))
            ->assertInertia(fn (Assert $page) => $page->has('lessonTests', 1)->where('lessonTests.0.id', $test->id));

        // a quiz lesson is its test: no "complete the lesson first" prerequisite
        $this->actingAs($student)->post(route('student.tests.begin', $test), ['confirm' => true])->assertRedirect();
        $attempt = TestAttempt::where('user_id', $student->id)->firstOrFail();
        $this->actingAs($student)->postJson(route('student.tests.save-answer', $attempt), ['question_id' => $question->id, 'answer' => $correct->id]);
        $this->actingAs($student)->postJson(route('student.tests.submit', $attempt), ['confirm' => true])->assertOk();

        $this->assertTrue((bool) LessonProgress::where('lesson_id', $quiz->id)->value('is_completed'));
        $this->assertEquals(100, $enrollment->fresh()->progress);
        // the completed course issues a certificate
        $this->assertSame(1, \App\Models\Certificate::where('user_id', $student->id)->count());
    }

    /** QA2-S-05: a quiz test passed before the rule existed left the lesson "Boshlanmagan" */
    public function test_an_earlier_passed_quiz_test_completes_the_lesson_when_the_course_is_opened(): void
    {
        $course = $this->course($this->teacher());
        $quiz = $this->lesson($this->module($course), ['type' => 'quiz', 'text_content' => null]);
        $student = $this->studentUser();
        $enrollment = Enrollment::forceCreate(['user_id' => $student->id, 'course_id' => $course->id, 'access_type' => 'free']);
        $test = Test::forceCreate([
            'course_id' => $course->id, 'type' => 'lesson', 'title' => 'Kinematika', 'testable_type' => Lesson::class,
            'testable_id' => $quiz->id, 'questions_count' => 1, 'passing_score' => 50, 'is_active' => true, 'max_attempts' => 0,
        ]);
        TestAttempt::forceCreate([
            'user_id' => $student->id, 'test_id' => $test->id, 'course_id' => $course->id, 'enrollment_id' => $enrollment->id,
            'attempt_number' => 1, 'status' => 'submitted', 'started_at' => now()->subHour(), 'expires_at' => now()->subMinutes(30), 'submitted_at' => now()->subMinutes(50),
            'score' => 100, 'is_passed' => true, 'total_questions' => 1,
        ]);
        $this->assertNull(LessonProgress::where('lesson_id', $quiz->id)->value('is_completed'));

        $this->actingAs($student)->get(route('student.learn.course', $course))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where("lessonProgresses.{$quiz->id}.is_completed", true)
                ->where('enrollment.progress', 100));
    }

    public function test_a_lesson_without_material_can_still_be_completed(): void
    {
        $course = $this->course($this->teacher());
        $video = $this->lesson($this->module($course), ['type' => 'video', 'video_url' => null, 'text_content' => null]);
        $student = $this->studentUser();
        Enrollment::forceCreate(['user_id' => $student->id, 'course_id' => $course->id, 'access_type' => 'free']);

        $this->actingAs($student)->get(route('student.learn.lesson', [$course, $video]))->assertOk();
        $this->actingAs($student)->postJson(route('student.learn.progress.complete', $video))->assertOk();

        $this->assertTrue((bool) LessonProgress::where('lesson_id', $video->id)->value('is_completed'));
    }

    public function test_teacher_cannot_submit_a_course_with_empty_lessons(): void
    {
        Storage::fake('public');
        $teacher = $this->teacher();
        $course = $this->course($teacher, ['status' => 'draft']);
        $module = $this->module($course);
        $this->lesson($module);
        $this->lesson($module, ['type' => 'video', 'title' => 'Bo\'sh video', 'video_url' => null, 'sort_order' => 2]);
        $this->lesson($module, ['type' => 'quiz', 'title' => 'Testsiz', 'sort_order' => 3]);
        $this->actingAs($teacher)->post(route('teacher.courses.thumbnail', $course), ['thumbnail' => UploadedFile::fake()->image('c.jpg')]);

        $this->actingAs($teacher)->patch(route('teacher.courses.submit', $course))
            ->assertSessionHas('error', fn ($message) => str_contains($message, "«Bo'sh video»") && str_contains($message, '«Testsiz»'));
        $this->assertSame('draft', $course->fresh()->status);
    }
}
