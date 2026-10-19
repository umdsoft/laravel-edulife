<?php

namespace Tests\Feature;

use App\Models\LabAttempt;
use App\Models\LabExperiment;
use App\Models\LabUserProgress;
use App\Models\StudentProfile;
use App\Models\User;
use App\Services\Lab\LabTaskChecker;
use Database\Seeders\LabBadgeSeeder;
use Database\Seeders\LabCategorySeeder;
use Database\Seeders\LabExperimentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LabTaskCheckingTest extends TestCase
{
    use RefreshDatabase;

    private const CONCLUSION = "Davr faqat ip uzunligiga bog'liq, massaga bog'liq emas. Katta amplitudada davr biroz ortadi.";

    private User $student;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(LabCategorySeeder::class);
        $this->seed(LabBadgeSeeder::class);
        $this->seed(LabExperimentSeeder::class);

        $this->student = User::factory()->create(['role' => 'student']);
        StudentProfile::firstOrCreate(['user_id' => $this->student->id]);
    }

    /** Opens the simulate page (creates the attempt) and returns its Inertia props. */
    private function openSimulation(string $slug = 'simple-pendulum', ?User $user = null): array
    {
        $response = $this->actingAs($user ?? $this->student)
            ->get("/student/lab/experiment/{$slug}/simulate")
            ->assertOk();

        return $response->viewData('page')['props'];
    }

    private function check(string $attemptId, array $payload, ?User $user = null)
    {
        return $this->actingAs($user ?? $this->student)
            ->postJson("/student/lab/api/attempt/{$attemptId}/check-task", $payload);
    }

    private function measurement(float $value, string $unit = 's'): array
    {
        return [['name' => 'Tebranish davri (T)', 'value' => $value, 'unit' => $unit]];
    }

    // ═══════════════════════════════════════════════════════════════════════
    // Answers never reach the browser
    // ═══════════════════════════════════════════════════════════════════════

    public function test_simulate_props_never_contain_expected_answers_for_any_experiment(): void
    {
        foreach (LabExperiment::pluck('slug') as $slug) {
            $props = $this->openSimulation($slug);
            $tasks = $props['experiment']['tasks'];

            $this->assertNotEmpty($tasks, $slug);
            foreach ($tasks as $task) {
                foreach (LabExperiment::TASK_ANSWER_KEYS as $key) {
                    $this->assertArrayNotHasKey($key, $task, "{$slug}: task {$task['step']} leaks '{$key}'");
                }
                $this->assertArrayHasKey('max_score', $task);
                $this->assertArrayHasKey('description', $task);
            }

            $json = json_encode($props['experiment']);
            $this->assertStringNotContainsString('"expected"', $json, $slug);
            $this->assertStringNotContainsString('tolerance_percent', $json, $slug);
        }

        // hints are still delivered
        $pendulum = $this->openSimulation('simple-pendulum')['experiment']['tasks'];
        $this->assertNotEmpty($pendulum[1]['hints']);
    }

    public function test_check_responses_never_contain_expected_answers(): void
    {
        $attemptId = $this->openSimulation()['attempt']['id'];

        // wrong answers: the expected value (2.0147 s / 9.81 m/s²) is never revealed
        foreach ([[2, ['measurements' => $this->measurement(1.0)]], [4, []], [4, ['calculations' => [['result' => 3.0, 'unit' => 'm/s²']]]]] as [$step, $payload]) {
            $content = $this->check($attemptId, ['task_number' => $step] + $payload)
                ->assertOk()->assertJson(['correct' => false])->getContent();
            $this->assertStringNotContainsString('expected', $content);
            $this->assertStringNotContainsString('tolerance_percent', $content);
            $this->assertStringNotContainsString('2.0147', $content);
            $this->assertStringNotContainsString('9.81', $content);
        }

        $content = $this->check($attemptId, ['task_number' => 2, 'measurements' => $this->measurement(2.0147)])
            ->assertOk()->assertJson(['correct' => true])->getContent();
        $this->assertStringNotContainsString('expected', $content);
        $this->assertStringNotContainsString('check', json_encode(array_keys(json_decode($content, true))));
    }

    // ═══════════════════════════════════════════════════════════════════════
    // check-task endpoint
    // ═══════════════════════════════════════════════════════════════════════

    public function test_correct_measurement_completes_the_task_with_server_score(): void
    {
        $attemptId = $this->openSimulation()['attempt']['id'];

        $this->check($attemptId, [
            'task_number' => 2,
            'measurements' => $this->measurement(2014.9, 'ms'),
            'score' => 999,           // ignored
            'max_score' => 999,       // ignored
        ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'task_number' => 2,
                'correct' => true,
                'score' => 10,
                'max_score' => 10,
                'task' => ['completed' => true, 'score' => 10],
                'progress' => ['completed_tasks' => 1, 'total_tasks' => 5, 'current_task' => 3, 'can_complete' => false],
            ])
            ->assertJsonStructure(['feedback']);

        $attempt = LabAttempt::findOrFail($attemptId);
        $this->assertSame(10, $attempt->raw_score);
        $this->assertSame(1, $attempt->completed_tasks);
    }

    public function test_client_sent_score_cannot_complete_a_task(): void
    {
        $attemptId = $this->openSimulation()['attempt']['id'];

        // the legacy endpoint shape: only a score, no answer
        $this->actingAs($this->student)
            ->postJson("/student/lab/api/attempt/{$attemptId}/complete-task", ['task_number' => 2, 'score' => 10, 'max_score' => 10])
            ->assertOk()
            ->assertJson(['correct' => false, 'counted' => false, 'score' => 0, 'task' => ['completed' => false]]);

        $this->check($attemptId, ['task_number' => 2, 'measurements' => $this->measurement(2.2), 'score' => 10])
            ->assertOk()
            ->assertJson(['correct' => false, 'counted' => true, 'score' => 0, 'progress' => ['completed_tasks' => 0]]);

        $this->assertSame(0, LabAttempt::findOrFail($attemptId)->raw_score);
    }

    public function test_repeated_correct_submission_is_idempotent(): void
    {
        $attemptId = $this->openSimulation()['attempt']['id'];
        $payload = ['task_number' => 2, 'measurements' => $this->measurement(2.0147)];

        $this->check($attemptId, $payload)->assertOk()->assertJson(['correct' => true, 'already_completed' => false]);
        $this->check($attemptId, $payload)->assertOk()->assertJson(['correct' => true, 'already_completed' => true, 'score' => 10]);

        $attempt = LabAttempt::findOrFail($attemptId);
        $this->assertSame(10, $attempt->raw_score);
        $this->assertSame(1, $attempt->completed_tasks);
    }

    public function test_input_is_validated(): void
    {
        $attemptId = $this->openSimulation()['attempt']['id'];

        $this->check($attemptId, [])->assertStatus(422)->assertJsonValidationErrors('task_number');
        $this->check($attemptId, ['task_number' => 2, 'measurements' => [['name' => 'T', 'value' => 'abc', 'unit' => 's']]])
            ->assertStatus(422)->assertJsonValidationErrors('measurements.0.value');
        $this->check($attemptId, ['task_number' => 2, 'measurements' => 'nope'])->assertStatus(422);
        $this->check($attemptId, ['task_number' => 5, 'text' => str_repeat('a', LabTaskChecker::MAX_TEXT_LENGTH + 1)])
            ->assertStatus(422)->assertJsonValidationErrors('text');
    }

    public function test_unknown_task_is_rejected(): void
    {
        $attemptId = $this->openSimulation()['attempt']['id'];

        $this->check($attemptId, ['task_number' => 42])
            ->assertStatus(422)
            ->assertJson(['success' => false, 'reason' => 'unknown_task']);
    }

    public function test_other_users_attempt_is_not_found(): void
    {
        $attemptId = $this->openSimulation()['attempt']['id'];
        $intruder = User::factory()->create(['role' => 'student']);

        $this->check($attemptId, ['task_number' => 2, 'measurements' => $this->measurement(2.0147)], $intruder)
            ->assertNotFound();
        $this->actingAs($intruder)->postJson("/student/lab/api/attempt/{$attemptId}/complete")->assertNotFound();

        $this->assertSame(0, LabAttempt::findOrFail($attemptId)->completed_tasks);
    }

    public function test_guest_cannot_check(): void
    {
        $attemptId = $this->openSimulation()['attempt']['id'];
        auth()->logout();

        $this->postJson("/student/lab/api/attempt/{$attemptId}/check-task", ['task_number' => 1])->assertUnauthorized();
    }

    // ═══════════════════════════════════════════════════════════════════════
    // Completing the experiment
    // ═══════════════════════════════════════════════════════════════════════

    private function solveAllPendulumTasks(string $attemptId): void
    {
        $this->check($attemptId, ['task_number' => 1])->assertJson(['correct' => true]);
        $this->check($attemptId, ['task_number' => 2, 'measurements' => $this->measurement(2.0147)])->assertJson(['correct' => true]);
        $this->check($attemptId, ['task_number' => 3, 'measurements' => $this->measurement(1.4246)])->assertJson(['correct' => true]);
        $this->check($attemptId, ['task_number' => 4, 'calculations' => [['formula_id' => 'gravity', 'result' => 9.8098, 'unit' => 'm/s²']]])
            ->assertJson(['correct' => true]);
        $this->check($attemptId, ['task_number' => 5, 'text' => self::CONCLUSION])
            ->assertJson(['correct' => true, 'progress' => ['completed_tasks' => 5, 'progress_percent' => 100, 'can_complete' => true]]);
    }

    public function test_experiment_cannot_be_completed_before_all_tasks_are_done(): void
    {
        $attemptId = $this->openSimulation()['attempt']['id'];
        $this->check($attemptId, ['task_number' => 1])->assertOk();

        $this->actingAs($this->student)
            ->postJson("/student/lab/api/attempt/{$attemptId}/complete")
            ->assertStatus(422)
            ->assertJson(['success' => false, 'reason' => 'tasks_incomplete', 'progress' => ['completed_tasks' => 1]]);

        $this->assertSame('in_progress', LabAttempt::findOrFail($attemptId)->status);
    }

    public function test_full_flow_completes_once_and_rewards_once(): void
    {
        $attemptId = $this->openSimulation()['attempt']['id'];
        $this->solveAllPendulumTasks($attemptId);

        $this->actingAs($this->student)
            ->postJson("/student/lab/api/attempt/{$attemptId}/complete")
            ->assertOk()
            ->assertJson(['success' => true, 'result' => ['status' => 'completed', 'passed' => true]]);

        $attempt = LabAttempt::findOrFail($attemptId);
        $this->assertSame('completed', $attempt->status);
        $this->assertSame(50, $attempt->raw_score);
        $this->assertEquals(100, (float) $attempt->percentage);
        $this->assertSame(self::CONCLUSION, $attempt->conclusion_text);

        $profileXp = StudentProfile::where('user_id', $this->student->id)->value('xp');
        $labXp = LabUserProgress::where('user_id', $this->student->id)->value('total_xp');
        $coins = $this->student->fresh()->coin_balance;
        $this->assertGreaterThan(0, $profileXp);
        $this->assertGreaterThan(0, $labXp);

        // second completion: rejected, nothing awarded again
        $this->actingAs($this->student)
            ->postJson("/student/lab/api/attempt/{$attemptId}/complete")
            ->assertStatus(409);
        $this->check($attemptId, ['task_number' => 5, 'text' => self::CONCLUSION])
            ->assertStatus(409)->assertJson(['reason' => 'not_in_progress']);

        $this->assertSame($profileXp, StudentProfile::where('user_id', $this->student->id)->value('xp'));
        $this->assertSame($labXp, LabUserProgress::where('user_id', $this->student->id)->value('total_xp'));
        $this->assertSame($coins, $this->student->fresh()->coin_balance);
    }

    public function test_simulate_props_report_server_progress(): void
    {
        $attemptId = $this->openSimulation()['attempt']['id'];
        $this->check($attemptId, ['task_number' => 2, 'measurements' => $this->measurement(2.0147)]);

        $attempt = $this->openSimulation()['attempt'];
        $this->assertSame($attemptId, $attempt['id']);
        $progress = json_decode(json_encode($attempt['tasks_progress']), true);
        $this->assertTrue($progress[2]['completed']);
        $this->assertSame(10, $progress[2]['score']);
        $this->assertSame(3, $attempt['current_task']);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // Every seeded answer is accepted (the checker agrees with the seed data)
    // ═══════════════════════════════════════════════════════════════════════

    public function test_every_seeded_expected_answer_is_accepted_by_the_checker(): void
    {
        $checker = new LabTaskChecker();
        $checked = 0;

        foreach (LabExperiment::all() as $experiment) {
            foreach ($experiment->tasks as $task) {
                $ex = $task['expected'] ?? null;
                if (!$ex) {
                    continue;
                }
                $answer = $ex['source'] === 'measurement'
                    ? ['measurements' => [['name' => $ex['measurement'] . ' (sozlamalar)', 'value' => $ex['value'], 'unit' => $ex['unit']]]]
                    : ['calculations' => [['formula_id' => $ex['check']['formula_id'], 'result' => $ex['value'], 'unit' => $ex['unit']]]];

                $label = "{$experiment->slug} #{$task['step']}";
                $this->assertTrue($checker->evaluate($task, $answer)['correct'], "{$label}: exact answer rejected");

                $off = $ex['value'] == 0 ? 1.0 : $ex['value'] * (1 + 3 * $ex['tolerance_percent'] / 100);
                $answer[array_key_first($answer)][0][$ex['source'] === 'measurement' ? 'value' : 'result'] = $off;
                $this->assertFalse($checker->evaluate($task, $answer)['correct'], "{$label}: answer 3× outside tolerance accepted");
                $checked++;
            }
        }

        $this->assertSame(233, $checked);
    }
}
