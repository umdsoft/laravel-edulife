<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * The platform serves a single teacher, so the multi-teacher rating / level
 * system (daily score calculation, score history, level changes) was removed.
 *
 * Fresh installs no longer create these tables; this migration cleans up
 * databases that ran the old migrations. Only the score/level-change tables
 * are dropped. Financial tables (teacher_earnings, teacher_payouts,
 * teacher_bank_accounts) are intentionally kept for record retention, and the
 * leftover teacher_profiles columns (level, commission_rate, score, ...) are
 * left in place because they are harmless and no longer read by the code.
 */
return new class extends Migration
{
    private const TABLES = [
        'teacher_level_changes',
        'teacher_score_history',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            if (Schema::hasTable($table)) {
                Schema::drop($table);
            }
        }
    }

    public function down(): void
    {
        // Irreversible: the teacher rating system and its history were removed on purpose.
    }
};
