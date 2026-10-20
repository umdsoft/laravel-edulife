<?php

namespace Tests\Concerns;

use App\Models\CoinPackage;
use App\Models\OlympiadRegistration;
use App\Models\PaymentOrder;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Payments\CheckoutService;
use Illuminate\Testing\TestResponse;

/**
 * Helpers for the payment tests. Only inbound provider callbacks are simulated;
 * no real Payme / Click API is called.
 */
trait MakesPayments
{
    use CreatesCourses, CreatesOlympiads;

    protected const PAYME_TEST_KEY = 'TEST-KEY-sandbox-0123456789abcdef0123';
    protected const PAYME_KEY = 'PROD-KEY-0123456789abcdef0123456789ab';
    protected const CLICK_SECRET = 'click-secret-key';

    protected function configurePayments(): void
    {
        config([
            'payments.payme.merchant_id' => '5e730e8e0b852a417aa49ceb',
            'payments.payme.key' => self::PAYME_KEY,
            'payments.payme.test_key' => self::PAYME_TEST_KEY,
            'payments.payme.test_mode' => true,
            'payments.payme.ip_check' => false,
            'payments.payme.fiscal.ikpu' => null,
            'payments.click.service_id' => '12345',
            'payments.click.merchant_id' => '6789',
            'payments.click.secret_key' => self::CLICK_SECRET,
            'payments.click.merchant_user_id' => '42',
            'payments.click.ip_check' => false,
            'payments.reverse_on_refund' => true,
        ]);
    }

    /** Payme → POST /payments/payme with Basic auth "Paycom:<key>". */
    protected function payme(string $method, array $params, ?string $key = self::PAYME_TEST_KEY, int $id = 1): TestResponse
    {
        $headers = $key === null ? [] : ['Authorization' => 'Basic '.base64_encode("Paycom:{$key}")];

        return $this->flushHeaders()->withHeaders($headers)->postJson('/payments/payme', ['method' => $method, 'params' => $params, 'id' => $id]);
    }

    protected function paymeAccount(PaymentOrder $order): array
    {
        return ['order_id' => (string) $order->id];
    }

    /** Signed Click Prepare (action=0) body. */
    protected function clickPrepareParams(PaymentOrder $order, array $override = [], ?string $secret = null): array
    {
        $params = array_merge([
            'click_trans_id' => '2281337',
            'service_id' => '12345',
            'click_paydoc_id' => '9988776',
            'merchant_trans_id' => (string) $order->id,
            'amount' => $order->amountForClick(),
            'action' => '0',
            'error' => '0',
            'error_note' => 'Success',
            'sign_time' => '2026-10-20 12:00:00',
        ], $override);
        $params['sign_string'] ??= md5($params['click_trans_id'].$params['service_id'].($secret ?? self::CLICK_SECRET)
            .$params['merchant_trans_id'].$params['amount'].$params['action'].$params['sign_time']);

        return $params;
    }

    /** Signed Click Complete (action=1) body. */
    protected function clickCompleteParams(PaymentOrder $order, int|string $prepareId, array $override = [], ?string $secret = null): array
    {
        $params = array_merge([
            'click_trans_id' => '2281337',
            'service_id' => '12345',
            'click_paydoc_id' => '9988776',
            'merchant_trans_id' => (string) $order->id,
            'merchant_prepare_id' => (string) $prepareId,
            'amount' => $order->amountForClick(),
            'action' => '1',
            'error' => '0',
            'error_note' => 'Success',
            'sign_time' => '2026-10-20 12:00:05',
        ], $override);
        $params['sign_string'] ??= md5($params['click_trans_id'].$params['service_id'].($secret ?? self::CLICK_SECRET)
            .$params['merchant_trans_id'].$params['merchant_prepare_id'].$params['amount'].$params['action'].$params['sign_time']);

        return $params;
    }

    protected function clickPrepare(array $params): TestResponse
    {
        return $this->post('/payments/click/prepare', $params);
    }

    protected function clickComplete(array $params): TestResponse
    {
        return $this->post('/payments/click/complete', $params);
    }

    // ------------------------------------------------------------ products

    protected function paidCourse(int $price = 200000)
    {
        $course = $this->course($this->teacher(), ['is_free' => false, 'price' => $price]);
        $this->lesson($this->module($course));

        return $course;
    }

    protected function coinPackage(int $coins = 500, int $bonus = 50, int $price = 25000): CoinPackage
    {
        return CoinPackage::forceCreate([
            'name' => 'Oʻrta', 'coins' => $coins, 'bonus_coins' => $bonus, 'total_coins' => $coins + $bonus,
            'price' => $price, 'is_active' => true,
        ]);
    }

    protected function plan(array $attributes = []): SubscriptionPlan
    {
        return SubscriptionPlan::forceCreate(array_merge([
            'name' => 'Premium', 'slug' => 'premium-'.uniqid(), 'interval' => 'month', 'interval_count' => 1,
            'price' => 99000, 'is_active' => true, 'features' => ['Barcha kurslar'],
        ], $attributes));
    }

    protected function pendingRegistration(User $student, int $fee = 30000): OlympiadRegistration
    {
        $olympiad = $this->olympiad(['entry_fee' => $fee]);

        return OlympiadRegistration::forceCreate([
            'olympiad_id' => $olympiad->id, 'user_id' => $student->id, 'status' => 'pending_payment',
            'original_price' => $fee, 'final_price' => $fee, 'registered_at' => now(),
        ]);
    }

    protected function order(User $student, string $type, string $productId, string $provider = 'payme', ?string $promo = null): PaymentOrder
    {
        return app(CheckoutService::class)->createOrder($student, $type, $productId, $provider, $promo);
    }
}
