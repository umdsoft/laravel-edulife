<?php

namespace Tests\Feature\Media;

use App\Models\Exam\QuestionBankOption;
use Database\Factories\Exam\ExamQuestionFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

/** media:prune-question-images — unreferenced uploads older than 7 days are removed. */
class PruneQuestionImagesTest extends TestCase
{
    use CreatesCourses, RefreshDatabase;

    private function putImage(string $name, int $daysOld): string
    {
        $path = "questions/2026/10/{$name}.webp";
        Storage::disk('public')->put($path, 'x');
        touch(Storage::disk('public')->path($path), now()->subDays($daysOld)->getTimestamp());

        return $path;
    }

    public function test_prunes_only_old_unreferenced_uploads(): void
    {
        Storage::fake('public');
        $inStem = $this->putImage('stem-img', 30);
        $inOption = $this->putImage('option-img', 30);
        $inSolution = $this->putImage('solution-img', 30);
        $inLesson = $this->putImage('lesson-img', 30);
        $inCourseTest = $this->putImage('course-img', 30);
        $orphanOld = $this->putImage('orphan-old', 8);
        $orphanNew = $this->putImage('orphan-new', 2);

        $q = ExamQuestionFactory::new()->create([
            'question_text' => "Qarang: ![a](/storage/{$inStem}){width=50%}",
            'solution_text' => "Yechim ![b](/storage/{$inSolution})",
        ]);
        QuestionBankOption::query()->where('question_id', $q->id)->limit(1)->update(['text' => "![c](/storage/{$inOption})"]);
        if (! QuestionBankOption::query()->where('question_id', $q->id)->exists()) {
            QuestionBankOption::create(['question_id' => $q->id, 'label' => 'A', 'text' => "![c](/storage/{$inOption})", 'sort_order' => 0]);
        }
        $lesson = $this->lesson($this->module($this->course($this->teacher())), ['text_content' => "![d](/storage/{$inLesson})"]);
        DB::table('questions')->insert([
            'id' => (string) Str::uuid(),
            'course_id' => $lesson->course_id,
            'type' => 'single_choice', 'question_text' => "![e](/storage/{$inCourseTest})", 'points' => 1, 'sort_order' => 0,
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->artisan('media:prune-question-images', ['--dry-run' => true])
            ->expectsOutputToContain("[dry-run] delete {$orphanOld}")
            ->expectsOutputToContain('Would delete 1 unreferenced image(s)')
            ->assertSuccessful();
        Storage::disk('public')->assertExists($orphanOld);

        $this->artisan('media:prune-question-images')->assertSuccessful();
        Storage::disk('public')->assertMissing($orphanOld);
        foreach ([$inStem, $inOption, $inSolution, $inLesson, $inCourseTest, $orphanNew] as $kept) {
            Storage::disk('public')->assertExists($kept);
        }
    }

    public function test_soft_deleted_questions_keep_their_images(): void
    {
        Storage::fake('public');
        $img = $this->putImage('trashed', 30);
        $q = ExamQuestionFactory::new()->create(['question_text' => "![a](/storage/{$img})"]);
        $q->delete();

        $this->artisan('media:prune-question-images')->assertSuccessful();
        Storage::disk('public')->assertExists($img);
    }
}
