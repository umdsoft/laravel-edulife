<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('english_game_levels', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('game_id');
            $table->uuid('english_level_id');
            $table->integer('level_number');
            $table->string('name', 50);
            $table->string('name_uz', 50)->nullable();
            $table->enum('difficulty', ['easy', 'medium', 'hard', 'expert'])->default('easy');
            $table->json('level_config')->nullable();
            $table->integer('stars_required')->default(0);
            $table->integer('xp_required')->default(0);
            $table->integer('xp_reward')->default(20);
            $table->integer('coin_reward')->default(10);
            $table->integer('max_stars')->default(3);
            $table->integer('pass_score')->default(70);
            $table->integer('three_star_score')->default(95);
            $table->integer('two_star_score')->default(85);
            $table->integer('order_number')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('game_id')->references('id')->on('english_games')->onDelete('cascade');
            $table->foreign('english_level_id')->references('id')->on('english_levels')->onDelete('cascade');
            $table->unique(['game_id', 'level_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('english_game_levels');
    }
};
