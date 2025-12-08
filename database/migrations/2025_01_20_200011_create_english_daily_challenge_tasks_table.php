<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('english_daily_challenge_tasks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('daily_challenge_id');

            // Task type
            $table->enum('task_type', [
                'learn_words',
                'review_words',
                'complete_lesson',
                'play_game',
                'win_battle',
                'study_minutes',
                'earn_xp',
                'perfect_quiz',
                'ai_conversation',
                'streak_maintain'
            ]);

            // Task info
            $table->string('title', 200);
            $table->string('title_uz', 200);
            $table->text('description')->nullable();

            // Requirements
            $table->integer('required_count')->default(1);
            $table->json('requirements')->nullable();

            // Rewards
            $table->integer('xp_reward')->default(20);
            $table->integer('coin_reward')->default(10);

            // Visual
            $table->string('icon', 50)->nullable();

            // Order
            $table->integer('order_number')->default(0);
            $table->boolean('is_bonus_task')->default(false);

            $table->timestamps();

            $table->foreign('daily_challenge_id')->references('id')->on('english_daily_challenges')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('english_daily_challenge_tasks');
    }
};
