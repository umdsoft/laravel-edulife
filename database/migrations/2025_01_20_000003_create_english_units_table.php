<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('english_units', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('level_id');

            // Identification
            $table->string('slug', 100)->unique();
            $table->integer('unit_number');

            // Content
            $table->string('title');
            $table->string('title_uz');
            $table->text('description')->nullable();
            $table->text('description_uz')->nullable();

            // Learning objectives
            $table->json('objectives')->nullable();
            $table->json('objectives_uz')->nullable();

            // Grammar & Vocabulary focus
            $table->json('grammar_focus')->nullable();
            $table->json('vocabulary_focus')->nullable();
            $table->integer('vocabulary_count')->default(50);

            // Requirements
            $table->integer('xp_required')->default(0);
            $table->integer('xp_reward')->default(200);
            $table->integer('estimated_minutes')->default(120);

            // Visual
            $table->string('icon', 50)->nullable();
            $table->string('color', 20)->nullable();
            $table->string('thumbnail')->nullable();
            $table->string('banner_image')->nullable();

            // Status
            $table->integer('order_number')->default(0);
            $table->boolean('is_free')->default(false);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->foreign('level_id')->references('id')->on('english_levels')->onDelete('cascade');
            $table->unique(['level_id', 'unit_number']);
            $table->index('slug');
            $table->index('order_number');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('english_units');
    }
};
