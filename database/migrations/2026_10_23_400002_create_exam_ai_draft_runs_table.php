<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stage 4: one AI drafting request (manual or weekly schedule) with its
 * prompt version, token usage and estimated cost - the "AI qoralamalar" log.
 * Created questions are question_bank rows (origin = ai_draft,
 * review_status = in_review); their ids are kept in question_ids.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_ai_draft_runs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('subject_id')->constrained('exam_subjects')->cascadeOnDelete();
            $table->foreignUuid('topic_id')->nullable()->constrained('exam_topics')->nullOnDelete();
            $table->unsignedTinyInteger('difficulty_level');           // 1..5
            $table->string('exam_format', 30)->nullable();             // ExamFormat
            $table->string('question_type', 30)->default('single_choice');
            $table->unsignedSmallInteger('count_requested');
            $table->json('source_document_ids')->nullable();
            $table->json('source_excerpts')->nullable();               // [{id, offset, length}]
            $table->string('trigger', 20)->default('manual');          // manual | schedule
            $table->string('status', 20)->default('queued');           // queued | running | succeeded | partial | failed | skipped
            $table->string('model', 60)->nullable();                   // requested model
            $table->string('response_model', 60)->nullable();          // model that answered
            $table->string('prompt_name', 60)->nullable();
            $table->string('prompt_version', 30)->nullable();
            $table->unsignedInteger('input_tokens')->default(0);
            $table->unsignedInteger('output_tokens')->default(0);
            $table->unsignedInteger('cache_creation_input_tokens')->default(0);
            $table->unsignedInteger('cache_read_input_tokens')->default(0);
            $table->decimal('estimated_cost_usd', 12, 6)->default(0);
            $table->string('stop_reason', 30)->nullable();
            $table->unsignedSmallInteger('drafts_created')->default(0);
            $table->unsignedSmallInteger('drafts_rejected')->default(0);  // failed server-side validation
            $table->unsignedSmallInteger('duplicates_skipped')->default(0);
            $table->json('question_ids')->nullable();
            $table->json('validation_errors')->nullable();
            $table->string('error', 500)->nullable();                  // no personal data
            $table->foreignUuid('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_ai_draft_runs');
    }
};
