<?php

namespace Tests\Feature\Exam;

use App\Jobs\Exam\GenerateExamVariantsJob;
use App\Models\Exam\ExamSession;
use App\Models\Exam\ExamVariant;
use App\Models\QuestionBank;
use App\Services\Exam\ExamReadinessChecker;
use App\Services\Exam\VariantGenerator;
use Database\Factories\Exam\ExamQuestionFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Concerns\BuildsExams;
use Tests\TestCase;

class VariantGeneratorTest extends TestCase
{
    use BuildsExams, RefreshDatabase;

    public function test_variant_follows_the_blueprint_with_distinct_approved_questions_only(): void
    {
        $subject = $this->examSubject();
        [$section, $a, $b] = $this->examTopics($subject);
        $easyA = $this->approvedQuestions($a, 3, 2);
        $anyB = $this->approvedQuestions($b, 3, 3);
        $open = $this->approvedQuestions($b, 2, 4, 'numeric');
        ExamQuestionFactory::new()->forTopic($a)->inReview()->withUnmarkedOptions()->count(5)->create(['difficulty_level' => 2]);

        $blueprint = $this->examBlueprint($subject, [
            [$a, 2, 'closed', 2],
            [$section, null, 'closed', 3],
            [$section, null, 'open', 2],
        ]);
        $session = $this->openWeeklySession($blueprint);
        $student = $this->examStudent($subject);

        $variant = app(VariantGenerator::class)->generate($session, $student);

        $ids = $variant->question_ids;
        $this->assertCount(7, $ids);
        $this->assertCount(7, array_unique($ids), 'no question twice');
        $approved = QuestionBank::query()->approved()->pluck('id')->map(fn ($id) => (string) $id)->all();
        $this->assertEmpty(array_diff($ids, $approved), 'only approved questions');

        $byRule = collect($variant->rule_map)->countBy();
        foreach ($blueprint->rules as $rule) {
            $this->assertSame($rule->count, $byRule[$rule->id] ?? 0, 'rule count respected');
        }
        $firstRule = $blueprint->rules->first();
        foreach (collect($variant->rule_map)->filter(fn ($r) => $r === $firstRule->id)->keys() as $qid) {
            $this->assertTrue($easyA->pluck('id')->contains($qid), 'difficulty 2 rule picks difficulty 2 questions of topic A');
        }

        // closed part first, open part last
        $this->assertEqualsCanonicalizing($open->pluck('id')->all(), array_slice($ids, 5));

        // options of every closed question, as a permutation of its own options
        foreach ([...$easyA, ...$anyB] as $q) {
            if (in_array($q->id, $ids, true)) {
                $this->assertEqualsCanonicalizing($q->answerOptions()->pluck('id')->all(), $variant->option_orders[$q->id]);
            }
        }
        $this->assertFalse($variant->used_fallback);
        $this->assertNull($variant->warnings);

        // one variant per student per session
        $this->assertTrue(app(VariantGenerator::class)->generate($session, $student)->is($variant));
    }

    public function test_seed_reproduces_the_same_variant_and_different_seeds_shuffle(): void
    {
        $subject = $this->examSubject();
        [$section, $a] = $this->examTopics($subject);
        $this->approvedQuestions($a, 12, 2);
        $blueprint = $this->examBlueprint($subject, [[$section, null, 'closed', 8]]);

        $generator = app(VariantGenerator::class);
        $one = $generator->plan($blueprint, 424242);
        $two = $generator->plan($blueprint->fresh(), 424242);
        $this->assertSame($one, $two, 'same seed -> same questions, order and option order');

        $plans = collect(range(1, 6))->map(fn ($seed) => $generator->plan($blueprint, $seed * 7919)['question_ids']);
        $this->assertGreaterThan(1, $plans->map(fn ($ids) => implode(',', $ids))->unique()->count());

        // the stored seed regenerates the stored variant
        $session = $this->openWeeklySession($blueprint);
        $variant = $generator->generate($session, $this->examStudent($subject), 99);
        $this->assertSame(99, $variant->seed);
        $replay = $generator->plan($blueprint, 99);
        $this->assertSame($variant->question_ids, $replay['question_ids']);
        $this->assertSame($variant->option_orders, $replay['option_orders']);
    }

    public function test_students_get_different_variants_and_recently_seen_questions_are_avoided(): void
    {
        $subject = $this->examSubject();
        [$section, $a] = $this->examTopics($subject);
        $this->approvedQuestions($a, 10, 2);
        $blueprint = $this->examBlueprint($subject, [[$section, null, 'closed', 5]], ['avoid_repeat_weeks' => 4]);
        $student = $this->examStudent($subject);
        $generator = app(VariantGenerator::class);

        $lastWeek = $this->openWeeklySession($blueprint, ['starts_at' => now()->subWeek(), 'ends_at' => now()->subWeek()->addHours(3), 'status' => 'graded']);
        $old = $generator->generate($lastWeek, $student);

        $thisWeek = $this->openWeeklySession($blueprint);
        $new = $generator->generate($thisWeek, $student);
        $this->assertSame([], array_values(array_intersect($old->question_ids, $new->question_ids)), '10 questions: two disjoint variants of 5');
        $this->assertNull($new->warnings);

        // a third week must repeat, and says so
        $third = $this->openWeeklySession($blueprint, ['starts_at' => now()->addWeek(), 'ends_at' => now()->addWeek()->addHours(3), 'status' => 'scheduled']);
        $again = $generator->generate($third, $student);
        $this->assertCount(5, $again->question_ids);
        $this->assertSame('repeat', $again->warnings[0]['type']);

        // two students of the same session get differently ordered/picked variants (with high probability)
        $others = collect(range(1, 4))->map(fn () => $generator->generate($thisWeek, $this->examStudent($subject))->question_ids);
        $this->assertGreaterThan(1, $others->map(fn ($ids) => implode(',', $ids))->unique()->count());
    }

    public function test_short_bank_fills_from_the_neighbouring_difficulty_and_reports_the_rest(): void
    {
        $subject = $this->examSubject();
        [$section, $a] = $this->examTopics($subject);
        $this->approvedQuestions($a, 1, 2);
        $this->approvedQuestions($a, 2, 3);
        $this->approvedQuestions($a, 3, 5); // not adjacent to 2

        $blueprint = $this->examBlueprint($subject, [[$a, 2, 'closed', 4]]);
        $session = $this->openWeeklySession($blueprint);
        $variant = app(VariantGenerator::class)->generate($session, $this->examStudent($subject));

        $this->assertCount(3, $variant->question_ids, '1 strict + 2 from difficulty 3, difficulty 5 is never used');
        $this->assertTrue($variant->used_fallback);
        $types = collect($variant->warnings)->pluck('type')->all();
        $this->assertSame(['adjacent', 'shortage'], $types);
        $this->assertSame([3 => 2], collect($variant->warnings)->firstWhere('type', 'adjacent')['levels']);
        $this->assertSame(1, collect($variant->warnings)->firstWhere('type', 'shortage')['missing']);
        $levels = QuestionBank::query()->whereIn('id', $variant->question_ids)->pluck('difficulty_level')->sort()->values()->all();
        $this->assertSame([2, 3, 3], $levels);

        // the teacher sees it before the session opens
        $report = app(ExamReadinessChecker::class)->forSession($session);
        $this->assertSame('short', $report['bank']['status']);
        $this->assertSame(['required' => 4, 'candidates' => 1, 'shortfall' => 3, 'adjacent' => 2],
            collect($report['bank']['rules'][0])->only(['required', 'candidates', 'shortfall', 'adjacent'])->all());

        // without the fallback the rule simply stays short
        $strict = $this->examBlueprint($subject, [[$a, 2, 'closed', 4]], ['allow_adjacent_difficulty' => false]);
        $variant = app(VariantGenerator::class)->generate($this->openWeeklySession($strict), $this->examStudent($subject));
        $this->assertCount(1, $variant->question_ids);
        $this->assertFalse($variant->used_fallback);
    }

    public function test_session_pre_generation_covers_the_audience_via_queued_job_and_command(): void
    {
        $subject = $this->examSubject();
        [$section, $a] = $this->examTopics($subject);
        $this->approvedQuestions($a, 6, 2);
        $blueprint = $this->examBlueprint($subject, [[$section, null, 'closed', 3]]);
        $session = ExamSession::factory()->create([
            'subject_id' => $subject->id, 'blueprint_id' => $blueprint->id,
            'starts_at' => now()->addHours(2), 'ends_at' => now()->addHours(5), 'status' => 'scheduled',
        ]);
        $students = collect(range(1, 3))->map(fn () => $this->examStudent($subject));
        $this->examStudent(['physics']); // other subject: not in the audience

        Queue::fake();
        $this->artisan('exam:tick')->assertSuccessful();
        Queue::assertPushed(GenerateExamVariantsJob::class, fn ($job) => $job->sessionId === $session->id);

        (new GenerateExamVariantsJob($session->id))->handle(app(VariantGenerator::class));
        $this->assertSame(3, ExamVariant::query()->where('session_id', $session->id)->count());
        $this->assertEqualsCanonicalizing($students->pluck('id')->all(), ExamVariant::query()->where('session_id', $session->id)->pluck('user_id')->all());
        $this->assertNotNull($session->fresh()->variants_generated_at);

        $this->artisan('exam:generate-variants', ['session' => $session->id])
            ->expectsOutputToContain('created 0, existing 3')
            ->assertSuccessful();
    }
}
