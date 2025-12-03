<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournament_matches', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tournament_id')->constrained()->cascadeOnDelete();
            
            // Round info
            $table->unsignedInteger('round_number');
            $table->unsignedInteger('match_number');
            $table->string('bracket_position')->nullable(); // e.g., "R1M1", "QF1", "SF1", "F"
            
            // Participants
            $table->foreignUuid('participant1_id')->nullable()->constrained('tournament_participants')->nullOnDelete();
            $table->foreignUuid('participant2_id')->nullable()->constrained('tournament_participants')->nullOnDelete();
            
            // Scores
            $table->unsignedInteger('participant1_score')->default(0);
            $table->unsignedInteger('participant2_score')->default(0);
            
            // Result
            $table->foreignUuid('winner_id')->nullable()->constrained('tournament_participants')->nullOnDelete();
            $table->boolean('is_bye')->default(false); // No opponent
            
            // Status
            $table->enum('status', ['pending', 'in_progress', 'completed'])->default('pending');
            
            // Linked battle
            $table->foreignUuid('battle_id')->nullable()->constrained()->nullOnDelete();
            
            // Timing
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('started_at')->nullable();
            $table->dateTime('ended_at')->nullable();
            
            $table->timestamps();
            
            $table->index(['tournament_id', 'round_number']);
            $table->index(['tournament_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_matches');
    }
};
