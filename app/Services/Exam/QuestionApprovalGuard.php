<?php

namespace App\Services\Exam;

use App\Enums\Exam\ExamQuestionType;
use App\Enums\Exam\ToleranceType;
use App\Exceptions\Exam\QuestionNotApprovable;
use App\Models\Exam\Topic;
use App\Models\QuestionBank;

/**
 * Decides whether a question is complete enough to be APPROVED.
 *
 * The rule the whole module relies on: AI drafts never mark the correct
 * answer, the teacher does; a question without a teacher-set answer can
 * never be approved and therefore never reaches an exam.
 *
 * Reads options / answers from the database (not from loaded relations),
 * so it judges what is actually stored.
 */
class QuestionApprovalGuard
{
    /** @return array<string, string> violation code => Uzbek message (empty = approvable) */
    public function violations(QuestionBank $question): array
    {
        $v = [];

        if (trim(strip_tags((string) $question->question_text)) === '') {
            $v['stem_empty'] = 'Savol sharti bo\'sh.';
        }
        if ($question->subject_id === null) {
            $v['subject_missing'] = 'Fan tanlanmagan.';
        }
        if ($question->topic_id === null) {
            $v['topic_missing'] = 'Mavzu tanlanmagan.';
        } elseif ($question->subject_id !== null) {
            $topicSubject = Topic::query()->whereKey($question->topic_id)->value('subject_id');
            if ($topicSubject !== $question->subject_id) {
                $v['topic_subject_mismatch'] = 'Mavzu tanlangan fanga tegishli emas.';
            }
        }
        $level = $question->difficulty_level;
        if ($level === null || $level < 1 || $level > 5) {
            $v['difficulty_invalid'] = 'Qiyinlik darajasi 1 dan 5 gacha bo\'lishi kerak.';
        }

        $type = $question->examType();
        if ($type === null || $type === ExamQuestionType::Expression) {
            $v['type_not_supported'] = 'Bu savol turi imtihon uchun hali qo\'llab-quvvatlanmaydi.';

            return $v;
        }

        return $type->isChoice()
            ? [...$v, ...$this->choiceViolations($question, $type)]
            : [...$v, ...$this->numericViolations($question, $type)];
    }

    public function isApprovable(QuestionBank $question): bool
    {
        return $this->violations($question) === [];
    }

    /** @throws QuestionNotApprovable */
    public function assertApprovable(QuestionBank $question): void
    {
        $violations = $this->violations($question);
        if ($violations !== []) {
            throw new QuestionNotApprovable($violations);
        }
    }

    /** @return array<string, string> */
    private function choiceViolations(QuestionBank $question, ExamQuestionType $type): array
    {
        $v = [];
        $options = $question->answerOptions()->get();
        $count = $options->count();

        [$min, $max] = $type === ExamQuestionType::TrueFalse ? [2, 2] : [2, 5];
        if ($count < $min || $count > $max) {
            $v['options_count'] = $min === $max
                ? "Variantlar soni {$min} ta bo'lishi kerak."
                : "Variantlar soni {$min} tadan {$max} tagacha bo'lishi kerak.";
        }
        if ($options->contains(fn ($o) => trim((string) $o->text) === '' && empty($o->image))) {
            $v['option_empty'] = 'Bo\'sh variant bor.';
        }

        $correct = $options->filter(fn ($o) => $o->is_correct === true)->count();
        if ($type === ExamQuestionType::MultipleChoice) {
            if ($correct < 1) {
                $v['correct_missing'] = 'Kamida bitta to\'g\'ri variant belgilanishi kerak.';
            }
        } elseif ($correct !== 1) {
            $v['correct_count'] = $correct === 0
                ? 'To\'g\'ri variant belgilanmagan.'
                : 'Faqat bitta to\'g\'ri variant belgilanishi kerak.';
        }

        return $v;
    }

    /** @return array<string, string> */
    private function numericViolations(QuestionBank $question, ExamQuestionType $type): array
    {
        $v = [];
        $answers = $question->numericAnswers()->get();

        if ($answers->isEmpty() || $answers->contains(fn ($a) => $a->value === null)) {
            $v['answer_missing'] = 'To\'g\'ri javob (son) kiritilmagan.';
        }
        if ($answers->contains(fn ($a) => (float) $a->tolerance < 0
            || ! $a->tolerance_type instanceof ToleranceType)) {
            $v['tolerance_invalid'] = 'Ruxsat etilgan xatolik noto\'g\'ri.';
        }

        if ($type->requiresUnit()) {
            if ($answers->contains(fn ($a) => trim((string) $a->unit) === '')) {
                $v['unit_missing'] = 'O\'lchov birligi ko\'rsatilmagan.';
            }
            $badFactor = $answers->contains(function ($a) {
                foreach ((array) ($a->accepted_units ?? []) as $factor) {
                    if (! is_numeric($factor) || (float) $factor <= 0) {
                        return true;
                    }
                }

                return false;
            });
            if ($badFactor) {
                $v['unit_factor_invalid'] = 'Birlik koeffitsiyenti musbat son bo\'lishi kerak.';
            }
        }

        return $v;
    }
}
