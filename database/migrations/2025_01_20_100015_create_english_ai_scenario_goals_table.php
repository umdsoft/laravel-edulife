<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('english_ai_scenario_goals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('scenario_id');
            $table->string('goal_key', 50);
            $table->string('title', 200);
            $table->string('title_uz', 200);
            $table->text('description')->nullable();
            $table->json('detection_keywords')->nullable();
            $table->json('detection_patterns')->nullable();
            $table->text('detection_prompt')->nullable();
            $table->integer('points')->default(10);
            $table->boolean('is_required')->default(true);
            $table->boolean('is_bonus')->default(false);
            $table->integer('order_number')->default(0);
            $table->timestamps();

            $table->foreign('scenario_id')->references('id')->on('english_ai_scenarios')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('english_ai_scenario_goals');
    }
};
