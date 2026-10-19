<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The battle title is derived from ELO with one scale (StudentProfile::
 * calculateRank: start 1000 = bronze). Profiles still carried the column
 * default "bronze" or a title from the old scale (1000 = silver). Recompute
 * every profile's title from its ELO (idempotent).
 */
return new class extends Migration
{
    public function up(): void
    {
        $scale = [
            ['min' => 2400, 'rank' => 'master'],
            ['min' => 2000, 'rank' => 'diamond'],
            ['min' => 1600, 'rank' => 'platinum'],
            ['min' => 1300, 'rank' => 'gold'],
            ['min' => 1100, 'rank' => 'silver'],
        ];

        $upper = null;
        foreach ($scale as $step) {
            DB::table('student_profiles')
                ->where('elo_rating', '>=', $step['min'])
                ->when($upper !== null, fn ($q) => $q->where('elo_rating', '<', $upper))
                ->update(['rank' => $step['rank']]);
            $upper = $step['min'];
        }

        DB::table('student_profiles')->where('elo_rating', '<', 1100)->update(['rank' => 'bronze']);
    }

    public function down(): void
    {
        // Data correction only.
    }
};
