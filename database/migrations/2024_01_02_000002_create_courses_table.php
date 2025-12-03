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
        Schema::create('courses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('teacher_id')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('direction_id')->constrained()->onDelete('cascade');
            $table->string('title', 255);
            $table->string('slug', 255)->unique();
            $table->text('short_description');
            $table->longText('description');
            $table->string('thumbnail')->nullable();
            $table->string('preview_video')->nullable();
            $table->enum('difficulty', ['beginner', 'intermediate', 'advanced'])->default('beginner');
            $table->enum('language', ['uz', 'ru', 'en'])->default('uz');
            $table->enum('status', ['draft', 'pending', 'approved', 'published', 'rejected'])->default('draft');
            $table->boolean('is_free')->default(false);
            $table->decimal('price', 12, 2)->default(0);
            $table->decimal('original_price', 12, 2)->nullable();
            $table->json('what_you_learn')->nullable();
            $table->json('requirements')->nullable();
            $table->json('target_audience')->nullable();
            $table->integer('modules_count')->default(0);
            $table->integer('lessons_count')->default(0);
            $table->integer('total_duration')->default(0);
            $table->integer('students_count')->default(0);
            $table->decimal('avg_rating', 3, 2)->default(0);
            $table->integer('reviews_count')->default(0);
            $table->boolean('is_featured')->default(false);
            $table->boolean('free_navigation')->default(false);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('teacher_id');
            $table->index('direction_id');
            $table->index('slug');
            $table->index('status');
            $table->index(['status', 'is_featured']);
            $table->index('difficulty');
            $table->index('language');
            $table->index('is_free');
            $table->index('price');
            $table->index('avg_rating');
            $table->index('students_count');
            $table->index('published_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
