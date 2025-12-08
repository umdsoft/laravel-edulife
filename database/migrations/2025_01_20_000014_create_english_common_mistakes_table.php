<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('english_common_mistakes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('grammar_rule_id')->nullable();
            $table->uuid('vocabulary_id')->nullable();
            $table->uuid('level_id');

            // Mistake
            $table->text('incorrect_form');
            $table->text('correct_form');

            // Explanation
            $table->text('explanation');
            $table->text('explanation_uz');

            // Category
            $table->enum('mistake_type', [
                'grammar',
                'vocabulary',
                'spelling',
                'pronunciation',
                'usage'
            ]);

            // Common among which L1 speakers
            $table->json('common_for_l1')->nullable();

            // Frequency
            $table->enum('frequency', ['very_common', 'common', 'occasional'])->default('common');

            $table->timestamps();

            $table->foreign('grammar_rule_id')->references('id')->on('english_grammar_rules')->onDelete('cascade');
            $table->foreign('vocabulary_id')->references('id')->on('english_vocabulary')->onDelete('cascade');
            $table->foreign('level_id')->references('id')->on('english_levels')->onDelete('cascade');
            $table->index('mistake_type');
            $table->index('frequency');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('english_common_mistakes');
    }
};
