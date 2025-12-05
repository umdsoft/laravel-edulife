<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lab Reports - Laboratoriya hisobotlari
     * 
     * PDF export, o'qituvchi baholashi, batafsil tahlil
     */
    public function up(): void
    {
        Schema::create('lab_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('attempt_id')->constrained('lab_attempts')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('experiment_id')->constrained('lab_experiments')->cascadeOnDelete();
            
            // ═══════════════════════════════════════════════════════════════
            // HISOBOT STRUKTURASI
            // ═══════════════════════════════════════════════════════════════
            
            // 1. Sarlavha
            $table->string('title', 255);
            
            // 2. Kirish
            $table->text('introduction')->nullable();
            
            // 3. Maqsad
            $table->text('objectives_text')->nullable();
            
            // 4. Nazariy asos
            $table->text('theory_summary')->nullable();
            $table->json('formulas_used')->nullable();
            
            // 5. Asboblar ro'yxati
            $table->json('equipment_list')->nullable();
            
            // 6. Ish tartibi
            $table->json('procedure_steps')->nullable();
            /*
             * [
             *   {"step": 1, "description": "Ip uzunligini 1 m ga o'rnatdim", "timestamp": "..."},
             *   {"step": 2, "description": "Mayatnikni 15° ga og'irdim"}
             * ]
             */
            
            // 7. Kuzatishlar va o'lchov natijalari
            $table->json('observations')->nullable();
            /*
             * {"text": "Mayatnik tekis tebranayotganini kuzatdim...", "notes": [...]}
             */
            
            $table->json('data_tables')->nullable();
            /*
             * [
             *   {
             *     "title": "Period va uzunlik o'lchovlari",
             *     "columns": [...],
             *     "rows": [{"n": 1, "L": 0.5, "T": 1.42, "T2": 2.02}, ...]
             *   }
             * ]
             */
            
            // 8. Grafiklar
            $table->json('graphs')->nullable();
            /*
             * [{"id": "period_vs_length", "title": "T² - L bog'liqligi", "image_url": "..."}]
             */
            
            // 9. Hisob-kitoblar
            $table->text('calculations_detail')->nullable();
            
            // 10. Natijalar
            $table->json('results_summary')->nullable();
            /*
             * {
             *   "measured_values": [{"name": "g", "value": 9.76, "unit": "m/s²"}],
             *   "theoretical_values": [...],
             *   "errors": [{"name": "Nisbiy xato", "value": 0.41, "unit": "%"}]
             * }
             */
            
            // 11. Xato tahlili
            $table->text('error_analysis')->nullable();
            
            // 12. Xulosa
            $table->text('conclusion')->nullable();
            
            // 13. Savollarga javoblar
            $table->json('questions_answers')->nullable();
            
            // ═══════════════════════════════════════════════════════════════
            // STATUS
            // ═══════════════════════════════════════════════════════════════
            $table->enum('status', ['draft', 'submitted', 'graded', 'returned', 'approved'])
                  ->default('draft');
            
            // ═══════════════════════════════════════════════════════════════
            // YUBORISH
            // ═══════════════════════════════════════════════════════════════
            $table->timestamp('submitted_at')->nullable();
            $table->text('submission_notes')->nullable();
            
            // ═══════════════════════════════════════════════════════════════
            // BAHOLASH
            // ═══════════════════════════════════════════════════════════════
            $table->boolean('is_graded')->default(false);
            $table->foreignUuid('graded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('graded_at')->nullable();
            
            $table->json('grading_rubric')->nullable();
            /*
             * {
             *   "criteria": [
             *     {"name": "Maqsad va kirish", "max_points": 10, "points": 9, "feedback": "..."},
             *     {"name": "O'lchov aniqligi", "max_points": 20, "points": 18}
             *   ],
             *   "total_points": 70,
             *   "max_points": 80
             * }
             */
            
            $table->unsignedTinyInteger('teacher_grade')->nullable(); // 0-100
            $table->text('teacher_feedback')->nullable();
            $table->string('feedback_audio_url', 500)->nullable();
            
            // ═══════════════════════════════════════════════════════════════
            // PDF EXPORT
            // ═══════════════════════════════════════════════════════════════
            $table->boolean('pdf_generated')->default(false);
            $table->string('pdf_path', 500)->nullable();
            $table->timestamp('pdf_generated_at')->nullable();
            $table->unsignedTinyInteger('pdf_version')->default(1);
            
            // Premium features
            $table->boolean('is_premium_export')->default(false);
            
            $table->timestamps();
            
            // Ensure one report per attempt
            $table->unique('attempt_id');
            
            // Indexes
            $table->index('user_id');
            $table->index('experiment_id');
            $table->index('status');
            $table->index('is_graded');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_reports');
    }
};
