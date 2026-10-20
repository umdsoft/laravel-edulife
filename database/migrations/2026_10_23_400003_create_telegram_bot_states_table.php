<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stage 6: conversation state of a Telegram user with the bot (the
 * 5-question registration). One row per telegram account; data holds the
 * answers until the account is created.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_bot_states', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('telegram_account_id')->unique()->constrained('telegram_accounts')->cascadeOnDelete();
            $table->string('flow', 30)->default('registration');
            $table->string('step', 30)->nullable();
            $table->json('data')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_bot_states');
    }
};
