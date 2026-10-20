<?php

namespace Tests\Feature\Teacher;

use App\Models\Exam\Subject;
use App\Models\Exam\Topic;
use App\Models\Question;
use App\Models\QuestionBank;
use App\Models\Test;
use App\Models\User;
use Database\Factories\Exam\ExamQuestionFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\CreatesCourses;
use Tests\TestCase;

/**
 * Course test → "Bankdan qo'shish": counts per level, hand-pick and a
 * distribution; the copy maps 1–5 to easy / medium / hard, keeps the origin
 * and never adds the same bank question twice to one test.
 */
class TestBankImportTest extends TestCase
{
    use CreatesCourses, RefreshDatabase;

    private User $teacher;

    private Test $test;

    private Subject $math;

    private Topic $section;

    private Topic $child;

    protected function setUp(): void
    {
        parent::setUp();
        $this->teacher = $this->teacher();
        $course = $this->course($this->teacher);
        $this->test = Test::forceCreate(['course_id' => $course->id, 'type' => 'final', 'title' => 'Yakuniy', 'is_active' => false]);
        $this->math = Subject::factory()->math()->create();
        $this->section = Topic::factory()->create(['subject_id' => $this->math->id, 'code' => 'math.alg']);
        $this->child = Topic::factory()->childOf($this->section)->create(['code' => 'math.alg.lin']);
    }

    /** @param array<int, int> $perLevel */
    private function bank(array $perLevel, string $type = 'single_choice', ?Topic $topic = null): void
    {
        foreach ($perLevel as $level => $n) {
            ExamQuestionFactory::new()->forTopic($topic ?? $this->child)->state(['question_type' => $type])->approved()
                ->count($n)->create(['difficulty_level' => $level, 'solution_text' => 'Yechim $x = 3$']);
        }
    }

    public function test_page_and_search_show_counts_per_level_for_choice_questions_only(): void
    {
        $this->bank([1 => 1, 2 => 2, 3 => 3, 5 => 1]);
        $this->bank([3 => 2], 'numeric'); // no course test equivalent
        ExamQuestionFactory::new()->forTopic($this->child)->inReview()->create(['difficulty_level' => 4]); // not approved

        $this->actingAs($this->teacher)->get(route('teacher.tests.questions.bank', $this->test))
            ->assertOk()->assertInertia(fn (Assert $p) => $p->component('Teacher/Courses/Tests/Questions/FromBank')
                ->has('subjects', 1)->where('levels.4.label', '5 — juda qiyin')->where('bands.2.levels', [4, 5]));

        $this->actingAs($this->teacher)->getJson(route('teacher.tests.questions.bank.search', [$this->test, 'subject_id' => $this->math->id, 'topic_id' => $this->section->id]))
            ->assertOk()
            ->assertJsonPath('levels', ['1' => 1, '2' => 2, '3' => 3, '4' => 0, '5' => 1])
            ->assertJsonPath('bands', ['easy' => 3, 'medium' => 3, 'hard' => 1])
            ->assertJsonPath('total', 7);

        $this->actingAs($this->teacher)->getJson(route('teacher.tests.questions.bank.search', [$this->test, 'subject_id' => $this->math->id, 'difficulty' => 'easy']))
            ->assertJsonPath('total', 3)->assertJsonPath('items.0.band', 'easy');
        $this->actingAs($this->teacher)->getJson(route('teacher.tests.questions.bank.search', [$this->test, 'subject_id' => $this->math->id, 'difficulty' => '5']))
            ->assertJsonPath('total', 1)->assertJsonPath('items.0.difficulty_level', 5);
    }

    public function test_hand_picked_questions_are_copied_with_mapped_difficulty_and_origin(): void
    {
        $this->bank([2 => 1, 3 => 1, 4 => 1]);
        $ids = QuestionBank::query()->orderBy('difficulty_level')->pluck('id')->all();

        $this->actingAs($this->teacher)->post(route('teacher.tests.questions.bank.store', $this->test), [
            'subject_id' => $this->math->id, 'mode' => 'pick', 'question_ids' => $ids,
        ])->assertRedirect(route('teacher.tests.questions.index', $this->test))->assertSessionHas('success');

        $copies = $this->test->questions()->with('options')->orderBy('sort_order')->get();
        $this->assertCount(3, $copies);
        $this->assertSame(['easy', 'medium', 'hard'], $copies->pluck('difficulty')->all());
        $this->assertSame($ids, $copies->pluck('bank_question_id')->all());
        $first = $copies->first();
        $source = QuestionBank::query()->with('answerOptions')->find($ids[0]);
        $this->assertSame($source->question_text, $first->question_text);
        $this->assertSame('Yechim $x = 3$', $first->explanation);
        $this->assertSame($this->test->course_id, $first->course_id);
        $this->assertSame('single_choice', $first->type);
        $this->assertSame($source->answerOptions->pluck('text')->all(), $first->options->pluck('option_text')->all());
        $correct = $first->options->firstWhere('is_correct', true);
        $this->assertSame($correct->id, $first->correct_answer, 'the answer key is derived');
        $this->assertSame(1, $first->options->where('is_correct', true)->count());

        // the same bank questions again: nothing is duplicated
        $this->actingAs($this->teacher)->post(route('teacher.tests.questions.bank.store', $this->test), [
            'subject_id' => $this->math->id, 'mode' => 'pick', 'question_ids' => $ids,
        ])->assertSessionHas('warning');
        $this->assertSame(3, $this->test->questions()->count());

        // and they no longer count as available for this test
        $this->actingAs($this->teacher)->getJson(route('teacher.tests.questions.bank.search', [$this->test, 'subject_id' => $this->math->id]))
            ->assertJsonPath('total', 0);
    }

    public function test_a_distribution_is_drawn_at_random_with_shortfall_reported(): void
    {
        $this->bank([1 => 2, 2 => 2, 3 => 4, 4 => 1]);

        $this->actingAs($this->teacher)->post(route('teacher.tests.questions.bank.store', $this->test), [
            'subject_id' => $this->math->id, 'topic_id' => $this->section->id, 'mode' => 'distribution',
            'distribution' => ['easy' => 3, 'medium' => 2, 'hard' => 3], 'seed' => 77,
        ])->assertRedirect()->assertSessionHas('success', fn ($m) => str_contains($m, '6 ta savol') && str_contains($m, 'qiyin: 2 ta'));

        $counts = $this->test->questions()->pluck('difficulty')->countBy()->all();
        $this->assertEquals(['easy' => 3, 'medium' => 2, 'hard' => 1], $counts);
        $this->assertSame(6, $this->test->questions()->distinct()->count('bank_question_id'));

        // a second draw only takes what is not in the test yet
        $this->actingAs($this->teacher)->post(route('teacher.tests.questions.bank.store', $this->test), [
            'subject_id' => $this->math->id, 'mode' => 'distribution', 'distribution' => ['easy' => 5, 'medium' => 5],
        ]);
        $this->assertSame(9, $this->test->questions()->count());
        $this->assertSame(9, $this->test->questions()->distinct()->count('bank_question_id'));
    }

    public function test_input_is_validated(): void
    {
        $this->bank([3 => 1]);
        $this->actingAs($this->teacher);
        $post = fn (array $data) => $this->post(route('teacher.tests.questions.bank.store', $this->test), $data);

        $post(['mode' => 'pick', 'question_ids' => []])->assertSessionHasErrors(['subject_id', 'question_ids']);
        $post(['subject_id' => $this->math->id, 'mode' => 'distribution', 'distribution' => ['easy' => 0]])->assertSessionHasErrors('distribution');
        $post(['subject_id' => $this->math->id, 'mode' => 'distribution', 'distribution' => ['easy' => -1]])->assertSessionHasErrors('distribution.easy');
        $post(['subject_id' => $this->math->id, 'mode' => 'magic'])->assertSessionHasErrors('mode');
        // a numeric or unapproved question cannot be smuggled in by id
        $numeric = ExamQuestionFactory::new()->forTopic($this->child)->numeric()->approved()->create(['difficulty_level' => 3]);
        $draft = ExamQuestionFactory::new()->forTopic($this->child)->create(['difficulty_level' => 3]);
        $post(['subject_id' => $this->math->id, 'mode' => 'pick', 'question_ids' => [$numeric->id, $draft->id]])->assertSessionHas('warning');
        $this->assertSame(0, Question::query()->count());
    }

    public function test_only_the_course_owner_can_add_from_the_bank(): void
    {
        $this->bank([3 => 1]);
        $id = QuestionBank::query()->value('id');
        $other = $this->teacher();
        $student = $this->studentUser();

        $this->actingAs($other)->get(route('teacher.tests.questions.bank', $this->test))->assertForbidden();
        $this->actingAs($other)->getJson(route('teacher.tests.questions.bank.search', [$this->test, 'subject_id' => $this->math->id]))->assertForbidden();
        $this->actingAs($other)->post(route('teacher.tests.questions.bank.store', $this->test), [
            'subject_id' => $this->math->id, 'mode' => 'pick', 'question_ids' => [$id],
        ])->assertForbidden();
        $this->actingAs($student)->get(route('teacher.tests.questions.bank', $this->test))->assertForbidden();
        $this->assertSame(0, Question::query()->count());
    }

    public function test_a_duplicated_question_drops_the_bank_origin(): void
    {
        $this->bank([3 => 1]);
        $this->actingAs($this->teacher)->post(route('teacher.tests.questions.bank.store', $this->test), [
            'subject_id' => $this->math->id, 'mode' => 'pick', 'question_ids' => [QuestionBank::query()->value('id')],
        ]);
        $copy = $this->test->questions()->firstOrFail();
        $this->actingAs($this->teacher)->post(route('teacher.questions.duplicate', $copy));

        $this->assertSame(2, $this->test->questions()->count());
        $this->assertSame(1, $this->test->questions()->whereNotNull('bank_question_id')->count());
    }
}
