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
        Schema::create('questions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('course_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('test_id')->nullable()->constrained()->onDelete('cascade');
            $table->enum('type', ['single_choice', 'multiple_choice', 'true_false', 'fill_blank', 'matching', 'ordering']);
            $table->enum('difficulty', ['easy', 'medium', 'hard'])->default('medium');
            $table->longText('question_text');
            $table->string('question_image')->nullable();
            $table->longText('explanation')->nullable();
            $table->integer('points')->default(10);
            $table->boolean('partial_credit')->default(false);
            $table->boolean('case_sensitive')->default(false);
            $table->json('correct_answer')->nullable(); // true_false, fill_blank uchun
            $table->json('matching_pairs')->nullable(); // matching uchun
            $table->json('ordering_items')->nullable(); // ordering uchun
            $table->json('accepted_answers')->nullable(); // fill_blank uchun
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index('course_id');
            $table->index('test_id');
            $table->index('type');
            $table->index('difficulty');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
