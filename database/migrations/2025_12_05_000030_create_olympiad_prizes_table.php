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
        Schema::create('olympiad_prizes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('olympiad_id')->constrained('olympiads')->onDelete('cascade');
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('attempt_id')->nullable()->constrained('olympiad_attempts')->onDelete('set null');
            $table->integer('rank');
            $table->string('prize_type', 20);
            // 'cash', 'coins', 'discount_coupon', 'badge', 'certificate'
            $table->decimal('cash_amount', 15, 2)->default(0);
            $table->integer('coins_amount')->default(0);
            $table->foreignUuid('coupon_id')->nullable()->constrained('discount_coupons')->onDelete('set null');
            $table->uuid('badge_id')->nullable();
            $table->foreignUuid('certificate_id')->nullable()->constrained('olympiad_certificates')->onDelete('set null');
            $table->string('status', 20)->default('pending');
            // 'pending', 'processing', 'delivered', 'failed'
            $table->timestamp('awarded_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index('olympiad_id');
            $table->index('user_id');
            $table->index('rank');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('olympiad_prizes');
    }
};
