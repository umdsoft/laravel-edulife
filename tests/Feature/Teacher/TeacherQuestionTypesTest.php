<?php

namespace Tests\Feature\Teacher;

use App\Models\Enrollment;
use App\Models\Question;
use App\Models\Test;
use App\Models\TestAttempt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

/**
 * QA-T-01: the teacher could not save a question of any type (500, missing
 * questions.course_id), and the non-choice types could never be graded.
 */
class TeacherQuestionTypesTest extends TestCase
{
    use RefreshDatabase, CreatesCourses;

    private const PAYLOADS = [
        'single_choice' => [
            ['option_text' => '3', 'is_correct' => false],
            ['option_text' => '4', 'is_correct' => true],
        ],
        'multiple_choice' => [
            ['option_text' => '2', 'is_correct' => true],
            ['option_text' => '9', 'is_correct' => false],
            ['option_text' => '3', 'is_correct' => true],
        ],
        'true_false' => [
            ['option_text' => 'Rost', 'is_correct' => false],
            ['option_text' => 'Yolg\'on', 'is_correct' => true],
        ],
        'fill_blank' => [
            ['option_text' => 'Nyuton', 'is_correct' => true],
            ['option_text' => 'Newton', 'is_correct' => true],
        ],
        'matching' => [
            ['option_text' => 'Kuch', 'match_text' => 'N', 'is_correct' => true],
            ['option_text' => 'Energiya', 'match_text' => 'J', 'is_correct' => true],
            ['option_text' => 'Quvvat', 'match_text' => 'W', 'is_correct' => true],
        ],
        'ordering' => [
            ['option_text' => '1', 'correct_position' => 1, 'is_correct' => true],
            ['option_text' => '2', 'correct_position' => 2, 'is_correct' => true],
            ['option_text' => '3', 'correct_position' => 3, 'is_correct' => true],
            ['option_text' => '4', 'correct_position' => 4, 'is_correct' => true],
        ],
    ];

    public function test_every_question_type_can_be_saved_and_graded(): void
    {
        $teacher = $this->teacher();
        $course = $this->course($teacher);
        $test = Test::forceCreate([
            'course_id' => $course->id, 'type' => 'final', 'title' => 'Aralash', 'questions_count' => 6,
            'passing_score' => 100, 'max_attempts' => 0, 'shuffle_questions' => false, 'is_active' => true,
        ]);

        foreach (self::PAYLOADS as $type => $options) {
            $this->actingAs($teacher)->post(route('teacher.tests.questions.store', $test), [
                'type' => $type,
                'question_text' => "Savol: {$type}",
                'points' => 1,
                'options' => $options,
            ])->assertSessionHasNoErrors()->assertRedirect(route('teacher.tests.questions.index', $test));
        }

        $this->assertSame(6, $test->questions()->count());
        $this->assertSame(6, $test->questions()->where('course_id', $course->id)->count());

        $student = $this->studentUser();
        Enrollment::forceCreate(['user_id' => $student->id, 'course_id' => $course->id, 'access_type' => 'free', 'status' => 'active', 'progress' => 100]);

        $this->actingAs($student)->post(route('student.tests.begin', $test), ['confirm' => true])->assertRedirect();
        $attempt = TestAttempt::where('user_id', $student->id)->firstOrFail();

        $page = $this->actingAs($student)->get(route('student.tests.attempt', $attempt))->assertOk()
            ->viewData('page')['props']['questions'];
        $shown = collect($page)->keyBy('type');

        // nothing on the page gives the answer away
        $this->assertSame([], $shown['fill_blank']['options']);
        $leftIds = collect($shown['matching']['options'])->where('column', 'left')->pluck('id');
        $rightIds = collect($shown['matching']['options'])->where('column', 'right')->pluck('id');
        $this->assertCount(3, $rightIds);
        $this->assertEmpty($leftIds->intersect($rightIds));
        $this->assertStringNotContainsString('is_correct', json_encode($page));

        $q = fn (string $type) => Question::where('test_id', $test->id)->where('type', $type)->with('options')->firstOrFail();

        $answers = [
            'single_choice' => $q('single_choice')->options->firstWhere('option_text', '4')->id,
            'multiple_choice' => $q('multiple_choice')->options->where('is_correct', true)->pluck('id')->reverse()->values()->all(),
            'true_false' => false, // "Yolg'on" is correct — a false answer must count as answered
            'fill_blank' => '  nyuton ',
            'matching' => $q('matching')->options->mapWithKeys(fn ($o) => [$o->id => Question::matchingKey($o->id)])->all(),
            'ordering' => $q('ordering')->options->sortBy('correct_position')->pluck('id')->values()->all(),
        ];

        foreach ($answers as $type => $answer) {
            $this->actingAs($student)->postJson(route('student.tests.save-answer', $attempt), [
                'question_id' => $q($type)->id,
                'answer' => $answer,
            ])->assertOk();
        }

        $this->actingAs($student)->postJson(route('student.tests.submit', $attempt), ['confirm' => true])->assertOk();

        $attempt->refresh();
        $wrong = $attempt->answers()->with('question')->where('is_correct', false)->get()->pluck('question.type')->all();
        $this->assertSame([], $wrong, 'Graded as wrong: '.implode(', ', $wrong));
        $this->assertEquals(100, $attempt->score);
        $this->assertTrue($attempt->is_passed);
        $this->assertSame(0, $attempt->skipped_questions);
    }

    public function test_question_options_are_validated_per_type(): void
    {
        $teacher = $this->teacher();
        $test = Test::forceCreate(['course_id' => $this->course($teacher)->id, 'type' => 'final', 'title' => 'T']);

        $post = fn (string $type, array $options) => $this->actingAs($teacher)->post(route('teacher.tests.questions.store', $test), [
            'type' => $type, 'question_text' => 'Savol', 'points' => 1, 'options' => $options,
        ]);

        $post('single_choice', [['option_text' => 'a', 'is_correct' => false], ['option_text' => 'b', 'is_correct' => false]])
            ->assertSessionHasErrors('options');
        $post('single_choice', [['option_text' => 'a', 'is_correct' => true], ['option_text' => 'b', 'is_correct' => true]])
            ->assertSessionHasErrors('options');
        $post('matching', [['option_text' => 'a', 'match_text' => ''], ['option_text' => 'b', 'match_text' => 'c']])
            ->assertSessionHasErrors('options');

        $this->assertSame(0, $test->questions()->count());
    }

    public function test_updating_a_question_rebuilds_its_answer_key(): void
    {
        $teacher = $this->teacher();
        $test = Test::forceCreate(['course_id' => $this->course($teacher)->id, 'type' => 'final', 'title' => 'T']);

        $this->actingAs($teacher)->post(route('teacher.tests.questions.store', $test), [
            'type' => 'true_false', 'question_text' => 'Yer yumaloq', 'points' => 1, 'options' => self::PAYLOADS['true_false'],
        ]);
        $question = $test->questions()->firstOrFail();
        $this->assertFalse($question->correct_answer);

        $this->actingAs($teacher)->put(route('teacher.questions.update', $question), [
            'question_text' => 'Yer yumaloq',
            'points' => 2,
            'options' => [['option_text' => 'Rost', 'is_correct' => true], ['option_text' => 'Yolg\'on', 'is_correct' => false]],
        ])->assertSessionHasNoErrors();

        $this->assertTrue($question->fresh()->correct_answer);
        $this->assertSame(2, $question->fresh()->options()->count());
    }
}
