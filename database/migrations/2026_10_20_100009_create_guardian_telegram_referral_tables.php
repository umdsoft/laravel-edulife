<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Platform wide (not exam specific) tables, so no exam_ prefix:
 *  guardian_student  - guardian user <-> student user, with consent
 *  telegram_accounts - Telegram bot / Mini App link of a user
 *  referral_codes    - personal invite code of a user
 *  referrals         - who invited whom, funnel status
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guardian_student', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('guardian_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('student_id')->constrained('users')->cascadeOnDelete();
            $table->string('relation', 20)->default('guardian'); // mother | father | guardian | other
            $table->timestamp('consent_at')->nullable();          // parental consent for data processing
            $table->string('consent_source', 30)->nullable();     // telegram | web | teacher
            $table->boolean('receives_reports')->default(true);   // Sunday report
            $table->timestamp('verified_at')->nullable();         // phone link confirmed
            $table->timestamps();

            $table->unique(['guardian_id', 'student_id']);
            $table->index('student_id');
        });

        Schema::create('telegram_accounts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            // NULL until the bot user finishes registration / links an account.
            $table->foreignUuid('user_id')->nullable()->unique()->constrained('users')->nullOnDelete();
            $table->unsignedBigInteger('telegram_id')->unique();
            $table->bigInteger('chat_id')->nullable();
            $table->string('username', 64)->nullable();
            $table->string('first_name', 128)->nullable();
            $table->string('last_name', 128)->nullable();
            $table->string('language_code', 10)->nullable();
            $table->boolean('is_blocked')->default(false);         // user blocked the bot
            $table->string('start_payload', 64)->nullable();       // /start <payload> (ref code)
            $table->timestamp('linked_at')->nullable();
            $table->timestamp('last_interaction_at')->nullable();
            $table->timestamps();
        });

        Schema::create('referral_codes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('owner_id')->unique()->constrained('users')->cascadeOnDelete();
            $table->string('code', 32)->unique();
            $table->unsignedInteger('uses_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('referrals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('referral_code_id')->nullable()->constrained('referral_codes')->nullOnDelete();
            $table->foreignUuid('referrer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('referred_id')->unique()->constrained('users')->cascadeOnDelete(); // invited once
            $table->string('status', 20)->default('joined'); // joined | placement_done | subscribed
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('placement_done_at')->nullable();
            $table->timestamp('subscribed_at')->nullable();
            $table->timestamp('reward_granted_at')->nullable();
            $table->timestamps();

            $table->index(['referrer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('referrals');
        Schema::dropIfExists('referral_codes');
        Schema::dropIfExists('telegram_accounts');
        Schema::dropIfExists('guardian_student');
    }
};
