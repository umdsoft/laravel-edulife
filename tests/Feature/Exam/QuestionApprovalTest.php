<?php

namespace Tests\Feature\Exam;

use App\Enums\Exam\ReviewStatus;
use App\Exceptions\Exam\InvalidReviewTransition;
use App\Exceptions\Exam\QuestionNotApprovable;
use App\Models\Exam\QuestionBankAnswer;
use App\Models\Exam\QuestionBankOption;
use App\Models\Exam\Topic;
use App\Models\QuestionBank;
use App\Models\User;
use App\Services\Exam\QuestionApprovalGuard;
use App\Services\Exam\QuestionReviewService;
use Database\Factories\Exam\ExamQuestionFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "AI never sets the correct option; unapproved questions never reach exams."
 */
class QuestionApprovalTest extends TestCase
{
    use RefreshDatabase;

    private QuestionReviewService $review;

    private User $teacher;

    protected function setUp(): void
    {
        parent::setUp();
        $this->review = app(QuestionReviewService::class);
        $this->teacher = User::factory()->create(['role' => 'teacher']);
    }

    private function aiDraft(): QuestionBank
    {
        return ExamQuestionFactory::new()->aiDraft()->withUnmarkedOptions()->create();
    }

    private function violations(QuestionBank $q): array
    {
        return app(QuestionApprovalGuard::class)->violations($q);
    }

    public function test_ai_draft_without_marked_option_can_not_be_approved_by_direct_update(): void
    {
        $q = $this->aiDraft();
        $this->assertSame(4, $q->answerOptions()->whereNull('is_correct')->count());

        try {
            $q->update(['review_status' => ReviewStatus::Approved]);
            $this->fail('approval without a correct option must be blocked');
        } catch (QuestionNotApprovable $e) {
            $this->assertArrayHasKey('correct_count', $e->violations);
        }

        $this->assertSame(ReviewStatus::InReview, $q->fresh()->review_status);
        $this->assertSame(0, QuestionBank::approved()->count());
    }

    public function test_ai_draft_can_not_be_approved_through_the_service_without_a_pick(): void
    {
        $q = $this->aiDraft();

        $this->expectException(QuestionNotApprovable::class);
        try {
            $this->review->approve($q, $this->teacher);
        } finally {
            // the transaction rolled back: options still unmarked
            $this->assertSame(4, $q->answerOptions()->whereNull('is_correct')->count());
        }
    }

    public function test_teacher_pick_approves_and_syncs_legacy_columns(): void
    {
        $q = $this->aiDraft();
        $pick = $q->answerOptions()->where('label', 'C')->first();

        $this->review->approve($q, $this->teacher, $pick->id);
        $q->refresh();

        $this->assertSame(ReviewStatus::Approved, $q->review_status);
        $this->assertTrue($q->reviewer->is($this->teacher));
        $this->assertNotNull($q->reviewed_at);
        $this->assertSame(['C'], $q->answerOptions()->where('is_correct', true)->pluck('label')->all());
        $this->assertSame(3, $q->answerOptions()->where('is_correct', false)->count());
        $this->assertSame(0, $q->answerOptions()->whereNull('is_correct')->count());
        $this->assertSame('c', $q->correct_answer);
        $this->assertTrue($q->checkAnswer('c'), 'olympiad grader still works');
        $this->assertSame(QuestionBank::STATUS_ACTIVE, $q->status);
        $this->assertTrue(QuestionBank::approved()->whereKey($q->id)->exists());
        $this->assertTrue(QuestionBank::examEligible()->whereKey($q->id)->exists());
    }

    public function test_single_choice_needs_exactly_one_correct_option(): void
    {
        $q = $this->aiDraft();
        $q->answerOptions()->whereIn('label', ['A', 'B'])->update(['is_correct' => true]);

        $this->assertArrayHasKey('correct_count', $this->violations($q));
        $this->expectException(QuestionNotApprovable::class);
        $this->review->approve($q, $this->teacher);
    }

    public function test_multiple_choice_allows_several_correct_options(): void
    {
        $q = ExamQuestionFactory::new()->inReview()->withUnmarkedOptions()->create(['question_type' => 'multiple_choice']);
        $q->answerOptions()->whereIn('label', ['A', 'D'])->update(['is_correct' => true]);

        $this->review->approve($q, $this->teacher);

        $this->assertSame(['a', 'd'], $q->fresh()->correct_answer);
    }

    public function test_option_count_and_classification_are_checked(): void
    {
        $one = ExamQuestionFactory::new()->inReview()->withUnmarkedOptions(1)->create();
        $this->assertArrayHasKey('options_count', $this->violations($one));

        $unclassified = ExamQuestionFactory::new()->inReview()->create([
            'topic_id' => null, 'subject_id' => null, 'difficulty_level' => null,
        ]);
        $v = $this->violations($unclassified);
        $this->assertArrayHasKey('topic_missing', $v);
        $this->assertArrayHasKey('subject_missing', $v);
        $this->assertArrayHasKey('difficulty_invalid', $v);

        $q = ExamQuestionFactory::new()->inReview()->create();
        $q->topic_id = Topic::factory()->create()->id; // topic of another subject
        $this->assertArrayHasKey('topic_subject_mismatch', $this->violations($q));
    }

    public function test_numeric_question_needs_a_teacher_value(): void
    {
        $q = ExamQuestionFactory::new()->numeric()->inReview()->create();
        $this->assertArrayHasKey('answer_missing', $this->violations($q));

        $answer = QuestionBankAnswer::create(['question_id' => $q->id, 'value' => null]);
        $this->assertArrayHasKey('answer_missing', $this->violations($q));

        $answer->update(['value' => '0.5', 'tolerance_type' => 'abs', 'tolerance' => 0]);
        $this->review->approve($q, $this->teacher);

        $this->assertTrue($q->fresh()->isApproved());
    }

    public function test_numeric_with_unit_needs_a_unit_and_positive_factors(): void
    {
        $q = ExamQuestionFactory::new()->numericWithUnit()->inReview()->create();
        $answer = QuestionBankAnswer::create(['question_id' => $q->id, 'value' => 20]);
        $this->assertArrayHasKey('unit_missing', $this->violations($q));

        $answer->update(['unit' => 'm/s', 'accepted_units' => ['km/soat' => 0]]);
        $this->assertArrayHasKey('unit_factor_invalid', $this->violations($q));

        $answer->update(['accepted_units' => ['km/soat' => 1 / 3.6]]);
        $this->review->approve($q, $this->teacher);
        $this->assertTrue($q->fresh()->isApproved());
    }

    public function test_review_transitions_are_enforced(): void
    {
        $q = ExamQuestionFactory::new()->approved()->create(['review_status' => ReviewStatus::InReview]);
        $draft = ExamQuestionFactory::new()->withUnmarkedOptions()->create();

        try {
            $draft->update(['review_status' => ReviewStatus::Approved]);
            $this->fail('draft -> approved must go through review');
        } catch (InvalidReviewTransition) {
        }

        $this->review->retire($q, $this->teacher, 'Shartda xato');
        $this->assertSame(ReviewStatus::Retired, $q->fresh()->review_status);
        $this->assertFalse(QuestionBank::approved()->whereKey($q->id)->exists());

        $this->expectException(InvalidReviewTransition::class);
        $q->fresh()->update(['review_status' => ReviewStatus::Approved]);
    }

    public function test_reject_requires_a_reason(): void
    {
        $q = $this->aiDraft();

        $this->review->reject($q, $this->teacher, 'Manba ko\'rsatilmagan');
        $this->assertSame(ReviewStatus::Rejected, $q->fresh()->review_status);
        $this->assertSame('Manba ko\'rsatilmagan', $q->fresh()->reject_reason);

        $this->expectException(\InvalidArgumentException::class);
        $this->review->reject($this->aiDraft(), $this->teacher, '  ');
    }

    public function test_foreign_option_can_not_be_picked(): void
    {
        $q = $this->aiDraft();
        $foreign = QuestionBankOption::factory()->create();

        $this->expectException(\InvalidArgumentException::class);
        $this->review->approve($q, $this->teacher, $foreign->id);
    }

    public function test_option_label_must_be_a_to_e(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        QuestionBankOption::factory()->create(['label' => 'F']);
    }

    public function test_normalized_hash_detects_cosmetic_duplicates(): void
    {
        $a = ExamQuestionFactory::new()->create(['question_text' => 'Tenglamani yeching:  $2x + 3 = 7$.']);
        $b = ExamQuestionFactory::new()->create(['question_text' => 'tenglamani yeching: $2x+3=7$']);

        $this->assertSame($a->normalized_hash, $b->normalized_hash);
    }
}
