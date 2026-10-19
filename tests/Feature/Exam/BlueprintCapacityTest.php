<?php

namespace Tests\Feature\Exam;

use App\Models\Exam\ExamBlueprint;
use App\Models\Exam\ExamBlueprintRule;
use App\Models\Exam\Subject;
use App\Models\Exam\Topic;
use App\Services\Exam\BlueprintCapacityChecker;
use Database\Factories\Exam\ExamQuestionFactory;
use Database\Seeders\Exam\Support\DemoQuestionVerifier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlueprintCapacityTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_approved_questions_count_and_rules_do_not_share_questions(): void
    {
        $subject = Subject::factory()->math()->create();
        $section = Topic::factory()->create(['subject_id' => $subject->id]);
        $leaf = Topic::factory()->childOf($section)->create();

        ExamQuestionFactory::new()->forTopic($leaf)->approved()->count(2)->create(['difficulty_level' => 2]);
        ExamQuestionFactory::new()->forTopic($leaf)->inReview()->withUnmarkedOptions()->count(5)->create(['difficulty_level' => 2]);
        ExamQuestionFactory::new()->forTopic($leaf)->numeric()->approved()->create(['difficulty_level' => 4]);

        $blueprint = ExamBlueprint::factory()->create(['subject_id' => $subject->id, 'total_questions' => 4]);
        $specific = ExamBlueprintRule::factory()->create([
            'blueprint_id' => $blueprint->id, 'topic_id' => $leaf->id, 'difficulty_level' => 2, 'count' => 1, 'sort_order' => 1,
        ]);
        $any = ExamBlueprintRule::factory()->create([
            'blueprint_id' => $blueprint->id, 'topic_id' => $section->id, 'difficulty_level' => null, 'count' => 2, 'sort_order' => 2,
        ]);
        $open = ExamBlueprintRule::factory()->create([
            'blueprint_id' => $blueprint->id, 'topic_id' => $section->id, 'part' => 'open', 'count' => 1, 'sort_order' => 3,
        ]);

        $report = app(BlueprintCapacityChecker::class)->check($blueprint);
        $lines = collect($report['rules'])->keyBy('rule_id');

        // 2 approved closed questions serve two closed rules needing 1 + 2 = 3
        $this->assertFalse($report['satisfiable']);
        $this->assertSame(2, $lines[$any->id]['candidates'], 'in-review questions never count');
        $this->assertSame(2, $lines[$specific->id]['candidates']);
        $this->assertSame(2, $lines[$specific->id]['allocated'] + $lines[$any->id]['allocated'], 'a question serves one rule only');
        $this->assertSame(1, $lines[$specific->id]['shortfall'] + $lines[$any->id]['shortfall']);
        $this->assertSame(0, $lines[$open->id]['shortfall']);

        // One more approved closed question makes it satisfiable.
        ExamQuestionFactory::new()->forTopic($leaf)->approved()->create(['difficulty_level' => 1]);
        $this->assertTrue(app(BlueprintCapacityChecker::class)->check($blueprint)['satisfiable']);
        $this->assertTrue($blueprint->isConsistent());
    }

    public function test_rule_validation(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        ExamBlueprintRule::factory()->create(['difficulty_level' => 6]);
    }

    public function test_verifier_rejects_wrong_or_ambiguous_demo_definitions(): void
    {
        $base = ['topic' => 'math.arith.natural', 'type' => 'single_choice', 'difficulty' => 1, 'stem' => '2 + 2 = ?'];

        $wrong = DemoQuestionVerifier::errors($base + ['answer' => 5, 'compute' => fn () => 2 + 2, 'options' => [5, 4, 3]], ['math.arith.natural']);
        $this->assertNotEmpty(array_filter($wrong, fn ($e) => str_contains($e, 'differs from expected')));

        $ambiguous = DemoQuestionVerifier::errors($base + ['answer' => 4, 'compute' => fn () => 2 + 2, 'options' => [4, ['\frac{8}{2}', 4.0], 3]], ['math.arith.natural']);
        $this->assertNotEmpty(array_filter($ambiguous, fn ($e) => str_contains($e, 'exactly one option')));

        $noUnit = DemoQuestionVerifier::errors(
            ['topic' => 'phys.x', 'type' => 'numeric_with_unit', 'difficulty' => 3, 'stem' => 'v?', 'answer' => 20, 'compute' => fn () => 20],
            ['phys.x']
        );
        $this->assertContains('phys.x: v? - numeric_with_unit needs a unit', $noUnit);

        $this->assertSame([], DemoQuestionVerifier::errors($base + ['answer' => 4, 'compute' => fn () => 2 + 2, 'options' => [4, 5, 3]], ['math.arith.natural']));
        $this->assertFalse(DemoQuestionVerifier::same(8e-14, 8e-20));
    }
}
