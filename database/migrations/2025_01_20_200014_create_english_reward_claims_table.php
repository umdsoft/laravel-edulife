<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('english_reward_claims', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->uuid('reward_id');

            // Claimed amounts
            $table->integer('xp_claimed')->default(0);
            $table->integer('coins_claimed')->default(0);
            $table->integer('gems_claimed')->default(0);
            $table->json('items_claimed')->nullable();

            // Source
            $table->string('claim_source', 50)->nullable();
            $table->uuid('source_id')->nullable();

            $table->timestamp('claimed_at');
            $table->timestamps();

            $table->foreign('reward_id')->references('id')->on('english_rewards')->onDelete('cascade');

            $table->index(['user_id', 'claimed_at']);
            $table->index(['reward_id', 'claimed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('english_reward_claims');
    }
};
