<?php

namespace App\Services;

use App\Models\DiscountCoupon;
use App\Models\Olympiad;
use App\Models\OlympiadPaymentTransaction;
use App\Models\OlympiadRegistration;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OlympiadRegistrationService
{
    /**
     * Check if user can register for olympiad
     */
    public function canRegister(User $user, Olympiad $olympiad): array
    {
        // Check olympiad status
        if (!in_array($olympiad->status, [Olympiad::STATUS_UPCOMING, Olympiad::STATUS_REGISTRATION_OPEN])) {
            return ['can_register' => false, 'reason' => 'registration_closed'];
        }

        // Check registration dates
        if ($olympiad->registration_start_at && now()->lt($olympiad->registration_start_at)) {
            return ['can_register' => false, 'reason' => 'registration_not_started'];
        }

        if ($olympiad->registration_end_at && now()->gt($olympiad->registration_end_at)) {
            return ['can_register' => false, 'reason' => 'registration_ended'];
        }

        // Check if already registered
        $existingRegistration = OlympiadRegistration::where('olympiad_id', $olympiad->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existingRegistration) {
            return ['can_register' => false, 'reason' => 'already_registered', 'registration' => $existingRegistration];
        }

        // Check max participants
        if ($olympiad->max_participants) {
            $currentCount = OlympiadRegistration::where('olympiad_id', $olympiad->id)
                ->whereIn('status', [OlympiadRegistration::STATUS_CONFIRMED, OlympiadRegistration::STATUS_PENDING_PAYMENT])
                ->count();

            if ($currentCount >= $olympiad->max_participants) {
                return ['can_register' => false, 'reason' => 'max_participants_reached'];
            }
        }

        return ['can_register' => true];
    }

    /**
     * Register user for olympiad
     */
    public function register(User $user, Olympiad $olympiad, array $data = []): OlympiadRegistration
    {
        $checkResult = $this->canRegister($user, $olympiad);
        
        if (!$checkResult['can_register']) {
            throw new \Exception("Cannot register: {$checkResult['reason']}");
        }

        return DB::transaction(function () use ($user, $olympiad, $data) {
            $originalPrice = $olympiad->registration_fee ?? 0;
            $discountAmount = 0;
            $discountPercent = 0;
            $couponId = null;

            // Apply coupon if provided
            if (!empty($data['coupon_code'])) {
                $coupon = $this->validateCoupon($data['coupon_code'], $olympiad->id, $user->id);
                if ($coupon) {
                    $discountAmount = $coupon->calculateDiscount($originalPrice);
                    $discountPercent = ($discountAmount / $originalPrice) * 100;
                    $couponId = $coupon->id;
                }
            }

            // Check for advancement discount
            if (!empty($data['advanced_from_olympiad_id'])) {
                $advancementDiscount = $this->getAdvancementDiscount(
                    $user->id, 
                    $data['advanced_from_olympiad_id'],
                    $olympiad->id
                );
                if ($advancementDiscount > $discountAmount) {
                    $discountAmount = $advancementDiscount;
                    $discountPercent = ($discountAmount / $originalPrice) * 100;
                }
            }

            $finalPrice = max(0, $originalPrice - $discountAmount);

            $registration = OlympiadRegistration::create([
                'olympiad_id' => $olympiad->id,
                'user_id' => $user->id,
                'status' => $finalPrice > 0 
                    ? OlympiadRegistration::STATUS_PENDING_PAYMENT 
                    : OlympiadRegistration::STATUS_CONFIRMED,
                'advanced_from_olympiad_id' => $data['advanced_from_olympiad_id'] ?? null,
                'advanced_from_rank' => $data['advanced_from_rank'] ?? null,
                'coupon_id' => $couponId,
                'discount_percent' => $discountPercent,
                'discount_amount' => $discountAmount,
                'original_price' => $originalPrice,
                'final_price' => $finalPrice,
                'school_id' => $data['school_id'] ?? null,
                'grade_level' => $data['grade_level'] ?? null,
                'registered_at' => now(),
                'confirmed_at' => $finalPrice > 0 ? null : now(),
            ]);

            // Mark coupon as used if free registration
            if ($finalPrice == 0 && $couponId) {
                DiscountCoupon::find($couponId)->use();
            }

            return $registration;
        });
    }

    /**
     * Validate coupon code
     */
    public function validateCoupon(string $code, string $olympiadId, string $userId): ?DiscountCoupon
    {
        $coupon = DiscountCoupon::where('code', $code)->first();

        if (!$coupon) {
            return null;
        }

        if (!$coupon->canBeUsedFor($olympiadId, $userId)) {
            return null;
        }

        return $coupon;
    }

    /**
     * Get advancement discount from previous olympiad
     */
    private function getAdvancementDiscount(string $userId, string $fromOlympiadId, string $toOlympiadId): float
    {
        $coupon = DiscountCoupon::where('user_id', $userId)
            ->where('source_olympiad_id', $fromOlympiadId)
            ->where(function ($q) use ($toOlympiadId) {
                $q->whereNull('olympiad_id')
                  ->orWhere('olympiad_id', $toOlympiadId);
            })
            ->valid()
            ->first();

        if (!$coupon) {
            return 0;
        }

        $olympiad = Olympiad::find($toOlympiadId);
        return $coupon->calculateDiscount($olympiad->registration_fee ?? 0);
    }

    /**
     * Process payment for registration
     */
    public function processPayment(OlympiadRegistration $registration, array $paymentData): OlympiadPaymentTransaction
    {
        if ($registration->status !== OlympiadRegistration::STATUS_PENDING_PAYMENT) {
            throw new \Exception('Registration is not pending payment');
        }

        return DB::transaction(function () use ($registration, $paymentData) {
            $transaction = OlympiadPaymentTransaction::create([
                'user_id' => $registration->user_id,
                'registration_id' => $registration->id,
                'payment_type' => OlympiadPaymentTransaction::TYPE_OLYMPIAD_REGISTRATION,
                'payment_method' => $paymentData['payment_method'],
                'amount' => $paymentData['amount'] ?? $registration->final_price,
                'coins_amount' => $paymentData['coins_amount'] ?? 0,
                'status' => OlympiadPaymentTransaction::STATUS_PENDING,
                'ip_address' => $paymentData['ip_address'] ?? null,
            ]);

            // For coins payment, process immediately
            if ($paymentData['payment_method'] === 'coins') {
                $user = $registration->user;
                $coinsNeeded = $paymentData['coins_amount'];
                
                if ($user->coins < $coinsNeeded) {
                    $transaction->fail('Insufficient coins');
                    throw new \Exception('Insufficient coins');
                }

                $user->decrement('coins', $coinsNeeded);
                $transaction->complete();
            }

            return $transaction;
        });
    }

    /**
     * Cancel registration
     */
    public function cancel(OlympiadRegistration $registration, string $reason = null): void
    {
        if (!in_array($registration->status, [
            OlympiadRegistration::STATUS_PENDING_PAYMENT,
            OlympiadRegistration::STATUS_CONFIRMED,
        ])) {
            throw new \Exception('Cannot cancel registration in current status');
        }

        DB::transaction(function () use ($registration, $reason) {
            // Refund if paid with coins
            if ($registration->payment_coins > 0) {
                $registration->user->increment('coins', $registration->payment_coins);
            }

            $registration->cancel($reason);
        });
    }

    /**
     * Get user's registration for olympiad
     */
    public function getUserRegistration(string $userId, string $olympiadId): ?OlympiadRegistration
    {
        return OlympiadRegistration::with(['olympiad', 'coupon', 'attempt'])
            ->where('user_id', $userId)
            ->where('olympiad_id', $olympiadId)
            ->first();
    }

    /**
     * Purchase demo for registration
     */
    public function purchaseDemo(OlympiadRegistration $registration, array $paymentData): bool
    {
        if ($registration->demo_purchased) {
            throw new \Exception('Demo already purchased');
        }

        $olympiad = $registration->olympiad;
        $demoPrice = $olympiad->demo_config['price'] ?? 0;

        if ($demoPrice == 0) {
            $registration->demo_purchased = true;
            $registration->save();
            return true;
        }

        return DB::transaction(function () use ($registration, $paymentData, $demoPrice) {
            $transaction = OlympiadPaymentTransaction::create([
                'user_id' => $registration->user_id,
                'registration_id' => $registration->id,
                'payment_type' => OlympiadPaymentTransaction::TYPE_DEMO_PURCHASE,
                'payment_method' => $paymentData['payment_method'],
                'amount' => $demoPrice,
                'coins_amount' => $paymentData['coins_amount'] ?? 0,
                'status' => OlympiadPaymentTransaction::STATUS_PENDING,
            ]);

            if ($paymentData['payment_method'] === 'coins') {
                $user = $registration->user;
                $coinsNeeded = $paymentData['coins_amount'];
                
                if ($user->coins < $coinsNeeded) {
                    $transaction->fail('Insufficient coins');
                    throw new \Exception('Insufficient coins');
                }

                $user->decrement('coins', $coinsNeeded);
                $transaction->complete();

                $registration->demo_purchased = true;
                $registration->save();
            }

            return true;
        });
    }
}
