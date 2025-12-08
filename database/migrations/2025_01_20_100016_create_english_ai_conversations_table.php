<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('english_ai_conversations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->uuid('scenario_id')->nullable();
            $table->enum('conversation_type', ['scenario', 'free_chat', 'practice', 'assessment'])->default('scenario');
            $table->string('level_code', 5)->nullable();
            $table->string('topic', 100)->nullable();
            $table->json('settings')->nullable();
            $table->enum('status', ['active', 'completed', 'abandoned'])->default('active');
            $table->integer('message_count')->default(0);
            $table->integer('user_message_count')->default(0);
            $table->integer('duration_seconds')->default(0);
            $table->json('goals_completed')->nullable();
            $table->integer('goals_score')->default(0);
            $table->json('analysis')->nullable();
            $table->integer('xp_earned')->default(0);
            $table->integer('coins_earned')->default(0);
            $table->timestamp('started_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('scenario_id')->references('id')->on('english_ai_scenarios')->onDelete('set null');
            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('english_ai_conversations');
    }
};
