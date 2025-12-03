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
        Schema::create('teacher_earnings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('teacher_id')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('course_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignUuid('payment_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('type', ['course_sale', 'subscription_pool']);
            $table->decimal('gross_amount', 12, 2);
            $table->decimal('commission_rate', 5, 2);
            $table->decimal('commission_amount', 12, 2);
            $table->decimal('net_amount', 12, 2);
            $table->enum('status', ['pending', 'confirmed', 'paid', 'cancelled'])->default('pending');
            $table->foreignUuid('payout_id')->nullable()->constrained('teacher_payouts')->onDelete('set null');
            $table->string('period', 7)->nullable(); // subscription_pool uchun: 2024-01
            $table->decimal('watch_minutes', 10, 2)->nullable(); // pool hisoblash uchun
            $table->decimal('pool_share', 5, 4)->nullable(); // 0.0523 = 5.23%
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();
            
            $table->index('teacher_id');
            $table->index('course_id');
            $table->index('type');
            $table->index('status');
            $table->index('payout_id');
            $table->index('period');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('teacher_earnings');
    }
};
