<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Course test questions copied from the exam bank ("Bankdan qo'shish",
 * docs/exam/03 §2.2) remember their origin: the same bank question is not
 * added twice to one test, and the teacher sees where a question came from.
 * Additive and nullable: questions written by hand keep NULL.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('questions', 'bank_question_id')) {
            return;
        }

        Schema::table('questions', function (Blueprint $table) {
            $table->foreignUuid('bank_question_id')->nullable()->after('test_id')
                ->constrained('question_bank')->nullOnDelete();
            $table->index(['test_id', 'bank_question_id']);
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('questions', 'bank_question_id')) {
            return;
        }

        Schema::table('questions', function (Blueprint $table) {
            $table->dropForeign(['bank_question_id']);
            $table->dropIndex(['test_id', 'bank_question_id']);
            $table->dropColumn('bank_question_id');
        });
    }
};
