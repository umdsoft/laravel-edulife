<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * config('app.timezone') changed from UTC to Asia/Tashkent (UTC+5, no DST):
 * new rows hold Tashkent wall-clock time. Every datetime/timestamp value that
 * was written while the app ran on UTC is moved forward by five hours once, so
 * old and new rows mean the same clock. All datetime columns of a table are
 * updated in one statement, so no ON UPDATE CURRENT_TIMESTAMP column fires.
 * down() moves them back, keeping a rollback + re-run symmetric.
 *
 * MySQL/MariaDB only: SQLite test databases are created fresh in the new zone.
 */
return new class extends Migration
{
    private const HOURS = 5;

    public function up(): void
    {
        $this->shift('+');
    }

    public function down(): void
    {
        $this->shift('-');
    }

    private function shift(string $sign): void
    {
        if (!in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        $columns = DB::select(
            "SELECT TABLE_NAME AS t, COLUMN_NAME AS c FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND DATA_TYPE IN ('datetime', 'timestamp')
               AND TABLE_NAME <> 'migrations'"
        );

        $byTable = [];
        foreach ($columns as $column) {
            $byTable[$column->t][] = $column->c;
        }

        foreach ($byTable as $table => $names) {
            $sets = implode(', ', array_map(
                fn ($c) => "`{$c}` = IF(`{$c}` IS NULL, NULL, `{$c}` {$sign} INTERVAL " . self::HOURS . ' HOUR)',
                $names
            ));
            DB::statement("UPDATE `{$table}` SET {$sets}");
        }
    }
};
