<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lab Badges - Laboratoriya badgelari
     * 
     * Bu jadval lab_experiments dan oldin yaratilishi kerak
     * chunki experiments jadvalida badge foreign key bor
     */
    public function up(): void
    {
        Schema::create('lab_badges', function (Blueprint $table) {
            $table->uuid('id')->primary();
            
            // Identifikatsiya
            $table->string('slug', 50)->unique();
            
            // Nomi (ko'p tilli)
            $table->string('name', 100);
            $table->string('name_uz', 100);
            $table->string('name_ru', 100)->nullable();
            
            // Tavsif
            $table->text('description')->nullable();
            $table->text('description_uz')->nullable();
            $table->text('description_ru')->nullable();
            
            // Vizual
            $table->string('icon', 100);
            $table->text('icon_svg')->nullable();
            $table->string('color', 20)->nullable();
            $table->string('background_gradient', 100)->nullable();
            
            // Rarity
            $table->enum('rarity', ['common', 'uncommon', 'rare', 'epic', 'legendary'])
                  ->default('common');
            
            // Qanday olish mumkin
            $table->json('earn_condition');
            /*
             * Condition types:
             * - complete_experiments: {type: "complete_experiments", count: 10, category: null}
             * - perfect_score: {type: "perfect_score", count: 5}
             * - complete_category: {type: "complete_category", category: "mechanics"}
             * - streak: {type: "streak", days: 7}
             * - speed_complete: {type: "speed_complete", under_minutes: 15, difficulty: "hard"}
             * - first_experiment: {type: "first_experiment"}
             * - total_xp: {type: "total_xp", amount: 1000}
             */
            
            // Mukofot
            $table->unsignedSmallInteger('xp_reward')->default(50);
            $table->unsignedSmallInteger('coin_reward')->default(10);
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_secret')->default(false); // Hidden until earned
            
            // Display order
            $table->unsignedSmallInteger('order_number')->default(0);
            
            $table->timestamps();
            
            // Indexes
            $table->index('slug');
            $table->index('rarity');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_badges');
    }
};
