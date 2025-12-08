<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('english_achievements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('category_id');

            // Identification
            $table->string('slug', 100)->unique();
            $table->string('code', 50)->unique();

            // Info
            $table->string('name', 150);
            $table->string('name_uz', 150);
            $table->text('description');
            $table->text('description_uz');

            // Tiers
            $table->enum('tier', ['bronze', 'silver', 'gold', 'platinum', 'diamond'])->default('bronze');
            $table->integer('tier_level')->default(1);

            // Requirements
            $table->enum('requirement_type', ['count', 'streak', 'score', 'time', 'level', 'battle', 'special']);
            $table->string('requirement_key', 100);
            $table->integer('requirement_value');
            $table->json('requirement_config')->nullable();

            // Rewards
            $table->integer('xp_reward')->default(0);
            $table->integer('coin_reward')->default(0);
            $table->integer('gem_reward')->default(0);
            $table->string('badge_image')->nullable();
            $table->string('badge_color', 20)->nullable();

            // Special rewards
            $table->json('special_rewards')->nullable();

            // Visual
            $table->string('icon', 50)->nullable();
            $table->string('locked_icon', 50)->nullable();

            // Rarity
            $table->enum('rarity', ['common', 'uncommon', 'rare', 'epic', 'legendary'])->default('common');
            $table->decimal('unlock_percentage', 5, 2)->default(0);

            // Progressive
            $table->uuid('next_tier_id')->nullable();
            $table->uuid('previous_tier_id')->nullable();

            // Display
            $table->boolean('is_secret')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('order_number')->default(0);

            $table->timestamps();

            $table->foreign('category_id')->references('id')->on('english_achievement_categories')->onDelete('cascade');

            $table->index(['requirement_type', 'requirement_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('english_achievements');
    }
};
