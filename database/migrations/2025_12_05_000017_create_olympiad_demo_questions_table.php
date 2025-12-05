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
        Schema::create('olympiad_demo_questions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('olympiad_id')->constrained('olympiads')->onDelete('cascade');
            $table->foreignUuid('question_id')->constrained('question_bank')->onDelete('cascade');
            $table->string('section_type', 20);
            $table->integer('order_number');
            $table->integer('points');
            $table->timestamps();
            
            // Unique constraints
            $table->unique(['olympiad_id', 'question_id']);
            
            // Indexes
            $table->index('olympiad_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('olympiad_demo_questions');
    }
};
