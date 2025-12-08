<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * This migration adds foreign key constraints for enrollment_id
 * to tables that were created BEFORE the enrollments table.
 * 
 * Order fix: These tables reference enrollments but were created earlier:
 * - test_attempts (2024_01_03_000004)
 * - lesson_progress (2024_01_04_000002)
 * - module_progress (2024_01_04_000003)
 * - reviews (2024_01_04_000004)
 * - certificates (2024_01_04_000006)
 */
return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add foreign key constraint to test_attempts
        if (Schema::hasTable('test_attempts') && Schema::hasTable('enrollments')) {
            Schema::table('test_attempts', function (Blueprint $table) {
                $table->foreign('enrollment_id')
                    ->references('id')
                    ->on('enrollments')
                    ->cascadeOnDelete();
            });
        }

        // Add foreign key constraint to lesson_progress
        if (Schema::hasTable('lesson_progress') && Schema::hasTable('enrollments')) {
            Schema::table('lesson_progress', function (Blueprint $table) {
                $table->foreign('enrollment_id')
                    ->references('id')
                    ->on('enrollments')
                    ->cascadeOnDelete();
            });
        }

        // Add foreign key constraint to module_progress
        if (Schema::hasTable('module_progress') && Schema::hasTable('enrollments')) {
            Schema::table('module_progress', function (Blueprint $table) {
                $table->foreign('enrollment_id')
                    ->references('id')
                    ->on('enrollments')
                    ->cascadeOnDelete();
            });
        }

        // Add foreign key constraint to reviews
        if (Schema::hasTable('reviews') && Schema::hasTable('enrollments')) {
            Schema::table('reviews', function (Blueprint $table) {
                $table->foreign('enrollment_id')
                    ->references('id')
                    ->on('enrollments')
                    ->cascadeOnDelete();
            });
        }

        // Add foreign key constraint to certificates
        if (Schema::hasTable('certificates') && Schema::hasTable('enrollments')) {
            Schema::table('certificates', function (Blueprint $table) {
                $table->foreign('enrollment_id')
                    ->references('id')
                    ->on('enrollments')
                    ->cascadeOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = ['test_attempts', 'lesson_progress', 'module_progress', 'reviews', 'certificates'];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    $table->dropForeign([$tableName . '_enrollment_id_foreign']);
                });
            }
        }
    }
};
