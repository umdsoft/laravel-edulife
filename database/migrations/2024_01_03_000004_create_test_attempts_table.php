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
        Schema::create('test_attempts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('test_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('course_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('enrollment_id')->constrained()->cascadeOnDelete();
            
            // Attempt info
            $table->unsignedInteger('attempt_number')->default(1);
            $table->enum('status', ['in_progress', 'submitted', 'expired', 'cancelled'])->default('in_progress');
            
            // Timing
            $table->dateTime('started_at');
            $table->dateTime('submitted_at')->nullable();
            $table->dateTime('expires_at'); // started_at + time_limit
            $table->unsignedInteger('time_spent')->default(0); // seconds
            
            // Results
            $table->unsignedInteger('total_questions')->default(0);
            $table->unsignedInteger('answered_questions')->default(0);
            $table->unsignedInteger('correct_answers')->default(0);
            $table->unsignedInteger('wrong_answers')->default(0);
            $table->unsignedInteger('skipped_questions')->default(0);
            $table->decimal('score', 5, 2)->default(0); // 0-100
            $table->boolean('is_passed')->default(false);
            
            // Anti-cheat
            $table->unsignedInteger('tab_switches')->default(0);
            $table->unsignedInteger('fullscreen_exits')->default(0);
            $table->boolean('is_flagged')->default(false);
            $table->text('flag_reason')->nullable();
            
            // XP
            $table->boolean('xp_awarded')->default(false);
            $table->unsignedInteger('xp_amount')->default(0);
            
            $table->timestamps();
            
            $table->index(['user_id', 'test_id']);
            $table->index(['user_id', 'status']);
            $table->index(['test_id', 'is_passed']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('test_attempts');
    }
};
