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
        Schema::create('user_streaks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->date('streak_date');
            $table->integer('day_number'); // Streak ning nechanchi kuni
            $table->decimal('multiplier', 3, 2)->default(1.00); // XP multiplier
            $table->integer('xp_bonus')->default(0);
            $table->integer('coin_bonus')->default(0);
            $table->boolean('bonus_claimed')->default(false);
            $table->timestamp('claimed_at')->nullable();
            $table->timestamps();
            
            $table->unique(['user_id', 'streak_date']);
            $table->index('user_id');
            $table->index('streak_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_streaks');
    }
};
