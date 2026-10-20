<?php

namespace App\Jobs\Exam;

use App\Events\Exam\WeeklyReportReady;
use App\Models\Exam\GuardianReport;
use App\Models\Exam\GuardianStudent;
use App\Notifications\Exam\WeeklyGuardianReportNotification;
use App\Services\Exam\ReportBuilder;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

/**
 * Sunday report (spec section 9): for every approved, consented guardian link
 * that receives reports, build the child's week (score, delta, topic map,
 * 3 recommended topics), store it once per week (exam_guardian_reports),
 * create the in-app notification and fire WeeklyReportReady (Telegram, stage 6).
 *
 * Scheduled in routes/console.php (Sunday 09:00 Asia/Tashkent) through
 * `php artisan exam:weekly-reports`. Re-running is safe: a week already sent
 * to a link is skipped.
 */
class SendWeeklyGuardianReportsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $timeout = 600;

    public function __construct(public ?string $weekStart = null) {}

    public function handle(ReportBuilder $builder): int
    {
        $week = ($this->weekStart ? Carbon::parse($this->weekStart) : now('Asia/Tashkent'))
            ->startOfWeek(CarbonInterface::MONDAY)->startOfDay();
        $sent = 0;

        GuardianStudent::query()
            ->verified()
            ->whereNotNull('consent_at')
            ->where('receives_reports', true)
            ->whereHas('guardian', fn ($q) => $q->where('role', 'guardian')->where('status', '!=', 'blocked'))
            ->whereHas('student')
            ->with(['guardian', 'student'])
            ->chunkById(100, function ($links) use ($builder, $week, &$sent) {
                foreach ($links as $link) {
                    if ($this->deliver($link, $builder, $week)) {
                        $sent++;
                    }
                }
            });

        Log::info('exam.weekly_reports.sent', ['week' => $week->toDateString(), 'count' => $sent]);

        return $sent;
    }

    private function deliver(GuardianStudent $link, ReportBuilder $builder, Carbon $week): bool
    {
        $exists = GuardianReport::query()
            ->where('guardian_student_id', $link->id)
            ->whereDate('week_start', $week->toDateString())
            ->exists();
        if ($exists) {
            return false;
        }

        $payload = $builder->weeklyReport($link->student, $week);

        try {
            $report = GuardianReport::create([
                'guardian_student_id' => $link->id,
                'guardian_id' => $link->guardian_id,
                'student_id' => $link->student_id,
                'week_start' => $week->toDateString(),
                'payload' => $payload,
                'sent_at' => now(),
            ]);
        } catch (UniqueConstraintViolationException) {
            return false; // a parallel run sent it
        }

        $link->guardian->notify(new WeeklyGuardianReportNotification($report));

        WeeklyReportReady::dispatch($link, $payload + [
            'report_id' => $report->id,
            'url' => Route::has('guardian.reports.show') ? route('guardian.reports.show', $report) : null,
        ]);

        return true;
    }
}
