<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_achievements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->uuid('achievement_id');

            // Progress
            $table->integer('current_progress')->default(0);
            $table->integer('required_progress');
            $table->decimal('progress_percentage', 5, 2)->default(0);

            // Status
            $table->enum('status', ['locked', 'in_progress', 'completed', 'claimed'])->default('locked');

            // Completion
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('claimed_at')->nullable();

            // Rewards claimed
            $table->integer('xp_claimed')->default(0);
            $table->integer('coins_claimed')->default(0);
            $table->integer('gems_claimed')->default(0);

            // Display
            $table->boolean('is_featured')->default(false);
            $table->boolean('show_notification')->default(true);

            $table->timestamps();

            $table->foreign('achievement_id')->references('id')->on('english_achievements')->onDelete('cascade');

            $table->unique(['user_id', 'achievement_id']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_achievements');
    }
};
