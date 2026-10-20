<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Telegram WebView (wave 2): a friend's battle challenge. The challenger's waiting
 * battle carries an opaque 8-character code (deep link t.me/<bot>?startapp=battle_<code>,
 * web /student/battles/challenge/<code>) — the battle id itself is never shared.
 * Random matchmaking skips battles that have a code.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('battles', 'invite_code')) {
            return;
        }

        Schema::table('battles', function (Blueprint $table) {
            $table->string('invite_code', 16)->nullable()->unique()->after('status');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('battles', 'invite_code')) {
            return;
        }

        Schema::table('battles', function (Blueprint $table) {
            $table->dropUnique(['invite_code']);
            $table->dropColumn('invite_code');
        });
    }
};
