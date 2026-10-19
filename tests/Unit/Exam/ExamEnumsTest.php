<?php

namespace Tests\Unit\Exam;

use App\Enums\Exam\BlueprintPart;
use App\Enums\Exam\ExamQuestionType;
use App\Enums\Exam\ReviewStatus;
use App\Enums\Exam\ToleranceType;
use App\Models\Exam\QuestionBankAnswer;
use App\Support\Exam\QuestionText;
use PHPUnit\Framework\TestCase;

class ExamEnumsTest extends TestCase
{
    public function test_review_status_transitions(): void
    {
        $this->assertTrue(ReviewStatus::Draft->canTransitionTo(ReviewStatus::InReview));
        $this->assertTrue(ReviewStatus::InReview->canTransitionTo(ReviewStatus::Approved));
        $this->assertTrue(ReviewStatus::Approved->canTransitionTo(ReviewStatus::Retired));
        $this->assertFalse(ReviewStatus::Draft->canTransitionTo(ReviewStatus::Approved));
        $this->assertFalse(ReviewStatus::Rejected->canTransitionTo(ReviewStatus::Approved));
        foreach (ReviewStatus::cases() as $to) {
            $this->assertSame($to === ReviewStatus::Retired, ReviewStatus::Retired->canTransitionTo($to), 'retired is terminal');
        }
        $this->assertSame([ReviewStatus::Approved], array_values(array_filter(
            ReviewStatus::cases(),
            fn ($s) => $s->isExamEligible()
        )));
    }

    public function test_question_type_parts(): void
    {
        $this->assertSame(['single_choice', 'multiple_choice', 'true_false'], ExamQuestionType::valuesForPart(BlueprintPart::Closed));
        $this->assertSame(['numeric', 'numeric_with_unit'], ExamQuestionType::valuesForPart(BlueprintPart::Open));
        $this->assertTrue(ExamQuestionType::NumericWithUnit->requiresUnit());
        $this->assertFalse(ExamQuestionType::Numeric->requiresUnit());
    }

    public function test_tolerance(): void
    {
        $this->assertTrue(ToleranceType::Absolute->accepts(0.3, 0.1 + 0.2, 0));
        $this->assertTrue(ToleranceType::Absolute->accepts(2.5, 2.55, 0.05));
        $this->assertFalse(ToleranceType::Absolute->accepts(2.5, 2.56, 0.05));
        $this->assertTrue(ToleranceType::Relative->accepts(200, 202, 0.01));
        $this->assertFalse(ToleranceType::Relative->accepts(200, 203, 0.01));
    }

    public function test_numeric_answer_with_units(): void
    {
        $answer = new QuestionBankAnswer([
            'value' => 20,
            'tolerance_type' => 'abs',
            'tolerance' => 0,
            'unit' => 'm/s',
            'accepted_units' => ['km/soat' => 1 / 3.6, 'km/h' => 1 / 3.6],
        ]);

        $this->assertTrue($answer->accepts(20, 'm/s'));
        $this->assertTrue($answer->accepts(72, 'km/soat'), '72 km/soat = 20 m/s');
        $this->assertFalse($answer->accepts(72, 'm/s'));
        $this->assertFalse($answer->accepts(20, 'sm/s'), 'unknown unit');

        $plain = new QuestionBankAnswer(['value' => 0.5, 'tolerance_type' => 'abs', 'tolerance' => 0]);
        $this->assertTrue($plain->accepts(1 / 2));
        $this->assertFalse($plain->accepts(0.5, 'm'));
    }

    public function test_question_text_normalization(): void
    {
        $this->assertSame(
            QuestionText::normalize("Jism  o‘rtacha tezligi 0,5 m/s.\n"),
            QuestionText::normalize("jism o'rtacha tezligi 0.5 m/s")
        );
        $this->assertNotSame(QuestionText::hash('2x+3=7'), QuestionText::hash('2x+3=8'));
    }
}
