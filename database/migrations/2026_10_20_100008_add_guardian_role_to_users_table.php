<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Adds 'guardian' (ota-ona) to the users.role enum.
 *
 * The application authorizes by users.role (RoleMiddleware), so the parent
 * role lives there too. Guarded per driver:
 *  - MySQL/MariaDB: reads the current ENUM definition and appends 'guardian'
 *    only when missing (keeps any value added by other migrations).
 *  - SQLite (dev/test): the enum is a CHECK constraint; only that constraint
 *    is rewritten in the stored schema, no table rebuild. (On a fresh SQLite
 *    database the constraint is already gone, see replaceSqliteRoleCheck.)
 */
return new class extends Migration
{
    public $withinTransaction = false;

    private const BASE_ROLES = ['student', 'teacher', 'admin', 'super_admin'];

    public function up(): void
    {
        $roles = $this->currentRoles();
        if (in_array('guardian', $roles, true)) {
            return;
        }

        $this->setRoles([...$roles, 'guardian']);
    }

    public function down(): void
    {
        if (DB::table('users')->where('role', 'guardian')->exists()) {
            throw new RuntimeException('Cannot remove the guardian role: guardian users exist.');
        }

        $this->setRoles(array_values(array_diff($this->currentRoles(), ['guardian'])));
    }

    /** @return list<string> */
    private function currentRoles(): array
    {
        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $row = DB::selectOne(
                'SELECT COLUMN_TYPE AS type FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
                ['users', 'role']
            );
            if ($row && preg_match_all("/'((?:[^']|'')*)'/", $row->type, $m)) {
                return array_map(fn ($v) => str_replace("''", "'", $v), $m[1]);
            }

            return self::BASE_ROLES;
        }

        if ($driver === 'sqlite') {
            $sql = (string) DB::table('sqlite_master')->where('type', 'table')->where('name', 'users')->value('sql');
            if (preg_match('/"role"[^,]*?check\s*\(\s*"role"\s+in\s*\(([^)]*)\)/i', $sql, $m)
                && preg_match_all("/'([^']*)'/", $m[1], $values)) {
                return $values[1];
            }
        }

        return self::BASE_ROLES;
    }

    /** @param list<string> $roles */
    private function setRoles(array $roles): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $list = implode(',', array_map(fn ($r) => DB::getPdo()->quote($r), $roles));
            DB::statement("ALTER TABLE `users` MODIFY `role` ENUM({$list}) NOT NULL DEFAULT 'student'");

            return;
        }

        if ($driver === 'sqlite') {
            $this->replaceSqliteRoleCheck($roles);

            return;
        }

        Schema::table('users', function (Blueprint $table) use ($roles) {
            $table->enum('role', $roles)->default('student')->change();
        });
    }

    /**
     * Rewrites only the CHECK constraint of users.role in the stored schema
     * (SQLite's documented procedure for constraint changes). A Laravel
     * ->change() would rebuild the table and silently drop the CHECK
     * constraints of the other enum columns (status, ...).
     *
     * @param list<string> $roles
     */
    private function replaceSqliteRoleCheck(array $roles): void
    {
        $sql = (string) DB::table('sqlite_master')->where('type', 'table')->where('name', 'users')->value('sql');
        $list = implode(', ', array_map(fn ($r) => "'".str_replace("'", "''", $r)."'", $roles));
        $count = 0;
        $new = preg_replace(
            '/check\s*\(\s*"role"\s+in\s*\([^)]*\)\s*\)/i',
            'check ("role" in ('.$list.'))',
            $sql,
            1,
            $count
        );

        if ($count === 0) {
            // An earlier rebuild of users (2025_12_03_140406) already dropped
            // the CHECK constraints: any role value is accepted, nothing to do.
            return;
        }

        $version = (int) DB::selectOne('PRAGMA schema_version')->schema_version;
        DB::statement('PRAGMA writable_schema = 1');
        DB::update("UPDATE sqlite_master SET sql = ? WHERE type = 'table' AND name = 'users'", [$new]);
        DB::statement('PRAGMA schema_version = '.($version + 1));
        DB::statement('PRAGMA writable_schema = 0');
    }
};
