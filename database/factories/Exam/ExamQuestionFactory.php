<?php

namespace Database\Factories\Exam;

use App\Enums\Exam\ReviewStatus;
use App\Models\Exam\QuestionBankAnswer;
use App\Models\Exam\QuestionBankOption;
use App\Models\Exam\Topic;
use App\Models\QuestionBank;
use App\Models\User;
use App\Services\Exam\QuestionReviewService;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Exam flavoured question_bank rows. Kept in the Exam namespace (instead of
 * Database\Factories\QuestionBankFactory) so the olympiad module keeps its
 * own factory name free. Use ExamQuestionFactory::new().
 *
 * States: singleChoice (default), numeric, numericWithUnit, inReview,
 * approved (creates a complete answer and approves through the review
 * service, so the approval guard runs).
 *
 * @extends Factory<QuestionBank>
 */
class ExamQuestionFactory extends Factory
{
    protected $model = QuestionBank::class;

    public function definition(): array
    {
        return [
            'topic_id' => Topic::factory(),
            'subject_id' => fn (array $attrs) => Topic::find($attrs['topic_id'])?->subject_id,
            'question_type' => 'single_choice',
            'question_text' => '$'.fake()->numberBetween(2, 50).'x = '.fake()->numberBetween(2, 500).'$ bo\'lsa, $x$ ni toping.',
            'difficulty' => 'medium',
            'difficulty_level' => 3,
            'exam_formats' => ['milliy_sertifikat'],
            'origin' => 'manual',
            'review_status' => ReviewStatus::Draft,
            'content_format' => 'latex_markdown',
            'base_points' => 1,
            'is_demo' => false,
            'status' => QuestionBank::STATUS_DRAFT,
        ];
    }

    public function forTopic(Topic $topic): static
    {
        return $this->state(['topic_id' => $topic->id, 'subject_id' => $topic->subject_id]);
    }

    public function numeric(): static
    {
        return $this->state(['question_type' => 'numeric']);
    }

    public function numericWithUnit(): static
    {
        return $this->state(['question_type' => 'numeric_with_unit', 'units_note' => 'g = 10 m/s²']);
    }

    public function aiDraft(): static
    {
        return $this->state(['origin' => 'ai_draft', 'review_status' => ReviewStatus::InReview]);
    }

    public function inReview(): static
    {
        return $this->state(['review_status' => ReviewStatus::InReview]);
    }

    /** Four options A-D, none marked (what an AI draft looks like). */
    public function withUnmarkedOptions(int $count = 4): static
    {
        return $this->afterCreating(function (QuestionBank $q) use ($count) {
            foreach (array_slice(QuestionBankOption::LABELS, 0, $count) as $i => $label) {
                QuestionBankOption::create([
                    'question_id' => $q->id,
                    'label' => $label,
                    'text' => '$'.($i + 1).'$',
                    'is_correct' => null,
                    'sort_order' => $i,
                ]);
            }
        });
    }

    /**
     * Complete the question (options with exactly one correct, or a numeric
     * answer) and approve it via QuestionReviewService.
     */
    public function approved(?User $reviewer = null): static
    {
        return $this->inReview()->afterCreating(function (QuestionBank $q) use ($reviewer) {
            $type = $q->examType();
            if ($type?->isChoice()) {
                foreach (['A', 'B', 'C', 'D'] as $i => $label) {
                    QuestionBankOption::create([
                        'question_id' => $q->id,
                        'label' => $label,
                        'text' => '$'.($i + 1).'$',
                        'is_correct' => $i === 0,
                        'sort_order' => $i,
                    ]);
                }
            } elseif ($type?->usesNumericAnswer()) {
                QuestionBankAnswer::create([
                    'question_id' => $q->id,
                    'value' => 20,
                    'tolerance_type' => 'abs',
                    'tolerance' => 0,
                    'unit' => $type->requiresUnit() ? 'm/s' : null,
                    'accepted_units' => $type->requiresUnit() ? ['km/soat' => 1 / 3.6, 'km/h' => 1 / 3.6] : null,
                ]);
            }

            app(QuestionReviewService::class)->approve($q, $reviewer ?? User::factory()->create(['role' => 'teacher']));
        });
    }
}
