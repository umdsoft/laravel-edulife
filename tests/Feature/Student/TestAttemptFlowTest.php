<?php

namespace Tests\Feature\Student;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Question;
use App\Models\QuestionOption;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

class TestAttemptFlowTest extends TestCase
{
    use RefreshDatabase, CreatesCourses;

    private User $student;
    private Test $test;
    /** @var array<int, array{question: Question, correct: QuestionOption, wrong: QuestionOption}> */
    private array $questions = [];

    protected function setUp(): void
    {
        parent::setUp();

        $course = $this->course($this->teacher());
        $this->student = $this->studentUser();

        Enrollment::forceCreate([
            'user_id' => $this->student->id,
            'course_id' => $course->id,
            'access_type' => 'free',
            'status' => 'active',
            'progress' => 100, // the final test opens at 80%
        ]);

        $this->test = Test::forceCreate([
            'course_id' => $course->id,
            'type' => 'final',
            'testable_type' => Course::class,
            'testable_id' => $course->id,
            'title' => 'Yakuniy test',
            'questions_count' => 2,
            'time_limit' => 30,
            'passing_score' => 60,
            'max_attempts' => 3,
            'shuffle_questions' => false,
            'is_active' => true,
        ]);

        foreach ([1, 2] as $i) {
            $question = Question::forceCreate([
                'course_id' => $course->id,
                'test_id' => $this->test->id,
                'type' => 'single_choice',
                'question_text' => "Savol $i",
                'points' => 1,
                'sort_order' => $i,
            ]);
            $correct = QuestionOption::forceCreate(['question_id' => $question->id, 'option_text' => 'To\'g\'ri', 'is_correct' => true, 'sort_order' => 1]);
            $wrong = QuestionOption::forceCreate(['question_id' => $question->id, 'option_text' => 'Noto\'g\'ri', 'is_correct' => false, 'sort_order' => 2]);
            $this->questions[] = compact('question', 'correct', 'wrong');
        }
    }

    private function beginAttempt(): TestAttempt
    {
        $this->actingAs($this->student)
            ->post(route('student.tests.begin', $this->test), ['confirm' => true])
            ->assertRedirect();

        return TestAttempt::where('user_id', $this->student->id)->orderByDesc('attempt_number')->firstOrFail();
    }

    private function answer(TestAttempt $attempt, int $index, bool $correct): void
    {
        $q = $this->questions[$index];
        $this->actingAs($this->student)
            ->postJson(route('student.tests.save-answer', $attempt), [
                'question_id' => $q['question']->id,
                'answer' => $correct ? $q['correct']->id : $q['wrong']->id,
            ])
            ->assertOk();
    }

    public function test_attempt_page_shows_question_text_without_correct_flags(): void
    {
        $attempt = $this->beginAttempt();

        $this->actingAs($this->student)
            ->get(route('student.tests.attempt', $attempt))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('questions.0.content', 'Savol 1')
                ->missing('questions.0.options.0.is_correct'));
    }

    public function test_score_below_passing_score_fails(): void
    {
        $attempt = $this->beginAttempt();
        $this->answer($attempt, 0, true);
        $this->answer($attempt, 1, false);

        $this->actingAs($this->student)
            ->postJson(route('student.tests.submit', $attempt), ['confirm' => true])
            ->assertOk();

        $attempt->refresh();
        $this->assertEquals(50, $attempt->score);
        $this->assertFalse($attempt->is_passed, '50% must not pass a 60% test');
        $this->assertSame(0, $this->student->studentProfile()->first()->xp);
    }

    public function test_passing_awards_xp_once_even_on_double_submit(): void
    {
        $attempt = $this->beginAttempt();
        $this->answer($attempt, 0, true);
        $this->answer($attempt, 1, true);

        $this->actingAs($this->student)
            ->postJson(route('student.tests.submit', $attempt), ['confirm' => true])
            ->assertOk();
        $this->actingAs($this->student)
            ->postJson(route('student.tests.submit', $attempt), ['confirm' => true])
            ->assertStatus(400);

        $attempt->refresh();
        $this->assertTrue($attempt->is_passed);
        $profile = $this->student->studentProfile()->first();
        $this->assertSame(1, $profile->tests_passed);
        $this->assertSame($attempt->xp_amount, $profile->xp);
    }

    /** QA3-A-01: "Test uchun XP / coin" from the admin settings were never used (always 40 XP, 0 coins) */
    public function test_passing_reward_follows_the_admin_settings(): void
    {
        \App\Models\Setting::forceCreate(['key' => 'xp_per_test_pass', 'group' => 'xp', 'value' => '40', 'type' => 'integer']);
        \App\Models\Setting::forceCreate(['key' => 'coin_per_test_pass', 'group' => 'coin', 'value' => '0', 'type' => 'integer']);
        \App\Models\Setting::get('xp_per_test_pass'); // cached before the admin edits it

        $this->actingAs(User::factory()->create(['role' => 'admin', 'status' => 'active']))
            ->put(route('admin.settings.update'), ['settings' => [
                ['key' => 'xp.xp_per_test_pass', 'value' => 100],
                ['key' => 'coin.coin_per_test_pass', 'value' => 25],
            ]])->assertSessionHas('success');

        $attempt = $this->beginAttempt();
        $this->answer($attempt, 0, true);
        $this->answer($attempt, 1, true);
        $this->actingAs($this->student)->postJson(route('student.tests.submit', $attempt), ['confirm' => true])->assertOk();

        $this->assertSame(100, $attempt->fresh()->xp_amount);
        $profile = $this->student->studentProfile()->first();
        $this->assertSame(100, $profile->xp);
        $this->assertSame(25, $profile->coins);
        $this->assertDatabaseHas('coin_transactions', ['user_id' => $this->student->id, 'source' => 'test_pass', 'amount' => 25]);
    }

    public function test_answers_cannot_be_changed_after_submit(): void
    {
        $attempt = $this->beginAttempt();
        $this->answer($attempt, 0, false);
        $this->actingAs($this->student)->postJson(route('student.tests.submit', $attempt), ['confirm' => true]);

        $this->actingAs($this->student)
            ->postJson(route('student.tests.save-answer', $attempt), [
                'question_id' => $this->questions[0]['question']->id,
                'answer' => $this->questions[0]['correct']->id,
            ])
            ->assertStatus(400);
    }

    public function test_other_student_cannot_view_or_submit_attempt(): void
    {
        $attempt = $this->beginAttempt();
        $intruder = $this->studentUser();

        $this->actingAs($intruder)->get(route('student.tests.attempt', $attempt))->assertForbidden();
        $this->actingAs($intruder)->postJson(route('student.tests.submit', $attempt), ['confirm' => true])->assertForbidden();
    }

    private function submitAttempt(TestAttempt $attempt): void
    {
        $this->actingAs($this->student)
            ->postJson(route('student.tests.submit', $attempt), ['confirm' => true])
            ->assertOk();
    }

    public function test_max_attempts_limit_is_enforced(): void
    {
        $this->test->update(['max_attempts' => 1]);
        $this->submitAttempt($this->beginAttempt());

        $this->actingAs($this->student)
            ->post(route('student.tests.begin', $this->test), ['confirm' => true])
            ->assertSessionHas('error');
        $this->assertSame(1, TestAttempt::where('user_id', $this->student->id)->count());

        $this->actingAs($this->student)
            ->get(route('student.tests.start', $this->test))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('canStart', false)->where('remainingAttempts', 0));
    }

    public function test_zero_max_attempts_means_unlimited(): void
    {
        $this->test->update(['max_attempts' => 0]);

        foreach (range(1, 4) as $i) {
            $this->submitAttempt($this->beginAttempt());
        }
        $this->assertSame(4, TestAttempt::where('user_id', $this->student->id)->count());

        $this->actingAs($this->student)
            ->get(route('student.tests.start', $this->test))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->where('canStart', true)->where('remainingAttempts', null));
    }

    public function test_inactive_test_cannot_be_started(): void
    {
        $this->test->update(['is_active' => false]);

        $this->actingAs($this->student)
            ->post(route('student.tests.begin', $this->test), ['confirm' => true])
            ->assertNotFound();
    }

    public function test_start_page_lists_the_question_types_of_this_test(): void
    {
        $this->actingAs($this->student)
            ->get(route('student.tests.start', $this->test))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('questionTypes', ['single_choice'])
                ->where('test.passing_score', fn ($v) => (float) $v === 60.0));
    }

    public function test_closed_review_redirects_to_result_with_a_message(): void
    {
        $this->test->update(['show_correct_answers' => false]);
        $attempt = $this->beginAttempt();
        $this->submitAttempt($attempt);

        $this->actingAs($this->student)
            ->get(route('student.tests.review', $attempt))
            ->assertRedirect(route('student.tests.result', $attempt))
            ->assertSessionHas('info');
    }
}
