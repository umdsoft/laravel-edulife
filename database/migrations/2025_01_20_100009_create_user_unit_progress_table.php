<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_unit_progress', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->uuid('unit_id');
            $table->enum('status', ['locked', 'available', 'in_progress', 'completed'])->default('locked');
            $table->integer('lessons_completed')->default(0);
            $table->integer('total_lessons')->default(0);
            $table->decimal('completion_percentage', 5, 2)->default(0);
            $table->integer('unit_test_score')->nullable();
            $table->integer('unit_test_attempts')->default(0);
            $table->boolean('unit_test_passed')->default(false);
            $table->timestamp('unit_test_completed_at')->nullable();
            $table->integer('xp_earned')->default(0);
            $table->integer('coins_earned')->default(0);
            $table->boolean('badge_earned')->default(false);
            $table->integer('time_spent_seconds')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('unit_id')->references('id')->on('english_units')->onDelete('cascade');
            $table->unique(['user_id', 'unit_id']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_unit_progress');
    }
};
