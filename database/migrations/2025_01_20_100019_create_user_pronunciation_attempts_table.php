<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_pronunciation_attempts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->uuid('pronunciation_word_id');
            $table->string('audio_url');
            $table->integer('audio_duration_ms');
            $table->integer('overall_score')->default(0);
            $table->integer('pronunciation_score')->default(0);
            $table->integer('fluency_score')->default(0);
            $table->integer('intonation_score')->default(0);
            $table->json('analysis')->nullable();
            $table->integer('stars_earned')->default(0);
            $table->integer('xp_earned')->default(0);
            $table->timestamps();

            $table->foreign('pronunciation_word_id')->references('id')->on('english_pronunciation_words')->onDelete('cascade');
            $table->index(['user_id', 'pronunciation_word_id']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_pronunciation_attempts');
    }
};
