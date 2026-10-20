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
 * Review queue over HTTP: approve is blocked on the server until the
 * teacher has set the correct answer; mark / approve / reject / next flow.
 */
class ReviewQueueTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;

    private Topic $topic;

    protected function setUp(): void
    {
        parent::setUp();
        $this->teacher = User::factory()->create(['role' => 'teacher', 'status' => 'active']);
        $subject = Subject::factory()->math()->create();
        $this->topic = Topic::factory()->create(['subject_id' => $subject->id]);
    }

    private function draft(array $attrs = []): QuestionBank
    {
        return ExamQuestionFactory::new()->aiDraft()->withUnmarkedOptions()->forTopic($this->topic)->create($attrs);
    }

    public function test_approve_is_blocked_without_a_correct_option(): void
    {
        $q = $this->draft();

        $this->actingAs($this->teacher)
            ->from(route('teacher.exam.review', ['question' => $q->id]))
            ->post(route('teacher.exam.review.approve', $q))
            ->assertRedirect(route('teacher.exam.review', ['question' => $q->id]))
            ->assertSessionHasErrors('approve');

        $q->refresh();
        $this->assertSame(ReviewStatus::InReview, $q->review_status);
        $this->assertSame(4, $q->answerOptions()->whereNull('is_correct')->count());
        $this->assertSame(0, QuestionBank::approved()->count());
    }

    public function test_approve_with_a_foreign_option_id_is_rejected(): void
    {
        $q = $this->draft();
        $other = $this->draft();

        $this->actingAs($this->teacher)
            ->post(route('teacher.exam.review.approve', $q), ['option_id' => $other->answerOptions()->first()->id])
            ->assertSessionHasErrors('approve');
        $this->assertSame(ReviewStatus::InReview, $q->fresh()->review_status);
    }

    public function test_mark_then_approve_moves_to_the_next_question(): void
    {
        $first = $this->draft(['created_at' => now()->subMinutes(5)]);
        $second = $this->draft(['created_at' => now()->subMinute()]);
        $optionC = $first->answerOptions()->where('label', 'C')->first();

        // one click (or key 3) marks C
        $this->actingAs($this->teacher)
            ->post(route('teacher.exam.review.mark', $first), ['option_id' => $optionC->id])
            ->assertRedirect();
        $this->assertSame(['C'], $first->answerOptions()->where('is_correct', true)->pluck('label')->all());
        $this->assertSame(3, $first->answerOptions()->where('is_correct', false)->count());

        // marking another option replaces the pick (single choice)
        $optionB = $first->answerOptions()->where('label', 'B')->first();
        $this->post(route('teacher.exam.review.mark', $first), ['option_id' => $optionB->id]);
        $this->assertSame(['B'], $first->answerOptions()->where('is_correct', true)->pluck('label')->all());

        // Enter
        $this->post(route('teacher.exam.review.approve', $first), ['subject' => 'math'])
            ->assertRedirect(route('teacher.exam.review', ['subject' => 'math', 'after' => $first->id]))
            ->assertSessionHasNoErrors();

        $first->refresh();
        $this->assertSame(ReviewStatus::Approved, $first->review_status);
        $this->assertTrue($first->reviewer->is($this->teacher));
        $this->assertSame('b', $first->correct_answer);

        $this->get(route('teacher.exam.review', ['subject' => 'math', 'after' => $first->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Teacher/Exam/Review/Index')
                ->where('question.id', $second->id)
                ->where('queue.remaining', 1)
                ->where('progress.approved', 1)
                ->where('progress.target', 100)
                ->has('question.options', 4)
                ->has('question.violations.correct_count'));
    }

    public function test_approve_with_option_id_in_one_request(): void
    {
        $q = $this->draft();
        $pick = $q->answerOptions()->where('label', 'D')->first();

        $this->actingAs($this->teacher)
            ->post(route('teacher.exam.review.approve', $q), ['option_id' => $pick->id])
            ->assertSessionHasNoErrors();

        $this->assertSame(ReviewStatus::Approved, $q->fresh()->review_status);
        $this->assertSame('d', $q->fresh()->correct_answer);
    }

    public function test_multiple_choice_mark_toggles(): void
    {
        $q = $this->draft(['question_type' => 'multiple_choice']);
        [$a, $c] = [$q->answerOptions()->where('label', 'A')->first(), $q->answerOptions()->where('label', 'C')->first()];

        $this->actingAs($this->teacher);
        $this->post(route('teacher.exam.review.mark', $q), ['option_id' => $a->id]);
        $this->post(route('teacher.exam.review.mark', $q), ['option_id' => $c->id]);
        $this->assertSame(['A', 'C'], $q->answerOptions()->where('is_correct', true)->pluck('label')->all());
        $this->post(route('teacher.exam.review.mark', $q), ['option_id' => $a->id]);
        $this->assertSame(['C'], $q->answerOptions()->where('is_correct', true)->pluck('label')->all());

        $this->post(route('teacher.exam.review.approve', $q))->assertSessionHasNoErrors();
        $this->assertSame(['c'], $q->fresh()->correct_answer);
    }

    public function test_numeric_question_needs_a_value_before_approval(): void
    {
        $q = ExamQuestionFactory::new()->numericWithUnit()->aiDraft()->forTopic($this->topic)->create();
        $this->actingAs($this->teacher);

        $this->post(route('teacher.exam.review.approve', $q))->assertSessionHasErrors('approve');

        $this->post(route('teacher.exam.review.answer', $q), ['answer' => [
            'value' => '20', 'tolerance_type' => 'rel', 'tolerance' => '0,01', 'unit' => 'm/s',
            'accepted_units' => [['unit' => 'km/soat', 'factor' => '0,2777777778']],
        ]])->assertSessionHasNoErrors();

        $answer = $q->numericAnswers()->first();
        $this->assertSame(20.0, (float) $answer->value);
        $this->assertSame(0.01, (float) $answer->tolerance);
        $this->assertTrue($answer->accepts(72, 'km/soat'));

        $this->post(route('teacher.exam.review.approve', $q))->assertSessionHasNoErrors();
        $this->assertSame(ReviewStatus::Approved, $q->fresh()->review_status);
    }

    public function test_reject_needs_a_reason_and_leaves_the_queue(): void
    {
        $q = $this->draft();
        $this->actingAs($this->teacher);

        $this->post(route('teacher.exam.review.reject', $q), ['reason' => ''])->assertSessionHasErrors('reason');
        $this->assertSame(ReviewStatus::InReview, $q->fresh()->review_status);

        $this->post(route('teacher.exam.review.reject', $q), ['reason' => 'Shart noaniq'])
            ->assertRedirect(route('teacher.exam.review', ['after' => $q->id]));
        $q->refresh();
        $this->assertSame(ReviewStatus::Rejected, $q->review_status);
        $this->assertSame('Shart noaniq', $q->reject_reason);

        $this->get(route('teacher.exam.review'))
            ->assertInertia(fn (Assert $page) => $page->where('question', null)->where('queue.remaining', 0));
    }

    public function test_inline_edit_keeps_marks_and_changes_difficulty_and_topic(): void
    {
        $q = $this->draft();
        $q->answerOptions()->where('label', 'A')->update(['is_correct' => true]);
        $other = Topic::factory()->create(['subject_id' => $this->topic->subject_id]);

        $this->actingAs($this->teacher)
            ->patch(route('teacher.exam.review.update', $q), [
                'question_text' => 'Yangi shart: $2x = 4$',
                'difficulty_level' => 5,
                'topic_id' => $other->id,
                'options' => [['text' => '$2$'], ['text' => '$3$'], ['text' => '$4$'], ['text' => '$5$']],
            ])->assertSessionHasNoErrors();

        $q->refresh();
        $this->assertSame('Yangi shart: $2x = 4$', $q->question_text);
        $this->assertSame(5, $q->difficulty_level);
        $this->assertSame($other->id, $q->topic_id);
        $this->assertSame(ReviewStatus::InReview, $q->review_status);
        $this->assertSame(['$2$', '$3$', '$4$', '$5$'], $q->answerOptions()->pluck('text')->all());
        $this->assertTrue($q->answerOptions()->where('label', 'A')->value('is_correct'));
    }

    public function test_topic_of_another_subject_is_refused(): void
    {
        $q = $this->draft();
        $physicsTopic = Topic::factory()->create(['subject_id' => Subject::factory()->physics()->create()->id]);

        $this->actingAs($this->teacher)
            ->patch(route('teacher.exam.review.update', $q), ['topic_id' => $physicsTopic->id])
            ->assertSessionHasErrors('topic_id');
        $this->assertSame($this->topic->id, $q->fresh()->topic_id);
    }

    public function test_next_wraps_and_filters_by_subject(): void
    {
        $a = $this->draft(['created_at' => now()->subMinutes(3)]);
        $b = $this->draft(['created_at' => now()->subMinutes(2)]);
        $physics = Topic::factory()->create(['subject_id' => Subject::factory()->physics()->create()->id]);
        ExamQuestionFactory::new()->aiDraft()->withUnmarkedOptions()->forTopic($physics)->create();

        $this->actingAs($this->teacher);
        $this->get(route('teacher.exam.review', ['subject' => 'math']))
            ->assertInertia(fn (Assert $p) => $p->where('question.id', $a->id)->where('queue.next_id', $b->id)->where('queue.remaining', 2)->where('queue.position', 1));
        $this->get(route('teacher.exam.review', ['subject' => 'math', 'after' => $b->id]))
            ->assertInertia(fn (Assert $p) => $p->where('question.id', $a->id));
        $this->get(route('teacher.exam.review'))
            ->assertInertia(fn (Assert $p) => $p->where('queue.remaining', 3));
    }

    public function test_weekly_progress_counts_this_weeks_teacher_approvals_only(): void
    {
        config(['exam.bank.weekly_review_target' => 120]);
        ExamQuestionFactory::new()->approved($this->teacher)->forTopic($this->topic)->count(2)->create();
        $old = ExamQuestionFactory::new()->approved($this->teacher)->forTopic($this->topic)->create();
        QuestionBank::query()->whereKey($old->id)->update(['reviewed_at' => now()->subWeeks(2)]);
        $demo = ExamQuestionFactory::new()->approved($this->teacher)->forTopic($this->topic)->create();
        QuestionBank::query()->whereKey($demo->id)->update(['is_demo' => true]);

        $this->actingAs($this->teacher)->get(route('teacher.exam.review'))
            ->assertInertia(fn (Assert $p) => $p->where('progress.approved', 2)->where('progress.target', 120));
    }
}
