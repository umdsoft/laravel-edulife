<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_vocabulary', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->uuid('vocabulary_id');
            $table->enum('status', ['new', 'learning', 'reviewing', 'mastered'])->default('new');
            $table->decimal('ease_factor', 4, 2)->default(2.50);
            $table->integer('interval_days')->default(1);
            $table->integer('repetitions')->default(0);
            $table->date('next_review_date')->nullable();
            $table->date('last_review_date')->nullable();
            $table->json('quality_history')->nullable();
            $table->integer('times_seen')->default(0);
            $table->integer('times_correct')->default(0);
            $table->integer('times_incorrect')->default(0);
            $table->integer('consecutive_correct')->default(0);
            $table->integer('mastery_level')->default(0);
            $table->uuid('learned_in_lesson_id')->nullable();
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('mastered_at')->nullable();
            $table->text('user_notes')->nullable();
            $table->boolean('is_favorite')->default(false);
            $table->boolean('is_difficult')->default(false);
            $table->timestamps();

            $table->foreign('vocabulary_id')->references('id')->on('english_vocabulary')->onDelete('cascade');
            $table->foreign('learned_in_lesson_id')->references('id')->on('english_lessons')->onDelete('set null');
            $table->unique(['user_id', 'vocabulary_id']);
            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'next_review_date']);
            $table->index(['user_id', 'is_favorite']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_vocabulary');
    }
};
