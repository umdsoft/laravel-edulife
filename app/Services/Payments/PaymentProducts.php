<?php

namespace App\Services\Payments;

use App\Models\CoinPackage;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\OlympiadRegistration;
use App\Models\PaymentOrder;
use App\Models\SubscriptionPlan;
use App\Models\User;

/**
 * What can be bought online and for how much. Prices always come from the database,
 * never from the request.
 */
class PaymentProducts
{
    /**
     * @return array{title: string, price: float, model: \Illuminate\Database\Eloquent\Model, promo_scopes: array<int, string>, course_id: ?string, plan_id: ?string}
     *
     * @throws CheckoutException
     */
    public function resolve(User $user, string $type, string $id): array
    {
        return match ($type) {
            PaymentOrder::TYPE_COURSE => $this->course($user, $id),
            PaymentOrder::TYPE_COIN_PACKAGE => $this->coinPackage($id),
            PaymentOrder::TYPE_SUBSCRIPTION => $this->plan($id),
            PaymentOrder::TYPE_OLYMPIAD => $this->olympiadRegistration($user, $id),
            default => throw new CheckoutException("Noma'lum mahsulot."),
        };
    }

    /** Why this order can no longer be paid (null = it can). */
    public function problem(PaymentOrder $order): ?string
    {
        $user = $order->user;
        if (! $user) {
            return "Foydalanuvchi topilmadi.";
        }

        try {
            $this->resolve($user, $order->product_type, (string) $order->product_id);
        } catch (CheckoutException $e) {
            return $e->getMessage();
        }

        return null;
    }

    private function course(User $user, string $id): array
    {
        $course = Course::where('id', $id)->where('status', 'published')->first();
        if (! $course) {
            throw new CheckoutException('Kurs topilmadi.');
        }
        if ($course->is_free || (float) $course->price <= 0) {
            throw new CheckoutException("Bu kurs bepul — to'lovsiz yozilish mumkin.");
        }
        if (Enrollment::where('user_id', $user->id)->where('course_id', $course->id)->exists()) {
            throw new CheckoutException('Siz bu kursga allaqachon yozilgansiz.');
        }

        return [
            'title' => "Kurs: {$course->title}",
            'price' => (float) $course->price,
            'model' => $course,
            'promo_scopes' => ['all', 'courses', 'specific_course'],
            'course_id' => $course->id,
            'plan_id' => null,
        ];
    }

    private function coinPackage(string $id): array
    {
        $package = CoinPackage::where('id', $id)->where('is_active', true)->first();
        if (! $package || (float) $package->price <= 0) {
            throw new CheckoutException('Coin paketi topilmadi.');
        }
        $coins = (int) ($package->total_coins ?: ((int) $package->coins + (int) $package->bonus_coins));

        return [
            'title' => "Coin paketi: {$package->name} ({$coins} coin)",
            'price' => (float) $package->price,
            'model' => $package,
            'promo_scopes' => ['all'],
            'course_id' => null,
            'plan_id' => null,
            'meta' => ['coins' => $coins],
        ];
    }

    private function plan(string $id): array
    {
        $plan = SubscriptionPlan::where('id', $id)->where('is_active', true)->first();
        if (! $plan || (float) $plan->price <= 0) {
            throw new CheckoutException('Tarif topilmadi.');
        }

        return [
            'title' => "Obuna: {$plan->name}",
            'price' => (float) $plan->price,
            'model' => $plan,
            'promo_scopes' => ['all', 'subscriptions'],
            'course_id' => null,
            'plan_id' => $plan->id,
        ];
    }

    private function olympiadRegistration(User $user, string $id): array
    {
        $registration = OlympiadRegistration::with('olympiad')
            ->where('id', $id)
            ->where('user_id', $user->id)
            ->first();
        if (! $registration) {
            throw new CheckoutException("Olimpiada ro'yxati topilmadi.");
        }
        if ($registration->status !== OlympiadRegistration::STATUS_PENDING_PAYMENT || (float) $registration->final_price <= 0) {
            throw new CheckoutException("Bu ro'yxatdan o'tish to'lov kutmayapti.");
        }

        return [
            'title' => 'Olimpiada: '.($registration->olympiad?->title ?? "ro'yxatdan o'tish"),
            'price' => (float) $registration->final_price,
            'model' => $registration,
            // the discount code is entered at registration and is already in final_price
            'promo_scopes' => [],
            'course_id' => null,
            'plan_id' => null,
        ];
    }
}
