<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tournament_participants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('tournament_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            
            // Status
            $table->enum('status', ['registered', 'active', 'eliminated', 'winner'])->default('registered');
            $table->unsignedInteger('current_round')->default(0);
            
            // Stats
            $table->unsignedInteger('matches_played')->default(0);
            $table->unsignedInteger('matches_won')->default(0);
            $table->unsignedInteger('matches_lost')->default(0);
            $table->unsignedInteger('total_score')->default(0);
            
            // Seeding
            $table->unsignedInteger('seed')->nullable();
            $table->unsignedInteger('final_position')->nullable();
            
            // Rewards
            $table->boolean('prize_claimed')->default(false);
            
            $table->dateTime('registered_at');
            $table->dateTime('eliminated_at')->nullable();
            
            $table->timestamps();
            
            $table->unique(['tournament_id', 'user_id']);
            $table->index(['tournament_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tournament_participants');
    }
};
