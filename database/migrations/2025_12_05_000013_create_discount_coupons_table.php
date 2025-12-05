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
        Schema::create('discount_coupons', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 50)->unique();
            $table->foreignUuid('olympiad_id')->nullable()->constrained('olympiads')->onDelete('cascade');
            $table->foreignUuid('series_id')->nullable()->constrained('olympiad_series')->onDelete('cascade');
            $table->foreignUuid('user_id')->nullable()->constrained('users')->onDelete('cascade');
            // Personal coupon if user_id is set
            $table->string('discount_type', 20);
            // 'percent', 'fixed', 'coins'
            $table->decimal('discount_value', 12, 2);
            $table->decimal('max_discount_amount', 12, 2)->nullable();
            $table->string('reason', 50);
            // 'winner_advancement', 'participant_reward', 'promo', 'referral', 'admin', 'special'
            $table->foreignUuid('source_olympiad_id')->nullable()->constrained('olympiads')->onDelete('set null');
            // From which olympiad this coupon was generated
            $table->integer('source_rank')->nullable();
            // What rank was achieved
            $table->timestamp('valid_from')->nullable();
            $table->timestamp('valid_until')->nullable();
            $table->integer('max_uses')->default(1);
            $table->integer('times_used')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Indexes
            $table->index('code');
            $table->index('olympiad_id');
            $table->index('user_id');
            $table->index('is_active');
            $table->index(['valid_from', 'valid_until']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('discount_coupons');
    }
};
