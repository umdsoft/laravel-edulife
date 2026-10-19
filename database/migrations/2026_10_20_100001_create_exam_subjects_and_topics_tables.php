<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Exam module: subjects (math, physics) and the topic tree
 * subject -> section (level 1) -> topic (level 2, deeper levels allowed).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_subjects', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 30)->unique(); // math, physics
            $table->string('name_uz', 100);
            $table->string('name_ru', 100)->nullable();
            $table->foreignUuid('direction_id')->nullable()->constrained('directions')->nullOnDelete();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('exam_topics', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('subject_id')->constrained('exam_subjects')->cascadeOnDelete();
            $table->foreignUuid('parent_id')->nullable()->constrained('exam_topics')->restrictOnDelete();
            // Stable, globally unique code, e.g. math.equations.quadratic
            $table->string('code', 100)->unique();
            $table->string('name_uz');
            $table->string('name_ru')->nullable();
            $table->text('description')->nullable();
            // 1 = section (bo'lim), 2 = topic (mavzu), 3+ = subtopic
            $table->unsignedTinyInteger('level')->default(1);
            $table->unsignedTinyInteger('grade_from')->nullable(); // 5..11
            $table->unsignedTinyInteger('grade_to')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['subject_id', 'parent_id', 'sort_order']);
            $table->index(['subject_id', 'level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_topics');
        Schema::dropIfExists('exam_subjects');
    }
};
