<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stage 5 (variant generator + exam engine):
 *  exam_variants.warnings              generator notes: adjacent difficulty fill, repeats, shortage
 *  exam_attempts.violation_log         light anti-cheat log (tab switches...), capped list
 *  exam_sessions.restricted_to_user_id a private session for one student (placement retake
 *                                      allowed by the teacher). No FK: a plain indexed uuid
 *                                      keeps SQLite from rebuilding the table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_variants', function (Blueprint $table) {
            $table->json('warnings')->nullable()->after('used_fallback');
        });

        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->json('violation_log')->nullable()->after('violations_count');
        });

        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->uuid('restricted_to_user_id')->nullable()->after('blueprint_id');
            $table->index('restricted_to_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->dropIndex(['restricted_to_user_id']);
            $table->dropColumn('restricted_to_user_id');
        });
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->dropColumn('violation_log');
        });
        Schema::table('exam_variants', function (Blueprint $table) {
            $table->dropColumn('warnings');
        });
    }
};
