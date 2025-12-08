<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Check if an index exists on a table
     */
    private function indexExists(string $table, string $indexName): bool
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list('{$table}')");
            foreach ($indexes as $index) {
                if ($index->name === $indexName) {
                    return true;
                }
            }
            return false;
        }

        // MySQL/MariaDB
        $indexes = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
        return count($indexes) > 0;
    }

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if username column exists before proceeding
        if (!Schema::hasColumn('teacher_profiles', 'username')) {
            return;
        }

        // First drop the regular index (if exists)
        if ($this->indexExists('teacher_profiles', 'teacher_profiles_username_index')) {
            Schema::table('teacher_profiles', function (Blueprint $table) {
                $table->dropIndex(['username']);
            });
        }

        // Then drop the unique constraint (if exists)
        if ($this->indexExists('teacher_profiles', 'teacher_profiles_username_unique')) {
            Schema::table('teacher_profiles', function (Blueprint $table) {
                $table->dropUnique(['username']);
            });
        }

        // Now safely drop the column
        Schema::table('teacher_profiles', function (Blueprint $table) {
            $table->dropColumn('username');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('teacher_profiles', 'username')) {
            Schema::table('teacher_profiles', function (Blueprint $table) {
                $table->string('username', 50)->unique()->after('user_id');
            });
        }
    }
};
