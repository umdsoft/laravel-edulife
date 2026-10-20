<?php

namespace Tests\Feature\Exam;

use App\Enums\Exam\ReviewStatus;
use App\Models\Exam\Subject;
use App\Models\Exam\Topic;
use App\Models\QuestionBank;
use App\Models\User;
use App\Services\Exam\QuestionImportService;
use Database\Factories\Exam\ExamQuestionFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * Import: CSV / Excel / JSON -> preview -> confirm. Duplicates by the
 * normalized stem; the file's answer is used only when the teacher confirms.
 */
class QuestionImportTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->teacher = User::factory()->create(['role' => 'teacher', 'status' => 'active']);
        $math = Subject::factory()->math()->create();
        $physics = Subject::factory()->physics()->create();
        $eq = Topic::factory()->create(['subject_id' => $math->id, 'code' => 'math.equations', 'name_uz' => 'Tenglamalar']);
        Topic::factory()->childOf($eq)->create(['code' => 'math.equations.quadratic', 'name_uz' => 'Kvadrat tenglamalar']);
        Topic::factory()->create(['subject_id' => $math->id, 'code' => 'math.percent.percent', 'name_uz' => 'Foizlar']);
        Topic::factory()->create(['subject_id' => $physics->id, 'code' => 'phys.kinematics.uniform', 'name_uz' => 'Tekis harakat']);
    }

    private function csv(string $body): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('savollar.csv', $body);
    }

    private function upload(UploadedFile $file): string
    {
        $response = $this->actingAs($this->teacher)->post(route('teacher.exam.import.upload'), ['file' => $file]);
        $response->assertSessionHasNoErrors()->assertRedirect();
        parse_str((string) parse_url($response->headers->get('Location'), PHP_URL_QUERY), $q);

        return $q['token'];
    }

    private function sampleCsv(): string
    {
        return "subject;topic;type;difficulty;question;option_a;option_b;option_c;option_d;correct;unit;tolerance;tolerance_type;accepted_units;source_ref\n"
            ."math;math.equations.quadratic;single_choice;2;\$x^2-5x+6=0\$ ildizlari yig'indisi;5;6;-5;1;A;;;;;To'plam 12-bet\n"
            ."Matematika;Kvadrat tenglamalar;single_choice;3;Bankdagi   SAVOL.;1;2;3;4;B;;;;;\n"
            ."math;Foizlar;numeric;3;200 ning 15% i;;;;;30;;0;abs;;\n"
            ."physics;phys.kinematics.uniform;numeric_with_unit;3;144 km 2 soatda — tezlik?;;;;;20;m/s;0,01;rel;km/soat=0.2777777778;\n"
            ."math;math.equations.quadratic;single_choice;2;\$x^2 - 5x + 6 = 0\$  ildizlari yig'indisi;5;6;-5;1;;;;;;\n"
            ."biology;;single_choice;2;Noma'lum fan;1;2;3;4;A;;;;;\n";
    }

    public function test_csv_preview_flags_errors_and_duplicates_then_imports_without_answers(): void
    {
        ExamQuestionFactory::new()->create(['question_text' => 'Bankdagi savol']);
        $token = $this->upload($this->csv($this->sampleCsv()));

        $this->get(route('teacher.exam.import.preview', ['token' => $token]))
            ->assertOk()
            ->assertInertia(fn (Assert $p) => $p->component('Teacher/Exam/Import/Preview')
                ->where('summary.total', 6)
                ->where('summary.valid', 3)
                ->where('summary.duplicates', 2)
                ->where('summary.errors', 1)
                ->where('summary.with_answers', 3)
                ->where('rows.1.duplicate', 'bank')
                ->where('rows.4.duplicate', 'file')
                ->where('rows.4.duplicate_of', 2)
                ->where('rows.0.answer_label', 'A')
                ->where('rows.3.answer_label', '20 m/s')
                ->where('rows.5.errors.0', 'Fan topilmadi: «biology».'));

        $this->post(route('teacher.exam.import.confirm'), ['token' => $token, 'accept_answers' => false])
            ->assertRedirect(route('teacher.exam.review', ['origin' => 'import']));

        $imported = QuestionBank::query()->where('origin', 'import')->get();
        $this->assertCount(3, $imported);
        $this->assertTrue($imported->every(fn ($q) => $q->review_status === ReviewStatus::InReview));
        $choice = $imported->firstWhere('question_type', 'single_choice');
        $this->assertSame(4, $choice->answerOptions()->whereNull('is_correct')->count());
        $this->assertSame('To\'plam 12-bet', $choice->source_ref);
        $unit = $imported->firstWhere('question_type', 'numeric_with_unit');
        $answer = $unit->numericAnswers()->first();
        $this->assertNull($answer->value, 'file answer not trusted without confirmation');
        $this->assertSame('m/s', $answer->unit);
        $this->assertSame(['km/soat' => 0.2777777778], $answer->accepted_units);

        // the token is single use
        $this->post(route('teacher.exam.import.confirm'), ['token' => $token])->assertSessionHas('error');
        $this->assertSame(3, QuestionBank::query()->where('origin', 'import')->count());
    }

    public function test_confirmed_file_answers_are_marked_but_still_in_review(): void
    {
        $token = $this->upload($this->csv($this->sampleCsv()));
        $this->post(route('teacher.exam.import.confirm'), ['token' => $token, 'accept_answers' => true])->assertRedirect();

        $choice = QuestionBank::query()->where('origin', 'import')->where('question_type', 'single_choice')->firstOrFail();
        $this->assertSame(['A'], $choice->answerOptions()->where('is_correct', true)->pluck('label')->all());
        $this->assertSame(ReviewStatus::InReview, $choice->review_status);
        $numeric = QuestionBank::query()->where('question_type', 'numeric')->firstOrFail();
        $this->assertSame(30.0, (float) $numeric->numericAnswers()->value('value'));
        $this->assertSame(0, QuestionBank::approved()->count());
    }

    public function test_excluded_rows_and_rows_that_became_duplicates_are_skipped(): void
    {
        $token = $this->upload($this->csv($this->sampleCsv()));
        // the same stem enters the bank between preview and confirm
        ExamQuestionFactory::new()->create(['question_text' => '200 ning 15% i']);

        $this->post(route('teacher.exam.import.confirm'), ['token' => $token, 'exclude_rows' => [2]]);

        // line 2 unticked, line 4 now in the bank, line 6 duplicates line 2 inside the file
        $this->assertEqualsCanonicalizing(
            ['Bankdagi   SAVOL.', '144 km 2 soatda — tezlik?'],
            QuestionBank::query()->where('origin', 'import')->pluck('question_text')->all()
        );
    }

    public function test_json_import_with_nested_options_and_answer(): void
    {
        $json = json_encode(['questions' => [
            ['subject' => 'math', 'topic' => 'math.equations.quadratic', 'type' => 'multiple_choice', 'difficulty' => 4,
                'question' => 'Qaysilari $x^2=4$ ning ildizi?',
                'options' => [['text' => '$2$', 'correct' => true], ['text' => '$-2$', 'correct' => true], ['text' => '$4$'], ['text' => '$0$']]],
            ['subject' => 'physics', 'topic' => 'phys.kinematics.uniform', 'type' => 'numeric_with_unit', 'difficulty' => 2,
                'question' => 'Tezlik 36 km/soat. m/s da?', 'answer' => ['value' => 10, 'unit' => 'm/s', 'accepted_units' => ['km/soat' => 0.2777777778]]],
            ['subject' => 'math', 'question' => 'Variantlar kam', 'options' => ['1', '2']],
        ]]);
        $token = $this->upload(UploadedFile::fake()->createWithContent('q.json', $json));

        $this->get(route('teacher.exam.import.preview', ['token' => $token]))
            ->assertInertia(fn (Assert $p) => $p->where('summary.valid', 2)->where('summary.errors', 1)
                ->where('rows.0.answer_label', 'A, B')->where('rows.1.answer_label', '10 m/s'));

        $this->post(route('teacher.exam.import.confirm'), ['token' => $token, 'accept_answers' => true]);
        $mc = QuestionBank::query()->where('question_type', 'multiple_choice')->firstOrFail();
        $this->assertSame(['A', 'B'], $mc->answerOptions()->where('is_correct', true)->pluck('label')->all());
        $this->assertSame(4, $mc->difficulty_level);
    }

    public function test_excel_template_round_trip(): void
    {
        $this->actingAs($this->teacher);
        $xlsx = $this->get(route('teacher.exam.import.template', ['format' => 'xlsx']))->assertOk();
        $path = tempnam(sys_get_temp_dir(), 'tpl').'.xlsx';
        file_put_contents($path, $xlsx->getContent());

        $token = $this->upload(new UploadedFile($path, 'shablon.xlsx', null, null, true));
        $this->get(route('teacher.exam.import.preview', ['token' => $token]))
            ->assertInertia(fn (Assert $p) => $p->where('summary.total', 3)->where('summary.valid', 3)->where('summary.with_answers', 3)
                ->where('rows.0.errors', [])->where('rows.0.warnings', []));
        @unlink($path);
    }

    public function test_csv_and_json_templates_parse_cleanly(): void
    {
        $service = app(QuestionImportService::class);
        $this->actingAs($this->teacher);

        $csv = $this->get(route('teacher.exam.import.template', ['format' => 'csv']))->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $rows = $service->normalizeRows($service->readCsv($csv->getContent()));
        $this->assertCount(3, $rows);
        $this->assertSame([[], [], []], array_column($rows, 'errors'));

        $json = $this->get(route('teacher.exam.import.template', ['format' => 'json']))->assertOk();
        $rows = $service->normalizeRows($service->readJson($json->getContent()));
        $this->assertSame([[], [], []], array_column($rows, 'errors'));
        $this->assertSame([[], [], []], array_column($rows, 'warnings'));
    }

    public function test_bad_files_are_refused(): void
    {
        $this->actingAs($this->teacher);
        $this->post(route('teacher.exam.import.upload'), ['file' => UploadedFile::fake()->createWithContent('x.pdf', '%PDF')])
            ->assertSessionHasErrors('file');
        $this->post(route('teacher.exam.import.upload'), ['file' => UploadedFile::fake()->createWithContent('x.json', '{not json')])
            ->assertSessionHasErrors('file');
        $this->post(route('teacher.exam.import.upload'), ['file' => $this->csv("foo,bar\n1,2\n")])
            ->assertSessionHasErrors('file');
        $this->get(route('teacher.exam.import.preview', ['token' => str_repeat('a', 32)]))
            ->assertRedirect(route('teacher.exam.import.index'));
    }
}
