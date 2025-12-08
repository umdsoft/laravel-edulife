<?php

namespace App\Jobs\English;

use App\Models\User;
use App\Services\English\AchievementService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessAchievementsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public string $trigger,
        public array $context = []
    ) {
    }

    public function handle(AchievementService $achievementService): void
    {
        $achievementService->checkAchievements($this->user, $this->trigger, $this->context);
    }
}
