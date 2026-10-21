<?php

namespace Tests\Feature\Exam;

use App\Enums\Exam\AttemptStatus;
use App\Exceptions\Exam\ExamUnavailable;
use App\Models\Exam\ExamAttempt;
use App\Models\Exam\ExamSession;
use App\Models\Exam\ExamSessionPurchase;
use App\Models\Exam\ExamVariant;
use App\Models\Exam\TelegramAccount;
use App\Models\Payment;
use App\Models\PaymentOrder;
use App\Models\PaymentTransaction;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Exam\ExamAccess;
use App\Services\Exam\ExamAttemptService;
use App\Services\Exam\VariantGenerator;
use App\Services\Payments\CheckoutException;
use App\Services\Payments\CheckoutService;
use App\Services\Payments\PaymentFulfillmentService;
use App\Services\Telegram\TelegramReminderService;
use App\Models\Exam\TelegramNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Concerns\BuildsExams;
use Tests\Concerns\MakesPayments;
use Tests\TestCase;

/**
 * Paid exams (docs/exam/QARORLAR.md item 9): a Saturday exam is free, opened by a subscription that
 * includes exams, or bought on its own with Payme / Click (payment product "exam_session").
 * Only inbound provider callbacks are simulated; no real Payme / Click / Telegram request is made.
 */
class PaidExamsTest extends TestCase
{
    use BuildsExams, MakesPayments, RefreshDatabase;

    private const PRICE = 15000;

    protected function setUp(): void
    {
        parent::setUp();
        Http::preventStrayRequests();
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 1]])]);
        $this->configurePayments();
    }

    /** @return array{0: \App\Models\Exam\Subject, 1: \App\Models\Exam\ExamBlueprint} */
    private function bank(): array
    {
        $subject = $this->examSubject();
        [$section, $a] = $this->examTopics($subject);
        $this->approvedQuestions($a, 6, 2);
        $blueprint = $this->examBlueprint($subject, [[$section, null, 'closed', 3, 1]], ['duration_min' => 30]);

        return [$subject, $blueprint];
    }

    private function paidSession(array $attributes = []): ExamSession
    {
        [, $blueprint] = $this->bank();

        return $this->openWeeklySession($blueprint, $attributes + ['price' => self::PRICE]);
    }

    private function subscribe(User $user, bool $includesExams = true, array $subscription = []): Subscription
    {
        $plan = $this->plan(['includes_exams' => $includesExams]);

        return Subscription::forceCreate($subscription + [
            'user_id' => $user->id, 'plan_id' => $plan->id, 'status' => 'active',
            'started_at' => now()->subDay(), 'ends_at' => now()->addMonth(),
        ]);
    }

    private function buy(User $user, ExamSession $session, string $provider = 'payme'): PaymentOrder
    {
        $order = $this->order($user, PaymentOrder::TYPE_EXAM_SESSION, $session->id, $provider);

        return app(PaymentFulfillmentService::class)->fulfil($order);
    }

    private function startReason(ExamSession $session, User $user): ?string
    {
        try {
            app(ExamAttemptService::class)->start($session, $user);

            return null;
        } catch (ExamUnavailable $e) {
            return $e->reason;
        }
    }

    // ---------------------------------------------------------------- access matrix

    public function test_access_matrix(): void
    {
        [, $blueprint] = $this->bank();
        $free = $this->openWeeklySession($blueprint, ['price' => 0]);
        $paid = $this->openWeeklySession($blueprint, ['price' => self::PRICE]);
        $access = app(ExamAccess::class);

        $nobody = $this->examStudent();
        $withExams = $this->examStudent();
        $this->subscribe($withExams, true);
        $withoutExams = $this->examStudent();
        $this->subscribe($withoutExams, false);
        $expired = $this->examStudent();
        $this->subscribe($expired, true, ['ends_at' => now()->subDay()]);
        $cancelled = $this->examStudent();
        $this->subscribe($cancelled, true, ['status' => 'cancelled']);
        $buyer = $this->examStudent();
        $this->buy($buyer, $paid);
        $teacher = $this->examTeacher();

        $this->assertSame(['allowed' => true, 'via' => 'free'], array_intersect_key($access->check($free, $nobody), ['allowed' => 1, 'via' => 1]));
        $this->assertSame('payment_required', $access->check($paid, $nobody)['reason']);
        $this->assertStringContainsString("15 000 so'm", $access->check($paid, $nobody)['message']);
        $this->assertSame('subscription', $access->check($paid, $withExams)['via']);
        $this->assertFalse($access->check($paid, $withoutExams)['allowed']);
        $this->assertFalse($access->check($paid, $expired)['allowed']);
        $this->assertFalse($access->check($paid, $cancelled)['allowed']);
        $this->assertSame('purchase', $access->check($paid, $buyer)['via']);
        $this->assertSame('staff', $access->check($paid, $teacher)['via']);

        // start() enforces it and records how the student got in
        $this->assertSame('payment_required', $this->startReason($paid, $nobody));
        $this->assertSame('payment_required', $this->startReason($paid, $withoutExams));
        $this->assertSame(0, ExamAttempt::query()->where('session_id', $paid->id)->count());
        foreach ([[$free, $nobody, 'free'], [$paid, $withExams, 'subscription'], [$paid, $buyer, 'purchase']] as [$session, $user, $via]) {
            $this->assertNull($this->startReason($session, $user));
            $this->assertSame($via, ExamAttempt::query()->where('session_id', $session->id)->where('user_id', $user->id)->value('access_via'));
        }
    }

    public function test_placement_is_always_free_even_with_a_price(): void
    {
        [, $blueprint] = $this->bank();
        $placement = ExamSession::factory()->create([
            'subject_id' => $blueprint->subject_id, 'blueprint_id' => $blueprint->id, 'type' => 'placement',
            'starts_at' => null, 'ends_at' => null, 'registration_closes_at' => null, 'status' => 'open', 'price' => 50000,
        ]);
        $student = $this->examStudent();

        $this->assertFalse(app(ExamAccess::class)->isPaid($placement));
        $this->assertSame('placement', app(ExamAccess::class)->check($placement, $student)['via']);
        $this->assertSame('Qabul testi bepul.', app(ExamAccess::class)->buyProblem($placement, $student));
    }

    public function test_the_start_route_shows_the_reason_and_take_and_autosave_recheck_access(): void
    {
        $session = $this->paidSession();
        $student = $this->examStudent();

        $this->actingAs($student)->from(route('student.exam.index'))
            ->post(route('student.exam.sessions.start', $session))
            ->assertRedirect(route('student.exam.index'))
            ->assertSessionHas('error', fn ($m) => str_contains($m, 'pullik'));

        // an attempt that did not pass start() (no access_via, legacy) on a paid session without access
        $this->buy($student, $session);
        $attempt = app(ExamAttemptService::class)->start($session, $student);
        $attempt->forceFill(['access_via' => null])->save();
        ExamSessionPurchase::query()->update(['status' => ExamSessionPurchase::STATUS_REFUNDED]);

        $this->actingAs($student)->get(route('student.exam.take', $attempt))->assertRedirect(route('student.exam.index'));
        $this->actingAs($student)->withHeader('X-Exam-Token', $attempt->session_token)
            ->postJson(route('student.exam.answers', $attempt), ['answers' => [['question_id' => 'x', 'answer_text' => '1']]])
            ->assertStatus(409)->assertJsonPath('reason', 'payment_required');

        // with access recorded at start the attempt keeps working
        $attempt->forceFill(['access_via' => 'purchase'])->save();
        $this->actingAs($student)->get(route('student.exam.take', $attempt))->assertOk();
    }

    // ---------------------------------------------------------------- teacher: price

    public function test_teacher_sets_the_price_with_validation(): void
    {
        [$subject, $blueprint] = $this->bank();
        $teacher = $this->examTeacher();
        $form = [
            'subject_id' => $subject->id, 'blueprint_id' => $blueprint->id, 'type' => 'weekly', 'title' => 'Pullik shanba',
            'date' => now()->addDays(3)->toDateString(), 'start_time' => '10:00', 'end_time' => '13:00',
        ];

        $this->actingAs($teacher)->post(route('teacher.exam.sessions.store'), $form + ['price' => 500])->assertSessionHasErrors('price');
        $this->actingAs($teacher)->post(route('teacher.exam.sessions.store'), $form + ['price' => -1])->assertSessionHasErrors('price');
        $this->actingAs($teacher)->post(route('teacher.exam.sessions.store'), $form + ['price' => 'abc'])->assertSessionHasErrors('price');
        $this->actingAs($teacher)->post(route('teacher.exam.sessions.store'), $form + ['price' => 20000000])->assertSessionHasErrors('price');
        $this->actingAs($teacher)->post(route('teacher.exam.sessions.store'), $form + ['price' => self::PRICE])->assertSessionHasNoErrors();
        $session = ExamSession::query()->where('title', 'Pullik shanba')->sole();
        $this->assertSame(self::PRICE, $session->price);

        // a form without the field (older page) keeps the price
        $this->actingAs($teacher)->put(route('teacher.exam.sessions.update', $session), $form)->assertSessionHasNoErrors();
        $this->assertSame(self::PRICE, $session->fresh()->price);
        $this->actingAs($teacher)->put(route('teacher.exam.sessions.update', $session), $form + ['price' => 0])->assertSessionHasNoErrors();
        $this->assertSame(0, $session->fresh()->price);

        // once somebody paid, the price is locked
        $session->refresh()->forceFill(['price' => self::PRICE, 'status' => 'scheduled'])->save();
        $this->buy($this->examStudent(), $session);
        $this->actingAs($teacher)->put(route('teacher.exam.sessions.update', $session), $form + ['price' => 9000])->assertSessionHasErrors('price');
        $this->assertSame(self::PRICE, $session->fresh()->price);

        $this->actingAs($teacher)->get(route('teacher.exam.sessions.edit', $session))
            ->assertInertia(fn (Assert $p) => $p->where('session.price', self::PRICE)->where('session.paid_count', 1)->has('examPlans'));
    }

    public function test_teacher_pages_show_the_price_and_who_got_in_how(): void
    {
        $session = $this->paidSession();
        $buyer = $this->examStudent();
        $this->buy($buyer, $session);
        app(ExamAttemptService::class)->start($session, $buyer);
        $subscriber = $this->examStudent();
        $this->subscribe($subscriber);
        app(ExamAttemptService::class)->start($session, $subscriber);
        $this->buy($this->examStudent(), $session); // paid, not started yet
        $teacher = $this->examTeacher();

        $this->actingAs($teacher)->get(route('teacher.exam.sessions.index'))
            ->assertInertia(fn (Assert $p) => $p->component('Teacher/Exam/Sessions/Index')
                ->where('sessions.data.0.price', self::PRICE)
                ->where('sessions.data.0.purchases_count', 2)
                ->where('sessions.data.0.via_purchase_count', 1)
                ->where('sessions.data.0.via_subscription_count', 1));

        $this->actingAs($teacher)->get(route('teacher.exam.sessions.show', $session))
            ->assertInertia(fn (Assert $p) => $p->component('Teacher/Exam/Sessions/Show')
                ->where('payments.price', self::PRICE)
                ->where('payments.purchased', 2)
                ->where('payments.via.purchase', 1)
                ->where('payments.via.subscription', 1)
                ->where('payments.via.free', 0));
    }

    // ---------------------------------------------------------------- checkout

    public function test_checkout_amount_comes_from_the_session(): void
    {
        $session = $this->paidSession();
        $student = $this->examStudent();

        $this->actingAs($student)->postJson(route('payments.quote'), [
            'product_type' => 'exam_session', 'product_id' => $session->id, 'promo_code' => null, 'amount' => 1,
        ])->assertOk()->assertJsonPath('amount', 15000)->assertJsonPath('title', "Imtihon: {$session->title}");

        // promo codes do not apply to exam entries
        $this->actingAs($student)->postJson(route('payments.quote'), [
            'product_type' => 'exam_session', 'product_id' => $session->id, 'promo_code' => 'YOZ25',
        ])->assertStatus(422);

        $response = $this->actingAs($student)->post(route('payments.checkout'), [
            'product_type' => 'exam_session', 'product_id' => $session->id, 'provider' => 'payme', 'amount' => 100,
        ]);
        $order = PaymentOrder::query()->sole();
        $this->assertSame(1_500_000, $order->amount_tiyin);
        $this->assertSame('exam_session', $order->product_type);
        $this->assertSame('exam_session', Payment::find($order->payment_id)->type);
        $location = $response->headers->get('Location') ?? $response->headers->get('X-Inertia-Location');
        $this->assertStringStartsWith('https://test.paycom.uz/', $location);
        $decoded = base64_decode(substr($location, strlen('https://test.paycom.uz/')));
        $this->assertStringContainsString('a=1500000', $decoded);
        // back to the exam page
        $this->assertStringContainsString('c='.route('student.exam.index', ['paid' => $order->uuid]), $decoded);

        // Click URL carries the same amount and return URL
        $click = app(CheckoutService::class)->createOrder($student, 'exam_session', $session->id, 'click');
        $url = app(CheckoutService::class)->checkoutUrl($click);
        $this->assertStringContainsString('amount=15000.00', $url);
        $this->assertStringContainsString(urlencode(route('student.exam.index', ['paid' => $click->uuid])), $url);
    }

    public function test_telegram_checkout_answers_with_the_url_and_returns_to_the_mini_app(): void
    {
        config(['exam.telegram.bot_username' => 'shanba_bot']);
        $session = $this->paidSession();
        $student = $this->examStudent();

        $json = $this->actingAs($student)->withSession(['webview' => 'telegram'])->postJson(route('payments.checkout'), [
            'product_type' => 'exam_session', 'product_id' => $session->id, 'provider' => 'click',
        ])->assertOk()->assertJsonPath('status', 'pending')->json();

        $order = PaymentOrder::query()->where('uuid', $json['order'])->sole();
        $this->assertSame('telegram', $order->meta['return_to']);
        $this->assertStringStartsWith('https://my.click.uz/services/pay?', $json['url']);
        $this->assertStringContainsString(urlencode('https://t.me/shanba_bot?startapp=exam_s_'.$session->id), $json['url']);

        // a refused purchase answers 422 JSON with the reason
        $this->subscribe($student);
        $this->actingAs($student)->withSession(['webview' => 'telegram'])->postJson(route('payments.checkout'), [
            'product_type' => 'exam_session', 'product_id' => $session->id, 'provider' => 'payme',
        ])->assertStatus(422)->assertJsonPath('message', "Obunangiz bilan bu imtihonga kirish ochiq — to'lash shart emas.");
    }

    public function test_buy_buttons_need_configured_providers(): void
    {
        config(['payments.payme.merchant_id' => null, 'payments.payme.key' => null, 'payments.payme.test_key' => null, 'payments.click.service_id' => null]);
        $session = $this->paidSession();
        $student = $this->examStudent();

        $this->assertSame([], CheckoutService::enabledProviders());
        $this->actingAs($student)->get(route('student.exam.index'))
            ->assertInertia(fn (Assert $p) => $p->where('payments.providers', [])->where('upcoming.0.access.state', 'pay'));
        $this->actingAs($student)->post(route('payments.checkout'), [
            'product_type' => 'exam_session', 'product_id' => $session->id, 'provider' => 'payme',
        ])->assertSessionHasErrors('checkout');
        $this->assertSame(0, PaymentOrder::count());
    }

    // ---------------------------------------------------------------- callbacks: exactly once

    public function test_payme_callbacks_fulfil_exactly_once(): void
    {
        $session = $this->paidSession();
        $student = $this->examStudent();
        $order = $this->order($student, 'exam_session', $session->id, 'payme');
        $id = '6305e3bab097f420a62ced0a';

        $this->payme('CheckPerformTransaction', ['amount' => 1_500_000, 'account' => $this->paymeAccount($order)])->assertJsonPath('result.allow', true);
        $this->payme('CheckPerformTransaction', ['amount' => 100, 'account' => $this->paymeAccount($order)])->assertJsonPath('error.code', -31001);
        $this->payme('CreateTransaction', ['id' => $id, 'time' => PaymentTransaction::nowMs(), 'amount' => 1_500_000, 'account' => $this->paymeAccount($order)])
            ->assertJsonPath('result.state', 1);
        $first = $this->payme('PerformTransaction', ['id' => $id])->assertJsonPath('result.state', 2)->json('result');
        $this->payme('PerformTransaction', ['id' => $id])->assertJsonPath('result', $first);

        $purchase = ExamSessionPurchase::query()->sole();
        $this->assertSame([$session->id, $student->id, 15000, 'payme', 'paid'], [$purchase->session_id, $purchase->user_id, $purchase->amount, $purchase->provider, $purchase->status]);
        $this->assertSame($order->id, $purchase->payment_order_id);
        $this->assertSame('paid', $order->fresh()->status);
        $this->assertSame(1, $student->notifications()->where('type', 'exam_purchase')->count());
        $this->assertSame('purchase', app(ExamAccess::class)->check($session, $student)['via']);

        // started: a Payme refund of the performed transaction is refused
        app(ExamAttemptService::class)->start($session, $student);
        $this->payme('CancelTransaction', ['id' => $id, 'reason' => 5])->assertJsonPath('error.code', -31007);
        $this->assertSame('paid', $purchase->fresh()->status);
    }

    public function test_click_callbacks_fulfil_exactly_once_and_a_payme_cancel_refunds_before_start(): void
    {
        $session = $this->paidSession();
        $student = $this->examStudent();
        $order = $this->order($student, 'exam_session', $session->id, 'click');

        $prepareId = $this->clickPrepare($this->clickPrepareParams($order))->assertJsonPath('error', 0)->json('merchant_prepare_id');
        $this->clickComplete($this->clickCompleteParams($order, $prepareId))->assertJsonPath('error', 0);
        $this->clickComplete($this->clickCompleteParams($order, $prepareId))->assertJsonPath('error', -4);
        $this->assertSame(1, ExamSessionPurchase::query()->count());
        $this->assertSame('click', ExamSessionPurchase::query()->value('provider'));

        // Payme: refund (CancelTransaction after perform) before the student starts closes the entry
        $other = $this->examStudent();
        $paymeOrder = $this->order($other, 'exam_session', $session->id, 'payme');
        $id = '6305e3bab097f420a62ced0b';
        $this->payme('CreateTransaction', ['id' => $id, 'time' => PaymentTransaction::nowMs(), 'amount' => 1_500_000, 'account' => $this->paymeAccount($paymeOrder)]);
        $this->payme('PerformTransaction', ['id' => $id])->assertJsonPath('result.state', 2);
        $this->payme('CancelTransaction', ['id' => $id, 'reason' => 5])->assertJsonPath('result.state', -2);
        $this->assertSame('refunded', ExamSessionPurchase::query()->where('user_id', $other->id)->value('status'));
        $this->assertFalse(app(ExamAccess::class)->check($session, $other)['allowed']);
    }

    // ---------------------------------------------------------------- refused purchases

    public function test_double_purchase_and_purchase_after_start_are_refused(): void
    {
        $session = $this->paidSession();
        $student = $this->examStudent();
        $this->buy($student, $session);

        try {
            $this->order($student, 'exam_session', $session->id, 'click');
            $this->fail('paid twice');
        } catch (CheckoutException $e) {
            $this->assertSame("Siz bu imtihon uchun to'lagansiz.", $e->getMessage());
        }

        // subscription holder: nothing to pay
        $subscriber = $this->examStudent();
        $this->subscribe($subscriber);
        $this->actingAs($subscriber)->postJson(route('payments.quote'), ['product_type' => 'exam_session', 'product_id' => $session->id])
            ->assertStatus(422)->assertJsonPath('message', "Obunangiz bilan bu imtihonga kirish ochiq — to'lash shart emas.");

        // an order opened before the student got in another way and started: the provider is refused
        $late = $this->examStudent();
        $pending = $this->order($late, 'exam_session', $session->id, 'payme');
        $this->subscribe($late);
        app(ExamAttemptService::class)->start($session, $late);
        Subscription::query()->where('user_id', $late->id)->delete();
        $this->assertSame('Siz bu imtihonni allaqachon boshlagansiz.', app(ExamAccess::class)->buyProblem($session, $late));
        $this->payme('CheckPerformTransaction', ['amount' => 1_500_000, 'account' => $this->paymeAccount($pending)])->assertJsonPath('error.code', -31052);

        // closed, cancelled, draft, free and someone else's subject: no sale
        $outsider = $this->examStudent(['physics']);
        $this->assertStringContainsString('siz uchun emas', app(ExamAccess::class)->buyProblem($session, $outsider));
        foreach (['graded', 'cancelled', 'draft'] as $status) {
            $session->forceFill(['status' => $status])->save();
            $this->assertStringContainsString("to'lov qabul qilinmaydi", app(ExamAccess::class)->buyProblem($session, $this->examStudent()));
        }
        $session->forceFill(['status' => 'open', 'price' => 0])->save();
        $this->assertStringContainsString('bepul', app(ExamAccess::class)->buyProblem($session, $this->examStudent()));
    }

    public function test_an_entry_paid_with_both_providers_is_listed_for_a_refund(): void
    {
        $session = $this->paidSession();
        $student = $this->examStudent();
        $payme = $this->order($student, 'exam_session', $session->id, 'payme');
        $click = $this->order($student, 'exam_session', $session->id, 'click');
        app(PaymentFulfillmentService::class)->fulfil($payme);
        app(PaymentFulfillmentService::class)->fulfil($click); // both providers went through

        $this->assertSame(1, ExamSessionPurchase::query()->count());
        $this->assertTrue($click->fresh()->fulfilment['duplicate']);

        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $this->actingAs($admin)->get(route('admin.exam.refunds.index'))
            ->assertInertia(fn (Assert $p) => $p->component('Admin/Exam/Refunds')->has('pending', 1)->where('pending.0.kind', 'duplicate'));
        $this->actingAs($admin)->post(route('admin.exam.refunds.order', $click), ['note' => 'Click kabinetidan'])->assertSessionHas('success');
        $this->assertSame('refunded', $click->fresh()->status);
        $this->assertSame('refunded', Payment::find($click->payment_id)->status);
        $this->assertSame('paid', ExamSessionPurchase::query()->value('status'), 'the first entry stays');
    }

    // ---------------------------------------------------------------- cancellation

    public function test_cancelling_a_session_marks_its_purchases_for_refund(): void
    {
        $session = $this->paidSession();
        $buyer = $this->examStudent();
        $order = $this->buy($buyer, $session);
        $attempt = app(ExamAttemptService::class)->start($session, $buyer);
        $teacher = $this->examTeacher();

        $this->actingAs($teacher)->post(route('teacher.exam.sessions.cancel', $session))
            ->assertSessionHas('success', fn ($m) => str_contains($m, "1 ta to'lov"));

        $purchase = ExamSessionPurchase::query()->sole();
        $this->assertSame('refund_pending', $purchase->status);
        $this->assertNotNull($purchase->refund_requested_at);
        $this->assertSame(AttemptStatus::Invalidated, $attempt->fresh()->status);
        $this->assertSame(1, $buyer->notifications()->where('type', 'exam_refund')->count());
        $this->assertFalse(app(ExamAccess::class)->check($session->fresh(), $this->examStudent())['allowed']);

        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $this->actingAs($admin)->get(route('admin.payments.index'))->assertInertia(fn (Assert $p) => $p->where('examRefundsPending', 1));
        $this->actingAs($admin)->get(route('admin.exam.refunds.index'))
            ->assertInertia(fn (Assert $p) => $p->has('pending', 1)->where('pending.0.kind', 'cancelled')->where('pending.0.amount', 15000));

        // students and teachers can not mark refunds
        $this->actingAs($teacher)->post(route('admin.exam.refunds.purchase', $purchase))->assertForbidden();

        $this->actingAs($admin)->post(route('admin.exam.refunds.purchase', $purchase), ['note' => 'Payme kabinetidan'])->assertSessionHas('success');
        $purchase->refresh();
        $this->assertSame(['refunded', $admin->id, 'Payme kabinetidan'], [$purchase->status, $purchase->refunded_by, $purchase->refund_note]);
        $this->assertSame('refunded', $order->fresh()->status);
        $this->assertSame('refunded', Payment::find($order->payment_id)->status);
        // idempotent
        $this->actingAs($admin)->post(route('admin.exam.refunds.purchase', $purchase))->assertSessionHas('error');

        // a payment that arrives after the cancellation goes straight to the refund list
        $session2 = $this->paidSession(['status' => 'scheduled', 'starts_at' => now()->addDay(), 'ends_at' => now()->addDay()->addHours(3)]);
        $late = $this->examStudent();
        $lateOrder = $this->order($late, 'exam_session', $session2->id);
        $session2->forceFill(['status' => 'cancelled'])->save();
        app(PaymentFulfillmentService::class)->fulfil($lateOrder);
        $this->assertSame('refund_pending', ExamSessionPurchase::query()->where('user_id', $late->id)->value('status'));
    }

    // ---------------------------------------------------------------- variants

    public function test_a_late_payer_gets_a_variant_on_start(): void
    {
        $session = $this->paidSession(['status' => 'scheduled', 'starts_at' => now()->addHour(), 'ends_at' => now()->addHours(4)]);
        $early = $this->examStudent();
        app(VariantGenerator::class)->generateForSession($session);
        $this->assertSame(1, ExamVariant::query()->where('session_id', $session->id)->count());

        $late = $this->examStudent(); // joined after the pre-generation
        $this->buy($late, $session);
        $session->forceFill(['status' => 'open', 'starts_at' => now()->subMinute()])->save();
        $attempt = app(ExamAttemptService::class)->start($session, $late);

        $this->assertNotNull(ExamVariant::query()->where('session_id', $session->id)->where('user_id', $late->id)->first());
        $this->assertSame(3, $attempt->answers()->count());
        $this->assertSame(2, ExamVariant::query()->where('session_id', $session->id)->count());
        $this->assertNotNull($early);
    }

    // ---------------------------------------------------------------- reminders

    public function test_reminders_choose_the_pay_or_start_button(): void
    {
        config(['exam.telegram.bot_token' => '111:TEST', 'exam.telegram.mini_app_url' => 'https://exam.example.uz/telegram/app']);
        $session = $this->paidSession(['status' => 'scheduled', 'starts_at' => now()->addMinutes(30), 'ends_at' => now()->addHours(3)]);
        $payer = $this->examStudent();
        TelegramAccount::factory()->create(['user_id' => $payer->id]);
        $subscriber = $this->examStudent();
        $this->subscribe($subscriber);
        TelegramAccount::factory()->create(['user_id' => $subscriber->id]);
        $buyer = $this->examStudent();
        $this->buy($buyer, $session);
        TelegramAccount::factory()->create(['user_id' => $buyer->id]);

        $sent = app(TelegramReminderService::class)->send($session, TelegramNotification::KIND_REMINDER_HOUR);
        $this->assertSame(3, $sent);

        $chats = TelegramAccount::query()->pluck('chat_id', 'user_id');
        $byChat = collect(Http::recorded(fn (HttpRequest $r) => str_ends_with($r->url(), '/sendMessage')))
            ->mapWithKeys(fn ($pair) => [(string) $pair[0]['chat_id'] => $pair[0]]);
        $button = fn (User $u) => json_decode(is_string($m = $byChat[(string) $chats[$u->id]]['reply_markup']) ? $m : json_encode($m), true)['inline_keyboard'][0][0];

        $this->assertSame("💳 To'lash", $button($payer)['text']);
        $this->assertSame('https://exam.example.uz/telegram/app?to=exam_s_'.$session->id, $button($payer)['web_app']['url']);
        $this->assertStringContainsString("15 000 so'm", $byChat[(string) $chats[$payer->id]]['text']);
        $this->assertSame('📝 Imtihonga kirish', $button($subscriber)['text']);
        $this->assertSame('📝 Imtihonga kirish', $button($buyer)['text']);
        $this->assertStringNotContainsString('pullik', $byChat[(string) $chats[$buyer->id]]['text']);

        // same schedule: a second run sends nothing
        $this->assertSame(0, app(TelegramReminderService::class)->send($session, TelegramNotification::KIND_REMINDER_HOUR));
    }

    // ---------------------------------------------------------------- student page props

    public function test_student_exam_page_shows_the_access_state(): void
    {
        $session = $this->paidSession(['title' => 'Pullik shanba']);
        $plan = $this->plan(['name' => 'Premium', 'includes_exams' => true]);
        $student = $this->examStudent();

        $this->actingAs($student)->get(route('student.exam.index'))
            ->assertInertia(fn (Assert $p) => $p->component('Student/Exam/Index')
                ->where('upcoming.0.access.state', 'pay')
                ->where('upcoming.0.access.price', self::PRICE)
                ->where('upcoming.0.access.can_buy', true)
                ->where('payments.providers', ['payme', 'click'])
                ->where('payments.exam_plans', ['Premium'])
                ->where('payments.plans_url', route('student.subscriptions.index'))
                ->where('payments.subscription', null)
                ->where('paymentNotice', null));

        // back from the provider: the notice follows the order
        $order = $this->order($student, 'exam_session', $session->id);
        $this->actingAs($student)->get(route('student.exam.index', ['paid' => $order->uuid]))
            ->assertInertia(fn (Assert $p) => $p->where('paymentNotice.status', 'pending')->where('paymentNotice.title', 'Pullik shanba'));
        app(PaymentFulfillmentService::class)->fulfil($order);
        $this->actingAs($student)->get(route('student.exam.index', ['paid' => $order->uuid]))
            ->assertInertia(fn (Assert $p) => $p->where('paymentNotice.status', 'paid')->where('upcoming.0.access.state', 'purchased'));
        // someone else's order is ignored
        $this->actingAs($this->examStudent())->get(route('student.exam.index', ['paid' => $order->uuid]))
            ->assertInertia(fn (Assert $p) => $p->where('paymentNotice', null));

        $subscriber = $this->examStudent();
        Subscription::forceCreate(['user_id' => $subscriber->id, 'plan_id' => $plan->id, 'status' => 'active', 'started_at' => now(), 'ends_at' => now()->addMonth()]);
        $this->actingAs($subscriber)->get(route('student.exam.index'))
            ->assertInertia(fn (Assert $p) => $p->where('upcoming.0.access.state', 'subscription')->where('payments.subscription.plan', 'Premium'));

        $session->forceFill(['price' => 0])->save();
        $this->actingAs($this->examStudent())->get(route('student.exam.index'))
            ->assertInertia(fn (Assert $p) => $p->where('upcoming.0.access.state', 'free')->where('upcoming.0.access.price', 0));

        // the status page sends the student back to the exam page
        $this->actingAs($student)->get(route('payments.return', $order))
            ->assertInertia(fn (Assert $p) => $p->where('order.status', 'paid')->where('nextUrl', route('student.exam.index')));
    }

    public function test_plans_include_exams_setting(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $data = ['name' => 'Oylik', 'slug' => 'oylik', 'price' => 99000, 'interval' => 'month', 'interval_count' => 1, 'features' => [], 'is_featured' => false, 'is_active' => true];

        $this->actingAs($admin)->post(route('admin.subscriptions.store-plan'), $data + ['includes_exams' => true])->assertSessionHasNoErrors();
        $plan = \App\Models\SubscriptionPlan::query()->where('slug', 'oylik')->sole();
        $this->assertTrue($plan->includes_exams);
        // an older form without the field keeps it
        $this->actingAs($admin)->put(route('admin.subscriptions.update-plan', $plan), $data)->assertSessionHasNoErrors();
        $this->assertTrue($plan->fresh()->includes_exams);
        $this->actingAs($admin)->put(route('admin.subscriptions.update-plan', $plan), $data + ['includes_exams' => false])->assertSessionHasNoErrors();
        $this->assertFalse($plan->fresh()->includes_exams);
        $this->actingAs($admin)->get(route('admin.subscriptions.plans'))->assertInertia(fn (Assert $p) => $p->where('plans.0.includes_exams', false));

        $this->actingAs($this->examStudent())->get(route('student.subscriptions.index'))
            ->assertInertia(fn (Assert $p) => $p->where('plans.0.includes_exams', false));
    }

    public function test_seeded_paid_plans_include_exams_and_the_trial_does_not(): void
    {
        $this->seed(\Database\Seeders\SubscriptionPlanSeeder::class);

        $plans = \App\Models\SubscriptionPlan::query()->pluck('includes_exams', 'slug');
        $this->assertFalse($plans['trial']);
        foreach (['standart', 'standart-yearly', 'premium', 'premium-yearly', 'vip', 'vip-yearly'] as $slug) {
            $this->assertTrue($plans[$slug], $slug);
        }
    }
}
