<?php

namespace App\Listeners\Telegram;

use App\Models\Exam\ExamAttempt;
use App\Services\Telegram\ExamTelegramNotifier;
use Illuminate\Contracts\Queue\ShouldQueue;

/**
 * App\Events\Exam\ExamAttemptGraded (stage 5) -> result message to the
 * student and to approved guardians. Registered in AppServiceProvider only
 * when the event class exists; does nothing while the bot is not configured.
 */
class SendAttemptResultToTelegram implements ShouldQueue
{
    public function __construct(private readonly ExamTelegramNotifier $notifier) {}

    public function handle(object $event): void
    {
        $attempt = $event->attempt ?? null;
        if ($attempt instanceof ExamAttempt) {
            $this->notifier->attemptGraded($attempt);
        }
    }

    public function shouldQueue(object $event): bool
    {
        return trim((string) config('exam.telegram.bot_token')) !== '';
    }
}
