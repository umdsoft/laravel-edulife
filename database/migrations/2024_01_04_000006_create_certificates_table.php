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
        Schema::create('certificates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('course_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('enrollment_id')->constrained()->onDelete('cascade');
            $table->string('credential_id', 50)->unique(); // EDULIFE-2024-XXXXXX
            $table->string('pdf_url')->nullable();
            $table->string('thumbnail_url')->nullable();
            $table->decimal('final_score', 5, 2)->nullable();
            $table->integer('completion_time')->default(0); // soatlarda
            $table->timestamp('issued_at');
            $table->timestamps();
            
            $table->unique(['user_id', 'course_id']);
            $table->index('user_id');
            $table->index('course_id');
            $table->index('credential_id');
            $table->index('issued_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
