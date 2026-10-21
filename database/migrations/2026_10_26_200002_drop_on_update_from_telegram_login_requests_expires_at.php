<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * With explicit_defaults_for_timestamp=OFF (MariaDB / older MySQL) the first NOT NULL TIMESTAMP
 * column silently gets "ON UPDATE CURRENT_TIMESTAMP" (see 2026_10_21_000002): every status change
 * of a Telegram login request moved expires_at to "now", so codes expired on the first update.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true) || ! Schema::hasColumn('telegram_login_requests', 'expires_at')) {
            return;
        }
        $extra = DB::table('information_schema.columns')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', 'telegram_login_requests')
            ->where('column_name', 'expires_at')
            ->value('extra');
        if (is_string($extra) && str_contains(strtolower($extra), 'on update')) {
            DB::statement('ALTER TABLE `telegram_login_requests` MODIFY `expires_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
        }
    }

    public function down(): void
    {
        // Irreversible on purpose: the auto-update behaviour was a bug.
    }
};
