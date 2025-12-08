<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('english_grammar_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('category_id');
            $table->uuid('level_id');

            // Identification
            $table->string('slug', 100)->unique();

            // Title
            $table->string('title');
            $table->string('title_uz');

            // Explanation
            $table->text('explanation');
            $table->text('explanation_uz');
            $table->text('explanation_simple')->nullable();

            // Formula (JSON for affirmative, negative, question)
            $table->json('formulas')->nullable();

            // When to use
            $table->json('usage_cases')->nullable();

            // Signal words
            $table->json('signal_words')->nullable();

            // Tips
            $table->json('tips')->nullable();
            $table->json('tips_uz')->nullable();

            // Media
            $table->string('video_url')->nullable();
            $table->string('infographic_url')->nullable();

            // Related rules (stored as JSON arrays of UUIDs)
            $table->json('related_rule_ids')->nullable();
            $table->json('prerequisite_rule_ids')->nullable();

            // Status
            $table->integer('order_number')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->foreign('category_id')->references('id')->on('english_grammar_categories')->onDelete('cascade');
            $table->foreign('level_id')->references('id')->on('english_levels')->onDelete('cascade');
            $table->index('slug');
            $table->index(['category_id', 'level_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('english_grammar_rules');
    }
};
