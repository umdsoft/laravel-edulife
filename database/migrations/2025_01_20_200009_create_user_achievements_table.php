<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Unlocked achievements per user (general achievement system).
     *
     * Previously this table belonged to the removed English module and pointed
     * at english_achievements; existing databases are converted by
     * 2026_10_19_000001_remove_english_module_tables.
     */
    public function up(): void
    {
        Schema::create('user_achievements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('achievement_id')->constrained('achievements')->cascadeOnDelete();
            $table->integer('xp_rewarded')->default(0);
            $table->integer('coin_rewarded')->default(0);
            $table->timestamp('earned_at')->nullable();
            $table->boolean('is_claimed')->default(false);
            $table->timestamp('claimed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'achievement_id']);
            $table->index('earned_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_achievements');
    }
};
