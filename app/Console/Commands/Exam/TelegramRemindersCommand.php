<?php

namespace App\Console\Commands\Exam;

use App\Services\Telegram\TelegramClient;
use App\Services\Telegram\TelegramReminderService;
use Illuminate\Console\Command;

/**
 * Stage 6: Saturday exam reminders (evening before, 1 hour before, start).
 * Scheduled every five minutes in routes/console.php; idempotent.
 */
class TelegramRemindersCommand extends Command
{
    protected $signature = 'exam:telegram-reminders';

    protected $description = 'Send due Telegram reminders for weekly exam sessions';

    public function handle(TelegramReminderService $reminders, TelegramClient $client): int
    {
        if (! $client->isConfigured()) {
            $this->info('Telegram bot is not configured; nothing sent.');

            return self::SUCCESS;
        }

        $sent = 0;
        foreach ($reminders->due(now()) as $due) {
            $sent += $reminders->send($due['session'], $due['kind']);
        }
        $this->info("{$sent} reminder(s) sent.");

        return self::SUCCESS;
    }
}
