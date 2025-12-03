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
        Schema::table('users', function (Blueprint $table) {
            // XP va Level
            $table->bigInteger('xp_total')->default(0)->after('role');
            $table->integer('level')->default(1)->after('xp_total');
            $table->string('title', 100)->nullable()->after('level'); // "Mohir O'quvchi"
            
            // COIN
            $table->bigInteger('coin_balance')->default(0)->after('title');
            $table->bigInteger('coin_earned_this_month')->default(0)->after('coin_balance');
            $table->bigInteger('coin_spent_total')->default(0)->after('coin_earned_this_month');
            
            // Battle/ELO
            $table->integer('elo_rating')->default(1000)->after('coin_spent_total');
            $table->enum('battle_rank', ['bronze_1', 'bronze_2', 'bronze_3', 'silver_1', 'silver_2', 'silver_3', 'gold_1', 'gold_2', 'gold_3', 'platinum_1', 'platinum_2', 'platinum_3', 'diamond_1', 'diamond_2', 'diamond_3', 'master'])->default('bronze_1')->after('elo_rating');
            $table->integer('battles_won')->default(0)->after('battle_rank');
            $table->integer('battles_total')->default(0)->after('battles_won');
            
            // Streak
            $table->integer('streak_current')->default(0)->after('battles_total');
            $table->integer('streak_best')->default(0)->after('streak_current');
            $table->date('streak_last_date')->nullable()->after('streak_best');
            
            // Indexes
            $table->index('xp_total');
            $table->index('level');
            $table->index('elo_rating');
            $table->index('battle_rank');
            $table->index('coin_balance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['xp_total']);
            $table->dropIndex(['level']);
            $table->dropIndex(['elo_rating']);
            $table->dropIndex(['battle_rank']);
            $table->dropIndex(['coin_balance']);
            
            $table->dropColumn([
                'xp_total', 'level', 'title',
                'coin_balance', 'coin_earned_this_month', 'coin_spent_total',
                'elo_rating', 'battle_rank', 'battles_won', 'battles_total',
                'streak_current', 'streak_best', 'streak_last_date'
            ]);
        });
    }
};
