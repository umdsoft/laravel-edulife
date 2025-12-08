<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('english_rewards', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Identification
            $table->string('slug', 100)->unique();
            $table->string('code', 50)->unique();

            // Info
            $table->string('name', 150);
            $table->string('name_uz', 150);
            $table->text('description')->nullable();
            $table->text('description_uz')->nullable();

            // Type
            $table->enum('reward_type', [
                'daily_login',
                'weekly_bonus',
                'monthly_bonus',
                'level_up',
                'achievement',
                'streak_milestone',
                'special_event',
                'referral',
                'purchase'
            ]);

            // Rewards
            $table->integer('xp_amount')->default(0);
            $table->integer('coin_amount')->default(0);
            $table->integer('gem_amount')->default(0);
            $table->integer('streak_freeze_amount')->default(0);
            $table->json('bonus_items')->nullable();

            // Conditions
            $table->json('conditions')->nullable();

            // Availability
            $table->timestamp('available_from')->nullable();
            $table->timestamp('available_until')->nullable();
            $table->boolean('is_recurring')->default(false);
            $table->string('recurrence_period')->nullable();

            // Visual
            $table->string('icon', 50)->nullable();
            $table->string('image')->nullable();
            $table->string('color', 20)->nullable();

            // Limits
            $table->integer('max_claims_per_user')->nullable();
            $table->integer('total_available')->nullable();
            $table->integer('total_claimed')->default(0);

            $table->boolean('is_active')->default(true);
            $table->integer('order_number')->default(0);

            $table->timestamps();

            $table->index(['reward_type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('english_rewards');
    }
};
