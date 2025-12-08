<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_english_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->uuid('current_level_id');
            $table->uuid('current_unit_id')->nullable();
            $table->uuid('current_lesson_id')->nullable();
            $table->bigInteger('total_xp')->default(0);
            $table->bigInteger('current_level_xp')->default(0);
            $table->bigInteger('coins')->default(0);
            $table->bigInteger('gems')->default(0);
            $table->integer('elo_rating')->default(1000);
            $table->integer('battles_played')->default(0);
            $table->integer('battles_won')->default(0);
            $table->integer('battles_lost')->default(0);
            $table->integer('win_streak')->default(0);
            $table->integer('best_win_streak')->default(0);
            $table->integer('words_learned')->default(0);
            $table->integer('words_mastered')->default(0);
            $table->integer('lessons_completed')->default(0);
            $table->integer('units_completed')->default(0);
            $table->integer('games_played')->default(0);
            $table->integer('tests_passed')->default(0);
            $table->integer('ai_conversations')->default(0);
            $table->integer('total_study_minutes')->default(0);
            $table->integer('today_study_minutes')->default(0);
            $table->date('last_study_date')->nullable();
            $table->integer('current_streak')->default(0);
            $table->integer('longest_streak')->default(0);
            $table->date('streak_start_date')->nullable();
            $table->boolean('streak_protected_today')->default(false);
            $table->integer('streak_freezes_available')->default(0);
            $table->boolean('placement_test_completed')->default(false);
            $table->timestamp('placement_test_date')->nullable();
            $table->json('placement_test_results')->nullable();
            $table->integer('daily_xp_goal')->default(50);
            $table->integer('today_xp_earned')->default(0);
            $table->json('daily_challenges_completed')->nullable();
            $table->json('preferences')->nullable();
            $table->decimal('target_ielts_band', 2, 1)->nullable();
            $table->decimal('estimated_ielts_band', 2, 1)->nullable();
            $table->timestamps();

            $table->foreign('current_level_id')->references('id')->on('english_levels');
            $table->foreign('current_unit_id')->references('id')->on('english_units')->onDelete('set null');
            $table->foreign('current_lesson_id')->references('id')->on('english_lessons')->onDelete('set null');
            $table->unique('user_id');
            $table->index('elo_rating');
            $table->index('total_xp');
            $table->index('current_streak');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_english_profiles');
    }
};
