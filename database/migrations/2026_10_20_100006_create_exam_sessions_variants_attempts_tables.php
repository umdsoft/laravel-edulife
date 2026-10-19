<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Exam engine tables:
 *  exam_sessions  - one scheduled exam (a Saturday exam, a placement window)
 *  exam_variants  - the personal variant of one student in one session
 *  exam_attempts  - the student's attempt (olympiad_attempts pattern)
 *  exam_answers   - one row per variant question
 *  question_error_reports - "Savolda xato bor" reports (may point to an attempt)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('subject_id')->constrained('exam_subjects')->cascadeOnDelete();
            $table->foreignUuid('blueprint_id')->constrained('exam_blueprints')->restrictOnDelete();
            $table->string('type', 20);                     // placement | weekly | open_regional
            $table->string('title');
            // Placement sessions may be an open window (both NULL).
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('registration_closes_at')->nullable();
            $table->unsignedSmallInteger('duration_min')->nullable(); // overrides blueprint
            $table->string('status', 20)->default('draft');
            $table->timestamp('variants_generated_at')->nullable();
            $table->timestamp('results_published_at')->nullable();
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['subject_id', 'type', 'starts_at']);
            $table->index(['status', 'starts_at']);
        });

        Schema::create('exam_variants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('session_id')->constrained('exam_sessions')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedBigInteger('seed');            // RNG seed, for audit / regeneration
            $table->json('question_ids');                  // ordered list of question_bank ids
            $table->json('option_orders')->nullable();     // {question_id: [option_id, ...]}
            $table->json('rule_map')->nullable();          // {question_id: blueprint_rule_id}
            $table->boolean('used_fallback')->default(false); // filled from adjacent difficulty
            $table->timestamp('generated_at')->nullable();
            $table->timestamps();

            $table->unique(['session_id', 'user_id']);
            $table->index('user_id');
        });

        Schema::create('exam_attempts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('session_id')->constrained('exam_sessions')->cascadeOnDelete();
            $table->foreignUuid('variant_id')->constrained('exam_variants')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('session_token', 64)->nullable()->unique();
            $table->string('status', 20)->default('in_progress');

            // Timing (server authoritative)
            $table->timestamp('started_at')->nullable();
            $table->timestamp('expires_at')->nullable();   // started_at + duration, capped by session end
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('graded_at')->nullable();
            $table->timestamp('regraded_at')->nullable();
            $table->unsignedInteger('time_spent_seconds')->nullable();
            $table->timestamp('last_heartbeat_at')->nullable();

            // Score
            $table->decimal('score', 8, 2)->default(0);
            $table->decimal('max_score', 8, 2)->default(0);
            $table->decimal('score_percent', 5, 2)->default(0);
            $table->unsignedSmallInteger('correct_count')->default(0);
            $table->unsignedSmallInteger('wrong_count')->default(0);
            $table->unsignedSmallInteger('skipped_count')->default(0);

            // Anti-cheat (olympiad pattern)
            $table->string('device_fingerprint', 128)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->unsignedSmallInteger('violations_count')->default(0);
            $table->string('invalidated_reason')->nullable();

            $table->timestamps();

            $table->unique(['session_id', 'user_id']);
            $table->index(['session_id', 'status']);
            $table->index(['session_id', 'score']);
            $table->index(['status', 'expires_at']); // auto-submit sweep
        });

        Schema::create('exam_answers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('attempt_id')->constrained('exam_attempts')->cascadeOnDelete();
            $table->foreignUuid('question_id')->constrained('question_bank')->restrictOnDelete();
            $table->unsignedSmallInteger('position');      // 1-based order in the variant
            // Choice: selected option id(s).
            $table->foreignUuid('selected_option_id')->nullable()->constrained('question_bank_options')->nullOnDelete();
            $table->json('selected_option_ids')->nullable(); // multiple_choice
            // Open: raw text as typed ("0,5", "1/2") + parsed number + unit.
            $table->string('answer_text', 255)->nullable();
            $table->decimal('numeric_value', 30, 10)->nullable();
            $table->string('unit', 30)->nullable();

            $table->boolean('is_correct')->nullable();     // NULL = not graded / skipped
            $table->boolean('unit_correct')->nullable();
            $table->decimal('points', 6, 2)->default(0);
            $table->decimal('max_points', 6, 2)->default(1);
            $table->boolean('is_flagged')->default(false); // "review later" flag
            $table->unsignedInteger('time_spent_seconds')->default(0);
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();

            $table->unique(['attempt_id', 'question_id']);
            $table->index(['attempt_id', 'position']);
            $table->index(['question_id', 'is_correct']); // item statistics, regrade
        });

        Schema::create('question_error_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('question_id')->constrained('question_bank')->cascadeOnDelete();
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignUuid('attempt_id')->nullable()->constrained('exam_attempts')->nullOnDelete();
            $table->text('message');
            $table->string('status', 20)->default('open'); // open | accepted | dismissed
            $table->foreignUuid('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->text('resolution_note')->nullable();
            $table->timestamps();

            $table->index(['question_id', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_error_reports');
        Schema::dropIfExists('exam_answers');
        Schema::dropIfExists('exam_attempts');
        Schema::dropIfExists('exam_variants');
        Schema::dropIfExists('exam_sessions');
    }
};
