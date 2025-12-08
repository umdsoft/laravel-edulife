<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('english_ai_scenarios', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('level_id');
            $table->string('slug', 100)->unique();
            $table->string('code', 50)->unique();
            $table->string('title', 200);
            $table->string('title_uz', 200);
            $table->text('description')->nullable();
            $table->text('description_uz')->nullable();
            $table->enum('category', ['restaurant', 'hotel', 'airport', 'hospital', 'shopping', 'job_interview', 'school', 'daily_life', 'business', 'travel', 'phone_call', 'social', 'emergency']);
            $table->json('scenario_config');
            $table->json('ai_config')->nullable();
            $table->integer('min_xp')->default(0);
            $table->json('prerequisite_vocabulary')->nullable();
            $table->json('prerequisite_grammar')->nullable();
            $table->integer('xp_reward')->default(50);
            $table->integer('coin_reward')->default(20);
            $table->string('icon', 50)->nullable();
            $table->string('thumbnail')->nullable();
            $table->string('background_image')->nullable();
            $table->integer('times_played')->default(0);
            $table->decimal('average_score', 5, 2)->default(0);
            $table->decimal('average_duration', 8, 2)->default(0);
            $table->integer('order_number')->default(0);
            $table->boolean('is_premium')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('level_id')->references('id')->on('english_levels')->onDelete('cascade');
            $table->index(['category', 'level_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('english_ai_scenarios');
    }
};
