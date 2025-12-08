<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('english_game_rewards', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('game_id');
            $table->enum('reward_type', ['first_play', 'high_score', 'perfect_score', 'streak', 'speed', 'daily_play', 'level_complete', 'achievement']);
            $table->json('condition')->nullable();
            $table->integer('xp_reward')->default(0);
            $table->integer('coin_reward')->default(0);
            $table->string('badge_id')->nullable();
            $table->string('achievement_id')->nullable();
            $table->string('title', 100);
            $table->string('title_uz', 100)->nullable();
            $table->text('description')->nullable();
            $table->string('icon', 50)->nullable();
            $table->boolean('is_one_time')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('game_id')->references('id')->on('english_games')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('english_game_rewards');
    }
};
