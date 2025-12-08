<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('english_game_content', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('game_id');
            $table->uuid('game_level_id')->nullable();
            $table->uuid('english_level_id');
            $table->enum('content_type', ['word', 'sentence', 'phrase', 'question', 'audio', 'image', 'dialogue', 'paragraph']);
            $table->json('content');
            $table->uuid('vocabulary_id')->nullable();
            $table->uuid('grammar_rule_id')->nullable();
            $table->enum('difficulty', ['easy', 'medium', 'hard'])->default('medium');
            $table->integer('points')->default(10);
            $table->integer('times_shown')->default(0);
            $table->integer('times_correct')->default(0);
            $table->decimal('accuracy_rate', 5, 2)->default(0);
            $table->integer('order_number')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('game_id')->references('id')->on('english_games')->onDelete('cascade');
            $table->foreign('game_level_id')->references('id')->on('english_game_levels')->onDelete('set null');
            $table->foreign('english_level_id')->references('id')->on('english_levels')->onDelete('cascade');
            $table->foreign('vocabulary_id')->references('id')->on('english_vocabulary')->onDelete('set null');
            $table->foreign('grammar_rule_id')->references('id')->on('english_grammar_rules')->onDelete('set null');
            $table->index(['game_id', 'english_level_id', 'difficulty']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('english_game_content');
    }
};
