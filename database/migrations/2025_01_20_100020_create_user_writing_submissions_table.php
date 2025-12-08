<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_writing_submissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->uuid('lesson_id')->nullable();
            $table->enum('task_type', ['essay', 'email', 'letter', 'story', 'description', 'summary', 'journal', 'free_writing']);
            $table->text('prompt')->nullable();
            $table->text('prompt_uz')->nullable();
            $table->integer('min_words')->nullable();
            $table->integer('max_words')->nullable();
            $table->text('content');
            $table->integer('word_count')->default(0);
            $table->integer('sentence_count')->default(0);
            $table->integer('paragraph_count')->default(0);
            $table->integer('overall_score')->default(0);
            $table->integer('grammar_score')->default(0);
            $table->integer('vocabulary_score')->default(0);
            $table->integer('coherence_score')->default(0);
            $table->integer('task_achievement_score')->default(0);
            $table->json('feedback')->nullable();
            $table->text('corrected_content')->nullable();
            $table->integer('xp_earned')->default(0);
            $table->integer('coins_earned')->default(0);
            $table->enum('status', ['draft', 'submitted', 'analyzed', 'reviewed'])->default('submitted');
            $table->timestamps();

            $table->foreign('lesson_id')->references('id')->on('english_lessons')->onDelete('set null');
            $table->index(['user_id', 'task_type']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_writing_submissions');
    }
};
