<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('battles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Players
            $table->foreignUuid('player1_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('player2_id')->nullable()->constrained('users')->nullOnDelete();
            
            // Settings
            $table->foreignUuid('direction_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('total_rounds')->default(5);
            $table->unsignedInteger('time_per_question')->default(15); // seconds
            
            // Status
            $table->enum('status', ['waiting', 'in_progress', 'completed', 'cancelled', 'expired'])->default('waiting');
            $table->unsignedInteger('current_round')->default(0);
            
            // Scores
            $table->unsignedInteger('player1_score')->default(0);
            $table->unsignedInteger('player2_score')->default(0);
            
            // Results
            $table->foreignUuid('winner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_draw')->default(false);
            
            // ELO changes
            $table->integer('player1_elo_change')->default(0);
            $table->integer('player2_elo_change')->default(0);
            
            // Rewards
            $table->unsignedInteger('xp_reward')->default(0);
            $table->unsignedInteger('coin_reward')->default(0);
            
            // Timing
            $table->dateTime('started_at')->nullable();
            $table->dateTime('ended_at')->nullable();
            $table->dateTime('expires_at')->nullable();
            
            $table->timestamps();
            
            $table->index(['status', 'created_at']);
            $table->index(['player1_id', 'status']);
            $table->index(['player2_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('battles');
    }
};
