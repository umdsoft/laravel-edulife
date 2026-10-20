<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fair battles (docs/battle/SCORING.md):
 *  - battles store the difficulty level, the selection seed and the ordered
 *    question ids, so both players and any reconnect see the same questions
 *    in the same order (option order is derived from the seed);
 *  - battle_rounds store the question's value at selection time, the
 *    estimated solve rate it came from and how many players solved it;
 *  - battle_question_stats counts every battle attempt per question: the
 *    empirical difficulty behind the question values.
 *
 * Additive only.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('battles', function (Blueprint $table) {
            if (! Schema::hasColumn('battles', 'requested_difficulty')) {
                // the lobby choice: easy / medium / hard, null = automatic (by ELO)
                $table->string('requested_difficulty', 10)->nullable();
            }
            if (! Schema::hasColumn('battles', 'question_seed')) {
                $table->unsignedBigInteger('question_seed')->nullable();
            }
            if (! Schema::hasColumn('battles', 'question_ids')) {
                $table->json('question_ids')->nullable();
            }
        });

        Schema::table('battle_rounds', function (Blueprint $table) {
            if (! Schema::hasColumn('battle_rounds', 'question_value')) {
                $table->unsignedInteger('question_value')->nullable();
            }
            if (! Schema::hasColumn('battle_rounds', 'p_estimate')) {
                $table->decimal('p_estimate', 6, 4)->nullable();
            }
            if (! Schema::hasColumn('battle_rounds', 'solver_count')) {
                $table->unsignedTinyInteger('solver_count')->nullable();
            }
        });

        if (! Schema::hasTable('battle_question_stats')) {
            Schema::create('battle_question_stats', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('question_id')->unique()->constrained()->cascadeOnDelete();
                $table->unsignedInteger('attempts')->default(0);
                $table->unsignedInteger('correct')->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('battle_question_stats');

        Schema::table('battle_rounds', function (Blueprint $table) {
            foreach (['question_value', 'p_estimate', 'solver_count'] as $column) {
                if (Schema::hasColumn('battle_rounds', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::table('battles', function (Blueprint $table) {
            foreach (['requested_difficulty', 'question_seed', 'question_ids'] as $column) {
                if (Schema::hasColumn('battles', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
