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
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->enum('type', ['course_purchase', 'subscription', 'subscription_renewal']);
            $table->foreignUuid('course_id')->nullable()->constrained()->onDelete('set null');
            // subscription_plan_id foreign key qo'shiladi subscription_plans yaratilgandan keyin
            $table->uuid('subscription_plan_id')->nullable();
            $table->decimal('amount', 12, 2);
            $table->decimal('original_amount', 12, 2)->nullable();
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->string('currency', 3)->default('UZS');
            $table->enum('status', ['pending', 'processing', 'completed', 'failed', 'refunded', 'cancelled'])->default('pending');
            $table->enum('provider', ['payme', 'click', 'uzum', 'manual'])->nullable();
            $table->string('provider_transaction_id')->nullable();
            $table->json('provider_response')->nullable();
            // promo_code_id foreign key qo'shiladi promo_codes yaratilgandan keyin
            $table->uuid('promo_code_id')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->decimal('refund_amount', 12, 2)->nullable();
            $table->text('refund_reason')->nullable();
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('type');
            $table->index('course_id');
            $table->index('status');
            $table->index('provider');
            $table->index('provider_transaction_id');
            $table->index('paid_at');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
