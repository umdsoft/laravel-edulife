<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_study_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->integer('duration_seconds')->default(0);
            $table->enum('activity_type', ['lesson', 'vocabulary_review', 'grammar_practice', 'game', 'ai_conversation', 'test', 'battle', 'general']);
            $table->uuid('lesson_id')->nullable();
            $table->uuid('game_id')->nullable();
            $table->integer('xp_earned')->default(0);
            $table->integer('coins_earned')->default(0);
            $table->integer('words_reviewed')->default(0);
            $table->integer('exercises_completed')->default(0);
            $table->string('device_type', 20)->nullable();
            $table->string('platform', 20)->nullable();
            $table->timestamps();

            $table->foreign('lesson_id')->references('id')->on('english_lessons')->onDelete('set null');
            $table->foreign('game_id')->references('id')->on('english_games')->onDelete('set null');
            $table->index(['user_id', 'started_at']);
            $table->index(['user_id', 'activity_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_study_sessions');
    }
};
