<?php

namespace Tests\Feature\Payments;

use App\Models\Payment;
use App\Models\PaymentOrder;
use App\Models\PaymentTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MakesPayments;
use Tests\TestCase;

/**
 * Click SHOP API Prepare / Complete (https://docs.click.uz/shop-api/requests,
 * https://docs.click.uz/shop-api/errors).
 */
class ClickShopApiTest extends TestCase
{
    use RefreshDatabase, MakesPayments;

    private PaymentOrder $order;

    private $student;

    private $package;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configurePayments();
        $this->student = $this->studentUser();
        $this->package = $this->coinPackage(500, 50, 25000);
        $this->order = $this->order($this->student, PaymentOrder::TYPE_COIN_PACKAGE, $this->package->id, 'click');
    }

    private function prepared(array $override = []): int
    {
        return (int) $this->clickPrepare($this->clickPrepareParams($this->order, $override))
            ->assertOk()->assertJsonPath('error', 0)->json('merchant_prepare_id');
    }

    public function test_prepare_and_complete_credit_the_coins_once(): void
    {
        $prepare = $this->clickPrepare($this->clickPrepareParams($this->order))->assertOk()->json();
        $this->assertSame(0, $prepare['error']);
        $this->assertSame('Success', $prepare['error_note']);
        $this->assertSame(2281337, $prepare['click_trans_id']);
        $this->assertSame((string) $this->order->id, $prepare['merchant_trans_id']);
        $this->assertIsInt($prepare['merchant_prepare_id']);
        $this->assertSame(1, PaymentTransaction::first()->state);

        // Prepare repeated with the same click_trans_id: the same prepare id
        $this->clickPrepare($this->clickPrepareParams($this->order))->assertJsonPath('merchant_prepare_id', $prepare['merchant_prepare_id']);
        $this->assertSame(1, PaymentTransaction::count());

        $complete = $this->clickComplete($this->clickCompleteParams($this->order, $prepare['merchant_prepare_id']))->assertOk()->json();
        $this->assertSame(0, $complete['error']);
        $this->assertSame($prepare['merchant_prepare_id'], $complete['merchant_confirm_id']);

        $this->assertSame(550, $this->student->fresh()->coins);
        $this->assertSame('paid', $this->order->fresh()->status);
        $payment = Payment::find($this->order->payment_id);
        $this->assertSame('completed', $payment->status);
        $this->assertSame('coin_package', $payment->type);
        $this->assertSame('click', $payment->provider);

        // repeated Complete: "Already paid", coins credited once
        $this->clickComplete($this->clickCompleteParams($this->order, $prepare['merchant_prepare_id']))->assertJsonPath('error', -4);
        $this->assertSame(550, $this->student->fresh()->coins);
        // Prepare for the paid order
        $this->clickPrepare($this->clickPrepareParams($this->order, ['click_trans_id' => '999']))->assertJsonPath('error', -4);
        $this->clickPrepare($this->clickPrepareParams($this->order))->assertJsonPath('error', -4);
    }

    public function test_amount_written_without_decimals_is_accepted(): void
    {
        $id = $this->prepared(['amount' => '25000']);
        $this->clickComplete($this->clickCompleteParams($this->order, $id, ['amount' => '25000']))->assertJsonPath('error', 0);
    }

    public function test_sign_check(): void
    {
        $this->clickPrepare($this->clickPrepareParams($this->order, [], 'wrong-secret'))
            ->assertJsonPath('error', -1)->assertJsonPath('error_note', 'SIGN CHECK FAILED!');
        // a parameter changed after signing
        $params = $this->clickPrepareParams($this->order);
        $params['amount'] = '1.00';
        $this->clickPrepare($params)->assertJsonPath('error', -1);
        $this->assertSame(0, PaymentTransaction::count());

        $id = $this->prepared();
        $this->clickComplete($this->clickCompleteParams($this->order, $id, [], 'wrong-secret'))->assertJsonPath('error', -1);
        // complete signed with the prepare formula (no merchant_prepare_id)
        $params = $this->clickCompleteParams($this->order, $id);
        $params['sign_string'] = md5($params['click_trans_id'].$params['service_id'].self::CLICK_SECRET.$params['merchant_trans_id'].$params['amount'].$params['action'].$params['sign_time']);
        $this->clickComplete($params)->assertJsonPath('error', -1);
        $this->assertSame(0, $this->student->fresh()->coins);
    }

    public function test_request_errors(): void
    {
        // missing parameter
        $params = $this->clickPrepareParams($this->order);
        unset($params['sign_time']);
        $this->clickPrepare($params)->assertJsonPath('error', -8);

        // wrong action on the endpoint
        $this->clickPrepare($this->clickPrepareParams($this->order, ['action' => '1']))->assertJsonPath('error', -3);
        $this->clickComplete($this->clickCompleteParams($this->order, 1, ['action' => '0']))->assertJsonPath('error', -3);

        // another service
        $this->clickPrepare($this->clickPrepareParams($this->order, ['service_id' => '777']))->assertJsonPath('error', -8);

        // wrong amount
        $this->clickPrepare($this->clickPrepareParams($this->order, ['amount' => '24999.00']))
            ->assertJsonPath('error', -2)->assertJsonPath('error_note', 'Incorrect parameter amount');

        // unknown order, an order for Payme
        $this->clickPrepare($this->clickPrepareParams($this->order, ['merchant_trans_id' => '987654']))->assertJsonPath('error', -5);
        $paymeOrder = $this->order($this->studentUser(), PaymentOrder::TYPE_COIN_PACKAGE, $this->package->id, 'payme');
        $this->clickPrepare($this->clickPrepareParams($paymeOrder))->assertJsonPath('error', -5);

        $this->assertSame(0, PaymentTransaction::count());
    }

    public function test_complete_errors(): void
    {
        $id = $this->prepared();

        // unknown prepare id / prepare id of another click transaction
        $this->clickComplete($this->clickCompleteParams($this->order, 999999))->assertJsonPath('error', -6);
        $this->clickComplete($this->clickCompleteParams($this->order, $id, ['click_trans_id' => '5555']))->assertJsonPath('error', -6);
        // wrong amount
        $this->clickComplete($this->clickCompleteParams($this->order, $id, ['amount' => '1000.00']))->assertJsonPath('error', -2);
        // unknown order
        $this->clickComplete($this->clickCompleteParams($this->order, $id, ['merchant_trans_id' => '777777']))->assertJsonPath('error', -5);

        $this->assertSame(0, $this->student->fresh()->coins);
        $this->assertSame(1, PaymentTransaction::first()->state);
    }

    public function test_click_reports_a_failed_payment_and_the_transaction_is_cancelled(): void
    {
        $id = $this->prepared();

        $this->clickComplete($this->clickCompleteParams($this->order, $id, ['error' => '-5017', 'error_note' => 'Insufficient funds']))
            ->assertJsonPath('error', -9)->assertJsonPath('error_note', 'Transaction cancelled');
        $this->assertSame(-1, PaymentTransaction::first()->state);
        $this->assertSame('cancelled', Payment::find($this->order->payment_id)->status);

        // later attempts for that transaction: cancelled
        $this->clickComplete($this->clickCompleteParams($this->order, $id))->assertJsonPath('error', -9);
        $this->clickPrepare($this->clickPrepareParams($this->order))->assertJsonPath('error', -9);
        $this->assertSame(0, $this->student->fresh()->coins);

        // the order can still be paid with a new Click payment
        $newId = $this->prepared(['click_trans_id' => '2281338']);
        $this->clickComplete($this->clickCompleteParams($this->order, $newId, ['click_trans_id' => '2281338']))->assertJsonPath('error', 0);
        $this->assertSame(550, $this->student->fresh()->coins);
    }

    public function test_two_prepared_payments_only_one_completes(): void
    {
        $a = $this->prepared(['click_trans_id' => '1001']);
        $b = $this->prepared(['click_trans_id' => '1002']);

        $this->clickComplete($this->clickCompleteParams($this->order, $a, ['click_trans_id' => '1001']))->assertJsonPath('error', 0);
        $this->clickComplete($this->clickCompleteParams($this->order, $b, ['click_trans_id' => '1002']))->assertJsonPath('error', -4);

        $this->assertSame(550, $this->student->fresh()->coins);
        $this->assertSame(-1, PaymentTransaction::find($b)->state);
    }

    public function test_cancelled_order_cannot_be_prepared(): void
    {
        $this->order->forceFill(['status' => PaymentOrder::STATUS_CANCELLED])->save();
        $this->clickPrepare($this->clickPrepareParams($this->order))->assertJsonPath('error', -9);

        $this->package->update(['is_active' => false]);
        $this->order->forceFill(['status' => PaymentOrder::STATUS_PENDING])->save();
        $this->clickPrepare($this->clickPrepareParams($this->order))->assertJsonPath('error', -9);
    }

    public function test_ip_allowlist_when_enabled(): void
    {
        config(['payments.click.ip_check' => true, 'payments.click.allowed_ips' => ['10.1.1.1']]);
        $this->clickPrepare($this->clickPrepareParams($this->order))->assertJsonPath('error', -8);

        $this->withServerVariables(['REMOTE_ADDR' => '10.1.1.1'])
            ->post('/payments/click/prepare', $this->clickPrepareParams($this->order))->assertJsonPath('error', 0);
    }
}
