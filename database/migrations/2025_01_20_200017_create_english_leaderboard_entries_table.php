<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('english_leaderboard_entries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('leaderboard_id');
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');

            // Score
            $table->bigInteger('score')->default(0);
            $table->bigInteger('previous_score')->default(0);

            // Position
            $table->integer('position')->nullable();
            $table->integer('previous_position')->nullable();
            $table->integer('position_change')->default(0);

            // Period tracking
            $table->date('period_date')->nullable();

            // Additional stats
            $table->json('stats')->nullable();

            // Rewards
            $table->boolean('reward_claimed')->default(false);
            $table->timestamp('reward_claimed_at')->nullable();

            $table->timestamp('last_updated_at')->nullable();
            $table->timestamps();

            $table->foreign('leaderboard_id')->references('id')->on('english_leaderboards')->onDelete('cascade');

            $table->unique(['leaderboard_id', 'user_id', 'period_date'], 'lb_entries_unique');
            $table->index(['leaderboard_id', 'score']);
            $table->index(['leaderboard_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('english_leaderboard_entries');
    }
};
