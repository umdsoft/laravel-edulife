<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * payments.type was enum(course_purchase, subscription, subscription_renewal). Online payments also
 * sell coin packages and olympiad registrations, and every paid order keeps a payments row so the
 * admin dashboard / reports count it. Widen the column to a string (existing values stay valid).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('type', 30)->change();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->enum('type', ['course_purchase', 'subscription', 'subscription_renewal'])->change();
        });
    }
};
