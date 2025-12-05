<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lab User Progress - Foydalanuvchi progressi
     * 
     * Umumiy statistika, kategoriya bo'yicha progress, skills, streaks
     */
    public function up(): void
    {
        Schema::create('lab_user_progress', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->unique()->constrained('users')->cascadeOnDelete();
            
            // ═══════════════════════════════════════════════════════════════
            // UMUMIY STATISTIKA
            // ═══════════════════════════════════════════════════════════════
            $table->unsignedSmallInteger('total_experiments')->default(0);
            $table->unsignedSmallInteger('completed_experiments')->default(0);
            $table->unsignedSmallInteger('perfect_scores')->default(0); // 100% score
            
            $table->unsignedInteger('total_attempts')->default(0);
            $table->unsignedInteger('total_time_spent_seconds')->default(0);
            
            $table->decimal('overall_avg_score', 5, 2)->default(0);
            $table->decimal('overall_completion_rate', 5, 2)->default(0);
            
            // ═══════════════════════════════════════════════════════════════
            // KATEGORIYA BO'YICHA PROGRESS
            // ═══════════════════════════════════════════════════════════════
            $table->json('category_progress')->nullable();
            /*
             * {
             *   "mechanics": {
             *     "total": 10, "completed": 7, "in_progress": 1, "locked": 2,
             *     "avg_score": 85.5, "total_time_seconds": 7200,
             *     "best_experiment": {"id": "...", "score": 98}
             *   },
             *   "electricity": {...}
             * }
             */
            
            // ═══════════════════════════════════════════════════════════════
            // SINF BO'YICHA PROGRESS
            // ═══════════════════════════════════════════════════════════════
            $table->json('grade_progress')->nullable();
            /*
             * {
             *   "6": {"total": 5, "completed": 5, "avg_score": 90.0},
             *   "7": {"total": 8, "completed": 6, "avg_score": 82.5}
             * }
             */
            
            // ═══════════════════════════════════════════════════════════════
            // ENG YAXSHI NATIJALAR (Top 10)
            // ═══════════════════════════════════════════════════════════════
            $table->json('best_experiments')->nullable();
            /*
             * [
             *   {
             *     "experiment_id": "...",
             *     "experiment_title": "Mayatnik",
             *     "score": 98,
             *     "percentage": 98.0,
             *     "completed_at": "...",
             *     "attempt_id": "..."
             *   }
             * ]
             */
            
            // ═══════════════════════════════════════════════════════════════
            // SKILLS (Ko'nikmalar)
            // ═══════════════════════════════════════════════════════════════
            $table->json('skills')->nullable();
            /*
             * {
             *   "measurement_accuracy": {"level": 3, "xp": 450, "next_level_xp": 500},
             *   "calculation_speed": {"level": 2, "xp": 280},
             *   "graph_analysis": {"level": 4, "xp": 720},
             *   "report_writing": {"level": 2, "xp": 190}
             * }
             */
            
            // ═══════════════════════════════════════════════════════════════
            // MUKOFOTLAR
            // ═══════════════════════════════════════════════════════════════
            $table->unsignedInteger('total_xp')->default(0);
            $table->unsignedSmallInteger('lab_level')->default(1);
            $table->unsignedInteger('level_xp')->default(0); // Current level XP
            $table->unsignedInteger('next_level_xp')->default(100); // XP needed for next
            
            $table->unsignedInteger('total_coins_earned')->default(0);
            
            $table->json('badges_earned')->nullable(); // UUID array
            $table->json('achievements_unlocked')->nullable(); // UUID array
            
            // ═══════════════════════════════════════════════════════════════
            // STREAK
            // ═══════════════════════════════════════════════════════════════
            $table->unsignedSmallInteger('current_streak')->default(0);
            $table->unsignedSmallInteger('longest_streak')->default(0);
            $table->date('last_lab_date')->nullable();
            
            // ═══════════════════════════════════════════════════════════════
            // HAFTALIK STATISTIKA
            // ═══════════════════════════════════════════════════════════════
            $table->unsignedSmallInteger('weekly_labs_completed')->default(0);
            $table->unsignedInteger('weekly_xp_earned')->default(0);
            $table->timestamp('weekly_reset_at')->nullable();
            
            // ═══════════════════════════════════════════════════════════════
            // PREFERENCES
            // ═══════════════════════════════════════════════════════════════
            $table->string('preferred_language', 5)->default('uz');
            $table->boolean('show_hints')->default(true);
            $table->boolean('show_formulas')->default(true);
            $table->boolean('auto_save')->default(true);
            $table->boolean('sound_enabled')->default(true);
            $table->boolean('show_tutorial')->default(true);
            
            $table->timestamps();
            
            // Indexes
            $table->index('total_xp');
            $table->index('lab_level');
            $table->index('current_streak');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_user_progress');
    }
};
