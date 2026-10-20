<?php

namespace App\Services\Payments;

use App\Models\Payment;
use App\Models\PaymentOrder;
use App\Models\PaymentTransaction;
use App\Models\PromoCode;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Creates orders (the amount is computed here from the database and server-side promo codes)
 * and builds the provider checkout URLs.
 */
class CheckoutService
{
    public function __construct(
        private readonly PaymentProducts $products,
        private readonly PaymentFulfillmentService $fulfilment,
    ) {}

    /** Providers whose credentials are configured. */
    public static function enabledProviders(): array
    {
        return array_values(array_filter([
            PaymeService::configured() ? PaymentOrder::PROVIDER_PAYME : null,
            ClickService::configured() ? PaymentOrder::PROVIDER_CLICK : null,
        ]));
    }

    /**
     * Price for the student, with an optional promo code applied.
     *
     * @return array{title: string, original: float, discount: float, amount: float, promo: ?PromoCode, course_id: ?string, plan_id: ?string, meta: ?array}
     *
     * @throws CheckoutException
     */
    public function quote(User $user, string $type, string $productId, ?string $promoCode = null): array
    {
        $product = $this->products->resolve($user, $type, $productId);
        $original = round($product['price'], 2);
        $discount = 0.0;
        $promo = null;

        $promoCode = trim((string) $promoCode);
        if ($promoCode !== '') {
            $promo = $this->promo($user, $promoCode, $product, $original);
            $discount = min($original, round((float) $promo->calculateDiscount($original), 2));
        }

        return [
            'title' => $product['title'],
            'original' => $original,
            'discount' => $discount,
            'amount' => round(max(0, $original - $discount), 2),
            'promo' => $promo,
            'course_id' => $product['course_id'],
            'plan_id' => $product['plan_id'],
            'meta' => $product['meta'] ?? null,
        ];
    }

    /**
     * Create (or reuse) the pending order the student will pay.
     *
     * @throws CheckoutException
     */
    public function createOrder(User $user, string $type, string $productId, string $provider, ?string $promoCode = null): PaymentOrder
    {
        if (! in_array($provider, self::enabledProviders(), true)) {
            throw new CheckoutException("Bu to'lov tizimi hozircha ulanmagan.");
        }

        $quote = $this->quote($user, $type, $productId, $promoCode);

        // a 100% promo code: nothing to pay online, grant it right away
        if ($quote['amount'] <= 0) {
            $order = DB::transaction(fn () => $this->newOrder($user, $type, $productId, PaymentOrder::PROVIDER_MANUAL, $quote));

            return $this->fulfilment->fulfil($order);
        }

        return DB::transaction(function () use ($user, $type, $productId, $provider, $quote) {
            $existing = PaymentOrder::where('user_id', $user->id)
                ->where('product_type', $type)
                ->where('product_id', $productId)
                ->where('provider', $provider)
                ->where('status', PaymentOrder::STATUS_PENDING)
                ->where('amount_tiyin', $this->tiyin($quote['amount']))
                ->where('promo_code_id', $quote['promo']?->id)
                ->where('created_at', '>=', now()->subMinutes((int) config('payments.order_reuse_minutes', 60)))
                ->whereDoesntHave('transactions', fn ($q) => $q->where('state', PaymentTransaction::STATE_CREATED))
                ->latest('id')
                ->lockForUpdate()
                ->first();

            return $existing ?? $this->newOrder($user, $type, $productId, $provider, $quote);
        });
    }

    /** Where to send the student to pay this order. */
    public function checkoutUrl(PaymentOrder $order): string
    {
        return match ($order->provider) {
            PaymentOrder::PROVIDER_PAYME => $this->paymeUrl($order),
            PaymentOrder::PROVIDER_CLICK => $this->clickUrl($order),
            default => route('payments.return', $order),
        };
    }

    /**
     * Payme checkout by GET: <checkout_url>/base64(m=…;ac.order_id=…;a=…;l=…;c=…;ct=…)
     * https://developer.help.paycom.uz/initsializatsiya-platezhey/otpravka-cheka-po-metodu-get
     */
    public function paymeUrl(PaymentOrder $order): string
    {
        $config = config('payments.payme');
        $params = [
            'm' => $config['merchant_id'],
            'ac.'.$config['account_field'] => $order->id,
            'a' => $order->amount_tiyin,
            'l' => $config['lang'] ?? 'uz',
            'c' => $this->returnUrl($order, $config['return_url'] ?? null),
            'ct' => (int) ($config['return_timeout_ms'] ?? 15000),
        ];
        $encoded = base64_encode(implode(';', array_map(fn ($k, $v) => "{$k}={$v}", array_keys($params), $params)));
        $base = rtrim($config['test_mode'] ? $config['test_checkout_url'] : $config['checkout_url'], '/');

        return "{$base}/{$encoded}";
    }

    /**
     * Click payment page: https://my.click.uz/services/pay?service_id=…&merchant_id=…&amount=N.NN&transaction_param=…&return_url=…
     * https://docs.click.uz/click-button
     */
    public function clickUrl(PaymentOrder $order): string
    {
        $config = config('payments.click');
        $params = array_filter([
            'service_id' => $config['service_id'],
            'merchant_id' => $config['merchant_id'],
            'merchant_user_id' => $config['merchant_user_id'] ?: null,
            'amount' => $order->amountForClick(),
            'transaction_param' => (string) $order->id, // = merchant_trans_id in SHOP API
            'return_url' => $this->returnUrl($order, $config['return_url'] ?? null),
            'card_type' => $config['card_type'] ?: null,
        ], fn ($v) => $v !== null && $v !== '');

        return $config['pay_url'].'?'.http_build_query($params);
    }

    public function tiyin(float|string $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }

    private function returnUrl(PaymentOrder $order, ?string $template): string
    {
        return $template
            ? str_replace('{order}', $order->uuid, $template)
            : route('payments.return', $order);
    }

    private function newOrder(User $user, string $type, string $productId, string $provider, array $quote): PaymentOrder
    {
        $payment = Payment::create([
            'user_id' => $user->id,
            'type' => PaymentOrder::PAYMENT_TYPES[$type],
            'course_id' => $quote['course_id'],
            'subscription_plan_id' => $quote['plan_id'],
            'amount' => $quote['amount'],
            'original_amount' => $quote['original'],
            'discount_amount' => $quote['discount'],
            'currency' => config('payments.currency', 'UZS'),
            'status' => 'pending',
            'provider' => $provider,
            'promo_code_id' => $quote['promo']?->id,
        ]);

        return PaymentOrder::create([
            'user_id' => $user->id,
            'product_type' => $type,
            'product_id' => $productId,
            'title' => mb_substr($quote['title'], 0, 250),
            'provider' => $provider,
            'original_amount' => $quote['original'],
            'discount_amount' => $quote['discount'],
            'amount' => $quote['amount'],
            'amount_tiyin' => $this->tiyin($quote['amount']),
            'currency' => config('payments.currency', 'UZS'),
            'promo_code_id' => $quote['promo']?->id,
            'payment_id' => $payment->id,
            'status' => PaymentOrder::STATUS_PENDING,
            'meta' => $quote['meta'] ?? null,
        ]);
    }

    private function promo(User $user, string $code, array $product, float $price): PromoCode
    {
        if ($product['promo_scopes'] === []) {
            throw new CheckoutException("Bu xarid uchun promokod qo'llanmaydi.");
        }

        $promo = PromoCode::whereRaw('UPPER(code) = ?', [mb_strtoupper($code)])->first();
        $invalid = new CheckoutException("Promokod noto'g'ri yoki muddati o'tgan.");

        if (! $promo || ! in_array($promo->applies_to, $product['promo_scopes'], true) || ! $promo->canBeUsedBy($user)) {
            throw $invalid;
        }
        if ($promo->applies_to === 'specific_course' && (string) $promo->course_id !== (string) $product['course_id']) {
            throw $invalid;
        }
        if ($promo->min_purchase !== null && $price < (float) $promo->min_purchase) {
            throw new CheckoutException('Promokod '.number_format((float) $promo->min_purchase, 0, '', ' ')." so'mdan yuqori xaridlar uchun.");
        }

        return $promo;
    }
}
