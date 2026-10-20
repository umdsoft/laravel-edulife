<?php

namespace Tests\Feature\Media;

use App\Models\Exam\Subject;
use App\Models\Exam\Topic;
use App\Models\QuestionBank;
use App\Models\Test;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

/** Images must be our uploads and graph blocks must be valid wherever teacher text is saved. */
class MediaValidationOnSaveTest extends TestCase
{
    use CreatesCourses, RefreshDatabase;

    private const IMG = '/storage/questions/2026/10/0b6a4b0e-1c1d-4f7e-9b0a-2d3c4e5f6a7b.webp';

    private const GRAPH = "```graph\n{\"x\":[-5,5],\"y\":[-3,9],\"functions\":[{\"expr\":\"x^2-2\",\"label\":\"f(x)\"}]}\n```";

    private const BAD_GRAPH = "```graph\n{\"x\":[-5,5],\"y\":[-3,9],\"functions\":[{\"expr\":\"alert(1)\"}]}\n```";

    private function bankPayload(Subject $math, Topic $topic, array $overrides = []): array
    {
        return array_merge([
            'subject_id' => $math->id, 'topic_id' => $topic->id, 'question_type' => 'single_choice',
            'question_text' => "Grafikka qarang:\n".self::GRAPH."\n![chizma](".self::IMG.')', 'difficulty_level' => 2,
            'options' => [
                ['text' => self::GRAPH, 'is_correct' => true], ['text' => '![b]('.self::IMG.')', 'is_correct' => false],
                ['text' => '$y=2x$', 'is_correct' => false], ['text' => '$y=x^3$', 'is_correct' => false],
            ],
            'solution_text' => "Yechim:\n".self::GRAPH,
            'intent' => 'review',
        ], $overrides);
    }

    public function test_exam_bank_accepts_own_images_and_valid_graphs_and_refuses_others(): void
    {
        $teacher = $this->teacher();
        $math = Subject::factory()->math()->create();
        $topic = Topic::factory()->create(['subject_id' => $math->id, 'code' => 'math.functions', 'name_uz' => 'Funksiyalar']);

        $this->actingAs($teacher)->post(route('teacher.exam.questions.store'), $this->bankPayload($math, $topic))
            ->assertSessionHasNoErrors();
        $this->assertSame(1, QuestionBank::query()->count());

        $this->post(route('teacher.exam.questions.store'), $this->bankPayload($math, $topic, [
            'question_text' => 'Tashqi rasm ![x](https://evil.example/x.png) — yana bir savol',
        ]))->assertSessionHasErrors('question_text');
        $this->post(route('teacher.exam.questions.store'), $this->bankPayload($math, $topic, [
            'question_text' => 'Boshqa savol', 'solution_text' => self::BAD_GRAPH,
        ]))->assertSessionHasErrors('solution_text');
        $bad = $this->bankPayload($math, $topic, ['question_text' => 'Uchinchi savol']);
        $bad['options'][2]['text'] = '![x](/storage/questions/2026/10/a.svg)';
        $this->post(route('teacher.exam.questions.store'), $bad)->assertSessionHasErrors('options.2.text');
        $this->assertSame(1, QuestionBank::query()->count());
    }

    public function test_course_test_question_and_lesson_text_are_checked(): void
    {
        $teacher = $this->teacher();
        $course = $this->course($teacher);
        $module = $this->module($course);
        $test = Test::forceCreate([
            'course_id' => $course->id, 'type' => 'final', 'title' => 'Grafiklar', 'questions_count' => 1,
            'passing_score' => 60, 'max_attempts' => 0, 'shuffle_questions' => false, 'is_active' => true,
        ]);
        $question = fn (string $text, string $option) => [
            'type' => 'single_choice', 'question_text' => $text, 'points' => 1, 'difficulty' => 'medium',
            'options' => [['option_text' => $option, 'is_correct' => true], ['option_text' => '$y=2x$', 'is_correct' => false]],
        ];

        $this->actingAs($teacher)->post(route('teacher.tests.questions.store', $test), $question("Qaysi grafik?\n".self::GRAPH, self::GRAPH))
            ->assertSessionHasNoErrors();
        $this->post(route('teacher.tests.questions.store', $test), $question('Qaysi?', self::BAD_GRAPH))
            ->assertSessionHasErrors('options.0.option_text');
        $this->post(route('teacher.tests.questions.store', $test), $question('![x](javascript:alert(1))', 'A'))
            ->assertSessionHasErrors('question_text');
        $this->assertSame(1, $test->questions()->count());

        $this->post(route('teacher.courses.modules.lessons.store', [$course, $module]), [
            'title' => 'Parabola', 'type' => 'text', 'content' => "## Parabola\n".self::GRAPH."\n![chizma](".self::IMG.')',
        ])->assertSessionHasNoErrors();
        $this->post(route('teacher.courses.modules.lessons.store', [$course, $module]), [
            'title' => 'Tashqi', 'type' => 'text', 'content' => '![x](http://tracker.example/pixel.gif)',
        ])->assertSessionHasErrors('content');
    }
}
