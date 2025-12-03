<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_notes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('course_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('lesson_id')->constrained()->cascadeOnDelete();
            
            $table->text('content');
            $table->unsignedInteger('video_timestamp')->nullable(); // seconds (where note was taken)
            $table->string('color')->default('yellow'); // yellow, blue, green, pink
            $table->boolean('is_pinned')->default(false);
            
            $table->timestamps();
            
            $table->index(['user_id', 'course_id']);
            $table->index(['user_id', 'lesson_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_notes');
    }
};
