<?php

namespace App\Services\Exam;

use App\Enums\Exam\AttemptStatus;
use App\Enums\Exam\ExamSessionStatus;
use App\Enums\Exam\ExamSessionType;
use App\Models\Exam\ExamAttempt;
use App\Models\Exam\ExamSession;
use App\Models\Exam\StudentWeeklyResult;
use Carbon\CarbonInterface;

/**
 * exam_student_weekly_results: one row per student per WEEKLY session.
 *
 *  week_start   Monday of the session's week (Asia/Tashkent)
 *  delta_prev   score_percent − the previous weekly result of the same subject (percent points)
 *  rank         1 = best score_percent in the session, ties share a rank (1, 2, 2, 4)
 *  percentile   share of participants with a strictly lower percent (0..100)
 *
 * Ranks are computed when the session closes (and again on a regrade);
 * while the session is open they stay NULL.
 */
class ExamResultService
{
    public function record(ExamAttempt $attempt): ?StudentWeeklyResult
    {
        $attempt->loadMissing('session');
        $session = $attempt->session;
        if (! $session || $session->type !== ExamSessionType::Weekly || $attempt->status !== AttemptStatus::Graded) {
            return null;
        }

        $result = StudentWeeklyResult::query()->firstOrNew(['user_id' => $attempt->user_id, 'session_id' => $session->id]);
        $result->fill([
            'attempt_id' => $attempt->id,
            'subject_id' => $session->subject_id,
            'week_start' => $this->weekStart($session, $attempt),
            'score' => $attempt->score,
            'max_score' => $attempt->max_score,
            'score_percent' => $attempt->score_percent,
        ])->save();

        $this->recomputeDeltas($attempt->user_id, $session->subject_id);

        if (in_array($session->status, [ExamSessionStatus::Closed, ExamSessionStatus::Graded], true)) {
            $this->rankSession($session);
        }

        return $result->fresh();
    }

    public function weekStart(ExamSession $session, ?ExamAttempt $attempt = null): string
    {
        /** @var CarbonInterface $at */
        $at = $session->starts_at ?? $attempt?->submitted_at ?? now();

        return $at->copy()->startOfWeek(CarbonInterface::MONDAY)->toDateString();
    }

    /** delta_prev along the student's history of a subject. */
    public function recomputeDeltas(string $userId, string $subjectId): void
    {
        $rows = StudentWeeklyResult::query()
            ->where('user_id', $userId)
            ->where('subject_id', $subjectId)
            ->with(['session' => fn ($q) => $q->withTrashed()->select('id', 'starts_at')])
            ->get()
            ->sortBy(fn (StudentWeeklyResult $r) => [$r->week_start?->toDateString(), $r->session?->starts_at?->timestamp ?? 0, $r->created_at?->timestamp ?? 0])
            ->values();

        $previous = null;
        foreach ($rows as $row) {
            $delta = $previous === null ? null : round((float) $row->score_percent - (float) $previous->score_percent, 2);
            $current = $row->delta_prev === null ? null : round((float) $row->delta_prev, 2);
            if ($current !== $delta) {
                $row->forceFill(['delta_prev' => $delta])->save();
            }
            $previous = $row;
        }
    }

    /** Competition ranking of a session by score_percent (then score). */
    public function rankSession(ExamSession $session): void
    {
        $rows = StudentWeeklyResult::query()
            ->where('session_id', $session->id)
            ->orderByDesc('score_percent')
            ->orderByDesc('score')
            ->get();

        $n = $rows->count();
        $rank = 0;
        $prevKey = null;

        foreach ($rows as $i => $row) {
            $key = (string) $row->score_percent;
            if ($key !== $prevKey) {
                $rank = $i + 1;
                $prevKey = $key;
            }
            $lower = $rows->filter(fn ($r) => (float) $r->score_percent < (float) $row->score_percent)->count();
            $row->forceFill([
                'rank' => $rank,
                'participants_count' => $n,
                'percentile' => $n > 0 ? round($lower / $n * 100, 2) : null,
            ])->save();
        }
    }
}
