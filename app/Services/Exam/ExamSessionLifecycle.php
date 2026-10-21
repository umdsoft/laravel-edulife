<?php

namespace App\Services\Exam;

use App\Enums\Exam\AttemptStatus;
use App\Enums\Exam\ExamSessionStatus;
use App\Enums\Exam\ExamSessionType;
use App\Events\Exam\ExamSessionOpened;
use App\Jobs\Exam\GenerateExamVariantsJob;
use App\Models\Exam\ExamAttempt;
use App\Models\Exam\ExamSession;
use Carbon\CarbonInterface;

/**
 * Session status by schedule: draft -> scheduled -> open -> closed -> graded
 * (or cancelled). Driven by `exam:tick` every minute and, lazily, by sync()
 * whenever a student or the teacher opens a session page.
 *
 *  open   scheduled and starts_at reached: status open, ExamSessionOpened (once)
 *  close  ends_at reached: in-progress attempts are auto-submitted and graded,
 *         weekly results ranked, status graded, results_published_at set
 */
class ExamSessionLifecycle
{
    public function __construct(
        private ExamAttemptService $attempts,
        private ExamResultService $results,
        private ExamRefunds $refunds,
    ) {}

    public function sync(ExamSession $session, ?CarbonInterface $now = null): ExamSession
    {
        $now ??= now();

        if ($session->status === ExamSessionStatus::Scheduled
            && $session->starts_at !== null && $session->starts_at->lte($now)
            && ($session->ends_at === null || $session->ends_at->gt($now))) {
            $this->open($session);
        }

        if (in_array($session->status, [ExamSessionStatus::Scheduled, ExamSessionStatus::Open], true)
            && $session->ends_at !== null && $session->ends_at->lte($now)) {
            $this->close($session, $now);
        }

        return $session;
    }

    /** scheduled -> open, atomically (the event fires exactly once). */
    public function open(ExamSession $session): bool
    {
        $changed = ExamSession::query()->whereKey($session->id)
            ->where('status', ExamSessionStatus::Scheduled->value)
            ->update(['status' => ExamSessionStatus::Open->value, 'updated_at' => now()]);

        $session->refresh();
        if ($changed === 1) {
            ExamSessionOpened::dispatch($session);
        }

        return $changed === 1;
    }

    /** -> closed -> graded: auto-submit what is left, rank, publish. */
    public function close(ExamSession $session, ?CarbonInterface $now = null): void
    {
        $now ??= now();

        $changed = ExamSession::query()->whereKey($session->id)
            ->whereIn('status', [ExamSessionStatus::Scheduled->value, ExamSessionStatus::Open->value])
            ->update(['status' => ExamSessionStatus::Closed->value, 'updated_at' => now()]);
        $session->refresh();
        if ($changed !== 1) {
            return;
        }

        ExamAttempt::query()->where('session_id', $session->id)->inProgress()->lazyById(100)
            ->each(fn (ExamAttempt $attempt) => $this->attempts->submit($attempt, true, $now));

        if ($session->type === ExamSessionType::Weekly) {
            $this->results->rankSession($session);
        }

        $session->forceFill([
            'status' => ExamSessionStatus::Graded,
            'results_published_at' => $session->results_published_at ?? now(),
        ])->save();
    }

    /** Teacher cancels a session that has not been graded. */
    public function cancel(ExamSession $session): void
    {
        if (in_array($session->status, [ExamSessionStatus::Graded, ExamSessionStatus::Cancelled], true)) {
            return;
        }

        ExamAttempt::query()->where('session_id', $session->id)->inProgress()
            ->update(['status' => AttemptStatus::Invalidated->value, 'invalidated_reason' => 'session_cancelled']);

        $session->forceFill(['status' => ExamSessionStatus::Cancelled])->save();

        // paid exams: entries bought for this session are listed for the admin to refund by hand
        $this->refunds->markSessionCancelled($session);
    }

    /**
     * One scheduler pass: pre-generate variants, open / close by schedule,
     * auto-submit expired attempts.
     *
     * @return array{generating: int, opened: int, closed: int, auto_submitted: int}
     */
    public function tick(?CarbonInterface $now = null): array
    {
        $now ??= now();
        $stats = ['generating' => 0, 'opened' => 0, 'closed' => 0, 'auto_submitted' => 0];

        $ahead = $now->copy()->addHours((int) config('exam.engine.pregenerate_hours', 24));
        ExamSession::query()
            ->where('status', ExamSessionStatus::Scheduled->value)
            ->whereNull('variants_generated_at')
            ->whereNotNull('starts_at')
            ->where('starts_at', '<=', $ahead)
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', $now))
            ->pluck('id')
            ->each(function ($id) use (&$stats) {
                GenerateExamVariantsJob::dispatch($id);
                $stats['generating']++;
            });

        ExamSession::query()
            ->whereIn('status', [ExamSessionStatus::Scheduled->value, ExamSessionStatus::Open->value])
            ->whereNotNull('starts_at')
            ->where(fn ($q) => $q->where('starts_at', '<=', $now)->orWhere('ends_at', '<=', $now))
            ->get()
            ->each(function (ExamSession $session) use ($now, &$stats) {
                $before = $session->status;
                $this->sync($session, $now);
                if ($before === ExamSessionStatus::Scheduled && $session->status !== ExamSessionStatus::Scheduled) {
                    $stats['opened']++;
                }
                if (in_array($session->status, [ExamSessionStatus::Closed, ExamSessionStatus::Graded], true)) {
                    $stats['closed']++;
                }
            });

        $stats['auto_submitted'] = $this->attempts->autoSubmitExpired($now);

        return $stats;
    }
}
