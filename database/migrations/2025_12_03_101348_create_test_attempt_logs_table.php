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
        Schema::create('test_attempt_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('attempt_id')->constrained('test_attempts')->cascadeOnDelete();
            
            $table->enum('event_type', [
                'test_started',
                'test_submitted',
                'test_expired',
                'question_viewed',
                'question_answered',
                'question_skipped',
                'tab_switch',
                'fullscreen_exit',
                'copy_attempt',
                'paste_attempt',
                'right_click',
                'screenshot_attempt',
                'connection_lost',
                'connection_restored',
            ]);
            
            $table->json('event_data')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->dateTime('occurred_at');
            
            $table->index(['attempt_id', 'event_type']);
            $table->index(['attempt_id', 'occurred_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('test_attempt_logs');
    }
};
