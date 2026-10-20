<?php

namespace Tests\Feature\Exam;

use App\Models\Exam\ExamSourceDocument;
use App\Models\Exam\Subject;
use App\Models\Exam\Topic;
use App\Models\QuestionBank;
use App\Models\User;
use App\Services\Exam\QuestionDraftService;
use Database\Factories\Exam\ExamQuestionFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * docs/exam/03 §2.1: a question never enters a bank without a difficulty the
 * teacher chose — no silent "medium" / 3 anywhere.
 */
class DifficultyEntryTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;

    private Subject $math;

    private Topic $topic;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        $this->teacher = User::factory()->create(['role' => 'teacher', 'status' => 'active']);
        $this->math = Subject::factory()->math()->create();
        $this->topic = Topic::factory()->create(['subject_id' => $this->math->id, 'code' => 'math.algebra.linear']);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'subject_id' => $this->math->id,
            'topic_id' => $this->topic->id,
            'question_type' => 'single_choice',
            'question_text' => '$2x = 6$ bo\'lsa, $x$ = ?',
            'options' => [
                ['text' => '$3$', 'is_correct' => true], ['text' => '$2$', 'is_correct' => false],
                ['text' => '$6$', 'is_correct' => false], ['text' => '$12$', 'is_correct' => false],
            ],
            'intent' => 'review',
        ], $overrides);
    }

    public function test_bank_form_requires_a_difficulty_for_every_intent(): void
    {
        foreach (['draft', 'review', 'approve'] as $intent) {
            $this->actingAs($this->teacher)->post(route('teacher.exam.questions.store'), $this->payload(['intent' => $intent]))
                ->assertSessionHasErrors('difficulty_level');
            $this->actingAs($this->teacher)->post(route('teacher.exam.questions.store'), $this->payload(['intent' => $intent, 'difficulty_level' => null]))
                ->assertSessionHasErrors('difficulty_level');
        }
        $this->assertSame(0, QuestionBank::query()->count());

        $this->actingAs($this->teacher)->post(route('teacher.exam.questions.store'), $this->payload(['difficulty_level' => 4]))
            ->assertSessionHasNoErrors();
        $q = QuestionBank::query()->firstOrFail();
        $this->assertSame(4, $q->difficulty_level);
        $this->assertSame('hard', $q->difficulty);

        // the edit form cannot clear it either
        $this->actingAs($this->teacher)->put(route('teacher.exam.questions.update', $q), $this->payload(['difficulty_level' => '']))
            ->assertSessionHasErrors('difficulty_level');
        $this->assertSame(4, $q->fresh()->difficulty_level);
    }

    public function test_create_form_has_no_preselected_level_but_keeps_the_one_just_chosen(): void
    {
        $this->actingAs($this->teacher)->get(route('teacher.exam.questions.create'))
            ->assertOk()->assertInertia(fn (Assert $p) => $p->where('defaults.difficulty_level', null));

        // "Saqlash va yana qo'shish" comes back with the level the teacher picked
        $this->actingAs($this->teacher)->post(route('teacher.exam.questions.store'), $this->payload(['difficulty_level' => 5, 'after' => 'create']))
            ->assertRedirect(route('teacher.exam.questions.create', ['subject' => 'math', 'topic' => $this->topic->id, 'type' => 'single_choice', 'difficulty' => 5]));
        $this->actingAs($this->teacher)->get(route('teacher.exam.questions.create', ['difficulty' => 5]))
            ->assertInertia(fn (Assert $p) => $p->where('defaults.difficulty_level', 5));
        $this->actingAs($this->teacher)->get(route('teacher.exam.questions.create', ['difficulty' => 'x']))
            ->assertInertia(fn (Assert $p) => $p->where('defaults.difficulty_level', null));
    }

    public function test_review_inline_edit_sets_but_never_clears_the_level(): void
    {
        $q = ExamQuestionFactory::new()->forTopic($this->topic)->inReview()->withUnmarkedOptions()->create(['difficulty_level' => null]);

        $this->actingAs($this->teacher)->patch(route('teacher.exam.review.update', $q), ['difficulty_level' => null])
            ->assertSessionHasErrors('difficulty_level');
        $this->actingAs($this->teacher)->patch(route('teacher.exam.review.update', $q), ['difficulty_level' => 9])
            ->assertSessionHasErrors('difficulty_level');
        $this->actingAs($this->teacher)->patch(route('teacher.exam.review.update', $q), ['difficulty_level' => 2])
            ->assertSessionHasNoErrors();
        $this->assertSame(2, $q->fresh()->difficulty_level);

        // other inline edits do not need to resend it
        $this->actingAs($this->teacher)->patch(route('teacher.exam.review.update', $q), ['source_ref' => 'To\'plam, 5-bet'])
            ->assertSessionHasNoErrors();
        $this->assertSame(2, $q->fresh()->difficulty_level);
    }

    public function test_ai_draft_run_requires_a_level_and_every_draft_stores_one(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $this->actingAs($admin)->post(route('admin.exam.ai-drafts.store'), ['subject_id' => $this->math->id, 'count' => 2])
            ->assertSessionHasErrors('difficulty_level');

        config(['exam.ai.api_key' => 'sk-ant-test', 'exam.ai.model' => 'claude-opus-5-5', 'exam.ai.store_suggested_answer' => false]);
        ExamSourceDocument::create([
            'subject_id' => $this->math->id, 'topic_id' => $this->topic->id, 'title' => 'To\'plam', 'source_ref' => 'Skanavi',
            'content_text' => '12-masala. 3x + 5 = 20 tenglamani yeching.', 'uploaded_by' => $this->teacher->id,
        ]);
        $item = fn (string $stem, int $level) => [
            'stem' => $stem,
            'options' => [['label' => 'A', 'text' => '$1$'], ['label' => 'B', 'text' => '$2$'], ['label' => 'C', 'text' => '$3$'], ['label' => 'D', 'text' => '$4$']],
            'topic_code' => 'math.algebra.linear', 'difficulty_level' => $level, 'source_ref' => 'AI', 'units_note' => null, 'suggested_correct_label' => 'A',
        ];
        Http::fake(['api.anthropic.com/*' => Http::response([
            'id' => 'msg_test', 'type' => 'message', 'role' => 'assistant', 'model' => 'claude-opus-5-5',
            'content' => [['type' => 'text', 'text' => json_encode(['questions' => [$item('$x + 1 = 2$, $x$ = ?', 2), $item('$x + 2 = 6$, $x$ = ?', 4)]])]],
            'stop_reason' => 'end_turn', 'usage' => ['input_tokens' => 100, 'output_tokens' => 200],
        ])]);

        $run = app(QuestionDraftService::class)->start([
            'subject_id' => $this->math->id, 'topic_id' => $this->topic->id, 'difficulty_level' => 3, 'count' => 2,
        ], $this->teacher)->fresh();

        $this->assertSame("succeeded", $run->status, (string) json_encode($run->only(["status", "error", "validation_errors"])));
        $drafts = QuestionBank::query()->whereIn("id", $run->question_ids ?? [])->get();
        $this->assertCount(2, $drafts);
        $this->assertSame(3, $run->difficulty_level);
        $this->assertEqualsCanonicalizing([2, 4], $drafts->pluck('difficulty_level')->all());
        $this->assertTrue($drafts->every(fn ($d) => $d->difficulty_level !== null));
        $this->assertEqualsCanonicalizing(['easy', 'hard'], $drafts->pluck('difficulty')->all());
    }
}
