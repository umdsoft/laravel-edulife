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
        Schema::create('lessons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('module_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('course_id')->constrained()->onDelete('cascade');
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->enum('type', ['video', 'text', 'quiz'])->default('video');
            $table->integer('sort_order')->default(0);
            $table->string('video_url')->nullable();
            $table->string('source_url')->nullable();
            $table->integer('video_duration')->default(0);
            $table->json('video_qualities')->nullable();
            $table->enum('video_status', ['pending', 'uploading', 'processing', 'ready', 'failed'])->default('pending');
            $table->longText('text_content')->nullable();
            $table->integer('reading_time')->default(0);
            $table->boolean('is_free')->default(false);
            $table->boolean('is_preview')->default(false);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('module_id');
            $table->index('course_id');
            $table->index(['module_id', 'sort_order']);
            $table->index('type');
            $table->index('video_status');
            $table->index('is_free');
            $table->index('is_preview');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
