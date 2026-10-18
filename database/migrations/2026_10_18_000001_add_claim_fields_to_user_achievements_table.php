<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * AchievementService::claim() relies on these columns.
     */
    public function up(): void
    {
        Schema::table('user_achievements', function (Blueprint $table) {
            $table->boolean('is_claimed')->default(false)->after('coin_rewarded');
            $table->timestamp('claimed_at')->nullable()->after('is_claimed');
        });
    }

    public function down(): void
    {
        Schema::table('user_achievements', function (Blueprint $table) {
            $table->dropColumn(['is_claimed', 'claimed_at']);
        });
    }
};
