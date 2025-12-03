<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_progresses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('enrollment_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('course_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('lesson_id')->constrained()->cascadeOnDelete();
            
            // Progress
            $table->boolean('is_completed')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('watch_time')->default(0); // seconds
            $table->unsignedInteger('last_position')->default(0); // seconds (video position)
            $table->unsignedInteger('total_duration')->default(0); // seconds
            $table->unsignedInteger('watch_percentage')->default(0); // 0-100
            
            // For text lessons
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            
            // XP awarded
            $table->boolean('xp_awarded')->default(false);
            $table->unsignedInteger('xp_amount')->default(0);
            
            $table->timestamps();
            
            $table->unique(['user_id', 'lesson_id']);
            $table->index(['enrollment_id', 'is_completed']);
            $table->index(['user_id', 'course_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_progresses');
    }
};
