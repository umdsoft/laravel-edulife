<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lab Assignment Submissions - Topshiriq bajarilishi
     */
    public function up(): void
    {
        Schema::create('lab_assignment_submissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('assignment_id')->constrained('lab_assignments')->cascadeOnDelete();
            $table->foreignUuid('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('attempt_id')->nullable()->constrained('lab_attempts')->nullOnDelete();
            $table->foreignUuid('report_id')->nullable()->constrained('lab_reports')->nullOnDelete();
            
            // Status
            $table->enum('status', [
                'pending',       // Hali boshlanmagan
                'in_progress',   // Bajarilmoqda
                'submitted',     // Topshirilgan
                'late_submitted', // Kech topshirilgan
                'graded',        // Baholangan
                'returned',      // Qaytarilgan (to'ldirish kerak)
                'resubmitted'    // Qayta topshirilgan
            ])->default('pending');
            
            // Vaqt
            $table->timestamp('started_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->boolean('is_late')->default(false);
            $table->decimal('late_hours', 10, 2)->nullable();
            
            // Natija (auto from attempt)
            $table->unsignedSmallInteger('auto_score')->nullable();
            $table->decimal('auto_percentage', 5, 2)->nullable();
            
            // O'qituvchi bahosi
            $table->unsignedTinyInteger('teacher_grade')->nullable();
            $table->unsignedTinyInteger('late_penalty')->default(0);
            $table->unsignedTinyInteger('final_grade')->nullable();
            $table->string('grade_letter', 5)->nullable(); // A+, A, B+, etc.
            
            // Feedback
            $table->text('teacher_feedback')->nullable();
            $table->string('feedback_audio_url', 500)->nullable();
            $table->json('feedback_annotations')->nullable(); // Specific comments on parts
            
            // Grading
            $table->foreignUuid('graded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('graded_at')->nullable();
            
            // Resubmission
            $table->boolean('resubmission_allowed')->default(false);
            $table->timestamp('resubmission_deadline')->nullable();
            $table->unsignedTinyInteger('resubmission_count')->default(0);
            $table->unsignedTinyInteger('max_resubmissions')->default(1);
            
            // Student notes
            $table->text('student_notes')->nullable();
            
            $table->timestamps();
            
            // One submission per student per assignment
            $table->unique(['assignment_id', 'student_id']);
            
            // Indexes
            $table->index('assignment_id');
            $table->index('student_id');
            $table->index('status');
            $table->index(['assignment_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_assignment_submissions');
    }
};
