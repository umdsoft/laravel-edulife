<?php

namespace Tests\Feature\Exam;

use App\Models\Exam\ExamSourceDocument;
use App\Models\Exam\QuestionDraftRun;
use App\Models\Exam\Subject;
use App\Models\Exam\Topic;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

/**
 * Stage 4 pages: admin "AI qoralamalar" (run log + costs + start form) and
 * the teacher's source upload page. No API key -> runs are skipped, nothing sent.
 */
class AiDraftPagesTest extends TestCase
{
    use RefreshDatabase;

    private Subject $math;

    private Topic $topic;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        Http::fake();
        config(['exam.ai.api_key' => '']);
        $this->math = Subject::factory()->math()->create();
        $this->topic = Topic::factory()->create(['subject_id' => $this->math->id, 'code' => 'math.algebra']);
    }

    public function test_admin_sees_the_run_log_with_costs(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        QuestionDraftRun::create([
            'subject_id' => $this->math->id, 'difficulty_level' => 3, 'count_requested' => 10, 'status' => 'succeeded',
            'input_tokens' => 1200, 'output_tokens' => 3400, 'estimated_cost_usd' => 0.0728, 'drafts_created' => 9,
            'prompt_name' => 'question_draft', 'prompt_version' => '1.0.0', 'model' => 'claude-opus-5-5',
        ]);

        $this->actingAs($admin)->get('/admin/exam/ai-drafts')
            ->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Admin/Exam/AiDrafts/Index', false)
                ->has('runs.data', 1)
                ->where('runs.data.0.prompt_version', 'question_draft@1.0.0')
                ->where('runs.data.0.input_tokens', 1200)
                ->where('totals.week.drafts', 9)
                ->where('settings.configured', false)
                ->where('settings.model', 'claude-opus-5-5')
                ->has('options.subjects', 1)
                ->has('options.formats', 3));
    }

    public function test_admin_start_form_creates_a_skipped_run_without_a_key(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post('/admin/exam/ai-drafts', [
            'subject_id' => $this->math->id,
            'topic_id' => $this->topic->id,
            'difficulty_level' => 3,
            'exam_format' => 'sat',
            'count' => 5,
        ])->assertRedirect()->assertSessionHas('error');

        $run = QuestionDraftRun::first();
        $this->assertSame(QuestionDraftRun::STATUS_SKIPPED, $run->status);
        $this->assertSame($admin->id, $run->requested_by);
        Http::assertNothingSent();
    }

    public function test_start_form_validates_input(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $physics = Subject::factory()->physics()->create();

        $this->actingAs($admin)->post('/admin/exam/ai-drafts', [
            'subject_id' => $physics->id,
            'topic_id' => $this->topic->id, // math topic
            'difficulty_level' => 7,
            'count' => 500,
        ])->assertSessionHasErrors(['difficulty_level', 'count']);

        $this->actingAs($admin)->post('/admin/exam/ai-drafts', [
            'subject_id' => $physics->id, 'topic_id' => $this->topic->id, 'difficulty_level' => 3, 'count' => 5,
        ])->assertSessionHasErrors(['topic_id']);

        $this->assertSame(0, QuestionDraftRun::count());
    }

    public function test_only_staff_reach_the_pages(): void
    {
        $student = User::factory()->create(['role' => 'student']);
        $this->actingAs($student)->get('/admin/exam/ai-drafts')->assertForbidden();
        $this->actingAs($student)->get('/teacher/exam/ai-drafts')->assertForbidden();
    }

    public function test_teacher_uploads_toggles_and_deletes_a_source(): void
    {
        Storage::fake('local');
        $teacher = User::factory()->create(['role' => 'teacher']);

        $this->actingAs($teacher)->get('/teacher/exam/ai-drafts')->assertOk()
            ->assertInertia(fn (AssertableInertia $page) => $page->component('Teacher/Exam/AiDrafts/Index', false)->has('documents', 0));

        $file = UploadedFile::fake()->createWithContent('kinematika.txt', "\xEF\xBB\xBF12-masala. \$3x + 5 = 20\$ tenglamani yeching.");
        $this->actingAs($teacher)->post('/teacher/exam/ai-drafts/sources', [
            'subject_id' => $this->math->id,
            'topic_id' => $this->topic->id,
            'title' => 'Algebra',
            'source_ref' => 'Skanavi, 2010',
            'file' => $file,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $doc = ExamSourceDocument::first();
        $this->assertSame('12-masala. $3x + 5 = 20$ tenglamani yeching.', $doc->content_text);
        $this->assertSame(mb_strlen($doc->content_text), $doc->char_count);
        $this->assertSame($teacher->id, $doc->uploaded_by);
        Storage::disk('local')->assertExists($doc->path);
        $this->assertStringStartsWith('exam-sources/', $doc->path);

        $this->actingAs($teacher)->patch("/teacher/exam/ai-drafts/sources/{$doc->id}/toggle")->assertRedirect();
        $this->assertFalse($doc->fresh()->is_active);

        $this->actingAs($teacher)->delete("/teacher/exam/ai-drafts/sources/{$doc->id}")->assertRedirect();
        $this->assertSoftDeleted($doc);
        Storage::disk('local')->assertMissing($doc->path);
    }

    public function test_teacher_can_paste_text_and_bad_files_are_refused(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);

        $this->actingAs($teacher)->post('/teacher/exam/ai-drafts/sources', [
            'subject_id' => $this->math->id, 'title' => 'Matn', 'content_text' => '7-masala. $x^2 = 9$.',
        ])->assertSessionHasNoErrors();
        $this->assertNull(ExamSourceDocument::first()->path);

        $this->actingAs($teacher)->post('/teacher/exam/ai-drafts/sources', [
            'subject_id' => $this->math->id, 'title' => 'Rasm', 'file' => UploadedFile::fake()->image('scan.png'),
        ])->assertSessionHasErrors('file');

        $this->actingAs($teacher)->post('/teacher/exam/ai-drafts/sources', [
            'subject_id' => $this->math->id, 'title' => 'Hech narsa',
        ])->assertSessionHasErrors(['file', 'content_text']);

        $this->assertSame(1, ExamSourceDocument::count());
    }

    public function test_teacher_can_start_a_run(): void
    {
        $teacher = User::factory()->create(['role' => 'teacher']);

        $this->actingAs($teacher)->post('/teacher/exam/ai-drafts/run', [
            'subject_id' => $this->math->id, 'difficulty_level' => 2, 'count' => 3,
        ])->assertRedirect();

        $this->assertSame(1, QuestionDraftRun::count());
        Http::assertNothingSent();
    }
}
