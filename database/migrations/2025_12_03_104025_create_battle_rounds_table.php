<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('battle_rounds', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('battle_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('question_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('round_number');
            
            // Player 1 answer
            $table->json('player1_answer')->nullable();
            $table->boolean('player1_correct')->nullable();
            $table->unsignedInteger('player1_time')->nullable(); // milliseconds
            
            // Player 2 answer
            $table->json('player2_answer')->nullable();
            $table->boolean('player2_correct')->nullable();
            $table->unsignedInteger('player2_time')->nullable();
            
            // Round winner
            $table->foreignUuid('round_winner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_draw')->default(false);
            
            // Points awarded
            $table->unsignedInteger('player1_points')->default(0);
            $table->unsignedInteger('player2_points')->default(0);
            
            $table->dateTime('started_at')->nullable();
            $table->dateTime('ended_at')->nullable();
            
            $table->timestamps();
            
            $table->unique(['battle_id', 'round_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('battle_rounds');
    }
};
