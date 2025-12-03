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
        Schema::create('coin_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['earn', 'spend', 'purchase', 'transfer_in', 'transfer_out', 'refund', 'bonus', 'admin']);
            $table->integer('amount'); // + yoki -
            $table->bigInteger('balance_after');
            $table->string('source', 100); // "lesson_complete", "battle_win", "purchase", "subscription"
            $table->string('description', 255);
            $table->json('metadata')->nullable(); // Qo'shimcha ma'lumotlar
            
            // Reference
            $table->string('reference_type')->nullable(); // "App\Models\Lesson"
            $table->uuid('reference_id')->nullable();
            
            // Transfer uchun
            $table->foreignUuid('related_user_id')->nullable()->constrained('users')->onDelete('set null');
            
            // Purchase uchun
            $table->foreignUuid('payment_id')->nullable()->constrained()->onDelete('set null');
            
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('type');
            $table->index('source');
            $table->index('created_at');
            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coin_transactions');
    }
};
