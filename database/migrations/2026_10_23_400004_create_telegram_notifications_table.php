<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stage 6: messages the bot sent (reminders, results, reports). The unique
 * (telegram_account_id, kind, ref_id) key makes the scheduled reminders and
 * the event listeners idempotent: a message is never sent twice.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('telegram_notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('telegram_account_id')->constrained('telegram_accounts')->cascadeOnDelete();
            // reminder_eve | reminder_hour | reminder_start | attempt_result | guardian_result | weekly_report
            $table->string('kind', 40);
            $table->string('ref_id', 64);                   // session / attempt / report id
            $table->string('status', 20)->default('sent');  // sent | failed | blocked
            $table->string('error', 255)->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['telegram_account_id', 'kind', 'ref_id'], 'tg_notifications_once');
            $table->index(['kind', 'ref_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('telegram_notifications');
    }
};
