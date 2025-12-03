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
        Schema::create('lesson_attachments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('lesson_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_type'); // pdf, zip, doc, xls, etc.
            $table->string('mime_type');
            $table->unsignedBigInteger('file_size'); // bytes
            $table->unsignedInteger('download_count')->default(0);
            $table->boolean('is_downloadable')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            
            $table->index('lesson_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_attachments');
    }
};
