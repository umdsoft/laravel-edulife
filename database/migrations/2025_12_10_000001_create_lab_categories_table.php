<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lab Categories - Fizika laboratoriya kategoriyalari
     * 
     * Kategoriyalar: mechanics, thermodynamics, electricity, optics, waves, magnetism, atomic
     */
    public function up(): void
    {
        Schema::create('lab_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // ═══════════════════════════════════════════════════════════════
            // IDENTIFIKATSIYA
            // ═══════════════════════════════════════════════════════════════
            $table->string('slug', 50)->unique();
            
            // Nomi (ko'p tilli)
            $table->string('name', 100);
            $table->string('name_uz', 100);
            $table->string('name_ru', 100)->nullable();
            
            // Tavsif (ko'p tilli)
            $table->text('description')->nullable();
            $table->text('description_uz')->nullable();
            $table->text('description_ru')->nullable();
            
            // ═══════════════════════════════════════════════════════════════
            // SINF DARAJASI
            // ═══════════════════════════════════════════════════════════════
            $table->json('grade_levels'); // [6, 7, 8, 9, 10, 11]
            $table->unsignedTinyInteger('min_grade')->default(6);
            $table->unsignedTinyInteger('max_grade')->default(11);
            
            // ═══════════════════════════════════════════════════════════════
            // UI / VIZUAL
            // ═══════════════════════════════════════════════════════════════
            $table->string('icon', 50); // Icon nomi (heroicons, etc.)
            $table->text('icon_svg')->nullable(); // Custom SVG
            $table->string('color', 20); // Asosiy rang (#3B82F6)
            $table->string('gradient', 100)->nullable(); // Gradient string
            $table->string('banner_image', 500)->nullable();
            $table->string('thumbnail', 500)->nullable();
            
            // ═══════════════════════════════════════════════════════════════
            // STATISTIKA (cached)
            // ═══════════════════════════════════════════════════════════════
            $table->unsignedInteger('total_experiments')->default(0);
            $table->unsignedInteger('free_experiments')->default(0);
            $table->unsignedInteger('total_completions')->default(0);
            $table->decimal('avg_rating', 3, 2)->default(0);
            $table->unsignedInteger('total_ratings')->default(0);
            
            // ═══════════════════════════════════════════════════════════════
            // TARTIB VA STATUS
            // ═══════════════════════════════════════════════════════════════
            $table->unsignedSmallInteger('order_number')->default(0);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            
            // ═══════════════════════════════════════════════════════════════
            // SEO
            // ═══════════════════════════════════════════════════════════════
            $table->string('meta_title', 255)->nullable();
            $table->text('meta_description')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('slug');
            $table->index('is_active');
            $table->index('is_featured');
            $table->index('order_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_categories');
    }
};
