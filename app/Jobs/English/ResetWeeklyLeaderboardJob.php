<?php

namespace App\Jobs\English;

use App\Services\English\LeaderboardService;
use App\Services\English\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ResetWeeklyLeaderboardJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(LeaderboardService $leaderboardService): void
    {
        $leaderboardService->resetDailyLeaderboards();
    }
}
