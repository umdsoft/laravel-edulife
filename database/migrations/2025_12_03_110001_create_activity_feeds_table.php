<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_feeds', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            
            // Activity type
            $table->string('type'); // course_enrolled, course_completed, achievement_unlocked, battle_won, level_up, etc.
            $table->string('title');
            $table->text('description')->nullable();
            
            // Related entity (polymorphic)
            $table->uuidMorphs('activityable');
            
            // Visibility
            $table->boolean('is_public')->default(true);
            
            $table->dateTime('occurred_at');
            $table->timestamps();
            
            $table->index(['user_id', 'occurred_at']);
            $table->index(['user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_feeds');
    }
};
