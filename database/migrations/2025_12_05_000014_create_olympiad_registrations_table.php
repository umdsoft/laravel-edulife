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
        Schema::create('olympiad_registrations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('olympiad_id')->constrained('olympiads')->onDelete('cascade');
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            
            $table->string('status', 30)->default('pending_payment');
            // 'pending_payment', 'confirmed', 'cancelled', 'refunded', 
            // 'disqualified', 'no_show', 'advanced', 'completed'
            
            // Advanced from previous stage
            $table->foreignUuid('advanced_from_olympiad_id')->nullable()->constrained('olympiads')->onDelete('set null');
            $table->integer('advanced_from_rank')->nullable();
            
            // Discount
            $table->foreignUuid('coupon_id')->nullable()->constrained('discount_coupons')->onDelete('set null');
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            
            // Payment
            $table->decimal('original_price', 12, 2);
            $table->decimal('final_price', 12, 2);
            $table->string('payment_method', 20)->nullable();
            // 'payme', 'click', 'coins', 'free', 'coupon', 'mixed'
            $table->integer('payment_coins')->default(0);
            $table->decimal('payment_cash', 12, 2)->default(0);
            $table->string('payment_status', 20)->default('pending');
            $table->string('payment_transaction_id', 255)->nullable();
            $table->timestamp('paid_at')->nullable();
            
            // School info (for stages)
            $table->foreignUuid('school_id')->nullable()->constrained('schools')->onDelete('set null');
            $table->integer('grade_level')->nullable();
            
            // Demo stats
            $table->boolean('demo_purchased')->default(false);
            $table->integer('demo_attempts_used')->default(0);
            $table->decimal('demo_best_score', 5, 2)->nullable();
            
            $table->timestamp('registered_at')->useCurrent();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
            
            // Unique constraint
            $table->unique(['olympiad_id', 'user_id']);
            
            // Indexes
            $table->index('olympiad_id');
            $table->index('user_id');
            $table->index('status');
            $table->index('payment_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('olympiad_registrations');
    }
};
