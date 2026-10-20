<?php

namespace Tests\Feature\Student;

use App\Models\Enrollment;
use App\Models\Question;
use App\Models\Setting;
use App\Models\Test;
use App\Models\TestAttempt;
use App\Services\BattleScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

/**
 * The formulas of docs/battle/SCORING.md.
 */
class BattleScoringTest extends TestCase
{
    use RefreshDatabase, CreatesCourses;

    private function scoring(): BattleScoringService
    {
        return app(BattleScoringService::class);
    }

    private function setting(string $key, $value): void
    {
        Setting::updateOrCreate(['key' => $key], ['group' => 'battle', 'value' => (string) $value, 'type' => 'string']);
    }

    public function test_fewer_solvers_overall_make_a_question_worth_more(): void
    {
        foreach (['linear', 'logit'] as $model) {
            $this->setting('battle_scoring_model', $model);
            $values = array_map(fn ($p) => $this->scoring()->questionValue($p), [0.95, 0.8, 0.6, 0.5, 0.3, 0.1, 0.02]);
            $sorted = $values;
            sort($sorted);
            $this->assertSame($sorted, $values, "{$model}: the value grows as the solve rate falls");
            $this->assertSame(count($values), count(array_unique($values)), "{$model}: strictly");
        }

        // the documented numbers
        $this->setting('battle_scoring_model', 'linear');
        $this->assertSame(102, $this->scoring()->questionValue(0.99)); // 100 × (1 + 2 × 0.01)
        $this->assertSame(200, $this->scoring()->questionValue(0.5));
        $this->assertSame(280, $this->scoring()->questionValue(0.1));
        $this->setting('battle_scoring_model', 'logit');
        $this->assertSame(100, $this->scoring()->questionValue(0.5), 'b = 0 at p̂ = ½');
        $this->assertEqualsWithDelta(log(9), $this->scoring()->difficultyLogit(0.1), 1e-9);
    }

    public function test_fewer_solvers_in_the_battle_score_more_per_solver(): void
    {
        $s = $this->scoring();
        $one = $s->roundPoints(200, true, 1, 10, 20);
        $two = $s->roundPoints(200, true, 2, 10, 20);
        $three = $s->roundPoints(200, true, 3, 10, 20);
        $this->assertGreaterThan($two, $one);
        $this->assertGreaterThan($three, $two);
        $this->assertSame(220, $one, '200 × 1 × (1 + 0.2 × 0.5)');
        $this->assertSame(110, $two, '200 × ½ × 1.1');
        $this->assertSame(0, $s->roundPoints(200, false, 1, 1, 20), 'a wrong answer scores nothing');
        $this->assertSame(0, $s->roundPoints(200, false, 0, 20, 20));
    }

    public function test_speed_is_secondary(): void
    {
        $s = $this->scoring();
        $this->assertGreaterThan($s->roundPoints(200, true, 1, 15, 20), $s->roundPoints(200, true, 1, 2, 20), 'faster scores more');
        // the fastest shared solve is still worth less than the slowest unique one
        $this->assertGreaterThan($s->roundPoints(200, true, 2, 0, 20), $s->roundPoints(200, true, 1, 20, 20));
        // and the time is capped by the limit
        $this->assertSame($s->roundPoints(200, true, 1, 20, 20), $s->roundPoints(200, true, 1, 999, 20));
    }

    public function test_a_new_question_starts_at_the_prior_of_its_label(): void
    {
        $s = $this->scoring();
        $this->assertEqualsWithDelta(0.80, $s->estimateSolveRate(0, 0, 'easy'), 1e-9);
        $this->assertEqualsWithDelta(0.60, $s->estimateSolveRate(0, 0, 'medium'), 1e-9);
        $this->assertEqualsWithDelta(0.35, $s->estimateSolveRate(0, 0, 'hard'), 1e-9);
        $this->assertEqualsWithDelta(0.60, $s->estimateSolveRate(0, 0, null), 1e-9, 'unknown label = medium');
        $this->assertSame(['easy', 'medium', 'hard'], array_map(fn ($l) => $s->level($s->estimateSolveRate(0, 0, $l)), ['easy', 'medium', 'hard']));
        $this->assertLessThan($s->questionValue(0.35), $s->questionValue(0.60));
        $this->assertLessThan($s->questionValue(0.60), $s->questionValue(0.80));
    }

    public function test_small_samples_move_the_estimate_only_a_little(): void
    {
        $s = $this->scoring();
        // one lucky solve of a hard question: (1 + 3.5) / 11 = 0.41, not 100 %
        $this->assertEqualsWithDelta(4.5 / 11, $s->estimateSolveRate(1, 1, 'hard'), 1e-9);
        // one miss of an easy question: 8 / 11 = 0.73, still easy
        $this->assertSame('easy', $s->level($s->estimateSolveRate(1, 0, 'easy')));
        // lots of evidence wins over the label
        $this->assertEqualsWithDelta(0.10, $s->estimateSolveRate(1000, 100, 'easy'), 0.01);
        $this->assertSame('hard', $s->level($s->estimateSolveRate(1000, 100, 'easy')));
        // the estimate is always inside (0, 1) so the logit is finite
        $this->assertGreaterThan(0, $s->estimateSolveRate(10000, 0, 'hard'));
        $this->assertLessThan(1, $s->estimateSolveRate(10000, 10000, 'easy'));
        $this->assertTrue(is_finite($s->difficultyLogit($s->estimateSolveRate(10000, 0, 'hard'))));

        // a stronger prior moves less
        $this->setting('battle_scoring_prior_strength', 50);
        $this->assertEqualsWithDelta((1 + 50 * 0.35) / 51, $this->scoring()->estimateSolveRate(1, 1, 'hard'), 1e-9);
        // no prior: the raw solve rate (clamped)
        $this->setting('battle_scoring_prior_strength', 0);
        $this->assertEqualsWithDelta(0.25, $this->scoring()->estimateSolveRate(4, 1, 'hard'), 1e-9);
    }

    public function test_course_test_answers_count_at_a_lower_weight(): void
    {
        $course = $this->course($this->teacher());
        $test = Test::forceCreate(['course_id' => $course->id, 'type' => 'final', 'title' => 'T', 'is_active' => true]);
        $question = Question::forceCreate(['course_id' => $course->id, 'test_id' => $test->id, 'type' => 'single_choice', 'question_text' => 'Q']);
        $student = $this->studentUser();
        $enrollment = Enrollment::forceCreate(['user_id' => $student->id, 'course_id' => $course->id, 'access_type' => 'free', 'status' => 'active']);
        foreach ([true, false, false, false, null] as $n => $correct) { // null = not graded: ignored
            $attempt = TestAttempt::forceCreate([
                'user_id' => $student->id, 'test_id' => $test->id, 'course_id' => $course->id, 'enrollment_id' => $enrollment->id,
                'attempt_number' => $n + 1, 'status' => 'submitted', 'started_at' => now()->subMinutes(5), 'submitted_at' => now(), 'expires_at' => now()->addMinutes(25),
            ]);
            DB::table('test_answers')->insert([
                'id' => (string) Str::uuid(), 'attempt_id' => $attempt->id, 'question_id' => $question->id,
                'is_answered' => true, 'is_correct' => $correct, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        $this->scoring()->recordAttempts($question->id, 2, 2);

        $evidence = $this->scoring()->evidence([$question->id])[$question->id];
        $this->assertEqualsWithDelta(2 + 0.5 * 4, $evidence['attempts'], 1e-9);
        $this->assertEqualsWithDelta(2 + 0.5 * 1, $evidence['correct'], 1e-9);

        $this->setting('battle_scoring_test_weight', 0);
        $this->assertSame(['attempts' => 2.0, 'correct' => 2.0], $this->scoring()->evidence([$question->id])[$question->id]);
    }

    public function test_the_numbers_come_from_the_settings(): void
    {
        $this->setting('battle_scoring_base', 50);
        $this->setting('battle_scoring_k', 1);
        $this->assertSame(75, $this->scoring()->questionValue(0.5), '50 × (1 + 1 × 0.5)');

        $this->setting('battle_scoring_solver_exponent', 0); // no sharing
        $this->setting('battle_scoring_speed_bonus', 0);
        $this->assertSame(75, $this->scoring()->roundPoints(75, true, 2, 3, 20));

        // nonsense falls back / is clamped
        $this->setting('battle_scoring_base', 'abc');
        $this->setting('battle_scoring_model', 'quantum');
        $this->assertSame('linear', $this->scoring()->settings()->model());
        $this->assertSame(100, $this->scoring()->settings()->base());
    }
}
