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
        Schema::create('video_uploads', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('lesson_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('uploaded_by')->constrained('users')->onDelete('cascade');
            $table->string('upload_id')->nullable(); // S3 multipart upload ID
            $table->string('s3_key')->nullable();
            $table->string('original_filename');
            $table->bigInteger('file_size');
            $table->string('mime_type', 50);
            $table->integer('total_chunks')->default(0);
            $table->integer('uploaded_chunks')->default(0);
            $table->enum('status', ['pending', 'uploading', 'completed', 'failed', 'cancelled'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            $table->index('lesson_id');
            $table->index('upload_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('video_uploads');
    }
};
