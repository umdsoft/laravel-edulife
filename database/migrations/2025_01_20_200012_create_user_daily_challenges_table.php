<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_daily_challenges', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->uuid('daily_challenge_id');

            // Progress
            $table->json('tasks_progress')->nullable();
            $table->integer('tasks_completed')->default(0);
            $table->integer('total_tasks')->default(0);
            $table->boolean('all_completed')->default(false);

            // Rewards
            $table->integer('xp_earned')->default(0);
            $table->integer('coins_earned')->default(0);
            $table->boolean('bonus_claimed')->default(false);

            // Timestamps
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            $table->foreign('daily_challenge_id')->references('id')->on('english_daily_challenges')->onDelete('cascade');

            $table->unique(['user_id', 'daily_challenge_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_daily_challenges');
    }
};
