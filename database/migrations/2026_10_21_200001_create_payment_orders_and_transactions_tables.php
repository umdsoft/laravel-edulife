<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Online payments (Payme Merchant API, Click SHOP API).
 *
 * payment_orders       — what the student buys; the numeric id is the order number sent to the
 *                        provider (Payme "ac.order_id", Click "merchant_trans_id").
 * payment_transactions — one row per provider transaction (Payme transaction id / Click
 *                        click_trans_id) with the Payme state machine fields needed for
 *                        idempotent CreateTransaction/PerformTransaction/CancelTransaction,
 *                        CheckTransaction and GetStatement
 *                        (https://developer.help.paycom.uz/metody-merchant-api/tipy-dannykh).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('payment_orders')) {
            Schema::create('payment_orders', function (Blueprint $table) {
                $table->id();
                // public id for return URLs (the numeric id is guessable)
                $table->uuid('uuid')->unique();
                $table->foreignUuid('user_id')->constrained()->cascadeOnDelete();
                // course | coin_package | subscription | olympiad_registration
                $table->string('product_type', 30);
                $table->uuid('product_id');
                $table->string('title');
                // payme | click | manual (a promo code made it free)
                $table->string('provider', 10);
                $table->decimal('original_amount', 12, 2);
                $table->decimal('discount_amount', 12, 2)->default(0);
                $table->decimal('amount', 12, 2);
                $table->unsignedBigInteger('amount_tiyin');
                $table->string('currency', 3)->default('UZS');
                $table->uuid('promo_code_id')->nullable();
                $table->uuid('payment_id')->nullable();
                // pending | paid | refunded | cancelled
                $table->string('status', 20)->default('pending');
                $table->timestamp('paid_at')->nullable();
                $table->timestamp('fulfilled_at')->nullable();
                $table->timestamp('refunded_at')->nullable();
                $table->timestamp('cancelled_at')->nullable();
                // what was granted, so a refund can take exactly that back
                $table->json('fulfilment')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();

                $table->foreign('promo_code_id')->references('id')->on('promo_codes')->nullOnDelete();
                $table->foreign('payment_id')->references('id')->on('payments')->nullOnDelete();
                $table->index(['user_id', 'status']);
                $table->index(['product_type', 'product_id']);
                $table->index(['provider', 'status']);
            });
        }

        if (! Schema::hasTable('payment_transactions')) {
            Schema::create('payment_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('payment_order_id')->constrained('payment_orders')->cascadeOnDelete();
                $table->string('provider', 10);
                // Payme: params.id (24 chars); Click: click_trans_id
                $table->string('provider_transaction_id', 64);
                // Payme states: 1 created, 2 performed, -1 cancelled, -2 cancelled after perform.
                // Click uses 1 prepared, 2 completed, -1 cancelled.
                $table->smallInteger('state');
                $table->unsignedBigInteger('amount_tiyin');
                // Payme params.time (ms) — GetStatement filters on it
                $table->unsignedBigInteger('provider_time')->nullable();
                // ms timestamps in the merchant billing (Payme Timestamp type)
                $table->unsignedBigInteger('create_time')->default(0);
                $table->unsignedBigInteger('perform_time')->default(0);
                $table->unsignedBigInteger('cancel_time')->default(0);
                $table->smallInteger('reason')->nullable();
                $table->string('click_paydoc_id', 64)->nullable();
                $table->json('account')->nullable();
                $table->json('meta')->nullable();
                $table->timestamps();

                $table->unique(['provider', 'provider_transaction_id']);
                $table->index(['payment_order_id', 'state']);
                $table->index(['provider', 'provider_time']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
        Schema::dropIfExists('payment_orders');
    }
};
