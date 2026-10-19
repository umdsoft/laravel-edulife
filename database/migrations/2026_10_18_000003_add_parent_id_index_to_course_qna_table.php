<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Replies (parent_id = ?) and top-level questions (parent_id IS NULL) are
     * queried on every Q&A page and in teacher score calculation.
     */
    public function up(): void
    {
        Schema::table('course_qna', function (Blueprint $table) {
            $table->index(['course_id', 'parent_id']);
            $table->index('parent_id');
        });
    }

    public function down(): void
    {
        Schema::table('course_qna', function (Blueprint $table) {
            $table->dropIndex(['course_id', 'parent_id']);
            $table->dropIndex(['parent_id']);
        });
    }
};
