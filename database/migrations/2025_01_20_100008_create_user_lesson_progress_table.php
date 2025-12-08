<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_lesson_progress', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->uuid('lesson_id');
            $table->enum('status', ['locked', 'available', 'in_progress', 'completed', 'mastered'])->default('locked');
            $table->integer('current_step')->default(0);
            $table->integer('total_steps')->default(0);
            $table->json('completed_steps')->nullable();
            $table->integer('best_score')->default(0);
            $table->integer('last_score')->default(0);
            $table->integer('attempts')->default(0);
            $table->integer('stars_earned')->default(0);
            $table->integer('xp_earned')->default(0);
            $table->integer('coins_earned')->default(0);
            $table->integer('time_spent_seconds')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('last_accessed_at')->nullable();
            $table->json('quiz_results')->nullable();
            $table->timestamps();

            $table->foreign('lesson_id')->references('id')->on('english_lessons')->onDelete('cascade');
            $table->unique(['user_id', 'lesson_id']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_lesson_progress');
    }
};
