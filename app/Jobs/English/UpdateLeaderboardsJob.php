<?php

namespace App\Jobs\English;

use App\Models\User;
use App\Services\English\LeaderboardService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UpdateLeaderboardsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $boardType,
        public int $scoreChange
    ) {
    }

    public function handle(LeaderboardService $leaderboardService): void
    {
        $leaderboardService->updateScore($this->user, $this->boardType, $this->scoreChange);
    }
}
