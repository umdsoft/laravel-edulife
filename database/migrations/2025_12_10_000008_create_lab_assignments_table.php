<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lab Assignments - O'qituvchi topshiriqlari
     * 
     * O'qituvchi sinf/guruhga tajriba topshirig'i berishi
     */
    public function up(): void
    {
        Schema::create('lab_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Kim berdi
            $table->foreignUuid('teacher_id')->constrained('users')->cascadeOnDelete();
            
            // Qaysi sinf/guruh uchun (ixtiyoriy) - FK qo'shilmaydi chunki jadvallar mavjud bo'lmasligi mumkin
            $table->char('class_id', 36)->nullable();
            $table->char('group_id', 36)->nullable();
            $table->json('student_ids')->nullable(); // Individual students - UUID array
            
            // Qaysi tajriba
            $table->foreignUuid('experiment_id')->constrained('lab_experiments')->cascadeOnDelete();
            
            // Vazifa ma'lumotlari
            $table->string('title', 255);
            $table->text('instructions')->nullable();
            
            // Muddat
            $table->timestamp('assigned_at')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->boolean('late_submission_allowed')->default(false);
            $table->unsignedTinyInteger('late_penalty_percent')->default(10);
            
            // Talablar
            $table->unsignedTinyInteger('min_score')->default(60);
            $table->boolean('require_report')->default(true);
            $table->boolean('require_conclusion')->default(true);
            $table->unsignedSmallInteger('min_time_minutes')->nullable();
            
            // Baholash
            $table->unsignedTinyInteger('max_grade')->default(100);
            $table->decimal('grade_weight', 5, 2)->default(1.0);
            
            // Custom settings
            $table->json('custom_config')->nullable();
            /*
             * {
             *   "locked_parameters": ["gravity"],
             *   "required_measurements": ["period", "frequency"],
             *   "min_data_points": 5,
             *   "show_theoretical": false
             * }
             */
            
            // Notifications
            $table->boolean('notify_on_submit')->default(true);
            $table->unsignedSmallInteger('reminder_before_hours')->default(24);
            $table->boolean('reminder_sent')->default(false);
            
            // Status
            $table->enum('status', ['draft', 'active', 'closed', 'grading', 'graded', 'archived'])
                  ->default('active');
            
            // Statistics (cached)
            $table->unsignedSmallInteger('total_assigned')->default(0);
            $table->unsignedSmallInteger('total_submitted')->default(0);
            $table->unsignedSmallInteger('total_graded')->default(0);
            $table->decimal('avg_score', 5, 2)->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('teacher_id');
            $table->index('experiment_id');
            $table->index('due_at');
            $table->index('status');
            $table->index(['teacher_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_assignments');
    }
};
