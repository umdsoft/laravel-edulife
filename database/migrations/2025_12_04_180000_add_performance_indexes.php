<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     * Performance indexes for frequently queried columns
     */
    public function up(): void
    {
        // Enrollments - frequently queried by user and course
        if (Schema::hasTable('enrollments')) {
            Schema::table('enrollments', function (Blueprint $table) {
                if (!$this->hasIndex('enrollments', 'enrollments_user_course_index')) {
                    $table->index(['user_id', 'course_id'], 'enrollments_user_course_index');
                }
                if (!$this->hasIndex('enrollments', 'enrollments_status_index')) {
                    $table->index('status', 'enrollments_status_index');
                }
            });
        }

        // Payments - frequently filtered by status
        if (Schema::hasTable('payments')) {
            Schema::table('payments', function (Blueprint $table) {
                if (!$this->hasIndex('payments', 'payments_status_index')) {
                    $table->index('status', 'payments_status_index');
                }
            });
        }

        // Lesson Progress - frequently queried by user and lesson
        if (Schema::hasTable('lesson_progress')) {
            Schema::table('lesson_progress', function (Blueprint $table) {
                if (!$this->hasIndex('lesson_progress', 'lesson_progress_user_lesson_index')) {
                    $table->index(['user_id', 'lesson_id'], 'lesson_progress_user_lesson_index');
                }
            });
        }

        // Coin Transactions - frequently queried by user and time
        if (Schema::hasTable('coin_transactions')) {
            Schema::table('coin_transactions', function (Blueprint $table) {
                if (!$this->hasIndex('coin_transactions', 'coin_transactions_user_created_index')) {
                    $table->index(['user_id', 'created_at'], 'coin_transactions_user_created_index');
                }
            });
        }

        // Courses - frequently filtered by status and teacher
        if (Schema::hasTable('courses')) {
            Schema::table('courses', function (Blueprint $table) {
                if (!$this->hasIndex('courses', 'courses_status_index')) {
                    $table->index('status', 'courses_status_index');
                }
            });
        }

        // Test Attempts - frequently queried by user and test
        if (Schema::hasTable('test_attempts')) {
            Schema::table('test_attempts', function (Blueprint $table) {
                if (!$this->hasIndex('test_attempts', 'test_attempts_user_test_index')) {
                    $table->index(['user_id', 'test_id'], 'test_attempts_user_test_index');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'enrollments' => ['enrollments_user_course_index', 'enrollments_status_index'],
            'payments' => ['payments_status_index'],
            'lesson_progress' => ['lesson_progress_user_lesson_index'],
            'coin_transactions' => ['coin_transactions_user_created_index'],
            'courses' => ['courses_status_index'],
            'test_attempts' => ['test_attempts_user_test_index'],
        ];

        foreach ($tables as $tableName => $indexes) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($indexes, $tableName) {
                    foreach ($indexes as $index) {
                        if ($this->hasIndex($tableName, $index)) {
                            $table->dropIndex($index);
                        }
                    }
                });
            }
        }
    }

    /**
     * Check if an index exists - supports both SQLite and MySQL
     */
    private function hasIndex(string $table, string $indexName): bool
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
};
