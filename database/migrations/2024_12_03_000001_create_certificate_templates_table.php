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
        Schema::create('certificate_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->string('pdf_template_path');
            $table->string('thumbnail_path')->nullable();
            $table->foreignUuid('direction_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('course_id')->nullable()->constrained()->nullOnDelete();
            $table->json('placeholders');
            $table->json('settings')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->unsignedInteger('usage_count')->default(0);
            $table->timestamps();

            $table->index('direction_id');
            $table->index('course_id');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificate_templates');
    }
};
