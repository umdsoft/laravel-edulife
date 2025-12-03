<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('course_qna', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('course_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('lesson_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete(); // student who asked
            $table->foreignUuid('parent_id')->nullable(); // for replies
            $table->text('content');
            $table->boolean('is_answered')->default(false);
            $table->boolean('is_pinned')->default(false);
            $table->boolean('is_highlighted')->default(false); // teacher marked as important
            $table->unsignedInteger('upvotes')->default(0);
            $table->timestamp('answered_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('parent_id')->references('id')->on('course_qna')->cascadeOnDelete();
            $table->index(['course_id', 'is_answered']);
            $table->index(['course_id', 'lesson_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('course_qna');
    }
};
