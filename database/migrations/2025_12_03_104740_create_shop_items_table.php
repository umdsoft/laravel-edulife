<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('description');
            $table->string('image');
            
            // Type
            $table->enum('type', ['avatar', 'badge', 'theme', 'boost', 'hint', 'extra_life']);
            $table->string('category')->nullable();
            
            // Pricing
            $table->unsignedInteger('price'); // COIN
            $table->unsignedInteger('original_price')->nullable(); // For discounts
            
            // Stock
            $table->unsignedInteger('stock')->nullable(); // null = unlimited
            $table->unsignedInteger('purchased_count')->default(0);
            
            // Limits
            $table->unsignedInteger('max_per_user')->default(1);
            $table->unsignedInteger('min_level')->default(1);
            
            // Duration (for boosts)
            $table->unsignedInteger('duration_hours')->nullable();
            
            // Data (for items that need config)
            $table->json('item_data')->nullable();
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->dateTime('available_from')->nullable();
            $table->dateTime('available_until')->nullable();
            
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            
            $table->index(['is_active', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_items');
    }
};
