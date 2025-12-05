<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('olympiad_attempts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('olympiad_id')->constrained('olympiads')->onDelete('cascade');
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('registration_id')->constrained('olympiad_registrations')->onDelete('cascade');
            $table->string('session_token', 64)->unique();
            
            // Timing
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('total_duration_seconds')->nullable();
            
            // Current state
            $table->uuid('current_section_id')->nullable();
            $table->integer('current_question_index')->default(0);
            
            // Scoring
            $table->decimal('total_raw_score', 10, 2)->default(0);
            $table->decimal('total_weighted_score', 10, 2)->default(0);
            $table->decimal('total_max_score', 10, 2);
            $table->decimal('score_percent', 5, 2)->default(0);
            $table->json('sections_results')->nullable();
            /*
             * {
             *   "test": {"raw_score": 80, "weighted_score": 16, "max_score": 100, "percent": 80},
             *   "listening": {...}
             * }
             */
            
            // Anti-cheat
            $table->uuid('device_lock_id')->nullable();
            $table->integer('tab_switches')->default(0);
            $table->integer('fullscreen_exits')->default(0);
            $table->integer('warnings_count')->default(0);
            $table->boolean('is_disqualified')->default(false);
            $table->string('disqualified_reason', 255)->nullable();
            $table->timestamp('disqualified_at')->nullable();
            $table->json('answers_snapshot')->nullable();
            // Snapshot of all answers at disqualification time
            
            // Ranking
            $table->integer('rank')->nullable();
            $table->integer('percentile')->nullable();
            
            // Grading state
            $table->boolean('requires_manual_grading')->default(false);
            $table->boolean('manual_grading_complete')->default(false);
            $table->timestamp('graded_at')->nullable();
            
            // Status
            $table->string('status', 30)->default('not_started');
            // 'not_started', 'in_progress', 'submitted', 'grading', 'graded', 
            // 'disqualified', 'expired', 'abandoned'
            
            $table->timestamps();
            
            // Unique constraints
            $table->unique(['olympiad_id', 'user_id']);
            
            // Indexes
            $table->index('olympiad_id');
            $table->index('user_id');
            $table->index('status');
            $table->index(['olympiad_id', 'status']);
            $table->index(['olympiad_id', 'total_weighted_score']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('olympiad_attempts');
    }
};
