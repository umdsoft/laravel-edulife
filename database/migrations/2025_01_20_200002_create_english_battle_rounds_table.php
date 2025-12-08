<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('english_battle_rounds', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('battle_id');

            // Round info
            $table->integer('round_number');

            // Question
            $table->uuid('question_id')->nullable();
            $table->json('question_data');

            // Player 1 answer
            $table->string('player1_answer')->nullable();
            $table->boolean('player1_correct')->nullable();
            $table->integer('player1_time_ms')->nullable();
            $table->integer('player1_points')->default(0);
            $table->timestamp('player1_answered_at')->nullable();

            // Player 2 answer
            $table->string('player2_answer')->nullable();
            $table->boolean('player2_correct')->nullable();
            $table->integer('player2_time_ms')->nullable();
            $table->integer('player2_points')->default(0);
            $table->timestamp('player2_answered_at')->nullable();

            // Round winner
            $table->foreignUuid('round_winner_id')->nullable()->constrained('users')->onDelete('set null');

            // Status
            $table->enum('status', ['pending', 'active', 'completed'])->default('pending');

            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('battle_id')->references('id')->on('english_battles')->onDelete('cascade');

            $table->unique(['battle_id', 'round_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('english_battle_rounds');
    }
};
