<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_game_attempts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->uuid('game_id');
            $table->uuid('game_level_id')->nullable();
            $table->integer('score')->default(0);
            $table->integer('max_possible_score')->default(0);
            $table->decimal('percentage', 5, 2)->default(0);
            $table->integer('stars_earned')->default(0);
            $table->integer('questions_total')->default(0);
            $table->integer('questions_correct')->default(0);
            $table->integer('questions_incorrect')->default(0);
            $table->integer('questions_skipped')->default(0);
            $table->integer('best_streak')->default(0);
            $table->integer('hints_used')->default(0);
            $table->integer('lives_remaining')->default(0);
            $table->integer('time_spent_seconds')->default(0);
            $table->integer('time_limit_seconds')->nullable();
            $table->decimal('average_answer_time', 8, 2)->default(0);
            $table->json('answers')->nullable();
            $table->integer('xp_earned')->default(0);
            $table->integer('coins_earned')->default(0);
            $table->json('bonuses_earned')->nullable();
            $table->enum('status', ['in_progress', 'completed', 'abandoned', 'timed_out'])->default('in_progress');
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('game_id')->references('id')->on('english_games')->onDelete('cascade');
            $table->foreign('game_level_id')->references('id')->on('english_game_levels')->onDelete('set null');
            $table->index(['user_id', 'game_id']);
            $table->index(['user_id', 'created_at']);
            $table->index(['game_id', 'score']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_game_attempts');
    }
};
