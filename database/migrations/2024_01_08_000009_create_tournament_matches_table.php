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
        Schema::create('tournament_matches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tournament_id')->constrained()->onDelete('cascade');
            $table->integer('round'); // 1, 2, 3... (Final = max round)
            $table->string('round_name', 100)->nullable(); // "Round of 64", "Quarter Finals", "Final"
            $table->integer('match_number'); // Bu rounddagi match raqami
            $table->string('bracket_position', 50)->nullable(); // "upper_1", "lower_3", "final"
            
            // Players
            $table->foreignUuid('player1_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignUuid('player2_id')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignUuid('winner_id')->nullable()->constrained('users')->onDelete('set null');
            
            // Scores (best of X)
            $table->integer('player1_wins')->default(0);
            $table->integer('player2_wins')->default(0);
            $table->integer('best_of')->default(1); // Best of 1, 3, 5
            
            // Status
            $table->enum('status', ['pending', 'ready', 'in_progress', 'finished', 'walkover', 'cancelled'])->default('pending');
            $table->foreignUuid('battle_id')->nullable()->constrained()->onDelete('set null'); // current battle
            
            // Timing
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
            
            $table->index('tournament_id');
            $table->index(['tournament_id', 'round']);
            $table->index('status');
            $table->index('scheduled_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tournament_matches');
    }
};
