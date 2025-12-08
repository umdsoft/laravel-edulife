<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_study_streaks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->date('date');
            $table->integer('day_number');
            $table->integer('xp_earned')->default(0);
            $table->integer('minutes_studied')->default(0);
            $table->integer('lessons_completed')->default(0);
            $table->integer('words_reviewed')->default(0);
            $table->integer('games_played')->default(0);
            $table->boolean('daily_goal_met')->default(false);
            $table->json('challenges_completed')->nullable();
            $table->boolean('used_freeze')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'date']);
            $table->index(['user_id', 'day_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_study_streaks');
    }
};
