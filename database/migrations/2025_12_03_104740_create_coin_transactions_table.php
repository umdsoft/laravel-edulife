<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coin_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
            
            $table->enum('type', ['earn', 'spend', 'refund', 'admin_add', 'admin_remove']);
            $table->integer('amount'); // Positive or negative
            $table->unsignedBigInteger('balance_after');
            
            // Source/Reason
            $table->string('source'); // mission, battle, tournament, shop, admin
            $table->string('description');
            $table->uuidMorphs('transactionable'); // Polymorphic relation
            
            $table->timestamps();
            
            $table->index(['user_id', 'created_at']);
            $table->index(['user_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('coin_transactions');
    }
};
