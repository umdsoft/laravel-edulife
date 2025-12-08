<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('english_leaderboards', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Identification
            $table->string('slug', 50)->unique();
            $table->string('code', 50)->unique();

            // Info
            $table->string('name', 100);
            $table->string('name_uz', 100);
            $table->text('description')->nullable();

            // Type
            $table->enum('type', [
                'daily_xp',
                'weekly_xp',
                'monthly_xp',
                'all_time_xp',
                'battle_elo',
                'battle_wins',
                'streak',
                'words_learned',
                'level_progress',
                'tournament'
            ]);

            // Period
            $table->enum('period', ['daily', 'weekly', 'monthly', 'all_time', 'custom'])->default('weekly');

            // Custom periods
            $table->timestamp('period_start')->nullable();
            $table->timestamp('period_end')->nullable();

            // Scope
            $table->uuid('level_id')->nullable();
            $table->string('country_code', 5)->nullable();

            // Display
            $table->integer('display_count')->default(100);
            $table->boolean('show_position_change')->default(true);

            // Rewards
            $table->json('position_rewards')->nullable();

            // Reset
            $table->timestamp('last_reset_at')->nullable();
            $table->timestamp('next_reset_at')->nullable();

            // Visual
            $table->string('icon', 50)->nullable();
            $table->string('color', 20)->nullable();

            $table->boolean('is_active')->default(true);
            $table->integer('order_number')->default(0);

            $table->timestamps();

            $table->foreign('level_id')->references('id')->on('english_levels')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('english_leaderboards');
    }
};
