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
        Schema::create('student_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->unique()->constrained()->cascadeOnDelete();
            
            // Gamification
            $table->unsignedBigInteger('xp')->default(0);
            $table->unsignedInteger('level')->default(1);
            $table->unsignedInteger('streak_days')->default(0);
            $table->date('last_activity_date')->nullable();
            $table->unsignedInteger('longest_streak')->default(0);
            
            // Battle/Ranking
            $table->unsignedInteger('elo_rating')->default(1000);
            $table->string('rank')->default('bronze'); // bronze, silver, gold, platinum, diamond, master
            $table->unsignedInteger('battles_won')->default(0);
            $table->unsignedInteger('battles_lost')->default(0);
            $table->unsignedInteger('battles_draw')->default(0);
            
            // COIN
            $table->unsignedBigInteger('coins')->default(0);
            $table->unsignedBigInteger('total_coins_earned')->default(0);
            
            // Stats
            $table->unsignedInteger('courses_completed')->default(0);
            $table->unsignedInteger('lessons_completed')->default(0);
            $table->unsignedInteger('tests_passed')->default(0);
            $table->unsignedBigInteger('total_watch_time')->default(0); // seconds
            $table->unsignedInteger('certificates_earned')->default(0);
            
            // Preferences
            $table->json('interests')->nullable(); // direction IDs
            $table->string('preferred_language')->default('uz');
            $table->boolean('email_notifications')->default(true);
            $table->boolean('push_notifications')->default(true);
            
            $table->timestamps();
            
            $table->index('xp');
            $table->index('level');
            $table->index('elo_rating');
            $table->index('streak_days');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_profiles');
    }
};
