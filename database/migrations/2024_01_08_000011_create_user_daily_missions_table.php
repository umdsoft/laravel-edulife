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
        Schema::create('user_daily_missions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('daily_mission_id')->constrained()->onDelete('cascade');
            $table->date('mission_date');
            $table->integer('current_progress')->default(0);
            $table->integer('target_count');
            $table->boolean('is_completed')->default(false);
            $table->boolean('is_claimed')->default(false);
            $table->integer('xp_reward')->default(0);
            $table->integer('coin_reward')->default(0);
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamps();
            
            $table->unique(['user_id', 'daily_mission_id', 'mission_date']);
            $table->index('user_id');
            $table->index('mission_date');
            $table->index(['user_id', 'mission_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_daily_missions');
    }
};
