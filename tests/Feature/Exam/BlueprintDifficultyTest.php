<?php

namespace Tests\Feature\Exam;

use App\Models\Exam\ExamBlueprint;
use App\Models\Exam\Subject;
use App\Models\Exam\Topic;
use App\Models\User;
use App\Services\Exam\BlueprintCapacityChecker;
use Database\Factories\Exam\ExamQuestionFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Blueprint rules: the difficulty is chosen explicitly per rule (1..5 or
 * "Istalgan") and every rule shows the approved questions per level of its
 * topic and part.
 */
class BlueprintDifficultyTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;

    private Subject $math;

    private Topic $section;

    protected function setUp(): void
    {
        parent::setUp();
        $this->teacher = User::factory()->create(['role' => 'teacher', 'status' => 'active']);
        $this->math = Subject::factory()->math()->create();
        $this->section = Topic::factory()->create(['subject_id' => $this->math->id, 'code' => 'math.alg']);
        $child = Topic::factory()->childOf($this->section)->create(['code' => 'math.alg.lin']);
        foreach ([2 => 3, 3 => 1, 5 => 2] as $level => $n) {
            ExamQuestionFactory::new()->forTopic($child)->approved()->count($n)->create(['difficulty_level' => $level]);
        }
        ExamQuestionFactory::new()->forTopic($child)->numeric()->approved()->create(['difficulty_level' => 3]); // open part
        ExamQuestionFactory::new()->forTopic($child)->inReview()->create(['difficulty_level' => 2]); // not approved
    }

    private function payload(array $rules): array
    {
        return [
            'subject_id' => $this->math->id, 'name' => 'Reja', 'purpose' => 'weekly', 'format' => null,
            'duration_min' => 60, 'wrong_unit_ratio' => 0.5, 'avoid_repeat_weeks' => 2,
            'allow_adjacent_difficulty' => false, 'shuffle_questions' => true, 'shuffle_options' => true, 'is_active' => true,
            'rules' => $rules,
        ];
    }

    public function test_live_capacity_reports_counts_per_level_and_the_shortfall(): void
    {
        $this->actingAs($this->teacher)->postJson(route('teacher.exam.blueprints.capacity'), [
            'subject_id' => $this->math->id,
            'allow_adjacent_difficulty' => false,
            'rules' => [
                ['key' => 'a', 'topic_id' => $this->section->id, 'difficulty_level' => 3, 'part' => 'closed', 'count' => 2],
                ['key' => 'b', 'topic_id' => $this->section->id, 'difficulty_level' => null, 'part' => 'open', 'count' => 1],
            ],
        ])->assertOk()
            ->assertJsonPath('rules.0.levels', ['1' => 0, '2' => 3, '3' => 1, '4' => 0, '5' => 2])
            ->assertJsonPath('rules.0.candidates', 1)
            ->assertJsonPath('rules.0.shortfall', 1)
            ->assertJsonPath('rules.0.status', 'short')
            ->assertJsonPath('rules.1.levels', ['1' => 0, '2' => 0, '3' => 1, '4' => 0, '5' => 0])
            ->assertJsonPath('rules.1.status', 'ok');
    }

    public function test_the_level_must_be_chosen_explicitly_any_is_null_on_purpose(): void
    {
        $rule = ['topic_id' => $this->section->id, 'part' => 'closed', 'count' => 1, 'points_per_question' => 1];

        $this->actingAs($this->teacher)->post(route('teacher.exam.blueprints.store'), $this->payload([$rule]))
            ->assertSessionHasErrors('rules.0.difficulty_level');
        $this->actingAs($this->teacher)->post(route('teacher.exam.blueprints.store'), $this->payload([$rule + ['difficulty_level' => 6]]))
            ->assertSessionHasErrors('rules.0.difficulty_level');
        $this->assertSame(0, ExamBlueprint::query()->count());

        $this->actingAs($this->teacher)->post(route('teacher.exam.blueprints.store'), $this->payload([
            $rule + ['difficulty_level' => 5], $rule + ['difficulty_level' => null],
        ]))->assertSessionHasNoErrors()->assertRedirect();

        $blueprint = ExamBlueprint::query()->firstOrFail();
        $this->assertSame([5, null], $blueprint->rules()->orderBy('sort_order')->pluck('difficulty_level')->all());

        $this->actingAs($this->teacher)->get(route('teacher.exam.blueprints.edit', $blueprint))
            ->assertOk()->assertInertia(fn (Assert $p) => $p
                ->where('readiness.rules.0.levels.5', 2)
                ->where('readiness.rules.1.levels.2', 3));
    }

    public function test_capacity_checker_counts_per_level_for_a_rule(): void
    {
        $blueprint = ExamBlueprint::factory()->create(['subject_id' => $this->math->id]);
        $rule = $blueprint->rules()->create(['topic_id' => $this->section->id, 'difficulty_level' => 2, 'part' => 'closed', 'count' => 4, 'points_per_question' => 1, 'sort_order' => 1]);
        $checker = app(BlueprintCapacityChecker::class);

        $this->assertSame([1 => 0, 2 => 3, 3 => 1, 4 => 0, 5 => 2], $checker->levelCounts($blueprint, $rule->load('topic')));
        $this->assertCount(3, $checker->candidateIds($blueprint, $rule));
        $report = $checker->check($blueprint);
        $this->assertFalse($report['satisfiable']);
        $this->assertSame(1, $report['rules'][0]['shortfall']);
    }

    public function test_students_cannot_reach_the_capacity_endpoint(): void
    {
        $student = User::factory()->create(['role' => 'student', 'status' => 'active']);
        $this->actingAs($student)->postJson(route('teacher.exam.blueprints.capacity'), ['subject_id' => $this->math->id, 'rules' => []])
            ->assertForbidden();
    }
}
