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
        Schema::create('achievements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 50)->unique(); // "first_lesson", "win_streak_5"
            $table->string('title', 100);
            $table->text('description');
            $table->string('icon', 50)->default('🏆'); // emoji yoki icon class
            $table->enum('category', ['learning', 'testing', 'battle', 'tournament', 'streak', 'social', 'special']);
            $table->enum('rarity', ['common', 'rare', 'epic', 'legendary'])->default('common');
            $table->integer('xp_reward')->default(0);
            $table->integer('coin_reward')->default(0);
            $table->json('requirements')->nullable(); // {"type": "lessons_complete", "count": 10}
            $table->boolean('is_hidden')->default(false); // Secret achievements
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            
            $table->index('category');
            $table->index('rarity');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('achievements');
    }
};
