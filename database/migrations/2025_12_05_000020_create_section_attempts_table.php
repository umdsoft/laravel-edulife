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
        Schema::create('section_attempts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('attempt_id')->constrained('olympiad_attempts')->onDelete('cascade');
            $table->foreignUuid('section_id')->constrained('olympiad_sections')->onDelete('cascade');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->integer('remaining_seconds')->nullable();
            $table->decimal('raw_score', 8, 2)->default(0);
            $table->decimal('weighted_score', 8, 2)->default(0);
            $table->decimal('max_score', 8, 2);
            $table->decimal('score_percent', 5, 2)->default(0);
            $table->integer('questions_answered')->default(0);
            $table->integer('questions_correct')->default(0);
            $table->boolean('requires_manual_grading')->default(false);
            $table->json('grading_details')->nullable();
            // For writing/speaking: {grammar: 20, vocabulary: 18, ...}
            $table->string('status', 20)->default('not_started');
            // 'not_started', 'in_progress', 'completed', 'expired', 'grading', 'graded'
            $table->timestamps();
            
            // Unique constraint
            $table->unique(['attempt_id', 'section_id']);
            
            // Indexes
            $table->index('attempt_id');
            $table->index('section_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('section_attempts');
    }
};
