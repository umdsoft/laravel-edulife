<?php

namespace App\Services\Exam;

use App\Enums\Exam\ExamQuestionType;
use App\Enums\Exam\ReviewStatus;
use App\Models\Exam\QuestionBankOption;
use App\Models\QuestionBank;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Teacher review workflow on question_bank (the data layer of the stage 3
 * review queue). State rules live in ReviewStatus and the ExamQuestion
 * saving hook; this service performs the multi-row changes atomically.
 */
class QuestionReviewService
{
    public function submitForReview(QuestionBank $question): QuestionBank
    {
        $question->review_status = ReviewStatus::InReview;
        $question->save();

        return $question;
    }

    /**
     * Approve a question. For single_choice / true_false the teacher's pick
     * may be passed as $correctOptionId; the remaining unmarked options are
     * stored as not correct. Throws QuestionNotApprovable when incomplete.
     * $reviewer is NULL only for system-seeded demo questions.
     */
    public function approve(QuestionBank $question, ?User $reviewer, ?string $correctOptionId = null): QuestionBank
    {
        return DB::transaction(function () use ($question, $reviewer, $correctOptionId) {
            $type = $question->examType();

            if ($type?->isChoice()) {
                if ($correctOptionId !== null) {
                    $this->markCorrectOption($question, $correctOptionId);
                }
                // Teacher decided: options left unmarked are wrong.
                $question->answerOptions()->whereNull('is_correct')->update(['is_correct' => false]);
            }

            $question->fill([
                'review_status' => ReviewStatus::Approved,
                'reviewed_by' => $reviewer?->id,
                'reviewed_at' => now(),
                'reject_reason' => null,
            ]);
            $this->syncLegacyAnswerColumns($question, $type);
            $question->save(); // runs QuestionApprovalGuard

            return $question;
        });
    }

    public function reject(QuestionBank $question, User $reviewer, string $reason): QuestionBank
    {
        if (trim($reason) === '') {
            throw new \InvalidArgumentException('A reject reason is required.');
        }

        $question->fill([
            'review_status' => ReviewStatus::Rejected,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'reject_reason' => $reason,
        ])->save();

        return $question;
    }

    /** Take an approved question out of circulation (e.g. an error was found). */
    public function retire(QuestionBank $question, User $reviewer, ?string $reason = null): QuestionBank
    {
        $question->fill([
            'review_status' => ReviewStatus::Retired,
            'reviewed_by' => $reviewer->id,
            'reviewed_at' => now(),
            'reject_reason' => $reason,
            'status' => QuestionBank::STATUS_ARCHIVED,
        ])->save();

        return $question;
    }

    private function markCorrectOption(QuestionBank $question, string $optionId): void
    {
        $exists = $question->answerOptions()->whereKey($optionId)->exists();
        if (! $exists) {
            throw new \InvalidArgumentException('The option does not belong to this question.');
        }
        $question->answerOptions()->update(['is_correct' => false]);
        QuestionBankOption::query()->whereKey($optionId)->update(['is_correct' => true]);
    }

    /**
     * Keep the olympiad-era JSON columns (options / correct_answer) in sync
     * so QuestionBank::checkAnswer() and olympiad code keep working.
     */
    private function syncLegacyAnswerColumns(QuestionBank $question, ?ExamQuestionType $type): void
    {
        if ($type?->isChoice()) {
            $options = $question->answerOptions()->get();
            $question->options = $options->map(fn ($o) => [
                'id' => strtolower($o->label),
                'text' => $o->text,
                'is_correct' => (bool) $o->is_correct,
            ])->values()->all();
            $correct = $options->where('is_correct', true)->map(fn ($o) => strtolower($o->label))->values()->all();
            $question->correct_answer = $type === ExamQuestionType::MultipleChoice ? $correct : ($correct[0] ?? null);
        } elseif ($type?->usesNumericAnswer()) {
            $question->correct_answer = $question->numericAnswers()->get()->map(fn ($a) => [
                'value' => $a->value,
                'unit' => $a->unit,
                'tolerance_type' => $a->tolerance_type?->value,
                'tolerance' => $a->tolerance,
            ])->values()->all();
        }

        $question->status = QuestionBank::STATUS_ACTIVE;
        $question->is_verified = true;
    }
}
