<?php

namespace Tests\Feature\Exam;

use App\Models\Exam\Subject;
use App\Models\Exam\Topic;
use App\Models\QuestionBank;
use App\Models\User;
use App\Services\Exam\QuestionImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Import: a missing / invalid difficulty never becomes "medium". The preview
 * asks for a level per row ("hammasiga qo'llash" fills the rest) and the
 * confirmation is refused, on the server too, while a row lacks one.
 */
class ImportDifficultyTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->teacher = User::factory()->create(['role' => 'teacher', 'status' => 'active']);
        $math = Subject::factory()->math()->create();
        Topic::factory()->create(['subject_id' => $math->id, 'code' => 'math.equations', 'name_uz' => 'Tenglamalar']);
    }

    /** Line 2: level 4 · line 3: no level · line 4: "oson" (invalid) · line 5: 9 (invalid) · line 6: a broken row. */
    private function csv(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('q.csv',
            "subject;topic;type;difficulty;question;option_a;option_b;option_c;option_d\n"
            ."math;math.equations;single_choice;4;Birinchi savol;1;2;3;4\n"
            ."math;math.equations;single_choice;;Ikkinchi savol;1;2;3;4\n"
            ."math;math.equations;single_choice;oson;Uchinchi savol;1;2;3;4\n"
            ."math;math.equations;single_choice;9;To'rtinchi savol;1;2;3;4\n"
            ."biology;;single_choice;;Xato qator;1;2;3;4\n");
    }

    private function upload(): string
    {
        $response = $this->actingAs($this->teacher)->post(route('teacher.exam.import.upload'), ['file' => $this->csv()]);
        $response->assertSessionHasNoErrors()->assertRedirect();
        parse_str((string) parse_url($response->headers->get('Location'), PHP_URL_QUERY), $q);

        return $q['token'];
    }

    public function test_preview_asks_for_a_level_instead_of_defaulting(): void
    {
        $token = $this->upload();

        $this->get(route('teacher.exam.import.preview', ['token' => $token]))
            ->assertInertia(fn (Assert $p) => $p
                ->where('summary.valid', 4)
                ->where('summary.errors', 1)
                ->where('summary.needs_difficulty', 3)
                ->where('rows.0.difficulty', 4)->where('rows.0.needs_difficulty', false)
                ->where('rows.1.difficulty', null)->where('rows.1.needs_difficulty', true)
                ->where('rows.2.needs_difficulty', true)->where('rows.2.errors', [])
                ->where('rows.3.needs_difficulty', true)
                ->where('levels.0.label', '1 — juda oson')
                ->where('rows.2.warnings', fn ($w) => collect($w)->contains(fn ($m) => str_contains($m, '«oson»'))));
    }

    public function test_confirmation_is_blocked_until_every_row_has_a_level(): void
    {
        $token = $this->upload();

        // nothing chosen: refused, nothing created, the upload is kept
        $this->post(route('teacher.exam.import.confirm'), ['token' => $token])->assertSessionHasErrors('difficulties');
        // only one of the three chosen: still refused
        $this->post(route('teacher.exam.import.confirm'), ['token' => $token, 'difficulties' => [3 => 2]])
            ->assertSessionHasErrors('difficulties');
        $this->post(route('teacher.exam.import.confirm'), ['token' => $token, 'difficulties' => [3 => 7, 4 => 2, 5 => 2]])
            ->assertSessionHasErrors('difficulties.3');
        $this->assertSame(0, QuestionBank::query()->count());

        // unticking a row removes it from the requirement; the rest chosen ("hammasiga qo'llash" sends the same level for all)
        $this->post(route('teacher.exam.import.confirm'), ['token' => $token, 'exclude_rows' => [5], 'difficulties' => [3 => 1, 4 => 1]])
            ->assertSessionHasNoErrors()->assertRedirect(route('teacher.exam.review', ['origin' => 'import']));

        $levels = QuestionBank::query()->pluck('difficulty_level', 'question_text')->all();
        $this->assertSame(['Birinchi savol' => 4, 'Ikkinchi savol' => 1, 'Uchinchi savol' => 1], $levels);
        $this->assertSame(0, QuestionBank::query()->where('question_text', "To'rtinchi savol")->count());
        $this->assertSame('easy', QuestionBank::query()->where('question_text', 'Ikkinchi savol')->value('difficulty'));

        // the upload is single use once confirmed
        $this->post(route('teacher.exam.import.confirm'), ['token' => $token, 'difficulties' => [5 => 3]])->assertSessionHas('error');
    }

    public function test_service_refuses_rows_without_a_level(): void
    {
        $service = app(QuestionImportService::class);
        $rows = $service->normalizeRows([
            2 => ['subject' => 'math', 'type' => 'single_choice', 'question' => 'A savol', 'option_a' => '1', 'option_b' => '2', 'option_c' => '3', 'option_d' => '4'],
        ]);
        $this->assertSame([2], $service->rowsMissingDifficulty($rows));
        $this->assertSame([], $service->rowsMissingDifficulty($rows, [], [2 => '5']));
        $this->assertSame([], $service->rowsMissingDifficulty($rows, [2]));

        $this->expectException(\DomainException::class);
        $service->import($rows, $this->teacher, false);
    }

    public function test_only_staff_can_confirm(): void
    {
        $token = $this->upload();
        $student = User::factory()->create(['role' => 'student', 'status' => 'active']);
        $this->actingAs($student)->post(route('teacher.exam.import.confirm'), ['token' => $token, 'difficulties' => [3 => 1, 4 => 1, 5 => 1]])
            ->assertForbidden();
        $this->assertSame(0, QuestionBank::query()->count());
    }
}
