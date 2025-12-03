<?php

namespace App\Jobs;

use App\Models\Battle;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessBattleMatchmaking implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct()
    {
        //
    }

    public function handle(): void
    {
        // Find expired battles
        Battle::where('status', 'waiting')
            ->where('expires_at', '<', now())
            ->update(['status' => 'expired']);
    }
}
