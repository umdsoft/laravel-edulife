<?php

namespace App\Listeners\Telegram;

use App\Models\Exam\GuardianStudent;
use App\Services\Telegram\ExamTelegramNotifier;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * App\Events\Exam\WeeklyReportReady (stage 7) -> the Sunday report to the
 * guardian's Telegram (the in-app copy is delivered by stage 7 itself).
 */
class SendWeeklyReportToTelegram implements ShouldQueue
{
    public function __construct(private readonly ExamTelegramNotifier $notifier) {}

    public function handle(object $event): void
    {
        $link = $event->link ?? null;
        $report = $event->report ?? null;
        if ($link instanceof GuardianStudent && is_array($report)) {
            $this->notifier->weeklyReport($link, $report);
        }
    }

    public function shouldQueue(object $event): bool
    {
        return trim((string) config('exam.telegram.bot_token')) !== '';
    }
}
