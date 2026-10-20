<?php

namespace App\Services\Exam;

use App\Enums\Exam\ExamQuestionType;
use App\Enums\Exam\QuestionOrigin;
use App\Enums\Exam\ReviewStatus;
use App\Exceptions\Exam\QuestionNotApprovable;
use App\Models\Exam\QuestionBankAnswer;
use App\Models\Exam\QuestionBankOption;
use App\Models\Exam\Subject;
use App\Models\QuestionBank;
use App\Support\Difficulty;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Teacher authoring of exam questions (question bank form, review queue
 * inline edit, import). Writes the question row, its options A..E or its
 * numeric answer, and keeps the review state honest:
 *
 *  - an APPROVED question whose content changes goes back to IN_REVIEW
 *    (unless the teacher re-approves it in the same step), so an edited
 *    question never stays in exams without passing QuestionApprovalGuard again;
 *  - approval always goes through QuestionReviewService (guard + legacy sync).
 *
 * Input shape (already validated by the caller):
 *   subject_id, topic_id, question_type, question_text, difficulty_level,
 *   exam_formats[], source_ref, solution_text, solution_video_url, units_note,
 *   constants {key: {value, unit}},
 *   options: [{text, is_correct: bool|null}] (choice types; labels by position),
 *   answer: {value, tolerance_type, tolerance, unit, accepted_units {unit: factor}, note} (numeric types)
 */
class QuestionEditorService
{
    public const INTENT_DRAFT = 'draft';
    public const INTENT_REVIEW = 'review';
    public const INTENT_APPROVE = 'approve';

    /** Fields whose change makes an approved question need a new review. */
    private const CONTENT_FIELDS = ['question_text', 'question_type', 'subject_id', 'topic_id', 'difficulty_level'];

    public function __construct(private readonly QuestionReviewService $review) {}

    /** @throws QuestionNotApprovable when $intent is approve and the question is incomplete (it stays saved, in review) */
    public function create(array $data, User $author, string $intent = self::INTENT_REVIEW, QuestionOrigin $origin = QuestionOrigin::Manual): QuestionBank
    {
        $question = DB::transaction(function () use ($data, $author, $intent, $origin) {
            $question = new QuestionBank;
            $question->fill($this->columns($data));
            $question->fill([
                'origin' => $origin,
                'review_status' => $intent === self::INTENT_DRAFT ? ReviewStatus::Draft : ReviewStatus::InReview,
                'status' => QuestionBank::STATUS_DRAFT,
                'is_verified' => false,
                'base_points' => 1,
                'created_by' => $author->id,
                'content_format' => 'latex_markdown',
            ]);
            $question->save();
            $this->syncAnswers($question, $data, false);

            return $question;
        });

        if ($intent === self::INTENT_APPROVE) {
            $this->approve($question, $author);
        }

        return $question->refresh();
    }

    /**
     * Update from the bank form ($preserveMarks = false: the form sends the
     * correct marks) or from the review queue inline edit ($preserveMarks =
     * true: only texts are sent, marks stay by label).
     *
     * @param  string|null  $intent  null = keep the current state (approved content edits still go back to review)
     *
     * @throws QuestionNotApprovable
     */
    public function update(QuestionBank $question, array $data, User $editor, ?string $intent = null, bool $preserveMarks = false): QuestionBank
    {
        if ($question->review_status === ReviewStatus::Retired) {
            throw new \DomainException('Muomaladan chiqarilgan savolni tahrirlab bo\'lmaydi. Uning nusxasini yarating.');
        }

        DB::transaction(function () use ($question, $data, $intent, $preserveMarks) {
            $before = $this->answerSnapshot($question);
            $question->fill($this->columns($data, $question));
            $contentDirty = $question->isDirty(self::CONTENT_FIELDS);
            $question->save();
            $this->syncAnswers($question, $data, $preserveMarks);
            $answersChanged = $before !== $this->answerSnapshot($question);

            $status = $question->review_status;
            $target = match ($intent) {
                self::INTENT_DRAFT => ReviewStatus::Draft,
                self::INTENT_REVIEW, self::INTENT_APPROVE => ReviewStatus::InReview,
                default => $status,
            };
            if ($status === ReviewStatus::Approved && ($contentDirty || $answersChanged || $intent !== null)) {
                // leaves exams until approved again
                $target = $target === ReviewStatus::Draft ? ReviewStatus::Draft : ReviewStatus::InReview;
            }

            if ($target !== $status) {
                if ($status === ReviewStatus::Approved && $target === ReviewStatus::Draft) {
                    $this->moveTo($question, ReviewStatus::InReview); // approved -> draft is not a direct transition
                }
                $this->moveTo($question, $target);
            }
        });

        if ($intent === self::INTENT_APPROVE) {
            $this->approve($question, $editor);
        }

        return $question->refresh();
    }

    /**
     * Approve through the review service; draft / rejected questions pass
     * through in_review first (the only path the state machine allows).
     *
     * @throws QuestionNotApprovable
     */
    public function approve(QuestionBank $question, User $reviewer, ?string $correctOptionId = null): QuestionBank
    {
        if (in_array($question->review_status, [ReviewStatus::Draft, ReviewStatus::Rejected], true)) {
            $this->moveTo($question, ReviewStatus::InReview);
        }

        return $this->review->approve($question, $reviewer, $correctOptionId);
    }

    /**
     * Teacher's one-click mark in the review queue.
     * single_choice / true_false: $optionId becomes the only correct option.
     * multiple_choice: $optionId toggles.
     */
    public function markOption(QuestionBank $question, string $optionId): void
    {
        $type = $question->examType();
        $option = $question->answerOptions()->whereKey($optionId)->firstOrFail();

        DB::transaction(function () use ($question, $type, $option) {
            if ($type === ExamQuestionType::MultipleChoice) {
                $option->update(['is_correct' => $option->is_correct !== true]);
            } else {
                $question->answerOptions()->update(['is_correct' => false]);
                $option->update(['is_correct' => true]);
            }
            if ($question->review_status === ReviewStatus::Approved) {
                $this->moveTo($question, ReviewStatus::InReview);
            }
        });
    }

    /** Set (or replace) the expected numeric answer from the review queue. */
    public function setNumericAnswer(QuestionBank $question, array $answer): void
    {
        DB::transaction(function () use ($question, $answer) {
            $before = $this->answerSnapshot($question);
            $this->syncNumericAnswer($question, $answer);
            if ($question->review_status === ReviewStatus::Approved && $before !== $this->answerSnapshot($question)) {
                $this->moveTo($question, ReviewStatus::InReview);
            }
        });
    }

    /** Copy of a question (e.g. to fix a retired one), unmarked answers kept as they are, state draft. */
    public function duplicate(QuestionBank $question, User $author): QuestionBank
    {
        return DB::transaction(function () use ($question, $author) {
            $copy = $question->replicate(['review_status', 'reviewed_by', 'reviewed_at', 'reject_reason',
                'error_reports_count', 'times_used', 'times_answered', 'times_correct', 'is_demo', 'normalized_hash']);
            $copy->fill([
                'review_status' => ReviewStatus::Draft,
                'status' => QuestionBank::STATUS_DRAFT,
                'is_verified' => false,
                'origin' => QuestionOrigin::Manual,
                'created_by' => $author->id,
            ]);
            $copy->save();
            foreach ($question->answerOptions()->get() as $o) {
                $copy->answerOptions()->create($o->only(['label', 'text', 'image', 'is_correct', 'sort_order']));
            }
            foreach ($question->numericAnswers()->get() as $a) {
                $copy->numericAnswers()->create($a->only(['value', 'tolerance_type', 'tolerance', 'unit', 'accepted_units', 'note', 'sort_order']));
            }

            return $copy;
        });
    }

    // ==================== internals ====================

    private function moveTo(QuestionBank $question, ReviewStatus $to): void
    {
        $attributes = ['review_status' => $to];
        if ($to !== ReviewStatus::Approved) {
            // olympiad-era columns: an unapproved question is not "active / verified"
            $attributes += ['status' => QuestionBank::STATUS_DRAFT, 'is_verified' => false];
        }
        if ($to === ReviewStatus::InReview || $to === ReviewStatus::Draft) {
            $attributes['reject_reason'] = null;
        }
        $question->fill($attributes)->save();
    }

    /** question_bank columns from the input (+ the legacy columns olympiads read). */
    private function columns(array $data, ?QuestionBank $existing = null): array
    {
        $map = [
            'subject_id', 'topic_id', 'question_type', 'question_text', 'difficulty_level', 'exam_formats',
            'source_ref', 'solution_text', 'solution_video_url', 'units_note', 'constants',
        ];
        $cols = array_intersect_key($data, array_flip($map));

        foreach (['source_ref', 'solution_text', 'solution_video_url', 'units_note'] as $nullable) {
            if (array_key_exists($nullable, $cols)) {
                $cols[$nullable] = trim((string) $cols[$nullable]) === '' ? null : trim((string) $cols[$nullable]);
            }
        }
        if (array_key_exists('question_text', $cols)) {
            $cols['question_text'] = trim((string) $cols['question_text']);
        }
        if (array_key_exists('exam_formats', $cols)) {
            $cols['exam_formats'] = array_values(array_unique((array) $cols['exam_formats'])) ?: null;
        }
        if (array_key_exists('constants', $cols)) {
            $cols['constants'] = $cols['constants'] ?: null;
        }
        if (array_key_exists('difficulty_level', $cols)) {
            $cols['difficulty'] = Difficulty::legacyBankValue($cols['difficulty_level'] === null ? null : (int) $cols['difficulty_level']);
        }
        if (array_key_exists('solution_text', $cols)) {
            $cols['explanation'] = $cols['solution_text'];
        }
        $subjectId = $cols['subject_id'] ?? $existing?->subject_id;
        if (array_key_exists('subject_id', $cols) && $subjectId) {
            $cols['direction_id'] = Subject::query()->whereKey($subjectId)->value('direction_id');
        }

        return $cols;
    }

    private function syncAnswers(QuestionBank $question, array $data, bool $preserveMarks): void
    {
        $type = $question->examType();
        if ($type?->isChoice()) {
            $question->numericAnswers()->delete();
            if (array_key_exists('options', $data)) {
                $this->syncOptions($question, (array) $data['options'], $preserveMarks);
            }
        } elseif ($type?->usesNumericAnswer()) {
            $question->answerOptions()->delete();
            if (array_key_exists('answer', $data)) {
                $this->syncNumericAnswer($question, (array) $data['answer']);
            }
        }
    }

    /** Options A..E by position. Marks: any true -> the rest false; none -> all NULL (unmarked). */
    private function syncOptions(QuestionBank $question, array $options, bool $preserveMarks): void
    {
        $options = array_values($options);
        $existing = $question->answerOptions()->get()->keyBy('label');
        $anyMarked = collect($options)->contains(fn ($o) => ($o['is_correct'] ?? null) === true);

        $keep = [];
        foreach (array_slice($options, 0, count(QuestionBankOption::LABELS)) as $i => $o) {
            $label = QuestionBankOption::LABELS[$i];
            $keep[] = $label;
            $row = $existing[$label] ?? new QuestionBankOption(['question_id' => $question->id, 'label' => $label]);
            $row->text = trim((string) ($o['text'] ?? ''));
            $row->sort_order = $i;
            if (! $preserveMarks) {
                $row->is_correct = $anyMarked ? (($o['is_correct'] ?? null) === true) : null;
            } elseif (! $row->exists) {
                $row->is_correct = null;
            }
            if (array_key_exists('image', $o)) {
                $row->image = $o['image'] ?: null;
            }
            $row->save();
        }

        $question->answerOptions()->whereNotIn('label', $keep)->delete();
    }

    private function syncNumericAnswer(QuestionBank $question, array $answer): void
    {
        $type = $question->examType();
        $units = [];
        foreach ((array) ($answer['accepted_units'] ?? []) as $unit => $factor) {
            if (is_array($factor)) { // [{unit, factor}] rows from the form
                [$unit, $factor] = [$factor['unit'] ?? '', $factor['factor'] ?? null];
            }
            $unit = trim((string) $unit);
            if ($unit !== '' && is_numeric($factor)) {
                $units[$unit] = (float) $factor;
            }
        }
        $value = $answer['value'] ?? null;

        $row = $question->numericAnswers()->first() ?? new QuestionBankAnswer(['question_id' => $question->id]);
        $row->fill([
            'value' => $value === null || $value === '' ? null : $value,
            'tolerance_type' => $answer['tolerance_type'] ?? 'abs',
            'tolerance' => $answer['tolerance'] ?? 0,
            'unit' => $type?->requiresUnit() ? (trim((string) ($answer['unit'] ?? '')) ?: null) : null,
            'accepted_units' => $type?->requiresUnit() && $units !== [] ? $units : null,
            'note' => trim((string) ($answer['note'] ?? '')) ?: null,
            'sort_order' => 0,
        ]);
        $row->save();
        $question->numericAnswers()->whereKeyNot($row->id)->delete();
    }

    /** Comparable picture of options + numeric answers (change detection). */
    private function answerSnapshot(QuestionBank $question): array
    {
        if (! $question->exists) {
            return [];
        }

        return [
            $question->answerOptions()->get()->map(fn ($o) => [$o->label, $o->text, $o->is_correct])->all(),
            $question->numericAnswers()->get()->map(fn ($a) => [
                $a->value === null ? null : (float) $a->value, $a->tolerance_type?->value, (float) $a->tolerance,
                $a->unit, $a->accepted_units,
            ])->all(),
        ];
    }
}
