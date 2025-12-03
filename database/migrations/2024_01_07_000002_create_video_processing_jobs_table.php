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
        Schema::create('video_processing_jobs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('lesson_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('video_upload_id')->nullable()->constrained()->onDelete('set null');
            $table->string('source_url');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->integer('progress')->default(0); // 0-100
            $table->string('current_step')->nullable(); // downloading, transcoding, uploading
            $table->integer('duration')->nullable(); // sekundlarda
            $table->integer('width')->nullable();
            $table->integer('height')->nullable();
            $table->string('codec')->nullable();
            $table->json('output_qualities')->nullable(); // [1080, 720, 480, 360]
            $table->string('output_url')->nullable(); // HLS master.m3u8
            $table->string('thumbnail_url')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            $table->index('lesson_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_processing_jobs');
    }
};
