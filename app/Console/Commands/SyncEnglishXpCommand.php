<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\English\UserEnglishProfile;

class SyncEnglishXpCommand extends Command
{
    protected $signature = 'english:sync-xp';
    protected $description = 'Sync English XP to Student Profile (one-time migration)';

    public function handle()
    {
        $profiles = UserEnglishProfile::with('user.studentProfile')
            ->where('total_xp', '>', 0)
            ->get();

        $count = 0;
        foreach ($profiles as $profile) {
            if ($profile->user && $profile->user->studentProfile) {
                $profile->user->studentProfile->increment('xp', $profile->total_xp);
                $this->info("Synced {$profile->total_xp} XP for user {$profile->user->id}");
                $count++;
            }
        }

        $this->info("Done! Synced XP for {$count} users.");
        return 0;
    }
}
