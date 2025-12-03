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
        Schema::create('tests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('course_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['lesson', 'module', 'final']);
            $table->uuidMorphs('testable'); // lesson_id yoki module_id uchun
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->integer('questions_count')->default(10);
            $table->integer('time_limit')->default(30); // daqiqalarda
            $table->decimal('passing_score', 5, 2)->default(86.00);
            $table->integer('max_attempts')->default(0); // 0 = unlimited
            $table->boolean('shuffle_questions')->default(true);
            $table->boolean('shuffle_options')->default(true);
            $table->boolean('show_correct_answers')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->index('course_id');
            $table->index('type');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tests');
    }
};
