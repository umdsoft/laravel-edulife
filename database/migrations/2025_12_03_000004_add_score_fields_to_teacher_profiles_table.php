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
        Schema::table('teacher_profiles', function (Blueprint $table) {
            // Add score column if not exists
            if (!Schema::hasColumn('teacher_profiles', 'score')) {
                $table->decimal('score', 5, 2)->default(0)->after('commission_rate');
            }

            // Add score_breakdown column if not exists
            if (!Schema::hasColumn('teacher_profiles', 'score_breakdown')) {
                $table->json('score_breakdown')->nullable()->after('score');
            }

            // Add score_updated_at column if not exists
            if (!Schema::hasColumn('teacher_profiles', 'score_updated_at')) {
                $table->timestamp('score_updated_at')->nullable()->after('score_breakdown');
            }

            // Add level_changed_at column if not exists
            if (!Schema::hasColumn('teacher_profiles', 'level_changed_at')) {
                $table->timestamp('level_changed_at')->nullable()->after('score_updated_at');
            }
        });

        // Add index for score only if it doesn't exist
        // Note: 'level' index already exists in create_teacher_profiles_table migration
        if (!$this->indexExists('teacher_profiles', 'teacher_profiles_score_index')) {
            Schema::table('teacher_profiles', function (Blueprint $table) {
                $table->index('score');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop score index if it exists
        if ($this->indexExists('teacher_profiles', 'teacher_profiles_score_index')) {
            Schema::table('teacher_profiles', function (Blueprint $table) {
                $table->dropIndex(['score']);
            });
        }

        // Note: DO NOT drop 'level' index - it was created in create_teacher_profiles_table migration

        // Drop columns if they exist
        Schema::table('teacher_profiles', function (Blueprint $table) {
            $columns = ['score', 'score_breakdown', 'score_updated_at', 'level_changed_at'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('teacher_profiles', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
