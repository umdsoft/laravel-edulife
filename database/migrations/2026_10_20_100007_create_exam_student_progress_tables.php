<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * exam_student_topic_mastery - per student x topic mastery (0..100), the
 *                               "topic map"; formula documented in
 *                               docs/exam/02_DATA_MODEL.md (stage 5).
 * exam_student_weekly_results - one row per student per session: growth history.
 * exam_student_profiles       - exam specific student data (grade, school, goal...).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_student_topic_mastery', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('topic_id')->constrained('exam_topics')->cascadeOnDelete();
            $table->foreignUuid('subject_id')->constrained('exam_subjects')->cascadeOnDelete();
            $table->decimal('score', 5, 2)->default(0);           // 0..100
            $table->unsignedInteger('attempts_count')->default(0); // attempts that touched the topic
            $table->unsignedInteger('answered_count')->default(0);
            $table->unsignedInteger('correct_count')->default(0);
            $table->string('source', 20)->nullable();             // placement | weekly | practice (last update)
            $table->timestamp('last_attempt_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'topic_id']);
            $table->index(['user_id', 'subject_id']);
        });

        Schema::create('exam_student_weekly_results', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('session_id')->constrained('exam_sessions')->cascadeOnDelete();
            $table->foreignUuid('attempt_id')->nullable()->constrained('exam_attempts')->nullOnDelete();
            $table->foreignUuid('subject_id')->constrained('exam_subjects')->cascadeOnDelete();
            $table->date('week_start');                            // Monday of the exam week
            $table->decimal('score', 8, 2)->default(0);
            $table->decimal('max_score', 8, 2)->default(0);
            $table->decimal('score_percent', 5, 2)->default(0);
            $table->decimal('delta_prev', 6, 2)->nullable();       // percent points vs previous result
            $table->unsignedInteger('rank')->nullable();
            $table->decimal('percentile', 5, 2)->nullable();
            $table->unsignedInteger('participants_count')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'session_id']);
            $table->index(['session_id', 'rank']);
            $table->index(['user_id', 'subject_id', 'week_start']);
            $table->index(['session_id', 'delta_prev']); // "eng ko'p o'sganlar"
        });

        Schema::create('exam_student_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('grade')->nullable();    // 5..11, NULL when graduated
            $table->boolean('is_graduate')->default(false);
            $table->json('subjects')->nullable();                // ["math","physics"]
            $table->string('goal', 30)->nullable();              // otm_grant | milliy_sertifikat | sat | school
            $table->foreignUuid('region_id')->nullable()->constrained('regions')->nullOnDelete();
            $table->foreignUuid('district_id')->nullable()->constrained('districts')->nullOnDelete();
            $table->foreignUuid('school_id')->nullable()->constrained('schools')->nullOnDelete();
            $table->string('school_name')->nullable();           // free text until schools are seeded
            // Public leaderboard: real name only with consent, otherwise the alias.
            $table->boolean('show_name_publicly')->default(false);
            $table->string('public_alias', 50)->nullable();
            $table->timestamp('placement_completed_at')->nullable();
            $table->timestamps();

            $table->index(['school_id']);
            $table->index(['district_id']);
            $table->index(['grade']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_student_profiles');
        Schema::dropIfExists('exam_student_weekly_results');
        Schema::dropIfExists('exam_student_topic_mastery');
    }
};
