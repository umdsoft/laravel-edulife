<?php

namespace App\Jobs\English;

use App\Models\User;
use App\Services\English\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendStreakReminderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public User $user
    ) {
    }

    public function handle(NotificationService $notificationService): void
    {
        $notificationService->sendDailyReminder($this->user);
    }
}
