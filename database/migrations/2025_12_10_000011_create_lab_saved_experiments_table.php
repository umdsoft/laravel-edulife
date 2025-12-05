<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Lab Saved Experiments - Saqlangan tajriba holatlari
     * 
     * Foydalanuvchi tajriba holatini keyinroq davom ettirish uchun saqlashi
     */
    public function up(): void
    {
        Schema::create('lab_saved_experiments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('experiment_id')->constrained('lab_experiments')->cascadeOnDelete();
            
            // Saqlangan holat
            $table->json('saved_state');
            /*
             * {
             *   "parameters": {...},
             *   "component_positions": {...},
             *   "measurements": [...],
             *   "graph_data": {...},
             *   "notes": "..."
             * }
             */
            
            // Meta
            $table->string('name', 255)->nullable(); // User custom name
            $table->text('description')->nullable();
            $table->string('thumbnail', 500)->nullable(); // Auto-generated screenshot
            
            // Sharing (premium feature)
            $table->boolean('is_public')->default(false);
            $table->string('share_code', 20)->unique()->nullable();
            $table->unsignedInteger('view_count')->default(0);
            $table->unsignedSmallInteger('copy_count')->default(0);
            $table->unsignedSmallInteger('like_count')->default(0);
            
            // Tags
            $table->json('tags')->nullable();
            
            $table->timestamps();
            
            // Unique per user per experiment per name
            $table->unique(['user_id', 'experiment_id', 'name']);
            
            // Indexes
            $table->index('user_id');
            $table->index('experiment_id');
            $table->index('share_code');
            $table->index('is_public');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lab_saved_experiments');
    }
};
