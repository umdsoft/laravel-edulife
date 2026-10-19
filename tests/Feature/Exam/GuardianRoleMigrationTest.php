<?php

namespace Tests\Feature\Exam;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The guardian-role migration on a legacy SQLite database whose users.role
 * still carries its ENUM CHECK constraint (fresh databases lost it in an
 * earlier table rebuild). Runs on a throw-away SQLite file.
 */
class GuardianRoleMigrationTest extends TestCase
{
    private string $file;

    private string $previousConnection;

    protected function setUp(): void
    {
        parent::setUp();

        $base = tempnam(sys_get_temp_dir(), 'exam_role_');
        @unlink($base);
        $this->file = $base.'.sqlite';
        touch($this->file);
        config(['database.connections.legacy_sqlite' => [
            'driver' => 'sqlite',
            'database' => $this->file,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]]);
        $this->previousConnection = DB::getDefaultConnection();
        DB::setDefaultConnection('legacy_sqlite');

        DB::statement('create table "users" ("id" varchar not null, '
            .'"role" varchar check ("role" in (\'student\', \'teacher\', \'admin\', \'super_admin\')) not null default (\'student\'), '
            .'"status" varchar check ("status" in (\'pending\', \'active\', \'blocked\')) not null default (\'pending\'), '
            .'primary key ("id"))');
    }

    protected function tearDown(): void
    {
        DB::disconnect('legacy_sqlite');
        DB::setDefaultConnection($this->previousConnection);
        @unlink($this->file);

        parent::tearDown();
    }

    private function migration(): object
    {
        return require database_path('migrations/2026_10_20_100008_add_guardian_role_to_users_table.php');
    }

    private function insert(string $id, string $role, string $status = 'active'): bool
    {
        try {
            DB::table('users')->insert(['id' => $id, 'role' => $role, 'status' => $status]);

            return true;
        } catch (\Illuminate\Database\QueryException) {
            return false;
        }
    }

    public function test_up_adds_guardian_and_keeps_other_check_constraints(): void
    {
        $this->assertFalse($this->insert('1', 'guardian'), 'precondition: CHECK rejects guardian');

        $this->migration()->up();
        DB::reconnect('legacy_sqlite');

        $this->assertTrue($this->insert('2', 'guardian'));
        $this->assertFalse($this->insert('3', 'student', 'bogus'), 'status CHECK must survive');
        $this->assertSame('ok', DB::selectOne('pragma integrity_check')->integrity_check);

        // Idempotent
        $this->migration()->up();
    }

    public function test_down_refuses_while_guardians_exist_and_restores_otherwise(): void
    {
        $migration = $this->migration();
        $migration->up();
        DB::reconnect('legacy_sqlite');
        $this->insert('g', 'guardian');

        try {
            $migration->down();
            $this->fail('down() must refuse while guardian users exist');
        } catch (\RuntimeException) {
        }

        DB::table('users')->where('id', 'g')->delete();
        $migration->down();
        DB::reconnect('legacy_sqlite');

        $this->assertFalse($this->insert('x', 'guardian'));
        $this->assertTrue($this->insert('y', 'teacher'));
    }
}
