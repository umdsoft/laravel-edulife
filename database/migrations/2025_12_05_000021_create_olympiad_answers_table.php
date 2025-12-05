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
        Schema::create('olympiad_answers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('attempt_id')->constrained('olympiad_attempts')->onDelete('cascade');
            $table->foreignUuid('section_attempt_id')->constrained('section_attempts')->onDelete('cascade');
            $table->foreignUuid('olympiad_question_id')->constrained('olympiad_questions')->onDelete('cascade');
            $table->foreignUuid('question_id')->constrained('question_bank')->onDelete('cascade');
            $table->json('user_answer')->nullable();
            $table->text('text_answer')->nullable();
            // For essay/short answer
            $table->string('audio_answer_url', 500)->nullable();
            // For speaking section
            $table->boolean('is_correct')->nullable();
            // null for manual grading
            $table->boolean('is_partially_correct')->default(false);
            $table->decimal('points_earned', 8, 2)->default(0);
            $table->decimal('max_points', 8, 2);
            $table->integer('time_spent_seconds')->default(0);
            $table->boolean('is_flagged')->default(false);
            // Student flagged for review
            $table->boolean('requires_manual_grading')->default(false);
            $table->boolean('is_graded')->default(false);
            $table->json('grading_details')->nullable();
            // {criteria: {grammar: 20, vocabulary: 18}, grader_id: "...", graded_at: "..."}
            $table->text('grader_feedback')->nullable();
            $table->timestamps();
            
            // Unique constraint
            $table->unique(['attempt_id', 'olympiad_question_id']);
            
            // Indexes
            $table->index('attempt_id');
            $table->index('section_attempt_id');
            $table->index('requires_manual_grading');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('olympiad_answers');
    }
};
