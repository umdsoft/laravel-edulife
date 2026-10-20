<?php

namespace Tests\Feature\Media;

use App\Models\Exam\ExamSourceDocument;
use App\Models\Exam\QuestionDraftRun;
use App\Models\Exam\Subject;
use App\Models\Exam\Topic;
use App\Models\QuestionBank;
use App\Models\User;
use App\Services\Exam\QuestionDraftPrompt;
use App\Services\Exam\QuestionDraftService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** AI drafts may carry ```graph blocks (validated by GraphSpec) but never images. */
class AiDraftGraphTest extends TestCase
{
    use RefreshDatabase;

    private Subject $math;

    private Topic $topic;

    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        config(['exam.ai.api_key' => 'sk-ant-test', 'exam.ai.model' => 'claude-opus-5-5', 'exam.ai.weekly.budget_usd' => 15]);

        $this->math = Subject::factory()->math()->create();
        $section = Topic::factory()->create(['subject_id' => $this->math->id, 'code' => 'math.functions', 'name_uz' => 'Funksiyalar']);
        $this->topic = Topic::factory()->childOf($section)->create(['code' => 'math.functions.quadratic', 'name_uz' => 'Kvadrat funksiya']);
        $this->teacher = User::factory()->create(['role' => 'teacher']);
        ExamSourceDocument::create([
            'subject_id' => $this->math->id, 'topic_id' => $section->id, 'title' => 'Funksiyalar', 'source_ref' => 'Skanavi',
            'content_text' => '5-masala. y = x^2 - 2 funksiya grafigi bo\'yicha f(2) ni toping.', 'uploaded_by' => $this->teacher->id,
        ]);
    }

    private function item(string $stem): array
    {
        return [
            'stem' => $stem,
            'options' => [['label' => 'A', 'text' => '$0$'], ['label' => 'B', 'text' => '$2$'], ['label' => 'C', 'text' => '$4$'], ['label' => 'D', 'text' => '$-2$']],
            'topic_code' => 'math.functions.quadratic', 'difficulty_level' => 2, 'source_ref' => '[S1] Skanavi, 5-masala',
            'units_note' => null, 'suggested_correct_label' => 'B',
        ];
    }

    public function test_valid_graphs_are_kept_and_invalid_graphs_or_images_are_rejected_with_a_reason(): void
    {
        $good = "Grafik bo'yicha \$f(2)\$ ni toping.\n```graph\n{\"x\":[-4,4],\"y\":[-3,9],\"functions\":[{\"expr\":\"x^2-2\",\"label\":\"f(x)\"}]}\n```";
        $badExpr = "Grafikka qarang.\n```graph\n{\"x\":[-4,4],\"y\":[-3,9],\"functions\":[{\"expr\":\"require('fs')\"}]}\n```";
        $badKey = "Grafikka qarang!\n```graph\n{\"x\":[-4,4],\"y\":[-3,9],\"script\":\"x\"}\n```";
        $image = "Rasmga qarang: ![a](/storage/questions/2026/10/abc.webp) \$f(2)\$ = ?";
        Http::fake(['api.anthropic.com/*' => Http::response([
            'id' => 'msg', 'type' => 'message', 'role' => 'assistant', 'model' => 'claude-opus-5-5', 'stop_reason' => 'end_turn',
            'content' => [['type' => 'text', 'text' => json_encode(['questions' => [$this->item($good), $this->item($badExpr), $this->item($badKey), $this->item($image)]])]],
            'usage' => ['input_tokens' => 1000, 'output_tokens' => 2000],
        ])]);

        $run = app(QuestionDraftService::class)->start([
            'subject_id' => $this->math->id, 'topic_id' => $this->topic->id, 'difficulty_level' => 2,
            'exam_format' => 'milliy_sertifikat', 'count' => 4,
        ], $this->teacher)->fresh();

        $this->assertSame(QuestionDraftRun::STATUS_PARTIAL, $run->status);
        $this->assertSame(1, $run->drafts_created);
        $this->assertSame(3, $run->drafts_rejected);
        $this->assertSame($good, QuestionBank::query()->sole()->question_text);

        $reasons = collect($run->validation_errors)->keyBy('index');
        $this->assertStringContainsString('invalid graph', implode(' ', $reasons[1]['errors']));
        $this->assertStringContainsString('Funksiya 1', implode(' ', $reasons[1]['errors']));
        $this->assertStringContainsString('script', implode(' ', $reasons[2]['errors']));
        $this->assertStringContainsString('images are not allowed', implode(' ', $reasons[3]['errors']));
    }

    public function test_prompt_documents_the_graph_spec_with_a_new_version(): void
    {
        $prompt = QuestionDraftPrompt::load('question_draft');
        $this->assertTrue(version_compare($prompt->version, '1.1.0', '>='));
        $this->assertStringContainsString('polylines', $prompt->system);
        $this->assertStringContainsString('Never insert images', $prompt->system);
    }
}
