<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('english_games', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('category_id');
            $table->string('slug', 100)->unique();
            $table->string('code', 50)->unique();
            $table->string('name', 100);
            $table->string('name_uz', 100);
            $table->text('description')->nullable();
            $table->text('description_uz')->nullable();
            $table->text('instructions')->nullable();
            $table->text('instructions_uz')->nullable();
            $table->enum('game_type', ['word_game', 'grammar_game', 'listening_game', 'speaking_game', 'memory_game', 'speed_game', 'quiz_game', 'matching_game']);
            $table->json('skills_focus')->nullable();
            $table->json('game_config')->nullable();
            $table->uuid('min_level_id')->nullable();
            $table->integer('min_xp')->default(0);
            $table->integer('xp_reward_min')->default(10);
            $table->integer('xp_reward_max')->default(50);
            $table->integer('coin_reward_min')->default(5);
            $table->integer('coin_reward_max')->default(25);
            $table->string('icon', 50)->nullable();
            $table->string('color', 20)->nullable();
            $table->string('thumbnail')->nullable();
            $table->string('banner_image')->nullable();
            $table->bigInteger('total_plays')->default(0);
            $table->decimal('average_score', 5, 2)->default(0);
            $table->decimal('average_time', 8, 2)->default(0);
            $table->boolean('is_premium')->default(false);
            $table->boolean('is_featured')->default(false);
            $table->integer('order_number')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('category_id')->references('id')->on('english_game_categories')->onDelete('cascade');
            $table->foreign('min_level_id')->references('id')->on('english_levels')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('english_games');
    }
};
