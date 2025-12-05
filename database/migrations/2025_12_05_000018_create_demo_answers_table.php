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
        Schema::create('demo_answers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('attempt_id')->constrained('demo_attempts')->onDelete('cascade');
            $table->foreignUuid('question_id')->constrained('question_bank')->onDelete('cascade');
            $table->json('user_answer')->nullable();
            $table->boolean('is_correct')->default(false);
            $table->decimal('points_earned', 8, 2)->default(0);
            $table->integer('time_spent_seconds')->default(0);
            $table->timestamps();
            
            // Unique constraint
            $table->unique(['attempt_id', 'question_id']);
            
            // Indexes
            $table->index('attempt_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('demo_answers');
    }
};
