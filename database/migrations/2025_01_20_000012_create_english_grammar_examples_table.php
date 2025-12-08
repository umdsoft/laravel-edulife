<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('english_grammar_examples', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('grammar_rule_id');

            // Example sentence
            $table->text('sentence');
            $table->text('sentence_uz')->nullable();

            // Highlight
            $table->string('highlight_text')->nullable();
            $table->json('highlight_positions')->nullable();

            // Explanation
            $table->text('explanation')->nullable();
            $table->text('explanation_uz')->nullable();

            // Type
            $table->enum('example_type', [
                'correct',
                'incorrect',
                'comparison'
            ])->default('correct');

            // For incorrect examples
            $table->text('correction')->nullable();
            $table->text('correction_explanation')->nullable();
            $table->text('correction_explanation_uz')->nullable();

            // Audio
            $table->string('audio_url')->nullable();

            // Order
            $table->integer('order_number')->default(0);
            $table->boolean('is_primary')->default(false);

            $table->timestamps();

            $table->foreign('grammar_rule_id')->references('id')->on('english_grammar_rules')->onDelete('cascade');
            $table->index(['grammar_rule_id', 'example_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('english_grammar_examples');
    }
};
