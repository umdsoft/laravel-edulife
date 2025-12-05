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
        Schema::create('olympiad_payment_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('registration_id')->nullable()->constrained('olympiad_registrations')->onDelete('cascade');
            $table->string('payment_type', 20);
            // 'olympiad_registration', 'demo_purchase'
            $table->string('payment_method', 20);
            // 'payme', 'click', 'coins', 'mixed'
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('UZS');
            $table->integer('coins_amount')->default(0);
            $table->string('provider_transaction_id', 255)->nullable();
            $table->json('provider_response')->nullable();
            $table->string('status', 20)->default('pending');
            // 'pending', 'processing', 'completed', 'failed', 'cancelled', 'refunded'
            $table->text('status_message')->nullable();
            $table->timestamps();
            $table->timestamp('completed_at')->nullable();
            $table->ipAddress('ip_address')->nullable();
            
            // Indexes
            $table->index('user_id');
            $table->index('registration_id');
            $table->index('status');
            $table->index('payment_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('olympiad_payment_transactions');
    }
};
