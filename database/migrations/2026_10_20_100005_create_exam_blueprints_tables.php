<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * exam_blueprints - the plan of an exam (placement / weekly / open_regional).
 * exam_blueprint_rules - topic x difficulty x count, per part (closed/open).
 *
 * A rule's topic may be a section: it then matches questions of the whole
 * subtree. difficulty_level NULL means "any difficulty".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_blueprints', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('subject_id')->constrained('exam_subjects')->cascadeOnDelete();
            $table->string('name');
            $table->string('purpose', 20);                 // placement | weekly | open_regional (ExamSessionType)
            $table->string('format', 30)->nullable();      // milliy_sertifikat | abituriyent | sat
            $table->unsignedSmallInteger('duration_min');
            $table->unsignedSmallInteger('total_questions'); // must equal SUM(rules.count)
            // Points ratio for a numeric_with_unit answer whose number is right
            // but unit is wrong: 0 = no points, 0.5 = half points.
            $table->decimal('wrong_unit_ratio', 3, 2)->default(0);
            // Variant generator: avoid questions the student saw in the last N weeks.
            $table->unsignedTinyInteger('avoid_repeat_weeks')->default(4);
            // Fill from the neighbouring difficulty when the bank is short.
            $table->boolean('allow_adjacent_difficulty')->default(true);
            $table->boolean('shuffle_questions')->default(true);
            $table->boolean('shuffle_options')->default(true);
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['subject_id', 'purpose', 'is_active']);
        });

        Schema::create('exam_blueprint_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('blueprint_id')->constrained('exam_blueprints')->cascadeOnDelete();
            $table->foreignUuid('topic_id')->constrained('exam_topics')->restrictOnDelete();
            $table->unsignedTinyInteger('difficulty_level')->nullable(); // 1..5, NULL = any
            $table->string('part', 10)->default('closed');                // closed | open
            $table->unsignedSmallInteger('count');
            $table->decimal('points_per_question', 6, 2)->default(1);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['blueprint_id', 'part', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_blueprint_rules');
        Schema::dropIfExists('exam_blueprints');
    }
};
