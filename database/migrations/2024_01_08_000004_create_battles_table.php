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
        Schema::create('battles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('player1_id')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('player2_id')->nullable()->constrained('users')->onDelete('cascade');
            $table->foreignUuid('direction_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('course_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('difficulty', ['easy', 'medium', 'hard', 'mixed'])->default('mixed');
            $table->enum('status', ['searching', 'matched', 'ready', 'in_progress', 'finished', 'cancelled', 'abandoned'])->default('searching');
            $table->integer('questions_count')->default(5);
            $table->integer('time_per_question')->default(20); // sekundlarda
            
            // Scores
            $table->integer('player1_score')->default(0);
            $table->integer('player2_score')->default(0);
            $table->integer('player1_correct')->default(0);
            $table->integer('player2_correct')->default(0);
            $table->integer('player1_avg_time')->nullable(); // ms
            $table->integer('player2_avg_time')->nullable();
            
            // ELO
            $table->integer('player1_elo_before')->nullable();
            $table->integer('player2_elo_before')->nullable();
            $table->integer('player1_elo_change')->default(0);
            $table->integer('player2_elo_change')->default(0);
            
            // Result
            $table->foreignUuid('winner_id')->nullable()->constrained('users')->onDelete('set null');
            $table->boolean('is_draw')->default(false);
            
            // Rewards
            $table->integer('winner_xp_reward')->default(0);
            $table->integer('winner_coin_reward')->default(0);
            $table->integer('loser_xp_reward')->default(0);
            
            // Timestamps
            $table->timestamp('matched_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
            
            $table->index('player1_id');
            $table->index('player2_id');
            $table->index('status');
            $table->index('direction_id');
            $table->index('winner_id');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('battles');
    }
};
