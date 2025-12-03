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
        Schema::create('lesson_resources', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('lesson_id')->constrained()->onDelete('cascade');
            $table->string('title', 255);
            $table->enum('type', ['file', 'link', 'code']);
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->integer('file_size')->nullable();
            $table->string('url')->nullable();
            $table->longText('code_content')->nullable();
            $table->string('code_language', 50)->nullable();
            $table->integer('sort_order')->default(0);
            $table->integer('downloads_count')->default(0);
            $table->timestamps();
            
            $table->index('lesson_id');
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_resources');
    }
};
