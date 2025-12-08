<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_skill_levels', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('skill', ['vocabulary', 'grammar', 'listening', 'speaking', 'reading', 'writing']);
            $table->integer('level')->default(1);
            $table->integer('xp')->default(0);
            $table->integer('xp_to_next_level')->default(100);
            $table->integer('exercises_completed')->default(0);
            $table->integer('correct_answers')->default(0);
            $table->integer('total_answers')->default(0);
            $table->decimal('accuracy', 5, 2)->default(0);
            $table->string('estimated_cefr', 5)->nullable();
            $table->json('weekly_progress')->nullable();
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'skill']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_skill_levels');
    }
};
