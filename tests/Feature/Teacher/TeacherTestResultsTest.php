<?php

namespace Tests\Feature\Teacher;

use App\Models\Enrollment;
use App\Models\Question;
use App\Models\Test;
use App\Models\TestAnswer;
use App\Models\TestAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

class TeacherTestResultsTest extends TestCase
{
    use RefreshDatabase, CreatesCourses;

    public function test_course_show_redirects_to_the_course_workspace(): void
    {
        $teacher = $this->teacher();
        $course = $this->course($teacher);

        $this->actingAs($teacher)->get(route('teacher.courses.show', $course))
            ->assertRedirect(route('teacher.courses.edit', $course));

        $this->actingAs($this->teacher())->get(route('teacher.courses.show', $course))->assertForbidden();
    }

    public function test_test_results_page_shows_attempt_and_question_stats(): void
    {
        $teacher = $this->teacher();
        $course = $this->course($teacher);
        $test = Test::forceCreate(['course_id' => $course->id, 'type' => 'final', 'title' => 'Yakuniy', 'passing_score' => 60]);
        $easy = Question::forceCreate(['course_id' => $course->id, 'test_id' => $test->id, 'type' => 'single_choice', 'question_text' => 'Oson']);
        $hard = Question::forceCreate(['course_id' => $course->id, 'test_id' => $test->id, 'type' => 'single_choice', 'question_text' => 'Qiyin']);

        foreach ([[100, true, true, true], [50, false, true, false]] as [$score, $passed, $easyOk, $hardOk]) {
            $student = User::factory()->create(['role' => 'student']);
            $enrollment = Enrollment::forceCreate(['user_id' => $student->id, 'course_id' => $course->id, 'access_type' => 'free']);
            $attempt = TestAttempt::forceCreate([
                'user_id' => $student->id,
                'test_id' => $test->id,
                'course_id' => $course->id,
                'enrollment_id' => $enrollment->id,
                'status' => 'submitted',
                'started_at' => now()->subMinutes(4),
                'submitted_at' => now(),
                'expires_at' => now()->addHour(),
                'time_spent' => 240,
                'score' => $score,
                'is_passed' => $passed,
            ]);
            TestAnswer::forceCreate(['attempt_id' => $attempt->id, 'question_id' => $easy->id, 'is_answered' => true, 'is_correct' => $easyOk]);
            TestAnswer::forceCreate(['attempt_id' => $attempt->id, 'question_id' => $hard->id, 'is_answered' => true, 'is_correct' => $hardOk]);
        }

        $this->actingAs($teacher)->get(route('teacher.courses.tests.show', [$course, $test]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Teacher/Courses/Tests/Show')
                ->where('stats.total_attempts', 2)
                ->where('stats.students', 2)
                ->where('stats.avg_score', 75)
                ->where('stats.pass_rate', 50)
                ->where('stats.avg_time', 240)
                ->has('attempts', 2)
                ->where('questions.0.question_text', 'Qiyin')
                ->where('questions.0.correct_rate', 50)
                ->where('questions.1.correct_rate', 100));
    }
}
