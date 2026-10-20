<?php

namespace Tests\Feature\Exam;

use App\Models\Exam\ExamVariant;
use App\Models\Exam\Subject;
use App\Models\Exam\Topic;
use App\Models\QuestionBank;
use App\Models\User;
use App\Services\Exam\QuestionPicker;
use Database\Factories\Exam\ExamQuestionFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestionPickerTest extends TestCase
{
    use RefreshDatabase;

    private Subject $math;

    private Topic $section;

    private Topic $child;

    private Topic $other;

    private QuestionPicker $picker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->picker = app(QuestionPicker::class);
        $this->math = Subject::factory()->math()->create();
        $this->section = Topic::factory()->create(['subject_id' => $this->math->id, 'code' => 'math.alg']);
        $this->child = Topic::factory()->childOf($this->section)->create(['code' => 'math.alg.lin']);
        $this->other = Topic::factory()->create(['subject_id' => $this->math->id, 'code' => 'math.geo']);
    }

    /** @param array<int, int> $perLevel level => how many approved questions */
    private function bank(Topic $topic, array $perLevel, string $type = 'single_choice'): void
    {
        foreach ($perLevel as $level => $n) {
            ExamQuestionFactory::new()->forTopic($topic)->state(['question_type' => $type])->approved()
                ->count($n)->create(['difficulty_level' => $level]);
        }
    }

    public function test_counts_per_level_follow_the_filters(): void
    {
        $this->bank($this->child, [1 => 2, 3 => 3, 5 => 1]);
        $this->bank($this->other, [3 => 4]);
        ExamQuestionFactory::new()->forTopic($this->child)->inReview()->create(['difficulty_level' => 3]); // not approved
        $this->bank($this->child, [2 => 2], 'numeric');

        $subtree = ['subject_id' => $this->math->id, 'topic_id' => $this->section->id];
        $this->assertSame([1 => 2, 2 => 2, 3 => 3, 4 => 0, 5 => 1], $this->picker->levelCounts($subtree));
        $this->assertSame([1 => 2, 2 => 0, 3 => 3, 4 => 0, 5 => 1], $this->picker->levelCounts($subtree + ['types' => ['single_choice']]));
        $this->assertSame(['easy' => 4, 'medium' => 3, 'hard' => 1], $this->picker->bandCounts($subtree));
        $this->assertSame(10, array_sum($this->picker->levelCounts(['subject_id' => $this->math->id, 'types' => ['single_choice']])));
        $this->assertSame(0, array_sum($this->picker->levelCounts(['topic_id' => '00000000-0000-0000-0000-000000000000'])));
    }

    public function test_level_distribution_with_shortfall(): void
    {
        $this->bank($this->child, [1 => 2, 3 => 5, 5 => 1]);

        $r = $this->picker->pick(['subject_id' => $this->math->id, 'topic_id' => $this->section->id], [1 => 2, 3 => 5, 5 => 2], 42);

        $this->assertSame(9, $r['requested']);
        $this->assertSame(8, $r['picked']);
        $this->assertSame(1, $r['shortfall']);
        $this->assertCount(8, array_unique($r['ids']));
        $byKey = collect($r['lines'])->keyBy('key');
        $this->assertSame(['requested' => 2, 'available' => 1, 'picked' => 1, 'shortfall' => 1], collect($byKey['5'])->only(['requested', 'available', 'picked', 'shortfall'])->all());
        $levels = QuestionBank::query()->whereIn('id', $r['ids'])->pluck('difficulty_level')->countBy()->sortKeys()->all();
        $this->assertSame([1 => 2, 3 => 5, 5 => 1], $levels);
    }

    public function test_band_distribution_maps_to_levels_and_any_comes_last(): void
    {
        $this->bank($this->child, [1 => 1, 2 => 2, 3 => 2, 4 => 1, 5 => 2]);

        $r = $this->picker->pick(['topic_id' => $this->section->id], ['easy' => 3, 'medium' => 1, 'hard' => 3], 7);
        $this->assertSame(0, $r['shortfall']);
        $bands = QuestionBank::query()->whereIn('id', $r['ids'])->pluck('difficulty_level')
            ->map(fn ($l) => \App\Support\Difficulty::bandOf($l))->countBy()->all();
        $this->assertEquals(['easy' => 3, 'medium' => 1, 'hard' => 3], $bands);

        // "any" never takes what a specific level needs
        $r = $this->picker->pick(['topic_id' => $this->section->id], ['any' => 6, 3 => 2], 7);
        $this->assertSame(0, $r['shortfall']);
        $this->assertSame('3', $r['lines'][0]['key']);
        $this->assertSame(8, count(array_unique($r['ids'])));
        $this->assertSame(1, $this->picker->pick(['topic_id' => $this->section->id], ['any' => 7, 3 => 2], 7)['shortfall']);
    }

    public function test_the_draw_is_seeded_and_respects_exclusions(): void
    {
        $this->bank($this->child, [3 => 12]);
        $filters = ['topic_id' => $this->child->id];

        $a = $this->picker->pick($filters, [3 => 5], 1234);
        $b = $this->picker->pick($filters, [3 => 5], 1234);
        $c = $this->picker->pick($filters, [3 => 5], 98765);
        $this->assertSame($a['ids'], $b['ids']);
        $this->assertNotSame($a['ids'], $c['ids']);
        $this->assertSame(1234, $a['seed']);
        $this->assertIsInt($this->picker->pick($filters, [3 => 1])['seed']);

        $d = $this->picker->pick($filters + ['exclude_ids' => $a['ids']], [3 => 12], 5);
        $this->assertSame(7, $d['picked']);
        $this->assertSame([], array_intersect($a['ids'], $d['ids']));
    }

    public function test_a_students_recent_variant_questions_come_last(): void
    {
        $this->bank($this->child, [2 => 6]);
        $student = User::factory()->create(['role' => 'student']);
        $all = QuestionBank::query()->orderBy('id')->pluck('id')->map(fn ($id) => (string) $id)->all();
        $seen = array_slice($all, 0, 4);
        ExamVariant::factory()->create(['user_id' => $student->id, 'question_ids' => $seen, 'generated_at' => now()->subWeek()]);
        // an old variant does not count
        ExamVariant::factory()->create(['user_id' => $student->id, 'question_ids' => array_slice($all, 4), 'generated_at' => now()->subWeeks(QuestionPicker::AVOID_WEEKS + 2)]);

        foreach ([1, 2, 3] as $seed) {
            $r = $this->picker->pick(['topic_id' => $this->child->id], [2 => 2], $seed, $student->id);
            $this->assertSame([], array_intersect($seen, $r['ids']), 'fresh questions first');
        }
        $r = $this->picker->pick(['topic_id' => $this->child->id], [2 => 4], 9, $student->id);
        $this->assertCount(4, $r['ids']);
        $this->assertCount(2, array_intersect($seen, $r['ids']), 'then the seen ones, not a shortfall');
        $this->assertSame(0, $r['shortfall']);
    }

    public function test_bad_distributions_are_refused(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        QuestionPicker::normalizeDistribution(['extreme' => 2]);
    }

    public function test_normalize_skips_zero_and_refuses_negative(): void
    {
        $this->assertSame([['key' => '3', 'levels' => [3], 'count' => 2]], QuestionPicker::normalizeDistribution(['easy' => 0, 3 => '2']));
        $this->expectException(\InvalidArgumentException::class);
        QuestionPicker::normalizeDistribution([3 => -1]);
    }
}
