<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('english_lessons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('unit_id');

            // Identification
            $table->string('slug', 100)->unique();
            $table->integer('lesson_number');

            // Content
            $table->string('title');
            $table->string('title_uz');
            $table->text('description')->nullable();
            $table->text('description_uz')->nullable();

            // Type
            $table->enum('lesson_type', [
                'standard',
                'vocabulary',
                'grammar',
                'practice',
                'review',
                'test',
                'conversation'
            ])->default('standard');

            // Learning focus
            $table->json('vocabulary_ids')->nullable();
            $table->json('grammar_rule_ids')->nullable();

            // Requirements
            $table->integer('xp_required')->default(0);
            $table->integer('xp_reward')->default(50);
            $table->integer('coin_reward')->default(10);
            $table->integer('estimated_minutes')->default(15);

            // Pass criteria
            $table->integer('pass_percentage')->default(80);

            // Visual
            $table->string('icon', 50)->nullable();
            $table->string('thumbnail')->nullable();

            // Status
            $table->integer('order_number')->default(0);
            $table->boolean('is_free')->default(false);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->foreign('unit_id')->references('id')->on('english_units')->onDelete('cascade');
            $table->unique(['unit_id', 'lesson_number']);
            $table->index('slug');
            $table->index('lesson_type');
            $table->index('order_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('english_lessons');
    }
};
