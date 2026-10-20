<?php

namespace Tests\Feature\Exam;

use App\Enums\Exam\ReviewStatus;
use App\Models\Exam\Subject;
use App\Models\Exam\Topic;
use App\Models\QuestionBank;
use App\Models\User;
use Database\Factories\Exam\ExamQuestionFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

/**
 * "Savollar banki": filters, create / edit for every supported type, and the
 * rule that an edited approved question is reviewed again.
 */
class QuestionBankTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;

    private Subject $math;

    private Subject $physics;

    private Topic $section;

    private Topic $quadratic;

    private Topic $kinematics;

    protected function setUp(): void
    {
        parent::setUp();
        $this->teacher = User::factory()->create(['role' => 'teacher', 'status' => 'active']);
        $this->math = Subject::factory()->math()->create();
        $this->physics = Subject::factory()->physics()->create();
        $this->section = Topic::factory()->create(['subject_id' => $this->math->id, 'code' => 'math.equations', 'name_uz' => 'Tenglamalar']);
        $this->quadratic = Topic::factory()->childOf($this->section)->create(['code' => 'math.equations.quadratic', 'name_uz' => 'Kvadrat tenglamalar']);
        $this->kinematics = Topic::factory()->create(['subject_id' => $this->physics->id, 'code' => 'phys.kinematics', 'name_uz' => 'Kinematika']);
    }

    private function choicePayload(array $overrides = []): array
    {
        return array_merge([
            'subject_id' => $this->math->id,
            'topic_id' => $this->quadratic->id,
            'question_type' => 'single_choice',
            'question_text' => '$x^2 - 5x + 6 = 0$ ildizlari yig\'indisi?',
            'difficulty_level' => 2,
            'exam_formats' => ['milliy_sertifikat'],
            'source_ref' => 'To\'plam, 12-bet',
            'options' => [
                ['text' => '$5$', 'is_correct' => true],
                ['text' => '$6$', 'is_correct' => false],
                ['text' => '$-5$', 'is_correct' => false],
                ['text' => '$1$', 'is_correct' => false],
            ],
            'solution_text' => 'Viyet: $x_1 + x_2 = 5$',
            'solution_video_url' => 'https://youtu.be/abc',
            'intent' => 'review',
        ], $overrides);
    }

    public function test_index_filters_by_subject_topic_subtree_difficulty_format_status_origin_source_and_search(): void
    {
        $hit = ExamQuestionFactory::new()->forTopic($this->quadratic)->inReview()->create([
            'difficulty_level' => 4, 'exam_formats' => ['sat'], 'origin' => 'import',
            'source_ref' => 'Skanavi 3.14', 'question_text' => 'Diskriminantni toping: $x^2+4x+1=0$',
        ]);
        ExamQuestionFactory::new()->forTopic($this->quadratic)->create(['difficulty_level' => 2, 'question_text' => 'Boshqa savol']);
        ExamQuestionFactory::new()->forTopic($this->kinematics)->create(['difficulty_level' => 4, 'exam_formats' => ['sat']]);
        QuestionBank::forceCreate(['question_type' => 'single_choice', 'question_text' => 'Olympiad only', 'status' => 'active']); // no subject

        $this->actingAs($this->teacher);
        $check = function (array $query, array $expectedIds) {
            $this->get(route('teacher.exam.questions.index', $query))->assertOk()
                ->assertInertia(fn (Assert $p) => $p->component('Teacher/Exam/Questions/Index')
                    ->where('questions.total', count($expectedIds))
                    ->where('questions.data', fn ($rows) => collect($rows)->pluck('id')->sort()->values()->all() === collect($expectedIds)->sort()->values()->all()));
        };

        $this->get(route('teacher.exam.questions.index'))->assertInertia(fn (Assert $p) => $p->where('questions.total', 3));
        $check(['subject' => 'math'], [$hit->id, QuestionBank::where('question_text', 'Boshqa savol')->value('id')]);
        $check(['subject' => 'math', 'topic' => $this->section->id], [$hit->id, QuestionBank::where('question_text', 'Boshqa savol')->value('id')]);
        $check(['subject' => 'math', 'difficulty' => 4], [$hit->id]);
        $check(['format' => 'sat', 'subject' => 'math'], [$hit->id]);
        $check(['status' => 'in_review'], [$hit->id]);
        $check(['origin' => 'import'], [$hit->id]);
        $check(['source' => 'Skanavi'], [$hit->id]);
        $check(['q' => 'Diskriminant'], [$hit->id]);

        $this->get(route('teacher.exam.questions.index', ['subject' => 'math']))
            ->assertInertia(fn (Assert $p) => $p->where('statusCounts.in_review', 1)->where('statusCounts.draft', 1));
    }

    public function test_create_single_choice_and_approve_in_one_step(): void
    {
        $this->actingAs($this->teacher)
            ->post(route('teacher.exam.questions.store'), $this->choicePayload(['intent' => 'approve']))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $q = QuestionBank::query()->firstOrFail();
        $this->assertSame(ReviewStatus::Approved, $q->review_status);
        $this->assertSame('manual', $q->origin->value);
        $this->assertSame(['A'], $q->answerOptions()->where('is_correct', true)->pluck('label')->all());
        $this->assertSame('a', $q->correct_answer);
        $this->assertSame('easy', $q->difficulty);
        $this->assertSame('Viyet: $x_1 + x_2 = 5$', $q->explanation);
        $this->assertSame('https://youtu.be/abc', $q->solution_video_url);
        $this->assertTrue($q->creator->is($this->teacher));
        $this->assertTrue(QuestionBank::examEligible()->whereKey($q->id)->exists());
    }

    public function test_unmarked_question_is_saved_but_not_approved(): void
    {
        $payload = $this->choicePayload(['intent' => 'approve']);
        $payload['options'] = array_map(fn ($o) => ['text' => $o['text'], 'is_correct' => null], $payload['options']);

        $this->actingAs($this->teacher)
            ->post(route('teacher.exam.questions.store'), $payload)
            ->assertSessionHas('warning');

        $q = QuestionBank::query()->firstOrFail();
        $this->assertSame(ReviewStatus::InReview, $q->review_status);
        $this->assertSame(4, $q->answerOptions()->whereNull('is_correct')->count());
    }

    public function test_validation_of_options_and_topic(): void
    {
        $this->actingAs($this->teacher);
        $three = $this->choicePayload();
        array_pop($three['options']);
        $this->post(route('teacher.exam.questions.store'), $three)->assertSessionHasErrors('options');

        $twoCorrect = $this->choicePayload();
        $twoCorrect['options'][1]['is_correct'] = true;
        $this->post(route('teacher.exam.questions.store'), $twoCorrect)->assertSessionHasErrors('options');

        $this->post(route('teacher.exam.questions.store'), $this->choicePayload(['topic_id' => $this->kinematics->id]))
            ->assertSessionHasErrors('topic_id');
        $this->post(route('teacher.exam.questions.store'), $this->choicePayload(['solution_video_url' => 'javascript:alert(1)']))
            ->assertSessionHasErrors('solution_video_url');

        $this->assertSame(0, QuestionBank::count());
    }

    public function test_multiple_choice_with_five_options(): void
    {
        $payload = $this->choicePayload(['question_type' => 'multiple_choice', 'intent' => 'approve']);
        $payload['options'][] = ['text' => '$0$', 'is_correct' => true];

        $this->actingAs($this->teacher)->post(route('teacher.exam.questions.store'), $payload)->assertSessionHasNoErrors();

        $q = QuestionBank::query()->firstOrFail();
        $this->assertSame(['A', 'B', 'C', 'D', 'E'], $q->answerOptions()->pluck('label')->all());
        $this->assertSame(['a', 'e'], $q->correct_answer);
        $this->assertSame(ReviewStatus::Approved, $q->review_status);
    }

    public function test_numeric_with_unit_question_with_tolerance_and_units(): void
    {
        $this->actingAs($this->teacher)->post(route('teacher.exam.questions.store'), [
            'subject_id' => $this->physics->id,
            'topic_id' => $this->kinematics->id,
            'question_type' => 'numeric_with_unit',
            'question_text' => '2 soatda 144 km. O\'rtacha tezlik?',
            'difficulty_level' => 3,
            'units_note' => 'Javob m/s da',
            'constants' => [['key' => 'g', 'value' => '10', 'unit' => 'm/s²']],
            'answer' => [
                'value' => '20', 'tolerance_type' => 'rel', 'tolerance' => '0,01', 'unit' => 'm/s',
                'accepted_units' => [['unit' => 'km/soat', 'factor' => '0.2777777778']],
            ],
            'intent' => 'approve',
        ])->assertSessionHasNoErrors();

        $q = QuestionBank::query()->firstOrFail();
        $this->assertSame(ReviewStatus::Approved, $q->review_status);
        $this->assertSame(['g' => ['value' => 10, 'unit' => 'm/s²']], $q->constants);
        $a = $q->numericAnswers()->firstOrFail();
        $this->assertSame('m/s', $a->unit);
        $this->assertTrue($a->accepts(72, 'km/soat'));
        $this->assertTrue($a->accepts(20.1, 'm/s'));
        $this->assertFalse($a->accepts(21, 'm/s'));
    }

    public function test_numeric_without_value_stays_in_review(): void
    {
        $this->actingAs($this->teacher)->post(route('teacher.exam.questions.store'), [
            'subject_id' => $this->math->id, 'topic_id' => $this->quadratic->id, 'question_type' => 'numeric',
            'question_text' => '$2+2$', 'difficulty_level' => 1, 'answer' => ['value' => null], 'intent' => 'approve',
        ])->assertSessionHas('warning');

        $q = QuestionBank::query()->firstOrFail();
        $this->assertSame(ReviewStatus::InReview, $q->review_status);
        $this->assertNull($q->numericAnswers()->value('value'));
    }

    public function test_editing_an_approved_question_sends_it_back_to_review_unless_reapproved(): void
    {
        $q = ExamQuestionFactory::new()->approved($this->teacher)->forTopic($this->quadratic)->create();
        $payload = $this->choicePayload(['intent' => null, 'question_text' => 'Tuzatilgan shart']);
        unset($payload['intent']);

        $this->actingAs($this->teacher)->put(route('teacher.exam.questions.update', $q), $payload)->assertSessionHasNoErrors();
        $q->refresh();
        $this->assertSame(ReviewStatus::InReview, $q->review_status);
        $this->assertFalse(QuestionBank::examEligible()->whereKey($q->id)->exists());
        $this->assertSame('draft', $q->status);

        $this->put(route('teacher.exam.questions.update', $q), [...$payload, 'intent' => 'approve'])->assertSessionHasNoErrors();
        $this->assertSame(ReviewStatus::Approved, $q->fresh()->review_status);

        // metadata only (solution video) keeps it approved
        $this->put(route('teacher.exam.questions.update', $q), [...$payload, 'solution_video_url' => 'https://youtu.be/new']);
        $this->assertSame(ReviewStatus::Approved, $q->fresh()->review_status);
    }

    public function test_edit_page_shows_guard_violations(): void
    {
        $q = ExamQuestionFactory::new()->aiDraft()->withUnmarkedOptions()->forTopic($this->quadratic)->create();

        $this->actingAs($this->teacher)->get(route('teacher.exam.questions.edit', $q))
            ->assertOk()
            ->assertInertia(fn (Assert $p) => $p->component('Teacher/Exam/Questions/Edit')
                ->where('question.id', $q->id)
                ->has('question.options', 4)
                ->has('question.violations.correct_count'));
    }

    public function test_approved_question_is_retired_not_deleted(): void
    {
        $q = ExamQuestionFactory::new()->approved($this->teacher)->forTopic($this->quadratic)->create();
        $this->actingAs($this->teacher);

        $this->delete(route('teacher.exam.questions.destroy', $q))->assertSessionHas('error');
        $this->assertNotNull($q->fresh());

        $this->post(route('teacher.exam.questions.retire', $q), ['reason' => 'Javobda xato'])->assertSessionHas('success');
        $this->assertSame(ReviewStatus::Retired, $q->fresh()->review_status);

        $this->post(route('teacher.exam.questions.duplicate', $q))->assertRedirect();
        $copy = QuestionBank::query()->whereKeyNot($q->id)->firstOrFail();
        $this->assertSame(ReviewStatus::Draft, $copy->review_status);
        $this->assertSame(4, $copy->answerOptions()->count());

        $draft = ExamQuestionFactory::new()->forTopic($this->quadratic)->create();
        $this->delete(route('teacher.exam.questions.destroy', $draft))->assertSessionHas('success');
        $this->assertSoftDeleted($draft);
    }

    public function test_create_page_renders_with_topics(): void
    {
        $this->actingAs($this->teacher)->get(route('teacher.exam.questions.create', ['subject' => 'physics']))
            ->assertOk()
            ->assertInertia(fn (Assert $p) => $p->component('Teacher/Exam/Questions/Create')
                ->where('defaults.subject_id', $this->physics->id)
                ->has('topics', 3));
    }
}
