<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * question_bank_options - answer options of closed questions.
 *   is_correct is NULL until the teacher marks it (AI drafts never set it).
 *
 * question_bank_answers - expected answers of open questions
 *   (numeric, numeric_with_unit; later expression). value is NULL until the
 *   teacher enters it. A question may have several accepted answers.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_bank_options', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('question_id')->constrained('question_bank')->cascadeOnDelete();
            $table->char('label', 1);                          // A..E
            $table->text('text');                              // LaTeX-markdown
            $table->string('image', 500)->nullable();
            $table->boolean('is_correct')->nullable()->default(null);
            // Optional hidden AI hint for the reviewer (config flag, off by default).
            $table->boolean('ai_suggested_correct')->nullable();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['question_id', 'label']);
            $table->index(['question_id', 'sort_order']);
        });

        Schema::create('question_bank_answers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('question_id')->constrained('question_bank')->cascadeOnDelete();
            // Exact decimal, written by the teacher. Accepted inputs like 1/2,
            // 0,5 and 0.5 are normalized to this value by the grader.
            $table->decimal('value', 30, 10)->nullable();
            $table->string('tolerance_type', 3)->default('abs'); // abs | rel
            $table->decimal('tolerance', 20, 10)->default(0);
            // numeric_with_unit: the canonical unit the value is expressed in,
            // e.g. "m/s", and accepted alternatives with a factor that converts
            // ONE alternative unit into the canonical unit:
            //   {"m/s": 1, "km/soat": 0.2777777778, "km/h": 0.2777777778}
            $table->string('unit', 30)->nullable();
            $table->json('accepted_units')->nullable();
            $table->string('note', 255)->nullable(); // e.g. "0,01 gacha yaxlitlang"
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['question_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_bank_answers');
        Schema::dropIfExists('question_bank_options');
    }
};
