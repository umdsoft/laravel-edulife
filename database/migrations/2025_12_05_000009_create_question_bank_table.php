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
        Schema::create('question_bank', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Category
            $table->foreignUuid('olympiad_type_id')->nullable()->constrained('olympiad_types')->onDelete('set null');
            $table->string('section_type', 20);
            // 'test', 'listening', 'reading', 'writing', 'speaking'
            $table->foreignUuid('direction_id')->nullable()->constrained('directions')->onDelete('set null');
            $table->uuid('topic_id')->nullable(); // References topics table if exists
            
            // Question Type
            $table->string('question_type', 30);
            // 'single_choice', 'multiple_choice', 'true_false', 'fill_blank', 
            // 'matching', 'ordering', 'essay', 'short_answer', 'audio_response'
            
            // Question Content
            $table->text('question_text');
            $table->text('question_html')->nullable();
            $table->json('question_media')->nullable();
            // {audio_url, image_url, passage, video_url, etc.}
            
            // Answers
            $table->json('options')->nullable();
            // Array of options for choice questions
            // [{"id": "a", "text": "Option A", "is_correct": false}, ...]
            $table->json('correct_answer');
            // Correct answer(s) - can be single value or array
            
            // Explanation
            $table->text('explanation')->nullable();
            $table->json('explanation_media')->nullable();
            
            // Scoring
            $table->string('difficulty', 20)->default('medium');
            // 'easy', 'medium', 'hard', 'expert'
            $table->integer('base_points')->default(1);
            $table->integer('estimated_time_seconds')->default(120);
            $table->boolean('negative_marking')->default(false);
            $table->decimal('negative_points', 5, 2)->default(0);
            
            // Manual Grading (Writing/Speaking)
            $table->boolean('requires_manual_grading')->default(false);
            $table->json('grading_rubric')->nullable();
            /*
             * {
             *   "criteria": [
             *     {"name": "Grammar", "max_points": 25, "description": "..."},
             *     {"name": "Vocabulary", "max_points": 25, "description": "..."}
             *   ]
             * }
             */
            
            // Tags for Demo similarity
            $table->json('concept_tags')->nullable();
            $table->json('skill_tags')->nullable();
            
            // Statistics
            $table->integer('times_used')->default(0);
            $table->integer('times_answered')->default(0);
            $table->integer('times_correct')->default(0);
            
            // Status
            $table->string('status', 20)->default('active');
            // 'draft', 'active', 'archived'
            $table->boolean('is_verified')->default(false);
            
            // Audit
            $table->foreignUuid('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('olympiad_type_id');
            $table->index('section_type');
            $table->index('direction_id');
            $table->index('question_type');
            $table->index('difficulty');
            $table->index('status');
            $table->index('is_verified');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('question_bank');
    }
};
