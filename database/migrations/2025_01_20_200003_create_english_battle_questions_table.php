<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('english_battle_questions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('level_id');

            // Question type
            $table->enum('question_type', [
                'vocabulary_meaning',
                'vocabulary_translation',
                'vocabulary_synonym',
                'vocabulary_antonym',
                'vocabulary_fill_gap',
                'grammar_correct_form',
                'grammar_error_find',
                'grammar_fill_gap',
                'grammar_sentence_order',
                'listening_word',
                'listening_sentence'
            ]);

            // Question content
            $table->text('question');
            $table->text('question_uz')->nullable();
            $table->string('correct_answer');
            $table->json('options');
            $table->text('explanation')->nullable();
            $table->text('explanation_uz')->nullable();

            // Media
            $table->string('audio_url')->nullable();
            $table->string('image_url')->nullable();

            // Difficulty & points
            $table->enum('difficulty', ['easy', 'medium', 'hard'])->default('medium');
            $table->integer('base_points')->default(10);
            $table->integer('time_bonus_max')->default(5);

            // Related content
            $table->uuid('vocabulary_id')->nullable();
            $table->uuid('grammar_rule_id')->nullable();

            // Statistics
            $table->integer('times_used')->default(0);
            $table->integer('times_correct')->default(0);
            $table->decimal('accuracy_rate', 5, 2)->default(50);
            $table->decimal('avg_answer_time', 8, 2)->default(0);

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('level_id')->references('id')->on('english_levels')->onDelete('cascade');
            $table->foreign('vocabulary_id')->references('id')->on('english_vocabulary')->onDelete('set null');
            $table->foreign('grammar_rule_id')->references('id')->on('english_grammar_rules')->onDelete('set null');

            $table->index(['level_id', 'question_type', 'difficulty']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('english_battle_questions');
    }
};
