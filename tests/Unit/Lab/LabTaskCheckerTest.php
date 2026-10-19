<?php

namespace Tests\Unit\Lab;

use App\Models\LabAttempt;
use App\Models\LabExperiment;
use App\Models\User;
use App\Services\Lab\LabTaskCheckException;
use App\Services\Lab\LabTaskChecker;
use Database\Seeders\LabCategorySeeder;
use Database\Seeders\LabExperimentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * simple-pendulum (seeded): 5 tasks × 10 points
 *   1 instruction · 2 measurement "Tebranish davri (T)" 2.0147 s ±1 %
 *   3 measurement 1.4246 s ±1 % · 4 calculation 9.81 m/s² ±1 % · 5 conclusion
 */
class LabTaskCheckerTest extends TestCase
{
    use RefreshDatabase;

    private LabTaskChecker $checker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->checker = new LabTaskChecker();
    }

    private const PERIOD_TASK = [
        'step' => 2,
        'type' => 'measurement',
        'title' => 'Davrni o\'lchang',
        'description' => 'L = 1,0 m da davrni o\'lchang.',
        'max_score' => 10,
        'expected' => [
            'source' => 'measurement',
            'measurement' => 'Tebranish davri (T)',
            'value' => 2.0147,
            'unit' => 's',
            'tolerance_percent' => 1,
        ],
    ];

    private function measure(float|string $value, string $unit = 's', string $name = 'Tebranish davri (T)'): array
    {
        return ['measurements' => [['name' => $name, 'value' => $value, 'unit' => $unit]]];
    }

    // ═══════════════════════════════════════════════════════════════════════
    // evaluate(): pure decisions
    // ═══════════════════════════════════════════════════════════════════════

    public function test_measurement_tolerance_edges(): void
    {
        $e = 2.0147;
        $this->assertTrue($this->checker->evaluate(self::PERIOD_TASK, $this->measure($e))['correct']);
        $this->assertTrue($this->checker->evaluate(self::PERIOD_TASK, $this->measure($e * 1.01))['correct'], 'upper edge +1 %');
        $this->assertTrue($this->checker->evaluate(self::PERIOD_TASK, $this->measure($e * 0.99))['correct'], 'lower edge −1 %');
        $this->assertFalse($this->checker->evaluate(self::PERIOD_TASK, $this->measure($e * 1.0101))['correct'], 'just above +1 %');
        $this->assertFalse($this->checker->evaluate(self::PERIOD_TASK, $this->measure($e * 0.9899))['correct'], 'just below −1 %');
        $this->assertFalse($this->checker->evaluate(self::PERIOD_TASK, $this->measure(1.4246))['correct'], 'measured with the wrong length');

        // simulations emit rounded strings
        $this->assertTrue($this->checker->evaluate(self::PERIOD_TASK, $this->measure('2.015'))['correct']);
    }

    public function test_measurement_units_are_normalised(): void
    {
        $this->assertTrue($this->checker->evaluate(self::PERIOD_TASK, $this->measure(2014.7, 'ms'))['correct'], 'ms → s');
        $this->assertFalse($this->checker->evaluate(self::PERIOD_TASK, $this->measure(2014.7, 's'))['correct']);

        $wrongUnit = $this->checker->evaluate(self::PERIOD_TASK, $this->measure(2.0147, 'm'));
        $this->assertFalse($wrongUnit['correct']);
        $this->assertTrue($wrongUnit['relevant']);
        $this->assertStringContainsString('birligi', $wrongUnit['feedback']);

        $this->assertEqualsWithDelta(1e-3, $this->checker->unitFactor('mA', 'A'), 1e-15);
        $this->assertEqualsWithDelta(10.0, $this->checker->unitFactor('cm', 'mm'), 1e-12);
        $this->assertEqualsWithDelta(1e-3, $this->checker->unitFactor('V/m', 'kV/m'), 1e-15);
        $this->assertEqualsWithDelta(1e3, $this->checker->unitFactor('N', 'mN'), 1e-9);
        $this->assertEqualsWithDelta(1e-3, $this->checker->unitFactor('µT', 'mT'), 1e-15);
        $this->assertEqualsWithDelta(1e-3, $this->checker->unitFactor('uT', 'mT'), 1e-15, 'ASCII micro');
        $this->assertEqualsWithDelta(1e6, $this->checker->unitFactor('MeV', 'eV'), 1e-6);
        $this->assertSame(1.0, $this->checker->unitFactor('m/s²', 'm/s²'));
        $this->assertSame(1.0, $this->checker->unitFactor('', ''));
        $this->assertNull($this->checker->unitFactor('V', 'A'));
        $this->assertNull($this->checker->unitFactor('°C', 'K'));
        $this->assertNull($this->checker->unitFactor('m/s', 'm'));
        $this->assertNull($this->checker->unitFactor('cs', 's'), 'centi only for lengths/volumes');
    }

    public function test_measurement_is_picked_by_name_prefix(): void
    {
        // simulations append parameters to the name: "… (L=1 m)"
        $this->assertTrue($this->checker->evaluate(self::PERIOD_TASK, $this->measure(2.0147, 's', 'Tebranish davri (T) (L=1.0 m)'))['correct']);
        $this->assertTrue($this->checker->evaluate(self::PERIOD_TASK, $this->measure(2.0147, 's', 'tebranish  DAVRI (t)'))['correct'], 'case/space-insensitive');

        $other = $this->checker->evaluate(self::PERIOD_TASK, $this->measure(2.0147, 's', 'Chastota'));
        $this->assertFalse($other['correct']);
        $this->assertFalse($other['relevant'], 'an unrelated measurement is not an attempt');

        // last matching measurement wins; unrelated ones are skipped
        $batch = ['measurements' => [
            ['name' => 'Tebranish davri (T)', 'value' => 1.0, 'unit' => 's'],
            ['name' => 'Tebranish davri (T)', 'value' => 2.02, 'unit' => 's'],
            ['name' => 'Chastota', 'value' => 0.5, 'unit' => 'Hz'],
        ]];
        $this->assertTrue($this->checker->evaluate(self::PERIOD_TASK, $batch)['correct']);

        $this->assertFalse($this->checker->evaluate(self::PERIOD_TASK, [])['relevant']);
        $this->assertFalse($this->checker->evaluate(self::PERIOD_TASK, $this->measure('∞'))['relevant'], 'non-numeric values are ignored');
    }

    public function test_apostrophe_variants_in_names(): void
    {
        $task = self::PERIOD_TASK;
        $task['expected']['measurement'] = "Polosalar oralig'i Δy";
        $task['expected']['unit'] = 'mm';
        $task['expected']['value'] = 5.5;

        $this->assertTrue($this->checker->evaluate($task, $this->measure(5.5, 'mm', 'Polosalar oraligʻi Δy'))['correct']);
        $this->assertTrue($this->checker->evaluate($task, $this->measure(0.55, 'cm', "Polosalar oralig'i Δy"))['correct']);
    }

    public function test_calculation_tasks(): void
    {
        $task = [
            'step' => 4, 'type' => 'calculation', 'max_score' => 10,
            'expected' => ['source' => 'calculation', 'value' => 9.81, 'unit' => 'm/s²', 'tolerance_percent' => 1,
                'check' => ['formula_id' => 'gravity', 'inputs' => ['L' => 1, 'T' => 2.0061]]],
        ];
        $calc = fn ($result, $unit = 'm/s²') => ['calculations' => [['formula_id' => 'gravity', 'result' => $result, 'unit' => $unit]]];

        $this->assertTrue($this->checker->evaluate($task, $calc(9.8099))['correct']);
        $this->assertTrue($this->checker->evaluate($task, $calc(9.81 * 1.01))['correct']);
        $this->assertFalse($this->checker->evaluate($task, $calc(9.81 * 1.011))['correct']);
        $this->assertTrue($this->checker->evaluate($task, $calc(9.81, null))['correct'], 'missing unit means the expected unit');

        $wrongFormula = $this->checker->evaluate($task, $calc(0.4985, 'Hz'));
        $this->assertFalse($wrongFormula['correct']);
        $this->assertTrue($wrongFormula['relevant']);

        $none = $this->checker->evaluate($task, []);
        $this->assertFalse($none['correct']);
        $this->assertFalse($none['relevant']);
    }

    public function test_text_and_instruction_tasks(): void
    {
        $conclusion = ['step' => 5, 'type' => 'conclusion', 'max_score' => 10,
            'title' => 'Xulosa yozing',
            'description' => "Davr qaysi kattaliklarga bog'liq, qaysilariga bog'liq emasligini tushuntiring va massani o'zgartirib ko'ring."];

        $this->assertTrue($this->checker->evaluate(['type' => 'instruction'], [])['correct']);

        $this->assertFalse($this->checker->evaluate($conclusion, ['text' => ''])['relevant']);
        $this->assertFalse($this->checker->evaluate($conclusion, ['text' => 'Davr bog\'liq.'])['correct'], 'too short');
        $this->assertFalse($this->checker->evaluate($conclusion, ['text' => str_repeat('aaaa ', 30)])['correct'], 'one repeated word');
        $this->assertFalse($this->checker->evaluate($conclusion, ['text' => "Davr qaysi kattaliklarga bog'liq, qaysilariga bog'liq emasligini tushuntiring"])['correct'], 'copied task text');

        $good = $this->checker->evaluate($conclusion, ['text' => "Davr faqat ip uzunligiga bog'liq: L to'rt marta ortsa, T ikki marta ortadi. Massa davrga ta'sir qilmaydi."]);
        $this->assertTrue($good['correct']);
        $this->assertIsString($good['submitted']);

        $graph = ['type' => 'graph_analysis', 'title' => 'Grafik', 'description' => 'Grafikni tahlil qiling.'];
        $this->assertTrue($this->checker->evaluate($graph, ['text' => "Grafikda kuchlanish vaqt o'tishi bilan eksponensial ravishda kamayadi va nolga yaqinlashadi."])['correct']);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // check(): recording on the attempt
    // ═══════════════════════════════════════════════════════════════════════

    private function seedPendulumAttempt(?User $user = null): array
    {
        $this->seed(LabCategorySeeder::class);
        $this->seed(LabExperimentSeeder::class);
        $experiment = LabExperiment::where('slug', 'simple-pendulum')->firstOrFail();
        $user ??= User::factory()->create(['role' => 'student']);

        $attempt = LabAttempt::create([
            'user_id' => $user->id,
            'experiment_id' => $experiment->id,
            'attempt_number' => 1,
            'started_at' => now(),
            'total_tasks' => count($experiment->tasks),
            'max_score' => $experiment->total_points,
        ]);

        return [$user, $attempt, $experiment];
    }

    public function test_correct_answer_scores_max_score_and_advances(): void
    {
        [$user, $attempt] = $this->seedPendulumAttempt();

        $r = $this->checker->check($user, $attempt, 2, $this->measure(2.0147));

        $this->assertTrue($r['correct']);
        $this->assertSame(10, $r['score']);
        $this->assertSame(10, $r['max_score']);
        $this->assertTrue($r['task']['completed']);
        $this->assertSame(1, $r['progress']['completed_tasks']);
        $this->assertSame(5, $r['progress']['total_tasks']);
        $this->assertSame(20.0, (float) $r['progress']['progress_percent']);
        $this->assertSame(3, $r['progress']['current_task']);
        $this->assertFalse($r['progress']['can_complete']);

        $attempt->refresh();
        $this->assertSame(10, $attempt->raw_score);
        $this->assertSame('completed', $attempt->taskEntry(2)['status']);
    }

    public function test_wrong_answer_is_recorded_without_score(): void
    {
        [$user, $attempt] = $this->seedPendulumAttempt();

        $r = $this->checker->check($user, $attempt, 2, $this->measure(2.5));

        $this->assertFalse($r['correct']);
        $this->assertTrue($r['counted']);
        $this->assertSame(0, $r['score']);
        $this->assertFalse($r['task']['completed']);
        $this->assertSame(1, $r['task']['attempts']);
        $this->assertSame(0, $r['progress']['completed_tasks']);
        $this->assertSame(0, $attempt->fresh()->raw_score);

        // a non-matching measurement does not count as an attempt
        $r = $this->checker->check($user, $attempt, 2, $this->measure(2.0147, 's', 'Chastota'));
        $this->assertFalse($r['counted']);
        $this->assertSame(1, $r['task']['attempts']);
    }

    public function test_repeated_submission_is_never_scored_twice(): void
    {
        [$user, $attempt] = $this->seedPendulumAttempt();

        $this->checker->check($user, $attempt, 2, $this->measure(3.0));              // wrong
        $first = $this->checker->check($user, $attempt, 2, $this->measure(2.0147));  // right
        $again = $this->checker->check($user, $attempt, 2, $this->measure(2.0147));  // repeated
        $wrongAfter = $this->checker->check($user, $attempt, 2, $this->measure(9.0)); // cannot undo

        $this->assertTrue($first['correct']);
        $this->assertSame(2, $first['task']['attempts']);
        $this->assertTrue($again['already_completed']);
        $this->assertFalse($again['counted']);
        $this->assertTrue($wrongAfter['already_completed']);
        $this->assertTrue($wrongAfter['task']['completed']);

        $attempt->refresh();
        $this->assertSame(10, $attempt->raw_score);
        $this->assertSame(1, $attempt->completed_tasks);
        $this->assertCount(1, $attempt->tasks_progress);
        $this->assertSame(2, $attempt->taskEntry(2)['attempts']);
    }

    public function test_unknown_task_step_is_rejected(): void
    {
        [$user, $attempt] = $this->seedPendulumAttempt();

        try {
            $this->checker->check($user, $attempt, 99, []);
            $this->fail('expected LabTaskCheckException');
        } catch (LabTaskCheckException $e) {
            $this->assertSame(LabTaskCheckException::UNKNOWN_TASK, $e->reason());
            $this->assertSame(422, $e->status());
        }
        $this->assertEmpty($attempt->fresh()->tasks_progress);
    }

    public function test_other_users_attempt_is_rejected(): void
    {
        [, $attempt] = $this->seedPendulumAttempt();
        $intruder = User::factory()->create(['role' => 'student']);

        try {
            $this->checker->check($intruder, $attempt, 2, $this->measure(2.0147));
            $this->fail('expected LabTaskCheckException');
        } catch (LabTaskCheckException $e) {
            $this->assertSame(LabTaskCheckException::NOT_OWNER, $e->reason());
            $this->assertSame(403, $e->status());
        }
        $this->assertEmpty($attempt->fresh()->tasks_progress);
    }

    public function test_finished_attempt_is_rejected_and_paused_one_resumes(): void
    {
        [$user, $attempt] = $this->seedPendulumAttempt();

        $attempt->update(['status' => 'paused']);
        $this->checker->check($user, $attempt, 1, []);
        $this->assertSame('in_progress', $attempt->fresh()->status);

        $attempt->update(['status' => 'completed']);
        $this->expectException(LabTaskCheckException::class);
        $this->checker->check($user, $attempt, 2, $this->measure(2.0147));
    }

    public function test_conclusion_text_is_kept_on_the_attempt(): void
    {
        [$user, $attempt] = $this->seedPendulumAttempt();
        $text = "Davr faqat ip uzunligiga bog'liq, massaga bog'liq emas. Katta amplitudada davr biroz ortadi.";

        $r = $this->checker->check($user, $attempt, 5, ['text' => $text]);

        $this->assertTrue($r['correct']);
        $this->assertSame($text, $r['task']['answer_text']);
        $this->assertSame($text, $attempt->fresh()->conclusion_text);
    }
}
