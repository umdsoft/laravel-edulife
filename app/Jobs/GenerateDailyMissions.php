<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\MissionService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateDailyMissions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        //
    }

    public function handle(MissionService $missionService): void
    {
        // Generate missions for all active students
        User::whereHas('studentProfile')
            ->where('is_active', true)
            ->chunk(100, function ($users) use ($missionService) {
                foreach ($users as $user) {
                    $missionService->generateDailyMissions($user);
                }
            });
    }
}
