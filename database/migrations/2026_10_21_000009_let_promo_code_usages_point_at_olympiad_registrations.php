<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Admin promo codes now apply to olympiad registrations, which have no
 * payments row (there is no payment gateway). A usage records either the
 * payment or the olympiad registration it discounted.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('promo_code_usages', function (Blueprint $table) {
            $table->uuid('payment_id')->nullable()->change();
        });

        if (!Schema::hasColumn('promo_code_usages', 'olympiad_registration_id')) {
            Schema::table('promo_code_usages', function (Blueprint $table) {
                $table->foreignUuid('olympiad_registration_id')->nullable()->after('payment_id')
                    ->constrained('olympiad_registrations')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('promo_code_usages', 'olympiad_registration_id')) {
            Schema::table('promo_code_usages', function (Blueprint $table) {
                $table->dropConstrainedForeignId('olympiad_registration_id');
            });
        }
    }
};
