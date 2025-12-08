<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('english_ai_messages', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('conversation_id');
            $table->enum('role', ['user', 'assistant', 'system']);
            $table->text('content');
            $table->enum('input_type', ['text', 'voice'])->default('text');
            $table->string('audio_url')->nullable();
            $table->integer('audio_duration_ms')->nullable();
            $table->json('grammar_check')->nullable();
            $table->json('vocabulary_used')->nullable();
            $table->json('new_vocabulary')->nullable();
            $table->json('suggestions')->nullable();
            $table->integer('word_count')->default(0);
            $table->integer('response_time_ms')->nullable();
            $table->integer('order_number')->default(0);
            $table->timestamps();

            $table->foreign('conversation_id')->references('id')->on('english_ai_conversations')->onDelete('cascade');
            $table->index(['conversation_id', 'order_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('english_ai_messages');
    }
};
