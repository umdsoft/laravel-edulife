<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * "Shanba imtihonlari kiradi": an active subscription on such a plan opens every paid Saturday exam.
 * A real column (not the features JSON): features is a list of display strings shown on the plans
 * page, while this flag is a rule the server checks on every exam start.
 * The seeded paid plans include exams, the trial does not (owner may change it in Admin -> Obuna tariflari).
 */
return new class extends Migration
{
    private const PAID_PLANS = ['standart', 'standart-yearly', 'premium', 'premium-yearly', 'vip', 'vip-yearly'];

    public function up(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->boolean('includes_exams')->default(false)->after('trial_days');
        });

        DB::table('subscription_plans')->whereIn('slug', self::PAID_PLANS)->update(['includes_exams' => true]);
    }

    public function down(): void
    {
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn('includes_exams');
        });
    }
};
