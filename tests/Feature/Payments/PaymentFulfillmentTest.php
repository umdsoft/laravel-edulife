<?php

namespace Tests\Feature\Payments;

use App\Models\CoinTransaction;
use App\Models\Enrollment;
use App\Models\OlympiadAttempt;
use App\Models\OlympiadPaymentTransaction;
use App\Models\PaymentOrder;
use App\Models\PromoCode;
use App\Models\PromoCodeUsage;
use App\Models\Subscription;
use App\Services\Payments\PaymentFulfillmentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Concerns\MakesPayments;
use Tests\TestCase;

/**
 * Each product type is granted exactly once, and a refund takes back exactly what was granted.
 */
class PaymentFulfillmentTest extends TestCase
{
    use RefreshDatabase, MakesPayments;

    private PaymentFulfillmentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->configurePayments();
        $this->service = app(PaymentFulfillmentService::class);
    }

    public function test_course_enrollment_exactly_once_and_refund(): void
    {
        $student = $this->studentUser();
        $course = $this->paidCourse(150000);
        $order = $this->order($student, PaymentOrder::TYPE_COURSE, $course->id);

        $this->service->fulfil($order);
        $this->service->fulfil($order);
        $this->service->fulfil($order->fresh());

        $enrollment = Enrollment::where('user_id', $student->id)->sole();
        $this->assertSame('purchase', $enrollment->access_type);
        $this->assertSame('paid', $enrollment->payment_status);
        $this->assertSame($order->payment_id, $enrollment->payment_id);
        $this->assertSame(1, $student->notifications()->where('type', 'course_access')->count());

        $this->service->reverse($order);
        $this->service->reverse($order);
        $this->assertFalse($student->fresh()->isEnrolled($course));
        $this->assertSame('refunded', Enrollment::withTrashed()->find($enrollment->id)->status);

        // buying again restores the same enrollment row (unique user+course)
        $again = $this->order($student, PaymentOrder::TYPE_COURSE, $course->id);
        $this->service->fulfil($again);
        $this->assertTrue($student->fresh()->isEnrolled($course));
        $this->assertSame(1, Enrollment::withTrashed()->where('user_id', $student->id)->count());
    }

    public function test_course_already_opened_by_an_admin_is_kept_on_refund(): void
    {
        $student = $this->studentUser();
        $course = $this->paidCourse();
        $order = $this->order($student, PaymentOrder::TYPE_COURSE, $course->id);
        Enrollment::create(['user_id' => $student->id, 'course_id' => $course->id, 'access_type' => 'gift', 'status' => 'active']);

        $this->service->fulfil($order);
        $this->assertTrue($order->fresh()->fulfilment['already_had_access']);

        $this->service->reverse($order);
        $this->assertTrue($student->fresh()->isEnrolled($course));
    }

    public function test_coin_package_credited_once_and_refund(): void
    {
        $student = $this->studentUser();
        $package = $this->coinPackage(1000, 200, 45000);
        $order = $this->order($student, PaymentOrder::TYPE_COIN_PACKAGE, $package->id);

        $this->service->fulfil($order);
        $this->service->fulfil($order);

        $this->assertSame(1200, $student->fresh()->coins);
        $this->assertSame(1, CoinTransaction::where('user_id', $student->id)->where('source', 'coin_package')->count());
        $this->assertSame(1200, $order->fresh()->fulfilment['coins']);

        $this->service->reverse($order);
        $this->service->reverse($order);
        $this->assertSame(0, $student->fresh()->coins);
        $this->assertSame(1, CoinTransaction::where('user_id', $student->id)->where('source', 'coin_package_refund')->count());
    }

    public function test_coin_package_bought_coins_ignore_the_monthly_earning_limit(): void
    {
        \App\Models\Setting::set('coin_monthly_limit_trial', 10);
        $student = $this->studentUser();
        $order = $this->order($student, PaymentOrder::TYPE_COIN_PACKAGE, $this->coinPackage(500, 0)->id);

        $this->service->fulfil($order);
        $this->assertSame(500, $student->fresh()->coins);
    }

    public function test_subscription_activated_once_renewed_and_refunded(): void
    {
        $student = $this->studentUser();
        $plan = $this->plan(['interval' => 'month', 'interval_count' => 1]);
        $order = $this->order($student, PaymentOrder::TYPE_SUBSCRIPTION, $plan->id);

        $this->service->fulfil($order);
        $this->service->fulfil($order);

        $subscription = Subscription::where('user_id', $student->id)->sole();
        $this->assertSame('active', $subscription->status);
        $this->assertSame($order->payment_id, $subscription->last_payment_id);
        $firstEnd = $subscription->ends_at;
        $this->assertTrue($firstEnd->between(now()->addMonth()->subMinute(), now()->addMonth()->addMinute()));

        // renewal: the paid month is added after the current one
        $renewal = $this->order($student, PaymentOrder::TYPE_SUBSCRIPTION, $plan->id);
        $this->assertNotSame($order->id, $renewal->id);
        $this->service->fulfil($renewal);
        $this->assertSame(1, Subscription::where('user_id', $student->id)->count());
        $this->assertEquals($firstEnd->copy()->addMonth()->toDateString(), $subscription->fresh()->ends_at->toDateString());

        // refund of the renewal gives back the old end date
        $this->service->reverse($renewal);
        $this->assertEquals($firstEnd->toDateTimeString(), $subscription->fresh()->ends_at->toDateTimeString());
        $this->assertSame('active', $subscription->fresh()->status);

        // refund of the first purchase ends it
        $this->service->reverse($order);
        $this->assertSame('cancelled', $subscription->fresh()->status);
    }

    public function test_subscription_to_another_plan_replaces_the_old_one(): void
    {
        $student = $this->studentUser();
        $basic = $this->plan(['name' => 'Standart', 'slug' => 'standart']);
        $premium = $this->plan(['name' => 'Premium', 'slug' => 'premium', 'price' => 199000]);

        $this->service->fulfil($this->order($student, PaymentOrder::TYPE_SUBSCRIPTION, $basic->id));
        $this->service->fulfil($this->order($student, PaymentOrder::TYPE_SUBSCRIPTION, $premium->id));

        $this->assertSame(1, Subscription::where('user_id', $student->id)->where('status', 'active')->count());
        $this->assertSame($premium->id, Subscription::where('user_id', $student->id)->where('status', 'active')->value('plan_id'));
        $this->assertSame('premium', app(\App\Services\RewardSettings::class)->tier($student));
    }

    public function test_olympiad_registration_marked_paid_once_and_refund(): void
    {
        $student = $this->studentUser();
        $registration = $this->pendingRegistration($student, 30000);
        $order = $this->order($student, PaymentOrder::TYPE_OLYMPIAD, $registration->id, 'click');

        $this->service->fulfil($order);
        $this->service->fulfil($order);

        $registration->refresh();
        $this->assertSame('confirmed', $registration->status);
        $this->assertSame('completed', $registration->payment_status);
        $this->assertSame('click', $registration->payment_method);
        $this->assertEquals(30000, (float) $registration->payment_cash);
        $this->assertNotNull($registration->paid_at);
        $this->assertSame(1, OlympiadPaymentTransaction::where('registration_id', $registration->id)->where('status', 'completed')->count());

        $this->service->reverse($order);
        $this->assertSame('refunded', $registration->fresh()->status);
        $this->assertSame('refunded', OlympiadPaymentTransaction::where('registration_id', $registration->id)->value('status'));
    }

    public function test_olympiad_refund_refused_after_the_exam_started(): void
    {
        $student = $this->studentUser();
        $registration = $this->pendingRegistration($student);
        $order = $this->order($student, PaymentOrder::TYPE_OLYMPIAD, $registration->id);
        $this->service->fulfil($order);

        OlympiadAttempt::forceCreate([
            'olympiad_id' => $registration->olympiad_id, 'user_id' => $student->id, 'registration_id' => $registration->id,
            'session_token' => Str::random(64), 'total_max_score' => 100,
        ]);

        $this->assertNotNull($this->service->reverseProblem($order->fresh()));
        $this->expectException(\DomainException::class);
        $this->service->reverse($order->fresh());
    }

    public function test_promo_code_usage_recorded_on_fulfilment_only(): void
    {
        $student = $this->studentUser();
        $course = $this->paidCourse(100000);
        $promo = PromoCode::forceCreate(['code' => 'YOZ25', 'type' => 'percentage', 'value' => 25, 'applies_to' => 'courses', 'is_active' => true, 'per_user_limit' => 1]);

        $order = $this->order($student, PaymentOrder::TYPE_COURSE, $course->id, 'payme', 'yoz25');
        $this->assertEquals(75000, (float) $order->amount);
        $this->assertSame(7500000, $order->amount_tiyin);
        $this->assertSame(0, PromoCodeUsage::count());

        $this->service->fulfil($order);
        $this->service->fulfil($order);
        $this->assertSame(1, PromoCodeUsage::where('promo_code_id', $promo->id)->count());
        $this->assertSame(1, $promo->fresh()->usage_count);

        $this->service->reverse($order);
        $this->assertSame(0, PromoCodeUsage::count());
        $this->assertSame(0, $promo->fresh()->usage_count);
    }
}
