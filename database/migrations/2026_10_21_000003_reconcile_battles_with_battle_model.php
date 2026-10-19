<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The battles table comes from the 2024 migration (searching/finished,
 * questions_count, finished_at), while the Battle model, MatchmakingService
 * and BattleController were written for the later schema (waiting/completed,
 * total_rounds, current_round, ended_at, expires_at, xp/coin rewards). Every
 * battle page failed on the missing columns (e.g. "Unknown column ended_at").
 *
 * Additive: adds the model's columns, widens status so the model's values fit,
 * makes the topic optional ("any topic" matchmaking) and maps old values.
 * The legacy columns stay.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('battles', function (Blueprint $table) {
            if (! Schema::hasColumn('battles', 'total_rounds')) {
                $table->unsignedInteger('total_rounds')->default(5);
            }
            if (! Schema::hasColumn('battles', 'current_round')) {
                $table->unsignedInteger('current_round')->default(0);
            }
            if (! Schema::hasColumn('battles', 'xp_reward')) {
                $table->unsignedInteger('xp_reward')->default(0);
            }
            if (! Schema::hasColumn('battles', 'coin_reward')) {
                $table->unsignedInteger('coin_reward')->default(0);
            }
            if (! Schema::hasColumn('battles', 'ended_at')) {
                $table->dateTime('ended_at')->nullable();
            }
            if (! Schema::hasColumn('battles', 'expires_at')) {
                $table->dateTime('expires_at')->nullable();
            }
        });

        // enum(searching, matched, ...) cannot hold waiting / completed / expired
        Schema::table('battles', function (Blueprint $table) {
            $table->string('status', 20)->default('waiting')->change();
            $table->uuid('direction_id')->nullable()->change();
        });

        $map = ['searching' => 'waiting', 'matched' => 'in_progress', 'ready' => 'in_progress', 'finished' => 'completed', 'abandoned' => 'cancelled'];
        foreach ($map as $old => $new) {
            DB::table('battles')->where('status', $old)->update(['status' => $new]);
        }

        if (Schema::hasColumn('battles', 'finished_at')) {
            DB::table('battles')->whereNull('ended_at')->whereNotNull('finished_at')->update(['ended_at' => DB::raw('finished_at')]);
        }
        if (Schema::hasColumn('battles', 'questions_count')) {
            DB::table('battles')->update(['total_rounds' => DB::raw('questions_count')]);
        }
    }

    public function down(): void
    {
        Schema::table('battles', function (Blueprint $table) {
            foreach (['total_rounds', 'current_round', 'xp_reward', 'coin_reward', 'ended_at', 'expires_at'] as $column) {
                if (Schema::hasColumn('battles', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
