<?php

namespace App\Services\Exam;

use App\Enums\Exam\AttemptStatus;
use App\Enums\Exam\ExamSessionStatus;
use App\Enums\Exam\ExamSessionType;
use App\Models\Exam\ExamAnswer;
use App\Models\Exam\ExamAttempt;
use App\Models\Exam\ExamSession;
use App\Models\Exam\StudentWeeklyResult;
use App\Models\Exam\Topic;
use App\Models\QuestionBank;
use App\Services\Exam\Grading\AnswerChecker;

/**
 * Inertia payloads of the student exam pages (Take, Result). Answer keys
 * never leave the server while an attempt runs; on the result page they
 * appear only once review is allowed (placement: at once; weekly: after
 * the session closed).
 */
class ExamAttemptPresenter
{
    public function session(ExamSession $session): array
    {
        $session->loadMissing('subject');

        return [
            'id' => $session->id,
            'title' => $session->title,
            'type' => $session->type?->value,
            'status' => $session->status?->value,
            'starts_at' => $session->starts_at?->toIso8601String(),
            'ends_at' => $session->ends_at?->toIso8601String(),
            'duration_min' => $session->effectiveDurationMin(),
            'subject' => $session->subject ? ['code' => $session->subject->code, 'name' => $session->subject->name_uz] : null,
        ];
    }

    /** Questions of a running attempt, in variant order, options in the stored order, no keys. */
    public function questions(ExamAttempt $attempt): array
    {
        $attempt->loadMissing(['variant', 'answers.question' => fn ($q) => $q->withTrashed(), 'answers.question.answerOptions', 'answers.question.numericAnswers']);
        $orders = $attempt->variant?->option_orders ?? [];

        return $attempt->answers->sortBy('position')->values()->map(function (ExamAnswer $answer) use ($orders) {
            $q = $answer->question;
            $type = $q?->examType();

            return [
                'id' => $q?->id,
                'position' => $answer->position,
                'type' => $q?->question_type,
                'part' => $type?->part()->value,
                'text' => (string) $q?->question_text,
                'image' => $this->image($q),
                'units_note' => $q?->units_note,
                'max_points' => (float) $answer->max_points,
                'options' => $type?->isChoice() ? $this->options($q, $orders[$q->id] ?? null) : [],
                'unit_choices' => $type?->requiresUnit() ? AnswerChecker::unitChoices($q) : [],
            ];
        })->all();
    }

    /** Saved answers keyed by question id (what the Take page restores). */
    public function savedAnswers(ExamAttempt $attempt): array
    {
        $attempt->loadMissing('answers');

        return $attempt->answers->mapWithKeys(fn (ExamAnswer $a) => [$a->question_id => [
            'selected_option_id' => $a->selected_option_id,
            'selected_option_ids' => $a->selected_option_ids ?? [],
            'answer_text' => $a->answer_text,
            'unit' => $a->unit,
            'is_flagged' => (bool) $a->is_flagged,
        ]])->all();
    }

    public function summary(ExamAttempt $attempt): array
    {
        return [
            'id' => $attempt->id,
            'status' => $attempt->status?->value,
            'score' => (float) $attempt->score,
            'max_score' => (float) $attempt->max_score,
            'score_percent' => (float) $attempt->score_percent,
            'correct_count' => $attempt->correct_count,
            'wrong_count' => $attempt->wrong_count,
            'skipped_count' => $attempt->skipped_count,
            'started_at' => $attempt->started_at?->toIso8601String(),
            'submitted_at' => $attempt->submitted_at?->toIso8601String(),
            'time_spent_seconds' => $attempt->time_spent_seconds,
            'auto_submitted' => $attempt->submitted_at !== null && $attempt->expires_at !== null && $attempt->submitted_at->gte($attempt->expires_at),
        ];
    }

    public function reviewAvailable(ExamAttempt $attempt, ExamSession $session): bool
    {
        if ($attempt->status !== AttemptStatus::Graded) {
            return false;
        }
        if ($session->type === ExamSessionType::Placement) {
            return true;
        }

        return in_array($session->status, [ExamSessionStatus::Closed, ExamSessionStatus::Graded], true)
            || ($session->ends_at !== null && $session->ends_at->isPast());
    }

    public function weekly(ExamAttempt $attempt): ?array
    {
        $row = StudentWeeklyResult::query()->where('attempt_id', $attempt->id)->first();

        return $row ? [
            'week_start' => $row->week_start?->toDateString(),
            'delta_prev' => $row->delta_prev === null ? null : (float) $row->delta_prev,
            'rank' => $row->rank,
            'participants_count' => $row->participants_count,
            'percentile' => $row->percentile === null ? null : (float) $row->percentile,
        ] : null;
    }

    /** Points per topic (no answer keys; shown on every result page). */
    public function topics(ExamAttempt $attempt): array
    {
        $attempt->loadMissing('answers.question');
        $rows = [];
        foreach ($attempt->answers as $answer) {
            $topicId = $answer->question?->topic_id;
            if (! $topicId || (float) $answer->max_points <= 0) {
                continue;
            }
            $rows[$topicId] ??= ['topic_id' => $topicId, 'points' => 0.0, 'max' => 0.0, 'questions' => 0];
            $rows[$topicId]['points'] += (float) $answer->points;
            $rows[$topicId]['max'] += (float) $answer->max_points;
            $rows[$topicId]['questions']++;
        }
        $names = Topic::withTrashed()->whereIn('id', array_keys($rows))->pluck('name_uz', 'id');

        return collect($rows)->map(fn ($r) => $r + [
            'name' => $names[$r['topic_id']] ?? '—',
            'percent' => $r['max'] > 0 ? round($r['points'] / $r['max'] * 100) : 0,
        ])->sortBy('percent')->values()->all();
    }

    /** Per question review with the key and the solution (only when reviewAvailable()). */
    public function review(ExamAttempt $attempt): array
    {
        $attempt->loadMissing(['variant', 'answers.question' => fn ($q) => $q->withTrashed(), 'answers.question.answerOptions', 'answers.question.numericAnswers']);
        $orders = $attempt->variant?->option_orders ?? [];

        return $attempt->answers->sortBy('position')->values()->map(function (ExamAnswer $answer) use ($orders) {
            $q = $answer->question;
            $type = $q?->examType();
            $voided = (float) $answer->max_points <= 0;
            $selected = $type === \App\Enums\Exam\ExamQuestionType::MultipleChoice
                ? ($answer->selected_option_ids ?? [])
                : array_filter([$answer->selected_option_id]);
            $showSolution = $answer->is_correct !== true;

            return [
                'id' => $q?->id,
                'position' => $answer->position,
                'type' => $q?->question_type,
                'text' => (string) $q?->question_text,
                'image' => $this->image($q),
                'units_note' => $q?->units_note,
                'voided' => $voided,
                'is_correct' => $answer->is_correct,
                'unit_correct' => $answer->unit_correct,
                'points' => (float) $answer->points,
                'max_points' => (float) $answer->max_points,
                'options' => $type?->isChoice() ? array_map(fn ($o) => $o + [
                    'is_correct' => (bool) ($q->answerOptions->firstWhere('id', $o['id'])?->is_correct),
                    'selected' => in_array($o['id'], $selected, true),
                ], $this->options($q, $orders[$q->id] ?? null)) : [],
                'given' => $type?->usesNumericAnswer() ? (trim(($answer->answer_text ?? '').' '.($answer->unit ?? '')) ?: null) : null,
                'expected' => $type?->usesNumericAnswer() ? $q->numericAnswers->map(fn ($e) => [
                    'value' => $this->number($e->value),
                    'unit' => $e->unit,
                    'note' => $e->note,
                ])->values()->all() : [],
                'solution_text' => $showSolution ? $q?->solution_text : null,
                'solution_video_url' => $showSolution ? $q?->solution_video_url : null,
            ];
        })->all();
    }

    private function options(QuestionBank $q, ?array $order): array
    {
        $options = $q->answerOptions->keyBy('id');
        $ids = $order ? array_values(array_filter($order, fn ($id) => $options->has($id))) : $options->keys()->all();
        foreach ($options->keys() as $id) {
            if (! in_array($id, $ids, true)) {
                $ids[] = $id; // an option added after the variant was generated
            }
        }

        return array_map(fn ($id) => [
            'id' => $id,
            'content' => (string) $options[$id]->text,
            'image' => $options[$id]->image,
        ], $ids);
    }

    private function image(?QuestionBank $q): ?string
    {
        $media = $q?->question_media;

        return is_array($media) ? ($media['image_url'] ?? null) : null;
    }

    private function number($value): ?string
    {
        if ($value === null) {
            return null;
        }
        $s = rtrim(rtrim(number_format((float) $value, 10, '.', ''), '0'), '.');

        return str_replace('.', ',', $s);
    }
}
