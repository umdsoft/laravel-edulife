<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * With explicit_defaults_for_timestamp=OFF (MariaDB / older MySQL default) the
 * first NOT NULL TIMESTAMP column of a table silently gets
 * "DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP". Every later save
 * then overwrote it with the server's local time:
 *   - lab_attempts.started_at → negative time spent, completing a lab 500'd
 *   - certificates.issued_at, tournament_participants.registered_at changed on
 *     every update, otp_codes.expires_at was extended on every update
 *
 * Re-declare those columns with a plain default and no ON UPDATE clause.
 * SQLite and correctly configured MySQL never had the clause.
 */
return new class extends Migration
{
    /** table => column */
    private const COLUMNS = [
        'lab_attempts' => 'started_at',
        'certificates' => 'issued_at',
        'otp_codes' => 'expires_at',
        'tournament_participants' => 'registered_at',
        'video_watch_logs' => 'started_at',
    ];

    public function up(): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        foreach (self::COLUMNS as $table => $column) {
            if (! Schema::hasColumn($table, $column) || ! $this->autoUpdates($table, $column)) {
                continue;
            }

            DB::statement("ALTER TABLE `{$table}` MODIFY `{$column}` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP");
        }

        // started_at was rewritten with local time on each save; the row's
        // creation time is the real start
        if (Schema::hasColumn('lab_attempts', 'created_at')) {
            DB::table('lab_attempts')->whereNotNull('created_at')->update(['started_at' => DB::raw('created_at')]);
        }
    }

    public function down(): void
    {
        // Irreversible on purpose: the auto-update behaviour was a bug.
    }

    private function autoUpdates(string $table, string $column): bool
    {
        $extra = DB::table('information_schema.columns')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('column_name', $column)
            ->value('extra');

        return is_string($extra) && str_contains(strtolower($extra), 'on update');
    }
};
