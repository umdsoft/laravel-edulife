<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lab Attempts - Foydalanuvchi urinishlari
     * 
     * Simulation state, measurements, calculations, graphs saqlash
     */
    public function up(): void
    {
        Schema::create('lab_attempts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('experiment_id')->constrained('lab_experiments')->cascadeOnDelete();
            
            // Urinish raqami (foydalanuvchi uchun)
            $table->unsignedSmallInteger('attempt_number');
            
            // ═══════════════════════════════════════════════════════════════
            // VAQT
            // ═══════════════════════════════════════════════════════════════
            $table->timestamp('started_at');
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('time_spent_seconds')->default(0);
            
            // ═══════════════════════════════════════════════════════════════
            // STATUS
            // ═══════════════════════════════════════════════════════════════
            $table->enum('status', ['in_progress', 'paused', 'completed', 'abandoned', 'expired'])
                  ->default('in_progress');
            
            // ═══════════════════════════════════════════════════════════════
            // SIMULYATSIYA HOLATI (Resume uchun)
            // ═══════════════════════════════════════════════════════════════
            $table->json('simulation_state')->nullable();
            /*
             * {
             *   "parameters": {"length": 1.0, "mass": 0.5, "angle": 15, "gravity": 9.8},
             *   "simulation_time": 45.5,
             *   "is_running": false,
             *   "component_positions": {...},
             *   "connections": [...]
             * }
             */
            
            // ═══════════════════════════════════════════════════════════════
            // VAZIFALAR PROGRESSI
            // ═══════════════════════════════════════════════════════════════
            $table->unsignedTinyInteger('current_task')->default(1);
            $table->json('tasks_progress')->nullable();
            /*
             * [
             *   {"step": 1, "status": "completed", "score": 10, "max_score": 10, "user_input": null},
             *   {"step": 2, "status": "completed", "score": 8, "max_score": 10, "user_input": "1.98"},
             *   {"step": 3, "status": "in_progress", "started_at": "..."},
             *   {"step": 4, "status": "locked"}
             * ]
             */
            
            $table->unsignedTinyInteger('completed_tasks')->default(0);
            $table->unsignedTinyInteger('total_tasks');
            
            // ═══════════════════════════════════════════════════════════════
            // O'LCHOV MA'LUMOTLARI
            // ═══════════════════════════════════════════════════════════════
            $table->json('measurements')->nullable();
            /*
             * [
             *   {
             *     "id": "period_1",
             *     "name": "period",
             *     "value": 2.01,
             *     "unit": "s",
             *     "timestamp": "...",
             *     "parameters_snapshot": {"length": 1.0, "gravity": 9.8},
             *     "method": "auto"
             *   }
             * ]
             */
            
            // ═══════════════════════════════════════════════════════════════
            // HISOBLAR
            // ═══════════════════════════════════════════════════════════════
            $table->json('calculations')->nullable();
            /*
             * [
             *   {
             *     "formula_id": "period",
             *     "inputs": {"L": 1.0, "g": 9.8},
             *     "calculated_result": 2.007,
             *     "user_result": 2.01,
             *     "is_correct": true,
             *     "error_percent": 0.15
             *   }
             * ]
             */
            
            // ═══════════════════════════════════════════════════════════════
            // GRAFIK MA'LUMOTLARI
            // ═══════════════════════════════════════════════════════════════
            $table->json('graph_data')->nullable();
            /*
             * {
             *   "angle_time": {"points": [[0, 15], [0.1, 14.8], ...], "max_points": 1000},
             *   "period_length": {"points": [[0.5, 1.42], [1.0, 2.01], [1.5, 2.46]]}
             * }
             */
            
            // ═══════════════════════════════════════════════════════════════
            // NATIJA
            // ═══════════════════════════════════════════════════════════════
            $table->unsignedSmallInteger('raw_score')->default(0);
            $table->unsignedSmallInteger('max_score');
            $table->decimal('percentage', 5, 2)->default(0);
            
            // Grade
            $table->string('grade', 5)->nullable(); // A+, A, B+, B, C+, C, D, F
            $table->decimal('grade_points', 3, 2)->nullable(); // 4.0, 3.7, 3.3, etc.
            $table->boolean('passed')->default(false);
            
            // ═══════════════════════════════════════════════════════════════
            // MUKOFOTLAR
            // ═══════════════════════════════════════════════════════════════
            $table->unsignedSmallInteger('xp_earned')->default(0);
            $table->unsignedSmallInteger('coins_earned')->default(0);
            $table->json('badges_earned')->nullable(); // UUID array
            $table->json('achievements_unlocked')->nullable(); // UUID array
            
            // ═══════════════════════════════════════════════════════════════
            // SCREENSHOTLAR VA YOZUVLAR
            // ═══════════════════════════════════════════════════════════════
            $table->json('screenshots')->nullable();
            /*
             * [{"url": "...", "timestamp": "...", "step": 3, "caption": "Period o'lchash"}]
             */
            $table->string('screen_recording_url', 500)->nullable();
            
            // ═══════════════════════════════════════════════════════════════
            // FOYDALANUVCHI SHARHLARI
            // ═══════════════════════════════════════════════════════════════
            $table->text('user_notes')->nullable();
            $table->text('conclusion_text')->nullable();
            $table->text('error_analysis')->nullable();
            
            // ═══════════════════════════════════════════════════════════════
            // DEVICE INFO
            // ═══════════════════════════════════════════════════════════════
            $table->string('device_type', 20)->nullable(); // desktop, tablet, mobile
            $table->string('browser', 100)->nullable();
            $table->string('screen_resolution', 20)->nullable();
            $table->string('ip_address', 45)->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('user_id');
            $table->index('experiment_id');
            $table->index('status');
            $table->index(['user_id', 'experiment_id']);
            $table->index(['user_id', 'status']);
            $table->index('started_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_attempts');
    }
};
