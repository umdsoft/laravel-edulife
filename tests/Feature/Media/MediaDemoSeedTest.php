<?php

namespace Tests\Feature\Media;

use App\Enums\Exam\ReviewStatus;
use App\Models\QuestionBank;
use App\Support\RichText\RichTextMedia;
use Database\Seeders\Exam\ExamMediaDemoSeeder;
use Database\Seeders\Exam\ExamSubjectSeeder;
use Database\Seeders\Exam\ExamTopicSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Demo questions with graphs and a stored picture: verified answers, approved, idempotent. */
class MediaDemoSeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_definitions_are_consistent(): void
    {
        $this->seed([ExamSubjectSeeder::class, ExamTopicSeeder::class]);
        $codes = \App\Models\Exam\Topic::query()->pluck('code')->all();
        foreach (ExamMediaDemoSeeder::definitions() as $q) {
            $this->assertSame([], ExamMediaDemoSeeder::errors($q, $codes));
        }
    }

    public function test_seeds_approved_demo_questions_with_graphs_and_an_uploaded_image(): void
    {
        Storage::fake('public');
        $this->seed([ExamSubjectSeeder::class, ExamTopicSeeder::class, ExamMediaDemoSeeder::class]);

        $questions = QuestionBank::query()->where('source_ref', ExamMediaDemoSeeder::SOURCE_REF)->with(['answerOptions', 'numericAnswers'])->get();
        $this->assertCount(5, $questions);
        foreach ($questions as $q) {
            $this->assertTrue($q->is_demo);
            $this->assertSame(ReviewStatus::Approved, $q->review_status);
            $this->assertSame([], RichTextMedia::errors($q->question_text));
            $this->assertTrue(RichTextMedia::hasMedia($q->question_text) || $q->answerOptions->contains(fn ($o) => RichTextMedia::hasMedia($o->text)));
            if ($q->answerOptions->isNotEmpty()) {
                $this->assertSame(1, $q->answerOptions->where('is_correct', true)->count());
            }
        }

        $vt = $questions->firstWhere('question_type', 'numeric_with_unit');
        $this->assertSame(24.0, (float) $vt->numericAnswers->first()->value);
        $this->assertSame('m', $vt->numericAnswers->first()->unit);

        $triangle = $questions->first(fn ($q) => RichTextMedia::images($q->question_text) !== []);
        $path = RichTextMedia::storagePaths($triangle->question_text)[0];
        Storage::disk('public')->assertExists($path);
        $this->assertStringEndsWith('.webp', $path);
        $this->assertSame('$5$', $triangle->answerOptions->firstWhere('is_correct', true)->text);

        $linear = $questions->first(fn ($q) => str_contains($q->question_text, 'y = 2x - 1'));
        $this->assertStringContainsString('"expr":"2x-1"', $linear->answerOptions->firstWhere('is_correct', true)->text);

        // idempotent: no new questions, no new image
        $this->seed(ExamMediaDemoSeeder::class);
        $this->assertSame(5, QuestionBank::query()->where('source_ref', ExamMediaDemoSeeder::SOURCE_REF)->count());
        $this->assertCount(1, Storage::disk('public')->allFiles('questions'));
    }
}
