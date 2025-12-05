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
        Schema::create('coding_submissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('attempt_id')->constrained('olympiad_attempts')->onDelete('cascade');
            $table->foreignUuid('section_attempt_id')->constrained('section_attempts')->onDelete('cascade');
            $table->foreignUuid('olympiad_problem_id')->constrained('olympiad_coding_problems')->onDelete('cascade');
            $table->foreignUuid('problem_id')->constrained('coding_problems')->onDelete('cascade');
            $table->integer('submission_number');
            $table->string('language', 20);
            $table->text('source_code');
            $table->integer('code_length')->default(0);
            $table->string('status', 30)->default('pending');
            // 'pending', 'running', 'accepted', 'wrong_answer', 'time_limit', 
            // 'memory_limit', 'runtime_error', 'compile_error', 'presentation_error'
            $table->integer('test_cases_passed')->default(0);
            $table->integer('test_cases_total');
            $table->decimal('points_earned', 8, 2)->default(0);
            $table->decimal('max_points', 8, 2);
            $table->integer('execution_time_ms')->nullable();
            $table->integer('memory_used_kb')->nullable();
            $table->text('compile_output')->nullable();
            $table->json('test_results')->nullable();
            // [{id: 1, status: 'passed', time_ms: 50, memory_kb: 1024}, ...]
            $table->boolean('is_best_submission')->default(false);
            $table->timestamps();
            
            // Indexes
            $table->index('attempt_id');
            $table->index(['attempt_id', 'olympiad_problem_id']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coding_submissions');
    }
};
