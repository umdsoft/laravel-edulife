<?php

namespace App\Http\Controllers\Payments;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Services\Payments\CheckoutService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/** Student page with the subscription plans, bought online with Payme / Click. */
class SubscriptionPlanController extends Controller
{
    private const INTERVALS = ['day' => 'kun', 'week' => 'hafta', 'month' => 'oy', 'year' => 'yil'];

    public function index(Request $request): Response
    {
        $user = $request->user();

        $current = Subscription::with('plan')
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>', now()))
            ->latest('started_at')
            ->first();

        $plans = SubscriptionPlan::where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('price')
            ->get()
            ->map(fn (SubscriptionPlan $plan) => [
                'id' => $plan->id,
                'name' => $plan->name,
                'description' => $plan->description,
                'price' => (float) $plan->price,
                'original_price' => $plan->original_price ? (float) $plan->original_price : null,
                'period' => ((int) $plan->interval_count > 1 ? $plan->interval_count.' ' : '').(self::INTERVALS[$plan->interval] ?? $plan->interval),
                'features' => array_values(array_filter((array) ($plan->features ?? []), 'is_string')),
                'is_featured' => (bool) $plan->is_featured,
                'is_current' => $current && (string) $current->plan_id === (string) $plan->id,
            ])
            ->values();

        return Inertia::render('Student/Subscriptions/Index', [
            'plans' => $plans,
            'current' => $current ? [
                'plan' => $current->plan?->name,
                'ends_at' => $current->ends_at?->format('d.m.Y'),
            ] : null,
            'paymentProviders' => CheckoutService::enabledProviders(),
        ]);
    }
}
