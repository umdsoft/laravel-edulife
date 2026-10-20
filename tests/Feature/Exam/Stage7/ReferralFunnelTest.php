<?php

namespace Tests\Feature\Exam\Stage7;

use App\Enums\Exam\ReferralStatus;
use App\Events\Exam\ExamAttemptGraded;
use App\Events\Exam\ReferralStatusChanged;
use App\Models\CoinTransaction;
use App\Models\Exam\ExamAttempt;
use App\Models\Exam\ExamSession;
use App\Models\Exam\ExamVariant;
use App\Models\Exam\Referral;
use App\Models\Exam\ReferralCode;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Exam\ReferralService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ReferralFunnelTest extends TestCase
{
    use BuildsExamPeople, RefreshDatabase;

    private function register(array $query = []): User
    {
        $phone = '+99890'.random_int(1000000, 9999999);
        $this->post(route('register.submit', $query), [
            'first_name' => 'Yangi', 'last_name' => 'O\'quvchi', 'phone' => $phone,
            'password' => 'secret123', 'password_confirmation' => 'secret123',
        ])->assertRedirect();

        return User::where('phone', $phone)->firstOrFail();
    }

    private function gradedAttempt(User $student, string $type): ExamAttempt
    {
        $session = $type === 'placement'
            ? ExamSession::factory()->placement()->create(['subject_id' => $this->math()->id])
            : $this->weeklySession($this->math(), $this->lastMonday());
        $variant = ExamVariant::factory()->create(['session_id' => $session->id, 'user_id' => $student->id]);

        return ExamAttempt::factory()->graded(30, 45)->create(['variant_id' => $variant->id]);
    }

    private function subscribe(User $user): Subscription
    {
        $plan = SubscriptionPlan::forceCreate(['name' => 'Oylik', 'slug' => 'oylik-'.uniqid(), 'interval' => 'month', 'price' => 99000]);

        return Subscription::forceCreate([
            'user_id' => $user->id, 'plan_id' => $plan->id, 'status' => 'active',
            'started_at' => now(), 'ends_at' => now()->addMonth(),
        ]);
    }

    public function test_the_invite_link_is_captured_at_registration(): void
    {
        Event::fake([ReferralStatusChanged::class]);
        $referrer = $this->student();
        $code = app(ReferralService::class)->codeFor($referrer);

        $this->get('/r/'.strtolower($code->code))->assertRedirect(route('register'));
        $new = $this->register();

        $referral = Referral::where('referred_id', $new->id)->firstOrFail();
        $this->assertSame($referrer->id, $referral->referrer_id);
        $this->assertSame(ReferralStatus::Joined, $referral->status);
        $this->assertNotNull($referral->joined_at);
        $this->assertSame(1, $code->fresh()->uses_count);
        Event::assertDispatched(ReferralStatusChanged::class, fn ($e) => $e->referral->is($referral) && $e->previous === null);
    }

    public function test_the_ref_query_parameter_also_works(): void
    {
        $referrer = $this->student();
        $code = app(ReferralService::class)->codeFor($referrer)->code;

        $new = $this->register(['ref' => $code]);

        $this->assertSame($referrer->id, Referral::where('referred_id', $new->id)->value('referrer_id'));
    }

    public function test_unknown_own_and_repeated_invites_are_ignored(): void
    {
        $service = app(ReferralService::class);
        $a = $this->student();
        $b = $this->student();
        $c = $this->student();

        $this->assertNull($service->recordJoin($a, 'NOSUCHCODE'));
        $this->assertNull($service->recordJoin($a, $service->codeFor($a)->code), 'self invite');
        $this->assertNotNull($service->recordJoin($c, 'ref_'.$service->codeFor($a)->code), 'telegram payload form');
        $this->assertNull($service->recordJoin($c, $service->codeFor($b)->code), 'already invited');

        $inactive = $service->codeFor($b);
        $inactive->update(['is_active' => false]);
        $this->assertNull($service->recordJoin($this->student(), $inactive->code));
        $this->assertSame(1, Referral::count());
    }

    public function test_placement_then_subscription_moves_the_funnel_and_pays_the_bonus_once(): void
    {
        $service = app(ReferralService::class);
        $referrer = $this->student();
        $child = $this->student();
        $referral = $service->recordJoin($child, $service->codeFor($referrer)->code);

        Event::fake([ReferralStatusChanged::class]);

        // a weekly attempt is not the placement test
        event(new ExamAttemptGraded($this->gradedAttempt($child, 'weekly')));
        $this->assertSame(ReferralStatus::Joined, $referral->fresh()->status);

        $placement = $this->gradedAttempt($child, 'placement');
        event(new ExamAttemptGraded($placement));
        event(new ExamAttemptGraded($placement)); // repeated event

        $referral->refresh();
        $this->assertSame(ReferralStatus::PlacementDone, $referral->status);
        $this->assertNotNull($referral->placement_done_at);
        $this->assertNotNull($referral->reward_granted_at);
        $this->assertSame(1, CoinTransaction::where('user_id', $referrer->id)->where('source', 'referral')->count());
        $this->assertStringNotContainsString((string) $child->last_name, (string) CoinTransaction::where('source', 'referral')->value('description'));

        $this->subscribe($child);
        $referral->refresh();
        $this->assertSame(ReferralStatus::Subscribed, $referral->status);
        $this->assertNotNull($referral->subscribed_at);

        // never back, never paid twice
        event(new ExamAttemptGraded($this->gradedAttempt($child, 'placement')));
        $this->assertSame(ReferralStatus::Subscribed, $referral->fresh()->status);
        $this->assertSame(1, CoinTransaction::where('user_id', $referrer->id)->where('source', 'referral')->count());

        Event::assertDispatchedTimes(ReferralStatusChanged::class, 2);
        Event::assertDispatched(ReferralStatusChanged::class, fn ($e) => $e->previous === ReferralStatus::PlacementDone && $e->referral->status === ReferralStatus::Subscribed);
    }

    public function test_a_regraded_attempt_does_not_move_the_funnel(): void
    {
        $service = app(ReferralService::class);
        $child = $this->student();
        $referral = $service->recordJoin($child, $service->codeFor($this->student())->code);

        event(new ExamAttemptGraded($this->gradedAttempt($child, 'placement'), regraded: true));

        $this->assertSame(ReferralStatus::Joined, $referral->fresh()->status);
    }

    public function test_guardians_who_invite_get_no_coins(): void
    {
        $service = app(ReferralService::class);
        $guardian = $this->guardianUser();
        $child = $this->student();
        $referral = $service->recordJoin($child, $service->codeFor($guardian)->code);

        event(new ExamAttemptGraded($this->gradedAttempt($child, 'placement')));

        $this->assertSame(ReferralStatus::PlacementDone, $referral->fresh()->status);
        $this->assertSame(0, CoinTransaction::where('user_id', $guardian->id)->count());
    }

    public function test_an_expired_or_inactive_subscription_does_not_count(): void
    {
        $service = app(ReferralService::class);
        $child = $this->student();
        $referral = $service->recordJoin($child, $service->codeFor($this->student())->code);

        $sub = $this->subscribe($child);
        $this->assertSame(ReferralStatus::Subscribed, $referral->fresh()->status);

        $other = $this->student();
        $r2 = $service->recordJoin($other, $service->codeFor($this->student())->code);
        $plan = SubscriptionPlan::forceCreate(['name' => 'X', 'slug' => 'x-'.uniqid(), 'interval' => 'month', 'price' => 1]);
        Subscription::forceCreate(['user_id' => $other->id, 'plan_id' => $plan->id, 'status' => 'cancelled', 'started_at' => now(), 'ends_at' => now()->addMonth()]);
        $this->assertSame(ReferralStatus::Joined, $r2->fresh()->status);
        $this->assertInstanceOf(ReferralCode::class, $service->codeFor($other));
    }
}
