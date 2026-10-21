<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A paid entry to one exam session (bought with Payme / Click, payment product "exam_session").
 * One row per student and session; a refund keeps the row (status refunded).
 *   paid            the student may start the session
 *   refund_pending  the teacher cancelled the session: the admin returns the money by hand
 *   refunded        returned (admin "Qaytarildi", or a Payme cancel after perform)
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_session_purchases', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('session_id')->constrained('exam_sessions')->cascadeOnDelete();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('payment_order_id')->nullable()->constrained('payment_orders')->nullOnDelete();
            $table->unsignedInteger('amount');
            $table->string('provider', 20)->nullable();
            $table->string('status', 20)->default('paid');
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('refund_requested_at')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->foreignUuid('refunded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('refund_note', 255)->nullable();
            $table->timestamps();

            $table->unique(['session_id', 'user_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_session_purchases');
    }
};
