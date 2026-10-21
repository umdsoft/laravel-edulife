<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Website login through the Telegram bot (docs/auth/TELEGRAM_LOGIN.md).
 *
 * kind=code: a 6-digit code the bot sends to the linked chat (only its HMAC is stored);
 * kind=link: a one-time t.me/<bot>?start=login_<token> deep link (only its sha256 is stored).
 * A request belongs to the browser session that started it (its id is kept in that session).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_login_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('kind', 8);                       // code | link
            $table->foreignUuid('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignUuid('telegram_account_id')->nullable()->constrained('telegram_accounts')->nullOnDelete();
            $table->string('role', 16)->nullable();          // link: student | guardian (registration page choice)
            $table->char('code_hash', 64)->nullable();
            $table->char('token_hash', 64)->nullable()->unique();
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->string('status', 16)->default('pending'); // pending | approved | used | cancelled | refused | expired
            $table->string('reason', 48)->nullable();        // refusal / outcome key (exam_bot.login.*)
            $table->boolean('remember')->default(false);
            $table->timestamp('expires_at');
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('used_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'kind', 'status']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_login_requests');
    }
};
