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
        Schema::create('coding_problems', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title', 255);
            $table->string('slug', 255)->unique();
            $table->text('description');
            $table->text('input_format');
            $table->text('output_format');
            $table->text('constraints')->nullable();
            $table->json('examples');
            // [{input: "...", output: "...", explanation: "..."}]
            $table->json('test_cases');
            // [{id: "1", input: "...", expected_output: "...", points: 10, is_sample: true}]
            $table->string('difficulty', 20)->default('medium');
            // 'easy', 'medium', 'hard', 'expert'
            $table->integer('max_points')->default(100);
            $table->boolean('partial_scoring')->default(true);
            $table->integer('time_limit_ms')->default(2000);
            $table->integer('memory_limit_mb')->default(256);
            $table->json('allowed_languages')->nullable();
            // ['python', 'javascript', 'cpp', 'java']
            $table->json('starter_code')->nullable();
            // {python: "def solve(...):", javascript: "function solve(...) {}", ...}
            $table->text('editorial')->nullable();
            $table->json('editorial_code')->nullable();
            // {python: "...", cpp: "..."}
            $table->json('tags')->nullable();
            // ["arrays", "sorting", "dynamic-programming"]
            $table->json('algorithms')->nullable();
            // ["binary-search", "two-pointers"]
            $table->string('status', 20)->default('active');
            // 'draft', 'active', 'archived'
            $table->foreignUuid('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('difficulty');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coding_problems');
    }
};
