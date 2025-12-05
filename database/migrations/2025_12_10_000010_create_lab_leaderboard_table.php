<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lab Leaderboard - Reyting jadvali
     * 
     * Kunlik, haftalik, oylik, yillik, umumiy reytinglar
     */
    public function up(): void
    {
        Schema::create('lab_leaderboard', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            
            // Period
            $table->enum('period_type', ['daily', 'weekly', 'monthly', 'yearly', 'all_time']);
            $table->date('period_start');
            $table->date('period_end');
            
            // Statistika
            $table->unsignedSmallInteger('labs_completed')->default(0);
            $table->unsignedInteger('total_score')->default(0);
            $table->decimal('avg_score', 5, 2)->default(0);
            $table->unsignedInteger('total_xp')->default(0);
            $table->unsignedSmallInteger('perfect_scores')->default(0);
            $table->unsignedInteger('total_time_seconds')->default(0);
            
            // Rank
            $table->unsignedInteger('rank');
            $table->unsignedInteger('previous_rank')->nullable();
            $table->integer('rank_change')->default(0); // +5, -3, etc.
            
            // Category best
            $table->string('best_category', 50)->nullable();
            $table->decimal('best_category_score', 5, 2)->nullable();
            
            // Cached user info (for fast display)
            $table->string('user_name', 255);
            $table->string('user_avatar', 500)->nullable();
            $table->unsignedSmallInteger('user_level')->default(1);
            $table->string('user_school', 255)->nullable();
            $table->string('user_region', 100)->nullable();
            $table->string('user_grade', 10)->nullable();
            
            $table->timestamp('updated_at');
            
            // Unique per user per period
            $table->unique(['user_id', 'period_type', 'period_start']);
            
            // Indexes
            $table->index(['period_type', 'period_start']);
            $table->index(['period_type', 'period_start', 'rank']);
            $table->index('total_xp');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_leaderboard');
    }
};
