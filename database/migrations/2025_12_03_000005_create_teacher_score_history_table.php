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
        Schema::create('teacher_score_history', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('teacher_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('score', 5, 2);
            $table->string('level', 20);
            $table->json('breakdown');
            $table->date('calculated_date');
            $table->timestamps();
            
            $table->index(['teacher_id', 'calculated_date']);
            $table->unique(['teacher_id', 'calculated_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_score_history');
    }
};
