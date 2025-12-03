<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_purchases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            $table->foreignUuid('item_id')->constrained('shop_items')->cascadeOnDelete();
            
            $table->unsignedInteger('price_paid');
            $table->unsignedInteger('quantity')->default(1);
            
            // For consumables
            $table->unsignedInteger('remaining_uses')->nullable();
            
            // For timed items
            $table->dateTime('expires_at')->nullable();
            
            // Status
            $table->boolean('is_active')->default(true);
            $table->boolean('is_equipped')->default(false); // For avatars, themes
            
            $table->timestamps();
            
            $table->index(['user_id', 'is_active']);
            $table->index(['user_id', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_purchases');
    }
};
