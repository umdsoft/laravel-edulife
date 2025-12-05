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
        Schema::create('demo_attempts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('olympiad_id')->constrained('olympiads')->onDelete('cascade');
            $table->foreignUuid('registration_id')->constrained('olympiad_registrations')->onDelete('cascade');
            $table->integer('attempt_number');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('duration_seconds')->nullable();
            $table->decimal('total_score', 8, 2)->default(0);
            $table->decimal('max_score', 8, 2);
            $table->decimal('score_percent', 5, 2)->default(0);
            $table->json('section_scores')->nullable();
            // {test: 80, listening: 75, reading: 85}
            $table->json('answers_summary')->nullable();
            // {total: 30, correct: 24, incorrect: 6}
            $table->string('status', 20)->default('in_progress');
            // 'in_progress', 'completed', 'expired', 'abandoned'
            $table->timestamps();
            
            // Indexes
            $table->index(['olympiad_id', 'user_id']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('demo_attempts');
    }
};
