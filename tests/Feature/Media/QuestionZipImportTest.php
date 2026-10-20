<?php

namespace Tests\Feature\Media;

use App\Models\Exam\Subject;
use App\Models\Exam\Topic;
use App\Models\QuestionBank;
use App\Models\User;
use App\Services\Media\QuestionZipTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/** Import with media: ZIP (questions.json + images/), image URLs and graph blocks in JSON / CSV. */
class QuestionZipImportTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->teacher = User::factory()->create(['role' => 'teacher', 'status' => 'active']);
        $math = Subject::factory()->math()->create();
        $physics = Subject::factory()->physics()->create();
        $plan = Topic::factory()->create(['subject_id' => $math->id, 'code' => 'math.planimetry', 'name_uz' => 'Planimetriya']);
        Topic::factory()->childOf($plan)->create(['code' => 'math.planimetry.triangles', 'name_uz' => 'Uchburchaklar']);
        $kin = Topic::factory()->create(['subject_id' => $physics->id, 'code' => 'phys.kinematics', 'name_uz' => 'Kinematika']);
        Topic::factory()->childOf($kin)->create(['code' => 'phys.kinematics.accelerated', 'name_uz' => 'Tekis tezlanuvchan']);
    }

    private function zip(array $files): UploadedFile
    {
        $tmp = tempnam(sys_get_temp_dir(), 'zt');
        $zip = new \ZipArchive;
        $zip->open($tmp, \ZipArchive::OVERWRITE);
        foreach ($files as $name => $content) {
            $zip->addFromString($name, $content);
        }
        $zip->close();

        return UploadedFile::fake()->createWithContent('savollar.zip', (string) file_get_contents($tmp));
    }

    private function upload(UploadedFile $file): string
    {
        $response = $this->actingAs($this->teacher)->post(route('teacher.exam.import.upload'), ['file' => $file]);
        $response->assertSessionHasNoErrors()->assertRedirect();
        parse_str((string) parse_url($response->headers->get('Location'), PHP_URL_QUERY), $q);

        return $q['token'];
    }

    public function test_template_zip_imports_with_uploaded_image_and_graph(): void
    {
        $this->actingAs($this->teacher)->get(route('teacher.exam.import.template', ['format' => 'zip']))
            ->assertOk()->assertHeader('Content-Type', 'application/zip');

        $file = UploadedFile::fake()->createWithContent('t.zip', app(QuestionZipTemplate::class)->build());
        $token = $this->upload($file);

        $this->get(route('teacher.exam.import.preview', ['token' => $token]))
            ->assertInertia(fn (Assert $p) => $p->where('summary.valid', 2)->where('summary.errors', 0)
                ->where('rows.0.answer_label', 'A')->where('rows.1.answer_label', '20 m'));

        $images = Storage::disk('public')->allFiles('questions');
        $this->assertCount(1, $images);
        $this->assertStringEndsWith('.webp', $images[0]);

        $this->post(route('teacher.exam.import.confirm'), ['token' => $token, 'accept_answers' => true])->assertRedirect();
        $geo = QuestionBank::query()->where('question_type', 'single_choice')->firstOrFail();
        $this->assertStringContainsString('(/storage/'.$images[0].'){width=60%}', $geo->question_text);
        $this->assertStringNotContainsString('images/uchburchak.png', $geo->question_text);
        $phys = QuestionBank::query()->where('question_type', 'numeric_with_unit')->firstOrFail();
        $this->assertStringContainsString('```graph', $phys->question_text);
        $this->assertSame(20.0, (float) $phys->numericAnswers()->value('value'));
    }

    public function test_missing_refused_and_external_images_are_row_errors(): void
    {
        $item = fn (string $q) => ['subject' => 'math', 'topic' => 'math.planimetry.triangles', 'type' => 'single_choice', 'difficulty' => 2,
            'question' => $q, 'options' => ['1', '2', '3', '4'], 'correct' => 'A'];
        $json = json_encode(['questions' => [
            $item('Bor rasm ![a](images/ok.png)'),
            $item('Yo\'q rasm ![a](images/yoq.png)'),
            $item('SVG ![a](images/x.svg)'),
            $item('Soxta PNG ![a](images/fake.png)'),
            $item('Tashqi ![a](https://evil.example/a.png)'),
            $item("Buzuq grafik\n```graph\n{\"x\":[1,0],\"y\":[0,1]}\n```"),
            $item('Yo\'l ![a](../../etc/passwd)'),
        ]]);
        $token = $this->upload($this->zip([
            'questions.json' => $json,
            'images/ok.png' => QuestionImageUploadTest::png(),
            'images/x.svg' => '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>',
            'images/fake.png' => '<html>not an image</html>',
            'images/unused.png' => QuestionImageUploadTest::png(),
        ]));

        $this->get(route('teacher.exam.import.preview', ['token' => $token]))
            ->assertInertia(fn (Assert $p) => $p->where('summary.valid', 1)->where('summary.errors', 6)
                ->where('rows.1.errors', fn ($e) => str_contains(collect($e)->implode(' '), 'topilmadi: images/yoq.png'))
                ->where('rows.2.errors', fn ($e) => str_contains(collect($e)->implode(' '), 'SVG'))
                ->where('rows.3.errors', fn ($e) => str_contains(collect($e)->implode(' '), 'images/fake.png'))
                ->where('rows.4.errors', fn ($e) => str_contains(collect($e)->implode(' '), 'platformaga yuklangan'))
                ->where('rows.5.errors', fn ($e) => str_contains(collect($e)->implode(' '), 'Grafik'))
                ->where('rows.6.errors', fn ($e) => str_contains(collect($e)->implode(' '), 'topilmadi')));

        // only the referenced, valid image was stored (unused.png is not uploaded)
        $this->assertCount(1, Storage::disk('public')->allFiles('questions'));
    }

    public function test_zip_without_questions_json_is_refused(): void
    {
        $this->actingAs($this->teacher)->post(route('teacher.exam.import.upload'), ['file' => $this->zip(['images/a.png' => 'x'])])
            ->assertSessionHasErrors(['file' => 'ZIP ichida questions.json (yoki questions.csv) topilmadi. Shablonga qarang.']);
    }

    public function test_csv_and_json_may_carry_uploaded_image_urls_and_graph_blocks(): void
    {
        $url = '/storage/questions/2026/10/0b6a4b0e-1c1d-4f7e-9b0a-2d3c4e5f6a7b.webp';
        $graph = "```graph\n{\"x\":[-3,3],\"y\":[-1,9],\"functions\":[{\"expr\":\"x^2\"}]}\n```";
        $json = json_encode(['questions' => [[
            'subject' => 'math', 'topic' => 'math.planimetry.triangles', 'type' => 'single_choice', 'difficulty' => 3,
            'question' => "Qaysi grafik \$y=x^2\$ ga mos?\n![chizma]({$url})",
            'options' => [$graph, '![b]('.$url.')', '$y=2x$', '$y=x^3$'], 'correct' => 'A',
        ]]]);
        $token = $this->upload(UploadedFile::fake()->createWithContent('q.json', $json));
        $this->post(route('teacher.exam.import.confirm'), ['token' => $token, 'accept_answers' => true])->assertRedirect();

        $q = QuestionBank::query()->firstOrFail();
        $this->assertStringContainsString($url, $q->question_text);
        $this->assertSame($graph, $q->answerOptions()->where('label', 'A')->value('text'));
    }
}
