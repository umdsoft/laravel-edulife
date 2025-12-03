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
        Schema::create('user_activities', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->date('activity_date');
            $table->integer('lessons_completed')->default(0);
            $table->integer('tests_taken')->default(0);
            $table->integer('time_spent')->default(0); // sekundlarda
            $table->integer('videos_watched')->default(0);
            $table->integer('watch_time')->default(0); // sekundlarda
            $table->timestamps();
            
            $table->unique(['user_id', 'activity_date']);
            $table->index('user_id');
            $table->index('activity_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_activities');
    }
};
