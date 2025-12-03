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
        Schema::create('test_answers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('attempt_id')->constrained('test_attempts')->cascadeOnDelete();
            $table->foreignUuid('question_id')->constrained()->cascadeOnDelete();
            
            // Answer data (JSON for flexibility)
            $table->json('answer')->nullable(); // User's answer
            $table->json('correct_answer')->nullable(); // Correct answer (stored for review)
            
            // Status
            $table->boolean('is_answered')->default(false);
            $table->boolean('is_correct')->nullable(); // null = not graded yet
            $table->decimal('points_earned', 5, 2)->default(0);
            $table->decimal('max_points', 5, 2)->default(1);
            
            // Timing
            $table->unsignedInteger('time_spent')->default(0); // seconds on this question
            $table->dateTime('answered_at')->nullable();
            
            // For review
            $table->boolean('is_flagged_for_review')->default(false);
            
            $table->timestamps();
            
            $table->unique(['attempt_id', 'question_id']);
            $table->index(['attempt_id', 'is_answered']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('test_answers');
    }
};
