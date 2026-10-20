<?php

namespace Tests\Feature\Exam;

use App\Enums\Exam\QuestionOrigin;
use App\Enums\Exam\ReviewStatus;
use App\Models\Exam\ExamSourceDocument;
use App\Models\Exam\QuestionBankOption;
use App\Models\Exam\QuestionDraftRun;
use App\Models\Exam\Subject;
use App\Models\Exam\Topic;
use App\Models\QuestionBank;
use App\Models\User;
use App\Services\Exam\QuestionDraftPrompt;
use App\Services\Exam\QuestionDraftSchema;
use App\Services\Exam\QuestionDraftService;
use App\Services\Exam\QuestionDraftWeeklyPlanner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Stage 4: AI drafts through the (faked) Anthropic Messages API.
 * No real request is ever sent: Http::preventStrayRequests().
 */
class QuestionDraftServiceTest extends TestCase
{
    use RefreshDatabase;

    private Subject $math;

    private Topic $section;

    private Topic $topic;

    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();

        config([
            'exam.ai.api_key' => 'sk-ant-test',
            'exam.ai.model' => 'claude-opus-5-5',
            'exam.ai.store_suggested_answer' => false,
            'exam.ai.weekly.budget_usd' => 15,
        ]);

        $this->math = Subject::factory()->math()->create();
        $this->section = Topic::factory()->create(['subject_id' => $this->math->id, 'code' => 'math.algebra', 'name_uz' => 'Algebra']);
        $this->topic = Topic::factory()->childOf($this->section)->create(['code' => 'math.algebra.linear', 'name_uz' => 'Chiziqli tenglamalar']);
        $this->teacher = User::factory()->create(['role' => 'teacher']);

        ExamSourceDocument::create([
            'subject_id' => $this->math->id,
            'topic_id' => $this->section->id,
            'title' => 'Algebra to\'plami',
            'source_ref' => 'Skanavi, 2010',
            'content_text' => "12-masala. 3x + 5 = 20 tenglamani yeching.\n\n13-masala. 2(x - 1) = 10 tenglamani yeching.",
            'uploaded_by' => $this->teacher->id,
        ]);
    }

    private function item(array $override = []): array
    {
        return array_merge([
            'stem' => '$3x + 5 = 20$ tenglamani yeching.',
            'options' => [
                ['label' => 'A', 'text' => '$3$'],
                ['label' => 'B', 'text' => '$5$'],
                ['label' => 'C', 'text' => '$15$'],
                ['label' => 'D', 'text' => '$\\frac{25}{3}$'],
            ],
            'topic_code' => 'math.algebra.linear',
            'difficulty_level' => 2,
            'source_ref' => '[S1] Skanavi, 2010, 12-masala',
            'units_note' => null,
            'suggested_correct_label' => 'B',
        ], $override);
    }

    private function reply(array $items, array $usage = ['input_tokens' => 1000, 'output_tokens' => 2000], string $stop = 'end_turn'): array
    {
        return [
            'id' => 'msg_test',
            'type' => 'message',
            'role' => 'assistant',
            'model' => 'claude-opus-5-5',
            'content' => [['type' => 'text', 'text' => json_encode(['questions' => $items])]],
            'stop_reason' => $stop,
            'usage' => $usage,
        ];
    }

    private function fakeApi(array $body, int $status = 200): void
    {
        Http::fake(['api.anthropic.com/*' => Http::response($body, $status)]);
    }

    private function startRun(int $count = 2): QuestionDraftRun
    {
        return app(QuestionDraftService::class)->start([
            'subject_id' => $this->math->id,
            'topic_id' => $this->topic->id,
            'difficulty_level' => 2,
            'exam_format' => 'milliy_sertifikat',
            'count' => $count,
        ], $this->teacher)->fresh();
    }

    public function test_valid_reply_creates_in_review_drafts_with_no_correct_option(): void
    {
        $this->fakeApi($this->reply([
            $this->item(),
            $this->item(['stem' => '$2(x - 1) = 10$ tenglamani yeching.', 'suggested_correct_label' => 'C']),
        ]));

        $run = $this->startRun();

        $this->assertSame(QuestionDraftRun::STATUS_SUCCEEDED, $run->status);
        $this->assertSame(2, $run->drafts_created);
        $this->assertCount(2, $run->question_ids);

        $questions = QuestionBank::query()->whereIn('id', $run->question_ids)->get();
        foreach ($questions as $q) {
            $this->assertSame(QuestionOrigin::AiDraft, $q->origin);
            $this->assertSame(ReviewStatus::InReview, $q->review_status);
            $this->assertSame($this->topic->id, $q->topic_id);
            $this->assertSame('single_choice', $q->question_type);
            $this->assertSame(['milliy_sertifikat'], $q->exam_formats);
            $this->assertStringStartsWith('[S1]', $q->source_ref);
        }
        // The AI never marks the answer; the hint is off by default too.
        $options = QuestionBankOption::query()->whereIn('question_id', $run->question_ids)->get();
        $this->assertCount(8, $options);
        $this->assertTrue($options->every(fn ($o) => $o->is_correct === null && $o->ai_suggested_correct === null));
        $this->assertSame(0, QuestionBank::approved()->count());
        $this->assertSame(2, QuestionBank::reviewQueue()->count());

        Http::assertSent(function (Request $request) {
            $body = $request->data();

            return $request->url() === 'https://api.anthropic.com/v1/messages'
                && $request->hasHeader('x-api-key', 'sk-ant-test')
                && $request->hasHeader('anthropic-version', '2023-06-01')
                && $request->hasHeader('anthropic-beta', 'server-side-fallback-2026-07-01')
                && $body['model'] === 'claude-opus-5-5'
                && $body['fallbacks'] === 'default'
                && $body['output_config']['format']['type'] === 'json_schema'
                && $body['output_config']['format']['schema'] === QuestionDraftSchema::jsonSchema()
                && ! isset($body['thinking'])
                && str_contains($body['messages'][0]['content'], '3x + 5 = 20')
                && str_contains($body['messages'][0]['content'], 'math.algebra.linear')
                && str_contains($body['system'], 'Exactly one option is correct');
        });
    }

    public function test_model_comes_from_config_and_prompt_version_is_stored(): void
    {
        config(['exam.ai.model' => 'claude-sonnet-5-5']);
        $this->fakeApi(['model' => 'claude-sonnet-5-5'] + $this->reply([$this->item()]));

        $run = $this->startRun(1);

        $prompt = QuestionDraftPrompt::load('question_draft');
        $this->assertSame('claude-sonnet-5-5', $run->model);
        $this->assertSame('question_draft', $run->prompt_name);
        $this->assertSame($prompt->version, $run->prompt_version);
        Http::assertSent(fn (Request $r) => $r->data()['model'] === 'claude-sonnet-5-5');
    }

    public function test_hint_flag_stores_the_ai_guess_only_as_a_hidden_hint(): void
    {
        config(['exam.ai.store_suggested_answer' => true]);
        $this->fakeApi($this->reply([$this->item()]));

        $run = $this->startRun(1);

        $options = QuestionBankOption::query()->where('question_id', $run->question_ids[0])->orderBy('label')->get();
        $this->assertSame([false, true, false, false], $options->pluck('ai_suggested_correct')->all());
        $this->assertTrue($options->every(fn ($o) => $o->is_correct === null));
    }

    public function test_cost_log_records_tokens_and_estimated_cost(): void
    {
        $this->fakeApi($this->reply([$this->item()], [
            'input_tokens' => 1000,
            'output_tokens' => 2000,
            'cache_creation_input_tokens' => 100,
            'cache_read_input_tokens' => 1000,
        ]));

        $run = $this->startRun(1);

        // 1000*4 + 2000*20 + 100*5 + 1000*0.2 = 44 700 per 1M -> 0.0447 USD
        $this->assertSame(1000, $run->input_tokens);
        $this->assertSame(2000, $run->output_tokens);
        $this->assertSame(100, $run->cache_creation_input_tokens);
        $this->assertSame(1000, $run->cache_read_input_tokens);
        $this->assertEqualsWithDelta(0.0447, (float) $run->estimated_cost_usd, 0.000001);
        $this->assertSame('claude-opus-5-5', $run->response_model);
        $this->assertEqualsWithDelta(0.0447, app(QuestionDraftService::class)->weeklySpend(), 0.000001);
    }

    public function test_invalid_items_are_rejected_and_valid_ones_kept(): void
    {
        $this->fakeApi($this->reply([
            $this->item(),
            $this->item(['stem' => 'Uch variantli savol $x = 1$?', 'options' => [
                ['label' => 'A', 'text' => '1'], ['label' => 'B', 'text' => '2'], ['label' => 'C', 'text' => '3'],
            ]]),
            $this->item(['stem' => 'Begona mavzu $y = 2$ bo\'lsa?', 'topic_code' => 'physics.kinematics']),
            $this->item(['stem' => 'Takror variantlar $z = 3$ bo\'lsa?', 'options' => [
                ['label' => 'A', 'text' => '$1$'], ['label' => 'B', 'text' => '$ 1 $'], ['label' => 'C', 'text' => '$2$'], ['label' => 'D', 'text' => '$3$'],
            ]]),
            $this->item(['stem' => 'Qiyinlik $w = 4$ bo\'lsa?', 'difficulty_level' => 9]),
            $this->item(['stem' => 'Harflar tartibi $v = 5$ bo\'lsa?', 'options' => [
                ['label' => 'B', 'text' => '1'], ['label' => 'A', 'text' => '2'], ['label' => 'C', 'text' => '3'], ['label' => 'D', 'text' => '4'],
            ]]),
            $this->item(['stem' => 'Ortiqcha maydon $u = 6$?', 'is_correct' => 'A']),
            'not an object',
        ]));

        $run = $this->startRun(8);

        $this->assertSame(QuestionDraftRun::STATUS_PARTIAL, $run->status);
        $this->assertSame(1, $run->drafts_created, json_encode($run->validation_errors));
        $this->assertSame(7, $run->drafts_rejected);
        $this->assertCount(7, $run->validation_errors);
        $this->assertSame(1, QuestionBank::query()->where('origin', 'ai_draft')->count());
    }

    public function test_physics_items_need_units_note(): void
    {
        $physics = Subject::factory()->physics()->create();
        $kin = Topic::factory()->create(['subject_id' => $physics->id, 'code' => 'physics.kinematics']);
        ExamSourceDocument::create(['subject_id' => $physics->id, 'title' => 'Rimkevich', 'content_text' => '34-masala. Jism 10 m/s tezlik bilan otildi.']);
        $this->fakeApi($this->reply([
            $this->item(['topic_code' => 'physics.kinematics', 'units_note' => null]),
            $this->item(['stem' => 'Jism $v_0 = 10\\ \\mathrm{m/s}$ bilan otildi. $g = 10\\ \\mathrm{m/s^2}$. Balandlik?', 'topic_code' => 'physics.kinematics', 'units_note' => 'Javob m da; g = 10 m/s²']),
        ]));

        $run = app(QuestionDraftService::class)->start(['subject_id' => $physics->id, 'topic_id' => $kin->id, 'difficulty_level' => 2, 'count' => 2])->fresh();

        $this->assertSame(1, $run->drafts_created);
        $this->assertSame(1, $run->drafts_rejected);
        $this->assertSame('Javob m da; g = 10 m/s²', QuestionBank::find($run->question_ids[0])->units_note);
    }

    public function test_reply_that_is_not_the_schema_fails_the_run(): void
    {
        $this->fakeApi(['content' => [['type' => 'text', 'text' => 'Here are your questions: ...']], 'stop_reason' => 'end_turn', 'model' => 'claude-opus-5-5', 'usage' => ['input_tokens' => 10, 'output_tokens' => 5]]);

        $run = $this->startRun(1);

        $this->assertSame(QuestionDraftRun::STATUS_FAILED, $run->status);
        $this->assertSame('reply does not match the JSON schema', $run->error);
        $this->assertSame(10, $run->input_tokens); // usage is logged even for a failed reply
        $this->assertSame(0, QuestionBank::count());
    }

    public function test_refusal_and_truncated_replies_fail_without_drafts(): void
    {
        Http::fakeSequence('api.anthropic.com/*')
            ->push($this->reply([], stop: 'refusal'))
            ->push($this->reply([$this->item()], stop: 'max_tokens'));
        $this->assertStringContainsString('refusal', $this->startRun(1)->error);

        $run = $this->startRun(1);
        $this->assertSame(QuestionDraftRun::STATUS_FAILED, $run->status);
        $this->assertStringContainsString('max_tokens', $run->error);
        $this->assertSame(0, QuestionBank::count());
    }

    public function test_api_error_is_logged_without_content(): void
    {
        $this->fakeApi(['type' => 'error', 'error' => ['type' => 'overloaded_error', 'message' => 'Overloaded']], 529);

        $run = $this->startRun(1);

        $this->assertSame(QuestionDraftRun::STATUS_FAILED, $run->status);
        $this->assertSame('API HTTP 529: overloaded_error', $run->error);
    }

    public function test_duplicates_of_bank_questions_are_skipped(): void
    {
        $this->fakeApi($this->reply([$this->item(), $this->item(['stem' => '$3x+5=20$  tenglamani  yeching'])]));

        $run = $this->startRun(2);

        $this->assertSame(1, $run->drafts_created);
        $this->assertSame(1, $run->duplicates_skipped);
    }

    public function test_without_api_key_nothing_is_sent_and_the_run_is_skipped(): void
    {
        config(['exam.ai.api_key' => '']);
        Http::fake();

        $run = $this->startRun(1);

        $this->assertSame(QuestionDraftRun::STATUS_SKIPPED, $run->status);
        $this->assertSame('ANTHROPIC_API_KEY is not set', $run->error);
        Http::assertNothingSent();
    }

    public function test_without_source_documents_the_run_is_skipped(): void
    {
        ExamSourceDocument::query()->update(['is_active' => false]);
        Http::fake();

        $run = $this->startRun(1);

        $this->assertSame(QuestionDraftRun::STATUS_SKIPPED, $run->status);
        Http::assertNothingSent();
    }

    public function test_weekly_planner_splits_the_target_and_is_idempotent(): void
    {
        config(['exam.ai.weekly.target' => 20, 'exam.ai.weekly.batch_size' => 10]);
        $this->fakeApi($this->reply([$this->item()]));

        $runs = app(QuestionDraftWeeklyPlanner::class)->run();
        $this->assertCount(2, $runs);
        $this->assertTrue(collect($runs)->every(fn ($r) => $r->trigger === QuestionDraftRun::TRIGGER_SCHEDULE && $r->count_requested === 10));

        $this->assertCount(0, app(QuestionDraftWeeklyPlanner::class)->run(), 'second call in the same week starts nothing');
    }

    public function test_weekly_planner_does_nothing_without_key_or_over_budget(): void
    {
        config(['exam.ai.api_key' => '']);
        Http::fake();
        $this->assertSame([], app(QuestionDraftWeeklyPlanner::class)->run());

        config(['exam.ai.api_key' => 'sk-ant-test', 'exam.ai.weekly.budget_usd' => 1]);
        QuestionDraftRun::create(['subject_id' => $this->math->id, 'difficulty_level' => 3, 'count_requested' => 1, 'status' => 'succeeded', 'estimated_cost_usd' => 1.5]);
        $this->assertSame([], app(QuestionDraftWeeklyPlanner::class)->run());
        Http::assertNothingSent();

        $this->artisan('exam:ai-drafts-weekly')->assertSuccessful();
    }

    public function test_prompt_file_is_versioned_and_rendered(): void
    {
        $prompt = QuestionDraftPrompt::load('question_draft');
        $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+$/', $prompt->version);

        $text = $prompt->renderUser(['subject_name' => 'Matematika', 'sources' => 'x^{{2}} {{unknown}}', 'count' => 3]);
        $this->assertStringContainsString('x^{{2}} {{unknown}}', $text, 'inserted values are not re-scanned');
        $this->assertStringNotContainsString('{{topic_name}}', $text);

        $this->expectException(\RuntimeException::class);
        QuestionDraftPrompt::parse("---\nname: x\n---\n<!-- system -->\nA\n<!-- user -->\nB");
    }
}
