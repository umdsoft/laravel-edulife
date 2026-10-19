<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Additive extension of question_bank for the exam module (Bosqich 2).
 * Olympiad columns stay untouched; olympiad code keeps working.
 *
 * origin / review_status are strings validated by PHP backed enums
 * (App\Enums\Exam\QuestionOrigin, ReviewStatus) - same convention as the
 * existing question_bank.status column - so adding a state later needs no
 * ALTER on MySQL.
 */
return new class extends Migration
{
    // SQLite rebuilds the table to add foreign keys; see 2026_10_20_100002.
    public $withinTransaction = false;

    public function up(): void
    {
        // question_bank.topic_id existed without a referenced table. Values in
        // it can not point to exam_topics (created in this release), so they
        // are cleared before the foreign key is added.
        DB::table('question_bank')->whereNotNull('topic_id')->update(['topic_id' => null]);

        Schema::table('question_bank', function (Blueprint $table) {
            $table->foreignUuid('subject_id')->nullable()->after('direction_id')
                ->constrained('exam_subjects')->nullOnDelete();
            $table->foreign('topic_id')->references('id')->on('exam_topics')->nullOnDelete();

            $table->unsignedTinyInteger('difficulty_level')->nullable()->after('difficulty'); // 1..5
            $table->json('exam_formats')->nullable();      // ["milliy_sertifikat","abituriyent","sat"]
            $table->string('source_ref', 255)->nullable(); // book / collection, page, problem no.

            $table->string('origin', 20)->default('manual');        // ai_draft | manual | import
            $table->string('review_status', 20)->default('draft');  // draft | in_review | approved | rejected | retired
            $table->foreignUuid('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('reject_reason')->nullable();

            $table->string('solution_video_url', 500)->nullable();
            $table->text('solution_text')->nullable();               // LaTeX-markdown
            $table->string('content_format', 30)->default('latex_markdown');
            $table->string('units_note', 500)->nullable();           // physics: units, rounding
            $table->json('constants')->nullable();                   // {"g": {"value": 10, "unit": "m/s^2"}}

            $table->unsignedInteger('error_reports_count')->default(0);
            $table->boolean('is_demo')->default(false);
            $table->char('normalized_hash', 40)->nullable();         // sha1 of normalized stem, duplicate detection

            $table->index(['subject_id', 'topic_id', 'difficulty_level', 'review_status'], 'qb_exam_selection_idx');
            $table->index(['review_status', 'created_at'], 'qb_review_queue_idx');
            $table->index('normalized_hash', 'qb_normalized_hash_idx');
            $table->index('is_demo');
        });

        // Backfill the numeric difficulty from the legacy string difficulty.
        foreach (['easy' => 2, 'medium' => 3, 'hard' => 4, 'expert' => 5] as $legacy => $level) {
            DB::table('question_bank')->where('difficulty', $legacy)->update(['difficulty_level' => $level]);
        }
    }

    public function down(): void
    {
        Schema::table('question_bank', function (Blueprint $table) {
            $table->dropIndex('qb_exam_selection_idx');
            $table->dropIndex('qb_review_queue_idx');
            $table->dropIndex('qb_normalized_hash_idx');
            $table->dropIndex(['is_demo']);
            $table->dropForeign(['topic_id']);
            $table->dropConstrainedForeignId('subject_id');
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn([
                'difficulty_level', 'exam_formats', 'source_ref', 'origin', 'review_status',
                'reviewed_at', 'reject_reason', 'solution_video_url', 'solution_text',
                'content_format', 'units_note', 'constants', 'error_reports_count', 'is_demo',
                'normalized_hash',
            ]);
        });
    }
};
