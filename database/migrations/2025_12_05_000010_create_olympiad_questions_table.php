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
        Schema::create('olympiad_questions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('olympiad_id')->constrained('olympiads')->onDelete('cascade');
            $table->foreignUuid('section_id')->constrained('olympiad_sections')->onDelete('cascade');
            $table->foreignUuid('question_id')->constrained('question_bank')->onDelete('cascade');
            $table->integer('order_number');
            $table->integer('points');
            $table->integer('time_limit_seconds')->nullable();
            $table->string('difficulty_group', 50)->nullable();
            // For demo similarity grouping
            $table->string('concept_group', 50)->nullable();
            $table->timestamps();
            
            // Unique constraints
            $table->unique(['olympiad_id', 'question_id']);
            $table->unique(['section_id', 'order_number']);
            
            // Indexes
            $table->index('olympiad_id');
            $table->index('section_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('olympiad_questions');
    }
};
