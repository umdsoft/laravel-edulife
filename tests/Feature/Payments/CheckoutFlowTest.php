<?php

namespace Tests\Feature\Payments;

use App\Models\Payment;
use App\Models\PaymentOrder;
use App\Models\PromoCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\MakesPayments;
use Tests\TestCase;

/**
 * The student checkout: server-side price and promo codes, order creation, redirect to
 * Payme / Click, the return page, and the shop / olympiad / subscription entry points.
 */
class CheckoutFlowTest extends TestCase
{
    use RefreshDatabase, MakesPayments;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configurePayments();
    }

    private function decodePaymeUrl(string $url): array
    {
        $this->assertStringStartsWith('https://test.paycom.uz/', $url);
        $decoded = base64_decode(substr($url, strlen('https://test.paycom.uz/')), true);
        $pairs = [];
        foreach (explode(';', $decoded) as $pair) {
            [$k, $v] = explode('=', $pair, 2);
            $pairs[$k] = $v;
        }

        return $pairs;
    }

    public function test_course_checkout_redirects_to_payme_with_the_server_amount(): void
    {
        $student = $this->studentUser();
        $course = $this->paidCourse(200000);

        $response = $this->actingAs($student)->post(route('payments.checkout'), [
            'product_type' => 'course', 'product_id' => $course->id, 'provider' => 'payme',
            'amount' => 1, // ignored: the price comes from the database
        ]);

        $order = PaymentOrder::sole();
        $this->assertSame(20000000, $order->amount_tiyin);
        $this->assertSame('pending', $order->status);
        $this->assertSame('course_purchase', Payment::find($order->payment_id)->type);
        $this->assertSame('pending', Payment::find($order->payment_id)->status);

        $location = $response->assertRedirect()->headers->get('Location');
        $params = $this->decodePaymeUrl($location);
        $this->assertSame('5e730e8e0b852a417aa49ceb', $params['m']);
        $this->assertSame((string) $order->id, $params['ac.order_id']);
        $this->assertSame('20000000', $params['a']);
        $this->assertSame(route('payments.return', $order), $params['c']);

        // an Inertia visit gets a 409 + X-Inertia-Location (full-page redirect)
        $this->actingAs($student)->withHeaders(['X-Inertia' => 'true'])
            ->post(route('payments.checkout'), ['product_type' => 'course', 'product_id' => $course->id, 'provider' => 'payme'])
            ->assertStatus(409)
            ->assertHeader('X-Inertia-Location', $location);
        // the pending order was reused
        $this->assertSame(1, PaymentOrder::count());

        // production mode: checkout.paycom.uz
        config(['payments.payme.test_mode' => false]);
        $this->assertStringStartsWith('https://checkout.paycom.uz/', app(\App\Services\Payments\CheckoutService::class)->checkoutUrl($order));
    }

    public function test_click_checkout_url(): void
    {
        $student = $this->studentUser();
        $plan = $this->plan(['price' => 99000]);

        $location = $this->actingAs($student)->post(route('payments.checkout'), [
            'product_type' => 'subscription', 'product_id' => $plan->id, 'provider' => 'click',
        ])->assertRedirect()->headers->get('Location');

        $order = PaymentOrder::sole();
        $this->assertStringStartsWith('https://my.click.uz/services/pay?', $location);
        parse_str(parse_url($location, PHP_URL_QUERY), $query);
        $this->assertSame('12345', $query['service_id']);
        $this->assertSame('6789', $query['merchant_id']);
        $this->assertSame('42', $query['merchant_user_id']);
        $this->assertSame('99000.00', $query['amount']);
        $this->assertSame((string) $order->id, $query['transaction_param']);
        $this->assertSame(route('payments.return', $order), $query['return_url']);
    }

    public function test_checkout_refusals(): void
    {
        $student = $this->studentUser();
        $course = $this->paidCourse();

        // guests and teachers cannot buy
        $this->post(route('payments.checkout'), ['product_type' => 'course', 'product_id' => $course->id, 'provider' => 'payme'])->assertRedirect(route('login'));
        $this->actingAs($this->teacher())->post(route('payments.checkout'), ['product_type' => 'course', 'product_id' => $course->id, 'provider' => 'payme'])
            ->assertStatus(403);

        // provider not configured
        config(['payments.click.secret_key' => null]);
        $this->actingAs($student)->post(route('payments.checkout'), ['product_type' => 'course', 'product_id' => $course->id, 'provider' => 'click'])
            ->assertSessionHasErrors('checkout');
        // free course
        $free = $this->course($this->teacher(), ['is_free' => true, 'price' => 0]);
        $this->actingAs($student)->post(route('payments.checkout'), ['product_type' => 'course', 'product_id' => $free->id, 'provider' => 'payme'])
            ->assertSessionHasErrors('checkout');
        // unknown product, invalid provider
        $this->actingAs($student)->post(route('payments.checkout'), ['product_type' => 'course', 'product_id' => (string) \Illuminate\Support\Str::uuid(), 'provider' => 'payme'])
            ->assertSessionHasErrors('checkout');
        $this->actingAs($student)->post(route('payments.checkout'), ['product_type' => 'course', 'product_id' => $course->id, 'provider' => 'uzum'])
            ->assertSessionHasErrors('provider');
        // somebody else's olympiad registration
        $registration = $this->pendingRegistration($this->studentUser());
        $this->actingAs($student)->post(route('payments.checkout'), ['product_type' => 'olympiad_registration', 'product_id' => $registration->id, 'provider' => 'payme'])
            ->assertSessionHasErrors('checkout');

        $this->assertSame(0, PaymentOrder::count());
    }

    public function test_quote_applies_promo_codes_on_the_server(): void
    {
        $student = $this->studentUser();
        $course = $this->paidCourse(100000);
        PromoCode::forceCreate(['code' => 'KURS20', 'type' => 'percentage', 'value' => 20, 'applies_to' => 'courses', 'is_active' => true, 'per_user_limit' => 1]);
        PromoCode::forceCreate(['code' => 'OBUNA', 'type' => 'fixed', 'value' => 5000, 'applies_to' => 'subscriptions', 'is_active' => true]);
        PromoCode::forceCreate(['code' => 'ESKI', 'type' => 'fixed', 'value' => 5000, 'applies_to' => 'all', 'is_active' => true, 'expires_at' => now()->subDay()]);

        $this->actingAs($student)->postJson(route('payments.quote'), ['product_type' => 'course', 'product_id' => $course->id])
            ->assertOk()
            ->assertJson(['original' => 100000, 'discount' => 0, 'amount' => 100000, 'providers' => ['payme', 'click']]);

        $this->actingAs($student)->postJson(route('payments.quote'), ['product_type' => 'course', 'product_id' => $course->id, 'promo_code' => 'kurs20'])
            ->assertOk()
            ->assertJson(['discount' => 20000, 'amount' => 80000, 'promo_code' => 'KURS20']);

        // scope / expiry
        $this->actingAs($student)->postJson(route('payments.quote'), ['product_type' => 'course', 'product_id' => $course->id, 'promo_code' => 'OBUNA'])
            ->assertStatus(422);
        $this->actingAs($student)->postJson(route('payments.quote'), ['product_type' => 'course', 'product_id' => $course->id, 'promo_code' => 'ESKI'])
            ->assertStatus(422)->assertJsonPath('message', "Promokod noto'g'ri yoki muddati o'tgan.");

        // the order is created with the promo price
        $this->actingAs($student)->post(route('payments.checkout'), ['product_type' => 'course', 'product_id' => $course->id, 'provider' => 'payme', 'promo_code' => 'KURS20']);
        $this->assertSame(8000000, PaymentOrder::sole()->amount_tiyin);
    }

    public function test_a_full_discount_is_granted_without_a_provider(): void
    {
        $student = $this->studentUser();
        $course = $this->paidCourse(50000);
        PromoCode::forceCreate(['code' => 'BEPUL', 'type' => 'percentage', 'value' => 100, 'applies_to' => 'all', 'is_active' => true]);

        $this->actingAs($student)->post(route('payments.checkout'), ['product_type' => 'course', 'product_id' => $course->id, 'provider' => 'payme', 'promo_code' => 'BEPUL'])
            ->assertRedirect(route('payments.return', PaymentOrder::sole()));

        $order = PaymentOrder::sole();
        $this->assertSame('manual', $order->provider);
        $this->assertSame('paid', $order->status);
        $this->assertTrue($student->fresh()->isEnrolled($course));
    }

    public function test_return_page_shows_the_order_status(): void
    {
        $student = $this->studentUser();
        $course = $this->paidCourse();
        $order = $this->order($student, PaymentOrder::TYPE_COURSE, $course->id);

        $this->actingAs($student)->get(route('payments.return', $order))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Student/Payments/Status')
                ->where('order.status', 'pending')
                ->where('order.can_retry', true)
                ->where('nextUrl', route('student.courses.show', $course->slug)));

        $this->payme('CreateTransaction', ['id' => str_repeat('a', 24), 'time' => \App\Models\PaymentTransaction::nowMs(), 'amount' => $order->amount_tiyin, 'account' => $this->paymeAccount($order)]);
        $this->actingAs($student)->get(route('payments.return', $order))
            ->assertInertia(fn (Assert $page) => $page->where('order.status', 'processing'));

        $this->payme('PerformTransaction', ['id' => str_repeat('a', 24)]);
        $this->actingAs($student)->get(route('payments.return', $order))
            ->assertInertia(fn (Assert $page) => $page->where('order.status', 'paid')
                ->where('order.can_retry', false)
                ->where('nextUrl', route('student.learn.course', $course->slug)));

        // another student cannot see it; the numeric id does not work as a link
        $this->actingAs($this->studentUser())->get(route('payments.return', $order))->assertNotFound();
        $this->actingAs($student)->get('/payments/orders/'.$order->id)->assertNotFound();

        // "pay again" only for pending orders
        $this->actingAs($student)->get(route('payments.pay', $order))->assertRedirect(route('payments.return', $order));
    }

    public function test_shop_coin_package_purchase_goes_to_the_provider(): void
    {
        $student = $this->studentUser();
        $package = $this->coinPackage();

        $this->actingAs($student)->get(route('student.shop.index'))
            ->assertInertia(fn (Assert $page) => $page->where('onlinePaymentAvailable', true)->where('paymentProviders', ['payme', 'click']));

        $location = $this->actingAs($student)->post(route('student.shop.purchase', $package), ['provider' => 'click'])
            ->assertRedirect()->headers->get('Location');
        $this->assertStringStartsWith('https://my.click.uz/services/pay?', $location);
        $this->assertSame('coin_package', PaymentOrder::sole()->product_type);

        $this->actingAs($student)->post(route('student.shop.purchase', $package), [])->assertSessionHasErrors('provider');
    }

    public function test_olympiad_payment_page_offers_payme_and_click(): void
    {
        $student = $this->studentUser();
        $registration = $this->pendingRegistration($student, 30000);
        $slug = $registration->olympiad->slug;

        $this->actingAs($student)->get(route('student.olympiads.payment', ['slug' => $slug, 'registration' => $registration->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Student/Olympiad/Payment')
                ->where('onlinePaymentAvailable', true)
                ->where('paymentProviders', ['payme', 'click']));

        $location = $this->actingAs($student)->post(route('student.olympiads.payment.process', ['slug' => $slug, 'registration' => $registration->id]), ['payment_method' => 'payme'])
            ->assertRedirect()->headers->get('Location');
        $params = $this->decodePaymeUrl($location);
        $this->assertSame('3000000', $params['a']);

        // Payme pays it → the registration is confirmed
        $order = PaymentOrder::sole();
        $id = str_repeat('b', 24);
        $this->payme('CreateTransaction', ['id' => $id, 'time' => \App\Models\PaymentTransaction::nowMs(), 'amount' => 3000000, 'account' => $this->paymeAccount($order)])
            ->assertJsonPath('result.state', 1);
        $this->payme('PerformTransaction', ['id' => $id])->assertJsonPath('result.state', 2);
        $this->assertSame('confirmed', $registration->fresh()->status);
        $this->assertSame('payme', $registration->fresh()->payment_method);
    }

    public function test_subscriptions_page(): void
    {
        $student = $this->studentUser();
        $plan = $this->plan(['name' => 'Premium', 'price' => 99000, 'interval' => 'month', 'interval_count' => 3]);

        $this->actingAs($student)->get(route('student.subscriptions.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Student/Subscriptions/Index')
                ->where('plans.0.id', $plan->id)
                ->where('plans.0.period', '3 oy')
                ->where('current', null)
                ->where('paymentProviders', ['payme', 'click']));
    }

    public function test_admin_payment_pages_show_provider_state_and_ids(): void
    {
        $student = $this->studentUser();
        $order = $this->order($student, PaymentOrder::TYPE_COIN_PACKAGE, $this->coinPackage()->id);
        $paymeId = str_repeat('c', 24);
        $this->payme('CreateTransaction', ['id' => $paymeId, 'time' => \App\Models\PaymentTransaction::nowMs(), 'amount' => $order->amount_tiyin, 'account' => $this->paymeAccount($order)]);
        $this->payme('PerformTransaction', ['id' => $paymeId]);

        $admin = \App\Models\User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $this->actingAs($admin)->get(route('admin.payments.index', ['provider' => 'payme', 'search' => (string) $order->id]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Payments/Index')
                ->where('payments.data.0.order_id', $order->id)
                ->where('payments.data.0.provider', 'payme')
                ->where('payments.data.0.transaction_id', $paymeId)
                ->where('payments.data.0.status', 'completed')
                ->where('payments.data.0.type', 'coin_package'));

        $this->actingAs($admin)->get(route('admin.payments.show', $order->payment_id))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page->component('Admin/Payments/Show')
                ->where('order.id', $order->id)
                ->where('order.status', 'paid')
                ->where('order.transactions.0.provider_transaction_id', $paymeId)
                ->where('order.transactions.0.state', 2)
                ->where('order.fulfilment.coins', 550));
    }

    public function test_paid_course_enroll_button_points_to_online_payment(): void
    {
        $student = $this->studentUser();
        $course = $this->paidCourse();

        $this->actingAs($student)->post(route('student.courses.enroll', $course))
            ->assertSessionHas('info', "Bu kurs pullik. «Sotib olish» tugmasini bosib, Payme yoki Click orqali to'lang.");
        $this->assertFalse($student->fresh()->isEnrolled($course));
    }
}
