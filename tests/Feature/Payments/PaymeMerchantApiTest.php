<?php

namespace Tests\Feature\Payments;

use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\PaymentOrder;
use App\Models\PaymentTransaction;
use App\Services\CoinService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MakesPayments;
use Tests\TestCase;

/**
 * Payme Merchant API (https://developer.help.paycom.uz/metody-merchant-api/) — every method and
 * error path, following the sandbox scenarios (https://developer.help.paycom.uz/pesochnitsa).
 */
class PaymeMerchantApiTest extends TestCase
{
    use RefreshDatabase, MakesPayments;

    private PaymentOrder $order;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configurePayments();
        $this->student = $this->studentUser();
        $this->courseModel = $this->paidCourse(200000);
        $this->order = $this->order($this->student, PaymentOrder::TYPE_COURSE, $this->courseModel->id);
    }

    private $student;

    private $courseModel;

    private function paymeId(string $suffix = 'a'): string
    {
        return substr(str_pad('5305e3bab097f420a62ced0'.$suffix, 24, '0'), 0, 24);
    }

    private function create(string $id, ?int $amount = null, ?array $account = null, ?int $time = null)
    {
        return $this->payme('CreateTransaction', [
            'id' => $id,
            'time' => $time ?? PaymentTransaction::nowMs(),
            'amount' => $amount ?? 20000000,
            'account' => $account ?? $this->paymeAccount($this->order),
        ]);
    }

    // --------------------------------------------------------------- protocol

    public function test_amounts_are_in_tiyin(): void
    {
        $this->assertSame(20000000, $this->order->amount_tiyin);
    }

    public function test_wrong_or_missing_authorization_is_rejected_with_32504(): void
    {
        $params = ['amount' => 20000000, 'account' => $this->paymeAccount($this->order)];

        $this->payme('CheckPerformTransaction', $params, null)->assertOk()->assertJsonPath('error.code', -32504);
        $this->payme('CheckPerformTransaction', $params, 'wrong-key')->assertJsonPath('error.code', -32504);
        // test mode: the production key is not accepted
        $this->payme('CheckPerformTransaction', $params, self::PAYME_KEY)->assertJsonPath('error.code', -32504);
        // a wrong login
        $this->flushHeaders()->withHeaders(['Authorization' => 'Basic '.base64_encode('Admin:'.self::PAYME_TEST_KEY)])
            ->postJson('/payments/payme', ['method' => 'CheckPerformTransaction', 'params' => $params, 'id' => 7])
            ->assertJsonPath('error.code', -32504)
            ->assertJsonPath('id', 7)
            ->assertJsonStructure(['error' => ['message' => ['uz', 'ru', 'en']]]);

        // production mode: only the production key
        config(['payments.payme.test_mode' => false]);
        $this->payme('CheckPerformTransaction', $params, self::PAYME_TEST_KEY)->assertJsonPath('error.code', -32504);
        $this->payme('CheckPerformTransaction', $params, self::PAYME_KEY)->assertJsonPath('result.allow', true);
    }

    public function test_ip_allowlist_when_enabled(): void
    {
        config(['payments.payme.ip_check' => true]);
        $this->payme('CheckPerformTransaction', ['amount' => 20000000, 'account' => $this->paymeAccount($this->order)])
            ->assertJsonPath('error.code', -32504);

        $this->flushHeaders()->withServerVariables(['REMOTE_ADDR' => '185.234.113.5'])
            ->withHeaders(['Authorization' => 'Basic '.base64_encode('Paycom:'.self::PAYME_TEST_KEY)])
            ->postJson('/payments/payme', ['method' => 'CheckPerformTransaction', 'params' => ['amount' => 20000000, 'account' => $this->paymeAccount($this->order)], 'id' => 1])
            ->assertJsonPath('result.allow', true);
    }

    public function test_protocol_errors(): void
    {
        $auth = 'Basic '.base64_encode('Paycom:'.self::PAYME_TEST_KEY);

        // not POST
        $this->flushHeaders()->withHeaders(['Authorization' => $auth])->get('/payments/payme')
            ->assertOk()->assertJsonPath('error.code', -32300);

        // broken JSON
        $this->call('POST', '/payments/payme', [], [], [], ['HTTP_AUTHORIZATION' => $auth, 'CONTENT_TYPE' => 'application/json'], '{"method": ')
            ->assertOk()->assertJsonPath('error.code', -32700);

        // no params
        $this->flushHeaders()->withHeaders(['Authorization' => $auth])->postJson('/payments/payme', ['method' => 'CheckTransaction', 'id' => 3])
            ->assertJsonPath('error.code', -32600);

        // unknown method: its name in data
        $this->payme('ChangeEverything', [])->assertJsonPath('error.code', -32601)->assertJsonPath('error.data', 'ChangeEverything');

        // wrong field types
        $this->payme('CheckPerformTransaction', ['amount' => 'lots', 'account' => $this->paymeAccount($this->order)])
            ->assertJsonPath('error.code', -32600);
        $this->payme('PerformTransaction', ['id' => ['x']])->assertJsonPath('error.code', -32600);
    }

    public function test_the_webhook_has_no_session_csrf_or_auth_middleware(): void
    {
        $router = app('router');
        foreach (['payments.payme', 'payments.click.prepare', 'payments.click.complete'] as $name) {
            $middleware = $router->gatherRouteMiddleware($router->getRoutes()->getByName($name));
            $this->assertNotContains(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class, $middleware, $name);
            $this->assertNotContains(\Illuminate\Session\Middleware\StartSession::class, $middleware, $name);
            $this->assertEmpty(array_filter($middleware, fn ($m) => str_starts_with($m, 'auth') || str_contains($m, 'Authenticate')), $name);
        }
    }

    // ------------------------------------------------- CheckPerformTransaction

    public function test_check_perform_transaction(): void
    {
        $this->payme('CheckPerformTransaction', ['amount' => 20000000, 'account' => $this->paymeAccount($this->order)])
            ->assertOk()
            ->assertExactJson(['jsonrpc' => '2.0', 'id' => 1, 'result' => ['allow' => true]]);
    }

    public function test_check_perform_transaction_errors(): void
    {
        // wrong amount
        $this->payme('CheckPerformTransaction', ['amount' => 19999900, 'account' => $this->paymeAccount($this->order)])
            ->assertJsonPath('error.code', -31001);
        // non-existent order (account error: localized message, data = field name)
        $this->payme('CheckPerformTransaction', ['amount' => 20000000, 'account' => ['order_id' => '999999']])
            ->assertJsonPath('error.code', -31050)
            ->assertJsonPath('error.data', 'order_id')
            ->assertJsonPath('error.message.uz', 'Buyurtma topilmadi');
        $this->payme('CheckPerformTransaction', ['amount' => 20000000, 'account' => ['phone' => '901234567']])
            ->assertJsonPath('error.code', -31050);
        // an order made for Click cannot be paid with Payme
        $clickOrder = $this->order($this->studentUser(), PaymentOrder::TYPE_COURSE, $this->courseModel->id, 'click');
        $this->payme('CheckPerformTransaction', ['amount' => 20000000, 'account' => $this->paymeAccount($clickOrder)])
            ->assertJsonPath('error.code', -31050);
        // student got the course another way meanwhile
        Enrollment::create(['user_id' => $this->student->id, 'course_id' => $this->courseModel->id, 'access_type' => 'gift', 'status' => 'active']);
        $this->payme('CheckPerformTransaction', ['amount' => 20000000, 'account' => $this->paymeAccount($this->order)])
            ->assertJsonPath('error.code', -31052);
        // an order that is no longer pending
        $this->order->forceFill(['status' => PaymentOrder::STATUS_CANCELLED])->save();
        $this->payme('CheckPerformTransaction', ['amount' => 20000000, 'account' => $this->paymeAccount($this->order)])
            ->assertJsonPath('error.code', -31051);
    }

    public function test_check_perform_transaction_returns_fiscal_detail_when_configured(): void
    {
        config(['payments.payme.fiscal' => ['ikpu' => '10305008002000000', 'package_code' => '1497583', 'vat_percent' => 0]]);

        $this->payme('CheckPerformTransaction', ['amount' => 20000000, 'account' => $this->paymeAccount($this->order)])
            ->assertJsonPath('result.allow', true)
            ->assertJsonPath('result.detail.receipt_type', 0)
            ->assertJsonPath('result.detail.items.0.price', 20000000)
            ->assertJsonPath('result.detail.items.0.count', 1)
            ->assertJsonPath('result.detail.items.0.code', '10305008002000000');
    }

    // ------------------------------------------------------- CreateTransaction

    public function test_create_transaction_and_repeated_create_returns_the_same_answer(): void
    {
        $first = $this->create($this->paymeId())->assertOk()->assertJsonPath('result.state', 1)->json('result');
        $this->assertIsInt($first['create_time']);
        $this->assertIsString($first['transaction']);

        // Payme repeats the call when the answer is lost
        $this->create($this->paymeId())->assertJsonPath('result', $first);
        $this->assertSame(1, PaymentTransaction::count());
        $this->assertSame('processing', Payment::find($this->order->payment_id)->status);
    }

    public function test_create_transaction_errors(): void
    {
        $this->create($this->paymeId('b'), 100)->assertJsonPath('error.code', -31001);
        $this->create($this->paymeId('b'), null, ['order_id' => '424242'])->assertJsonPath('error.code', -31050);
        $this->assertSame(0, PaymentTransaction::count());

        $this->create($this->paymeId())->assertJsonPath('result.state', 1);
        // one-time account: a second transaction for an order awaiting payment
        $this->create($this->paymeId('c'))->assertJsonPath('error.code', -31008);
        $this->assertSame(1, PaymentTransaction::count());
    }

    public function test_create_transaction_after_the_12_hour_timeout(): void
    {
        $old = PaymentTransaction::nowMs() - 43_200_000 - 1000;
        $this->create($this->paymeId(), null, null, $old)->assertJsonPath('result.state', 1);

        // the repeated call finds it timed out: cancelled with reason 4, -31008
        $this->create($this->paymeId(), null, null, $old)->assertJsonPath('error.code', -31008);
        $tx = PaymentTransaction::firstOrFail();
        $this->assertSame(-1, $tx->state);
        $this->assertSame(4, $tx->reason);
        $this->payme('CheckTransaction', ['id' => $this->paymeId()])->assertJsonPath('result.state', -1)->assertJsonPath('result.reason', 4);

        // the order can be paid with a new transaction
        $this->create($this->paymeId('d'))->assertJsonPath('result.state', 1);
    }

    public function test_a_stale_transaction_does_not_block_a_new_one(): void
    {
        $this->create($this->paymeId(), null, null, PaymentTransaction::nowMs() - 43_300_000)->assertJsonPath('result.state', 1);

        $this->create($this->paymeId('e'))->assertJsonPath('result.state', 1);
        $this->assertSame(4, PaymentTransaction::where('provider_transaction_id', $this->paymeId())->value('reason'));
    }

    // ------------------------------------------------------ PerformTransaction

    public function test_perform_transaction_fulfils_once(): void
    {
        $this->create($this->paymeId());

        $result = $this->payme('PerformTransaction', ['id' => $this->paymeId()])
            ->assertJsonPath('result.state', 2)->json('result');
        $this->assertGreaterThan(0, $result['perform_time']);

        // repeated: same answer, no second enrollment
        $this->payme('PerformTransaction', ['id' => $this->paymeId()])->assertJsonPath('result', $result);

        $this->assertSame(1, Enrollment::where('user_id', $this->student->id)->where('course_id', $this->courseModel->id)->count());
        $order = $this->order->fresh();
        $this->assertSame('paid', $order->status);
        $this->assertNotNull($order->fulfilled_at);
        $payment = Payment::find($order->payment_id);
        $this->assertSame('completed', $payment->status);
        $this->assertSame('payme', $payment->provider);
        $this->assertSame($this->paymeId(), $payment->provider_transaction_id);
        $this->assertTrue($this->student->fresh()->isEnrolled($this->courseModel));

        // the order is now paid
        $this->payme('CheckPerformTransaction', ['amount' => 20000000, 'account' => $this->paymeAccount($this->order)])
            ->assertJsonPath('error.code', -31051);
        $this->create($this->paymeId('f'))->assertJsonPath('error.code', -31051);
    }

    public function test_perform_transaction_errors(): void
    {
        $this->payme('PerformTransaction', ['id' => $this->paymeId('9')])->assertJsonPath('error.code', -31003);

        // timed out: cancelled (reason 4) and -31008, nothing granted
        $this->create($this->paymeId(), null, null, PaymentTransaction::nowMs() - 43_200_001 - 5)->assertJsonPath('result.state', 1);
        $this->payme('PerformTransaction', ['id' => $this->paymeId()])->assertJsonPath('error.code', -31008);
        $this->assertSame(-1, PaymentTransaction::first()->state);
        $this->assertSame(4, PaymentTransaction::first()->reason);
        $this->assertFalse($this->student->fresh()->isEnrolled($this->courseModel));

        // cancelled transaction cannot be performed
        $this->create($this->paymeId('2'))->assertJsonPath('result.state', 1);
        $this->payme('CancelTransaction', ['id' => $this->paymeId('2'), 'reason' => 3])->assertJsonPath('result.state', -1);
        $this->payme('PerformTransaction', ['id' => $this->paymeId('2')])->assertJsonPath('error.code', -31008);
        $this->assertSame('pending', $this->order->fresh()->status);
    }

    // ------------------------------------------------------- CancelTransaction

    public function test_cancel_a_created_transaction_and_repeat(): void
    {
        $this->create($this->paymeId());

        $first = $this->payme('CancelTransaction', ['id' => $this->paymeId(), 'reason' => 1])
            ->assertJsonPath('result.state', -1)->json('result');
        $this->assertGreaterThan(0, $first['cancel_time']);
        $this->payme('CancelTransaction', ['id' => $this->paymeId(), 'reason' => 1])->assertJsonPath('result', $first);

        $this->payme('CheckTransaction', ['id' => $this->paymeId()])
            ->assertJsonPath('result.state', -1)
            ->assertJsonPath('result.reason', 1)
            ->assertJsonPath('result.perform_time', 0);
        $this->assertSame('cancelled', Payment::find($this->order->payment_id)->status);
        // the order is free again: a new transaction may pay it
        $this->create($this->paymeId('3'))->assertJsonPath('result.state', 1);
    }

    public function test_cancel_after_perform_reverses_the_course_access(): void
    {
        $this->create($this->paymeId());
        $this->payme('PerformTransaction', ['id' => $this->paymeId()]);

        $result = $this->payme('CancelTransaction', ['id' => $this->paymeId(), 'reason' => 5])
            ->assertJsonPath('result.state', -2)->json('result');
        $this->payme('CancelTransaction', ['id' => $this->paymeId(), 'reason' => 5])->assertJsonPath('result', $result);

        $this->assertFalse($this->student->fresh()->isEnrolled($this->courseModel));
        $this->assertSame('refunded', $this->order->fresh()->status);
        $this->assertSame('refunded', Payment::find($this->order->payment_id)->status);
        $this->payme('CheckTransaction', ['id' => $this->paymeId()])->assertJsonPath('result.state', -2)->assertJsonPath('result.reason', 5);
        $this->payme('PerformTransaction', ['id' => $this->paymeId()])->assertJsonPath('error.code', -31008);
    }

    public function test_cancel_errors(): void
    {
        $this->payme('CancelTransaction', ['id' => $this->paymeId('7'), 'reason' => 1])->assertJsonPath('error.code', -31003);
        $this->payme('CancelTransaction', ['id' => $this->paymeId('7')])->assertJsonPath('error.code', -32600);

        // coins already spent: the service was used, cannot cancel (-31007)
        $package = $this->coinPackage(500, 0, 25000);
        $order = $this->order($this->student, PaymentOrder::TYPE_COIN_PACKAGE, $package->id);
        $this->create($this->paymeId('8'), 2500000, $this->paymeAccount($order))->assertJsonPath('result.state', 1);
        $this->payme('PerformTransaction', ['id' => $this->paymeId('8')])->assertJsonPath('result.state', 2);
        $this->assertSame(500, $this->student->fresh()->coins);
        app(CoinService::class)->spendCoins($this->student->fresh(), 400, 'shop', 'Avatar');

        $this->payme('CancelTransaction', ['id' => $this->paymeId('8'), 'reason' => 5])->assertJsonPath('error.code', -31007);
        $this->assertSame(2, PaymentTransaction::where('provider_transaction_id', $this->paymeId('8'))->value('state'));
        $this->assertSame('paid', $order->fresh()->status);

        // refunds can be switched off
        config(['payments.reverse_on_refund' => false]);
        $this->create($this->paymeId());
        $this->payme('PerformTransaction', ['id' => $this->paymeId()]);
        $this->payme('CancelTransaction', ['id' => $this->paymeId(), 'reason' => 5])->assertJsonPath('error.code', -31007);
    }

    // ------------------------------------------------- CheckTransaction / GetStatement

    public function test_check_transaction(): void
    {
        $this->payme('CheckTransaction', ['id' => $this->paymeId()])->assertJsonPath('error.code', -31003);

        $created = $this->create($this->paymeId())->json('result');
        $this->payme('CheckTransaction', ['id' => $this->paymeId()])->assertExactJson(['jsonrpc' => '2.0', 'id' => 1, 'result' => [
            'create_time' => $created['create_time'], 'perform_time' => 0, 'cancel_time' => 0,
            'transaction' => $created['transaction'], 'state' => 1, 'reason' => null,
        ]]);
    }

    public function test_get_statement(): void
    {
        $t1 = PaymentTransaction::nowMs() - 5000;
        $this->create($this->paymeId(), null, null, $t1);
        $this->payme('PerformTransaction', ['id' => $this->paymeId()]);

        $other = $this->order($this->studentUser(), PaymentOrder::TYPE_COURSE, $this->courseModel->id);
        $t2 = $t1 + 1000;
        $this->create($this->paymeId('5'), null, $this->paymeAccount($other), $t2);

        $list = $this->payme('GetStatement', ['from' => $t1, 'to' => $t2])->assertOk()->json('result.transactions');
        $this->assertCount(2, $list);
        $this->assertSame($this->paymeId(), $list[0]['id']);
        $this->assertSame($t1, $list[0]['time']);
        $this->assertSame(20000000, $list[0]['amount']);
        $this->assertSame(['order_id' => (string) $this->order->id], $list[0]['account']);
        $this->assertSame(2, $list[0]['state']);
        $this->assertGreaterThan(0, $list[0]['perform_time']);
        $this->assertSame(1, $list[1]['state']);
        $this->assertArrayHasKey('reason', $list[1]);

        $this->payme('GetStatement', ['from' => $t2 + 1, 'to' => $t2 + 100000])->assertJsonPath('result.transactions', []);
        $this->payme('GetStatement', ['from' => 'yesterday', 'to' => $t2])->assertJsonPath('error.code', -32600);
    }

    public function test_set_fiscal_data(): void
    {
        $this->create($this->paymeId());
        $fiscal = ['receipt_id' => 121, 'status_code' => 0, 'message' => 'accepted', 'terminal_id' => 'EP000000000025',
            'fiscal_sign' => '800031554082', 'qr_code_url' => 'https://ofd.soliq.uz/check?t=1', 'date' => '20220706221021'];

        $this->payme('SetFiscalData', ['id' => $this->paymeId(), 'type' => 'PERFORM', 'fiscal_data' => $fiscal])
            ->assertJsonPath('result.success', true);
        $this->assertSame('800031554082', PaymentTransaction::first()->meta['fiscal']['perform_data']['fiscal_sign']);

        $this->payme('SetFiscalData', ['id' => $this->paymeId('6'), 'type' => 'PERFORM', 'fiscal_data' => $fiscal])
            ->assertJsonPath('error.code', -32001);
        $this->payme('SetFiscalData', ['id' => $this->paymeId(), 'type' => 'REFUND', 'fiscal_data' => $fiscal])
            ->assertJsonPath('error.code', -32602);
    }

    /** The sandbox flow end to end: check → create ×2 → check → perform ×2 → check → cancel → check. */
    public function test_full_sandbox_conversation(): void
    {
        $account = $this->paymeAccount($this->order);
        $id = $this->paymeId();

        $this->payme('CheckPerformTransaction', ['amount' => 20000000, 'account' => $account], id: 101)
            ->assertJsonPath('id', 101)->assertJsonPath('result.allow', true);
        $created = $this->create($id)->assertJsonPath('result.state', 1)->json('result');
        $this->create($id)->assertJsonPath('result', $created);
        $this->payme('CheckTransaction', ['id' => $id])->assertJsonPath('result.state', 1);
        $this->create($this->paymeId('1'))->assertJsonPath('error.code', -31008);

        $performed = $this->payme('PerformTransaction', ['id' => $id])->assertJsonPath('result.state', 2)->json('result');
        $this->payme('PerformTransaction', ['id' => $id])->assertJsonPath('result', $performed);
        $this->payme('CheckTransaction', ['id' => $id])
            ->assertJsonPath('result.state', 2)
            ->assertJsonPath('result.perform_time', $performed['perform_time'])
            ->assertJsonPath('result.transaction', $created['transaction']);

        $cancelled = $this->payme('CancelTransaction', ['id' => $id, 'reason' => 5])->assertJsonPath('result.state', -2)->json('result');
        $this->payme('CancelTransaction', ['id' => $id, 'reason' => 5])->assertJsonPath('result', $cancelled);
        $this->payme('CheckTransaction', ['id' => $id])
            ->assertJsonPath('result.state', -2)
            ->assertJsonPath('result.cancel_time', $cancelled['cancel_time'])
            ->assertJsonPath('result.reason', 5);

        $this->assertSame(0, Enrollment::where('user_id', $this->student->id)->count());
    }
}
