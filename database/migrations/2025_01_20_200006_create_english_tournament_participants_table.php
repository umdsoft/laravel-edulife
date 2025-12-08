<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('english_tournament_participants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('tournament_id');
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');

            // Registration
            $table->integer('seed_number')->nullable();
            $table->integer('elo_at_registration');
            $table->timestamp('registered_at');

            // Entry fee
            $table->boolean('entry_fee_paid')->default(false);
            $table->timestamp('paid_at')->nullable();

            // Progress
            $table->integer('current_round')->default(0);
            $table->integer('wins')->default(0);
            $table->integer('losses')->default(0);
            $table->integer('total_score')->default(0);
            $table->integer('matches_played')->default(0);

            // Elimination
            $table->boolean('is_eliminated')->default(false);
            $table->integer('eliminated_in_round')->nullable();
            $table->uuid('eliminated_by_battle_id')->nullable();

            // Final position
            $table->integer('final_position')->nullable();

            // Prizes
            $table->json('prizes_received')->nullable();
            $table->boolean('prizes_claimed')->default(false);

            // Status
            $table->enum('status', [
                'registered',
                'confirmed',
                'active',
                'eliminated',
                'winner',
                'withdrawn',
                'disqualified'
            ])->default('registered');

            $table->timestamps();

            $table->foreign('tournament_id')->references('id')->on('english_tournaments')->onDelete('cascade');

            $table->unique(['tournament_id', 'user_id']);
            $table->index(['tournament_id', 'status']);
            $table->index(['tournament_id', 'seed_number']);
        });

        // Add foreign key to battles table for tournament
        Schema::table('english_battles', function (Blueprint $table) {
            $table->foreign('tournament_id')->references('id')->on('english_tournaments')->onDelete('set null');
            $table->foreign('tournament_round_id')->references('id')->on('english_tournament_rounds')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('english_battles', function (Blueprint $table) {
            $table->dropForeign(['tournament_id']);
            $table->dropForeign(['tournament_round_id']);
        });
        Schema::dropIfExists('english_tournament_participants');
    }
};
