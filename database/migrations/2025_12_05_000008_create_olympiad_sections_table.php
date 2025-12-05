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
        Schema::create('olympiad_sections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('olympiad_id')->constrained('olympiads')->onDelete('cascade');
            $table->string('section_type', 20);
            // 'test', 'listening', 'reading', 'writing', 'speaking', 'coding'
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->text('instructions')->nullable();
            $table->integer('duration_minutes');
            $table->integer('order_number');
            $table->integer('max_points');
            $table->decimal('weight_percent', 5, 2);
            $table->decimal('passing_percent', 5, 2)->default(60);
            $table->json('section_config')->nullable();
            // Section-specific config (audio_url, passages, tasks, etc.)
            $table->boolean('requires_manual_grading')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Unique constraints
            $table->unique(['olympiad_id', 'section_type']);
            $table->unique(['olympiad_id', 'order_number']);
            
            // Indexes
            $table->index('olympiad_id');
            $table->index('section_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('olympiad_sections');
    }
};
