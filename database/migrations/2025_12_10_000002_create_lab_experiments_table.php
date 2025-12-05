<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lab Experiments - Virtual fizika tajribalari
     * 
     * JSONB strukturalar:
     * - objectives: Maqsadlar ro'yxati (ko'p tilli)
     * - theory_introduction: Nazariy kirish
     * - formulas: Formulalar (LaTeX)
     * - required_equipment: Kerakli asboblar
     * - simulation_config: Simulyatsiya konfiguratsiyasi
     * - tasks: Vazifalar (step-by-step)
     */
    public function up(): void
    {
        Schema::create('lab_experiments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('category_id')->constrained('lab_categories')->cascadeOnDelete();
            
            // ═══════════════════════════════════════════════════════════════
            // IDENTIFIKATSIYA
            // ═══════════════════════════════════════════════════════════════
            $table->string('slug', 100)->unique();
            $table->unsignedSmallInteger('experiment_number'); // Lab #1, #2, etc.
            
            // Nomi (ko'p tilli)
            $table->string('title', 255);
            $table->string('title_uz', 255);
            $table->string('title_ru', 255)->nullable();
            
            // Qisqa tavsif (card uchun)
            $table->string('short_description', 500)->nullable();
            $table->string('short_description_uz', 500)->nullable();
            $table->string('short_description_ru', 500)->nullable();
            
            // To'liq tavsif
            $table->text('description')->nullable();
            $table->text('description_uz')->nullable();
            $table->text('description_ru')->nullable();
            
            // ═══════════════════════════════════════════════════════════════
            // SINF VA QIYINLIK
            // ═══════════════════════════════════════════════════════════════
            $table->unsignedTinyInteger('grade_level'); // 6-11
            $table->enum('difficulty', ['easy', 'medium', 'hard'])->default('medium');
            $table->unsignedTinyInteger('difficulty_score')->default(5); // 1-10
            
            // Vaqt (daqiqalarda)
            $table->unsignedSmallInteger('estimated_duration')->default(30);
            $table->unsignedSmallInteger('min_duration')->default(15);
            $table->unsignedSmallInteger('max_duration')->default(60);
            
            // ═══════════════════════════════════════════════════════════════
            // ACCESS CONTROL (FREEMIUM)
            // ═══════════════════════════════════════════════════════════════
            $table->boolean('is_free')->default(false);
            $table->boolean('is_premium')->default(true);
            $table->enum('required_subscription', ['free', 'basic', 'premium', 'school'])
                  ->default('basic');
            
            // Bepul preview
            $table->boolean('free_preview_enabled')->default(true);
            $table->unsignedTinyInteger('free_preview_steps')->default(3);
            
            // ═══════════════════════════════════════════════════════════════
            // MAQSAD VA NAZARIYA
            // ═══════════════════════════════════════════════════════════════
            $table->json('objectives'); // {uz: [...], ru: [...], en: [...]}
            $table->json('theory_introduction')->nullable();
            $table->json('formulas'); // LaTeX formulalar
            $table->json('important_notes')->nullable();
            
            // ═══════════════════════════════════════════════════════════════
            // KERAKLI ASBOBLAR
            // ═══════════════════════════════════════════════════════════════
            $table->json('required_equipment');
            
            // ═══════════════════════════════════════════════════════════════
            // SIMULYATSIYA KONFIGURATSIYASI
            // ═══════════════════════════════════════════════════════════════
            $table->string('simulation_type', 50);
            /*
             * MAVJUD TURLAR:
             * pendulum_simple, pendulum_spring, projectile_motion, free_fall,
             * inclined_plane, friction, lever, pulley, archimedes,
             * circuit_simple, circuit_series, circuit_parallel, ohm_law, electromagnet,
             * lens_converging, lens_diverging, mirror_plane, mirror_curved,
             * refraction, prism, wave_transverse, wave_longitudinal,
             * sound_resonance, doppler, magnetic_field, electromagnetic_induction,
             * atom_model, radioactive_decay
             */
            $table->json('simulation_config'); // Canvas, physics, parameters, measurements, graphs, visual
            
            // ═══════════════════════════════════════════════════════════════
            // VAZIFALAR (STEP-BY-STEP)
            // ═══════════════════════════════════════════════════════════════
            $table->json('tasks'); // Step-by-step instructions with validation
            $table->unsignedSmallInteger('total_points')->default(100);
            $table->unsignedSmallInteger('passing_points')->default(60);
            
            // ═══════════════════════════════════════════════════════════════
            // O'QUV MATERIALLARI
            // ═══════════════════════════════════════════════════════════════
            $table->string('video_tutorial_url', 500)->nullable();
            $table->unsignedInteger('video_duration_seconds')->nullable();
            $table->json('additional_resources')->nullable();
            $table->json('faq')->nullable();
            
            // ═══════════════════════════════════════════════════════════════
            // GAMIFIKATSIYA
            // ═══════════════════════════════════════════════════════════════
            $table->unsignedSmallInteger('xp_reward')->default(50);
            $table->unsignedSmallInteger('xp_reward_premium')->default(100);
            $table->unsignedSmallInteger('coin_reward')->default(10);
            $table->unsignedSmallInteger('coin_reward_premium')->default(25);
            
            $table->foreignUuid('badge_on_complete')->nullable()
                  ->constrained('lab_badges')->nullOnDelete();
            $table->foreignUuid('badge_on_perfect')->nullable()
                  ->constrained('lab_badges')->nullOnDelete();
            $table->json('achievement_triggers')->nullable();
            
            // ═══════════════════════════════════════════════════════════════
            // MEDIA
            // ═══════════════════════════════════════════════════════════════
            $table->string('thumbnail', 500)->nullable();
            $table->string('banner_image', 500)->nullable();
            $table->string('preview_gif', 500)->nullable();
            
            // ═══════════════════════════════════════════════════════════════
            // STATISTIKA (cached)
            // ═══════════════════════════════════════════════════════════════
            $table->unsignedInteger('total_attempts')->default(0);
            $table->unsignedInteger('total_completions')->default(0);
            $table->decimal('completion_rate', 5, 2)->default(0);
            $table->decimal('avg_score', 5, 2)->default(0);
            $table->unsignedInteger('avg_duration_seconds')->default(0);
            $table->decimal('avg_rating', 3, 2)->default(0);
            $table->unsignedInteger('total_ratings')->default(0);
            
            // ═══════════════════════════════════════════════════════════════
            // BOG'LANISHLAR
            // ═══════════════════════════════════════════════════════════════
            $table->foreignUuid('related_lesson_id')->nullable()
                  ->constrained('lessons')->nullOnDelete();
            $table->foreignUuid('related_course_id')->nullable()
                  ->constrained('courses')->nullOnDelete();
            $table->json('prerequisite_labs')->nullable(); // UUID array
            
            // ═══════════════════════════════════════════════════════════════
            // STATUS
            // ═══════════════════════════════════════════════════════════════
            $table->enum('status', ['draft', 'active', 'archived', 'maintenance'])
                  ->default('draft');
            $table->boolean('is_featured')->default(false);
            $table->unsignedSmallInteger('featured_order')->nullable();
            
            // SEO
            $table->string('meta_title', 255)->nullable();
            $table->text('meta_description')->nullable();
            $table->string('meta_keywords', 500)->nullable();
            
            // Audit
            $table->foreignUuid('created_by')->nullable()
                  ->constrained('users')->nullOnDelete();
            $table->foreignUuid('reviewed_by')->nullable()
                  ->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('category_id');
            $table->index('grade_level');
            $table->index('is_free');
            $table->index('status');
            $table->index('is_featured');
            $table->index('simulation_type');
            $table->index(['category_id', 'experiment_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_experiments');
    }
};
