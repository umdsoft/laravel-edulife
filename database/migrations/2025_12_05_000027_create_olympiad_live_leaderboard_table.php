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
        Schema::create('olympiad_live_leaderboard', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('olympiad_id')->constrained('olympiads')->onDelete('cascade');
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('attempt_id')->constrained('olympiad_attempts')->onDelete('cascade');
            $table->integer('rank');
            $table->integer('previous_rank')->nullable();
            $table->integer('rank_change')->default(0);
            $table->decimal('score', 10, 2);
            $table->decimal('weighted_score', 10, 2)->default(0);
            $table->decimal('max_score', 10, 2);
            $table->decimal('score_percent', 5, 2);
            $table->integer('time_spent_seconds');
            $table->integer('questions_answered')->default(0);
            $table->integer('questions_correct')->default(0);
            $table->json('section_scores')->nullable();
            $table->boolean('is_disqualified')->default(false);
            $table->timestamps();
            
            // Unique constraint
            $table->unique(['olympiad_id', 'user_id']);
            
            // Indexes
            $table->index('olympiad_id');
            $table->index(['olympiad_id', 'rank']);
            $table->index(['olympiad_id', 'weighted_score']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('olympiad_live_leaderboard');
    }
};
