<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('english_battles', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Players
            $table->foreignUuid('player1_id')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('player2_id')->nullable()->constrained('users')->onDelete('cascade');

            // Battle type
            $table->enum('battle_type', ['ranked', 'friendly', 'practice', 'tournament'])->default('ranked');

            // Level & Topic
            $table->uuid('level_id')->nullable();
            $table->enum('topic_focus', ['vocabulary', 'grammar', 'mixed'])->default('mixed');

            // ELO ratings
            $table->integer('player1_elo_before')->default(1000);
            $table->integer('player2_elo_before')->default(1000);
            $table->integer('player1_elo_after')->nullable();
            $table->integer('player2_elo_after')->nullable();
            $table->integer('elo_change')->nullable();

            // Scores
            $table->integer('player1_score')->default(0);
            $table->integer('player2_score')->default(0);
            $table->integer('player1_correct')->default(0);
            $table->integer('player2_correct')->default(0);

            // Time tracking
            $table->integer('player1_total_time_ms')->default(0);
            $table->integer('player2_total_time_ms')->default(0);
            $table->decimal('player1_avg_time', 8, 2)->default(0);
            $table->decimal('player2_avg_time', 8, 2)->default(0);

            // Winner
            $table->foreignUuid('winner_id')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('result', ['player1_win', 'player2_win', 'draw', 'player1_forfeit', 'player2_forfeit', 'cancelled'])->nullable();

            // Settings
            $table->json('settings')->nullable();

            // Rewards
            $table->integer('winner_xp')->default(0);
            $table->integer('loser_xp')->default(0);
            $table->integer('winner_coins')->default(0);
            $table->integer('loser_coins')->default(0);

            // Status
            $table->enum('status', ['waiting', 'ready', 'in_progress', 'completed', 'cancelled', 'expired'])->default('waiting');

            // Tournament reference
            $table->uuid('tournament_id')->nullable();
            $table->uuid('tournament_round_id')->nullable();

            // Timestamps
            $table->timestamp('matched_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('level_id')->references('id')->on('english_levels')->onDelete('set null');

            $table->index(['status', 'created_at']);
            $table->index(['player1_id', 'status']);
            $table->index(['player2_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('english_battles');
    }
};
