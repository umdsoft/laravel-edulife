<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('english_daily_challenges', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Date
            $table->date('challenge_date')->unique();

            // Theme
            $table->string('theme', 100)->nullable();
            $table->string('theme_uz', 100)->nullable();
            $table->string('theme_icon', 50)->nullable();

            // Total rewards
            $table->integer('total_xp_reward')->default(100);
            $table->integer('total_coin_reward')->default(50);
            $table->integer('bonus_xp')->default(50);

            // Special
            $table->boolean('is_special')->default(false);
            $table->json('special_config')->nullable();

            // Statistics
            $table->integer('participants_count')->default(0);
            $table->integer('completions_count')->default(0);
            $table->decimal('completion_rate', 5, 2)->default(0);

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['challenge_date', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('english_daily_challenges');
    }
};
