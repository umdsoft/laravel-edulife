<?php

namespace App\Listeners\Telegram;

use App\Models\Exam\ExamSession;
use App\Models\Exam\TelegramNotification;
use App\Services\Telegram\TelegramReminderService;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * App\Events\Exam\ExamSessionOpened (stage 5) -> "the exam started" reminder
 * to the students of the subject (same idempotent record as the scheduled
 * start reminder, so nobody gets it twice).
 */
class SendSessionStartToTelegram implements ShouldQueue
{
    public function __construct(private readonly TelegramReminderService $reminders) {}

    public function handle(object $event): void
    {
        $session = $event->session ?? null;
        if ($session instanceof ExamSession && $session->type?->value === 'weekly') {
            $this->reminders->send($session, TelegramNotification::KIND_REMINDER_START);
        }
    }

    public function shouldQueue(object $event): bool
    {
        return trim((string) config('exam.telegram.bot_token')) !== '';
    }
}
