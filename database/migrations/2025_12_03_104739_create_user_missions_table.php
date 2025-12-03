<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_missions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('mission_id')->constrained('daily_missions')->cascadeOnDelete();
            
            // Progress
            $table->unsignedInteger('current_value')->default(0);
            $table->unsignedInteger('target_value');
            $table->boolean('is_completed')->default(false);
            $table->boolean('is_claimed')->default(false);
            
            // Date
            $table->date('assigned_date');
            $table->dateTime('completed_at')->nullable();
            $table->dateTime('claimed_at')->nullable();
            
            $table->timestamps();
            
            $table->unique(['user_id', 'mission_id', 'assigned_date']);
            $table->index(['user_id', 'assigned_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_missions');
    }
};
