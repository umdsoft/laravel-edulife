<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');

            // Type
            $table->enum('type', [
                'achievement_unlocked',
                'level_up',
                'streak_milestone',
                'daily_challenge',
                'battle_invite',
                'battle_result',
                'tournament_start',
                'tournament_result',
                'leaderboard_position',
                'reward_available',
                'friend_request',
                'system_message',
                'streak_warning',
                'review_reminder'
            ]);

            // Content
            $table->string('title', 200);
            $table->string('title_uz', 200)->nullable();
            $table->text('message');
            $table->text('message_uz')->nullable();

            // Reference
            $table->string('reference_type', 50)->nullable();
            $table->uuid('reference_id')->nullable();

            // Action
            $table->string('action_url')->nullable();
            $table->string('action_text', 100)->nullable();

            // Visual
            $table->string('icon', 50)->nullable();
            $table->string('image')->nullable();

            // Status
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->boolean('is_pushed')->default(false);
            $table->timestamp('pushed_at')->nullable();

            // Priority
            $table->enum('priority', ['low', 'normal', 'high'])->default('normal');

            // Expiry
            $table->timestamp('expires_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'is_read', 'created_at']);
            $table->index(['user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notifications');
    }
};
