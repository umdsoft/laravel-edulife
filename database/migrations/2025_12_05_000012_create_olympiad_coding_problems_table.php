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
        Schema::create('olympiad_coding_problems', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('olympiad_id')->constrained('olympiads')->onDelete('cascade');
            $table->foreignUuid('section_id')->constrained('olympiad_sections')->onDelete('cascade');
            $table->foreignUuid('problem_id')->constrained('coding_problems')->onDelete('cascade');
            $table->integer('order_number');
            $table->integer('points');
            $table->integer('time_limit_override_ms')->nullable();
            $table->integer('memory_limit_override_mb')->nullable();
            $table->timestamps();
            
            // Unique constraints
            $table->unique(['olympiad_id', 'problem_id']);
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
        Schema::dropIfExists('olympiad_coding_problems');
    }
};
