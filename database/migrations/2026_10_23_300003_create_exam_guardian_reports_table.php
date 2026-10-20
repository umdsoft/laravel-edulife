<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sunday report sent to a guardian about one child for one exam week.
 * (guardian_student_id, week_start) is unique: re-running the job never sends
 * the same week twice; the payload is what the guardian saw.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_guardian_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('guardian_student_id')->constrained('guardian_student')->cascadeOnDelete();
            $table->foreignUuid('guardian_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('student_id')->constrained('users')->cascadeOnDelete();
            $table->date('week_start');                 // Monday of the exam week
            $table->json('payload');                    // score, delta, topic map, recommendations
            $table->timestamp('sent_at')->nullable();   // in-app notification created
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->unique(['guardian_student_id', 'week_start']);
            $table->index(['guardian_id', 'week_start']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_guardian_reports');
    }
};
