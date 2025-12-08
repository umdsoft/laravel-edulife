<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('english_grammar_exercises', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('grammar_rule_id');
            $table->uuid('level_id');

            // Exercise type
            $table->enum('exercise_type', [
                'fill_gap',
                'multiple_choice',
                'error_correction',
                'sentence_transformation',
                'word_order',
                'matching',
                'true_false',
                'complete_sentence'
            ]);

            // Question
            $table->text('question');
            $table->text('question_uz')->nullable();

            // Content based on type (JSON for flexibility)
            $table->json('content');

            // Explanation for answer
            $table->text('explanation')->nullable();
            $table->text('explanation_uz')->nullable();

            // Difficulty
            $table->enum('difficulty', ['easy', 'medium', 'hard'])->default('medium');
            $table->integer('points')->default(10);

            // Statistics
            $table->integer('times_attempted')->default(0);
            $table->integer('times_correct')->default(0);

            // Status
            $table->integer('order_number')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->foreign('grammar_rule_id')->references('id')->on('english_grammar_rules')->onDelete('cascade');
            $table->foreign('level_id')->references('id')->on('english_levels')->onDelete('cascade');
            $table->index('exercise_type');
            $table->index('difficulty');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('english_grammar_exercises');
    }
};
