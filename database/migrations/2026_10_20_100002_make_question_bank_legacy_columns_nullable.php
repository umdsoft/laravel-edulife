<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * question_bank.section_type and question_bank.correct_answer were NOT NULL
 * (olympiad specific). Exam questions keep their answers in
 * question_bank_options / question_bank_answers and have no olympiad section,
 * so both columns become nullable. Approved by the owner (Bosqich 0, Q-4).
 *
 * Only nullability changes; types, data and indexes are kept.
 * MySQL alters the columns in place. SQLite has no ALTER COLUMN, Laravel
 * rebuilds the table; foreign key checks must be off during the rebuild,
 * which SQLite ignores inside a transaction, hence $withinTransaction = false.
 */
return new class extends Migration
{
    public $withinTransaction = false;

    public function up(): void
    {
        Schema::table('question_bank', function (Blueprint $table) {
            $table->string('section_type', 20)->nullable()->change();
            $table->json('correct_answer')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Rows created by the exam module have NULLs; give them neutral values
        // so the NOT NULL constraint can be restored.
        DB::table('question_bank')->whereNull('section_type')->update(['section_type' => 'test']);
        DB::table('question_bank')->whereNull('correct_answer')->update(['correct_answer' => '[]']);

        Schema::table('question_bank', function (Blueprint $table) {
            $table->string('section_type', 20)->nullable(false)->change();
            $table->json('correct_answer')->nullable(false)->change();
        });
    }
};
