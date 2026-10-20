<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stage 4: the teacher's own material (manuals, problem collections) that AI
 * drafts are built from. The uploaded file stays in storage/app/exam-sources/;
 * content_text is its plain text (LaTeX-markdown) the prompt quotes from.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_source_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('subject_id')->constrained('exam_subjects')->cascadeOnDelete();
            $table->foreignUuid('topic_id')->nullable()->constrained('exam_topics')->nullOnDelete();
            $table->string('title', 255);
            $table->string('source_ref', 255)->nullable();   // "Rimkevich, 2015" - default source_ref of drafts
            $table->string('disk', 30)->nullable();
            $table->string('path', 500)->nullable();          // NULL when the text was pasted
            $table->string('original_name', 255)->nullable();
            $table->string('mime', 100)->nullable();
            $table->unsignedInteger('size_bytes')->default(0);
            $table->longText('content_text');
            $table->unsignedInteger('char_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignUuid('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['subject_id', 'topic_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_source_documents');
    }
};
