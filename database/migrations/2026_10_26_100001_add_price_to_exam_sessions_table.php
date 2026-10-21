<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Paid exams (docs/exam/QARORLAR.md item 9): a Saturday session may cost money (UZS, whole so'm).
 * 0 = free, which every existing session stays. Placement sessions are always free (ExamAccess).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->unsignedInteger('price')->default(0)->after('duration_min');
        });

        // how a student got into the session (free | subscription | purchase | placement | staff), set at start
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->string('access_via', 20)->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->dropColumn('access_via');
        });
        Schema::table('exam_sessions', function (Blueprint $table) {
            $table->dropColumn('price');
        });
    }
};
