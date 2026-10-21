<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\OlympiadRegistration;
use App\Models\PaymentOrder;
use App\Services\Payments\CheckoutException;
use App\Services\Payments\CheckoutService;
use App\Services\Telegram\WebviewSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

/**
 * The student side of an online payment: price quote, order creation and the redirect to
 * Payme / Click, and the page the provider sends the student back to.
 */
class CheckoutController extends Controller
{
    public function __construct(private readonly CheckoutService $checkout) {}

    /** Price with the promo code applied, and the providers that can be used. */
    public function quote(Request $request): JsonResponse
    {
        $data = $request->validate([
            'product_type' => ['required', Rule::in(PaymentOrder::TYPES)],
            'product_id' => ['required', 'uuid'],
            'promo_code' => ['nullable', 'string', 'max:50'],
        ]);

        try {
            $quote = $this->checkout->quote($request->user(), $data['product_type'], $data['product_id'], $data['promo_code'] ?? null);
        } catch (CheckoutException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'title' => $quote['title'],
            'original' => $quote['original'],
            'discount' => $quote['discount'],
            'amount' => $quote['amount'],
            'promo_code' => $quote['promo']?->code,
            'providers' => CheckoutService::enabledProviders(),
        ]);
    }

    /** Create the order (amount from the server) and send the student to the provider. */
    public function store(Request $request): SymfonyResponse
    {
        $data = $request->validate([
            'product_type' => ['required', Rule::in(PaymentOrder::TYPES)],
            'product_id' => ['required', 'uuid'],
            'provider' => ['required', Rule::in([PaymentOrder::PROVIDER_PAYME, PaymentOrder::PROVIDER_CLICK])],
            'promo_code' => ['nullable', 'string', 'max:50'],
        ]);

        return $this->startCheckout($request, $data['product_type'], $data['product_id'], $data['provider'], $data['promo_code'] ?? null);
    }

    /** Shared by the shop and olympiad payment forms. */
    public function startCheckout(Request $request, string $type, string $productId, string $provider, ?string $promoCode = null): SymfonyResponse
    {
        // a plain JSON request (Telegram WebView): answer with the provider URL, the page opens it
        // with Telegram.WebApp.openLink — the Mini App itself never navigates to Payme / Click
        $wantsUrl = $request->expectsJson() && ! $request->header('X-Inertia');

        try {
            $order = $this->checkout->createOrder($request->user(), $type, $productId, $provider, $promoCode);
        } catch (CheckoutException $e) {
            return $wantsUrl
                ? response()->json(['message' => $e->getMessage()], 422)
                : back()->withErrors(['checkout' => $e->getMessage()])->with('error', $e->getMessage());
        }

        // paid exams: from the Mini App the provider sends the student back to Telegram (CheckoutService::returnUrl)
        if ($type === PaymentOrder::TYPE_EXAM_SESSION && WebviewSession::active($request)
            && ($order->meta['return_to'] ?? null) !== 'telegram' && $order->isPending()) {
            $order->forceFill(['meta' => array_merge($order->meta ?? [], ['return_to' => 'telegram'])])->save();
        }

        if ($order->isPaid()) {
            return $wantsUrl
                ? response()->json(['status' => 'paid', 'redirect' => route('payments.return', $order)])
                : redirect()->route('payments.return', $order);
        }

        if ($wantsUrl) {
            return response()->json(['status' => 'pending', 'url' => $this->checkout->checkoutUrl($order), 'order' => $order->uuid]);
        }

        // full-page redirect to Payme / Click (Inertia answers 409 + X-Inertia-Location)
        return Inertia::location($this->checkout->checkoutUrl($order));
    }

    /** Open the provider page again for a pending order. */
    public function pay(Request $request, PaymentOrder $order): SymfonyResponse
    {
        $this->authorizeOwner($request, $order);

        if (! $order->isPending() || ! in_array($order->provider, CheckoutService::enabledProviders(), true)) {
            return redirect()->route('payments.return', $order);
        }

        return Inertia::location($this->checkout->checkoutUrl($order));
    }

    /** The return page: shows the order status (the callback may arrive a few seconds later). */
    public function show(Request $request, PaymentOrder $order): Response
    {
        $this->authorizeOwner($request, $order);
        $order->load('transactions');
        $status = $order->publicStatus();

        [$nextUrl, $nextLabel] = $this->next($order);

        return Inertia::render('Student/Payments/Status', [
            'order' => [
                'id' => $order->id,
                'uuid' => $order->uuid,
                'title' => $order->title,
                'provider' => $order->provider,
                'amount' => (float) $order->amount,
                'original_amount' => (float) $order->original_amount,
                'discount_amount' => (float) $order->discount_amount,
                'status' => $status,
                'created_at' => $order->created_at?->format('d.m.Y H:i'),
                'paid_at' => $order->paid_at?->format('d.m.Y H:i'),
                'coins' => $order->fulfilment['coins'] ?? null,
                'can_retry' => $order->isPending() && in_array($order->provider, CheckoutService::enabledProviders(), true),
            ],
            'nextUrl' => $nextUrl,
            'nextLabel' => $nextLabel,
        ]);
    }

    private function next(PaymentOrder $order): array
    {
        switch ($order->product_type) {
            case PaymentOrder::TYPE_COURSE:
                $course = Course::find($order->product_id);
                if (! $course) {
                    return [route('student.courses.index'), 'Kurslar'];
                }

                return $order->isPaid()
                    ? [route('student.learn.course', $course->slug), 'Kursni boshlash']
                    : [route('student.courses.show', $course->slug), 'Kursga qaytish'];
            case PaymentOrder::TYPE_COIN_PACKAGE:
                return [route('student.shop.index'), "Do'konga qaytish"];
            case PaymentOrder::TYPE_SUBSCRIPTION:
                return [route('student.subscriptions.index'), 'Tariflar sahifasi'];
            case PaymentOrder::TYPE_OLYMPIAD:
                $slug = OlympiadRegistration::with('olympiad')->find($order->product_id)?->olympiad?->slug;

                return $slug
                    ? [route('student.olympiads.show', $slug), 'Olimpiadaga qaytish']
                    : [route('student.olympiads.index'), 'Olimpiadalar'];
            case PaymentOrder::TYPE_EXAM_SESSION:
                return [route('student.exam.index'), 'Imtihon sahifasiga qaytish'];
            default:
                return [route('student.dashboard'), 'Bosh sahifa'];
        }
    }

    private function authorizeOwner(Request $request, PaymentOrder $order): void
    {
        abort_unless((string) $order->user_id === (string) $request->user()->id, 404);
    }
}
