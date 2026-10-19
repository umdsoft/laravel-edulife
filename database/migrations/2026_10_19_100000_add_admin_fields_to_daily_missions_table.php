<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The admin daily-mission form edits code, icon and difficulty, but the
     * daily_missions table never had those columns (every create/update failed).
     */
    public function up(): void
    {
        Schema::table('daily_missions', function (Blueprint $table) {
            if (!Schema::hasColumn('daily_missions', 'code')) {
                $table->string('code', 50)->nullable()->unique()->after('id');
            }
            if (!Schema::hasColumn('daily_missions', 'icon')) {
                $table->string('icon', 50)->nullable()->after('description');
            }
            if (!Schema::hasColumn('daily_missions', 'difficulty')) {
                $table->enum('difficulty', ['easy', 'medium', 'hard'])->default('easy')->after('coin_reward');
            }
        });
    }

    public function down(): void
    {
        Schema::table('daily_missions', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropColumn(['code', 'icon', 'difficulty']);
        });
    }
};
