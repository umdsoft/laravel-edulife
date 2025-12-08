<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('english_lesson_steps', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('lesson_id');

            // Step info
            $table->integer('step_number');
            $table->string('title');
            $table->string('title_uz');

            // Step type
            $table->enum('step_type', [
                'vocabulary_intro',
                'vocabulary_practice',
                'grammar_intro',
                'grammar_practice',
                'listening',
                'speaking',
                'reading',
                'writing',
                'game',
                'ai_conversation',
                'quiz',
                'review'
            ]);

            // Content configuration (JSON for flexibility)
            $table->json('content_config')->nullable();

            // Requirements
            $table->integer('xp_reward')->default(10);
            $table->integer('estimated_minutes')->default(5);
            $table->boolean('is_required')->default(true);
            $table->boolean('is_skippable')->default(false);

            // Status
            $table->integer('order_number')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->foreign('lesson_id')->references('id')->on('english_lessons')->onDelete('cascade');
            $table->unique(['lesson_id', 'step_number']);
            $table->index('step_type');
            $table->index('order_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('english_lesson_steps');
    }
};
