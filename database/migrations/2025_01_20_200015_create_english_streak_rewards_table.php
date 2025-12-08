<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('english_streak_rewards', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Streak milestone
            $table->integer('streak_days')->unique();

            // Info
            $table->string('title', 100);
            $table->string('title_uz', 100);
            $table->text('message')->nullable();
            $table->text('message_uz')->nullable();

            // Rewards
            $table->integer('xp_reward')->default(0);
            $table->integer('coin_reward')->default(0);
            $table->integer('gem_reward')->default(0);
            $table->integer('streak_freeze_reward')->default(0);

            // Special rewards
            $table->string('badge_id')->nullable();
            $table->json('special_rewards')->nullable();

            // Visual
            $table->string('icon', 50)->nullable();
            $table->string('animation')->nullable();

            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('english_streak_rewards');
    }
};
