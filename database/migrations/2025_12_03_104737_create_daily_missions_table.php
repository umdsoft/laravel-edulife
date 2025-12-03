<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_missions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('title');
            $table->text('description');
            $table->string('icon');
            
            // Task type
            $table->string('task_type'); // watch_lesson, complete_test, battle_win, etc.
            $table->unsignedInteger('target_value'); // How many to complete
            
            // Rewards
            $table->unsignedInteger('xp_reward')->default(0);
            $table->unsignedInteger('coin_reward')->default(0);
            
            // Rarity affects how often it appears
            $table->enum('difficulty', ['easy', 'medium', 'hard'])->default('easy');
            $table->unsignedInteger('weight')->default(100); // Selection weight
            
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_missions');
    }
};
