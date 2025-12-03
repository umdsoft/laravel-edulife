<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSubscriptionPlanRequest;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    /**
     * Display subscription plans.
     */
    public function plans(): Response
    {
        $plans = SubscriptionPlan::orderBy('price')->get()->map(fn ($plan) => [
            'id' => $plan->id,
            'name' => $plan->name,
            'slug' => $plan->slug,
            'description' => $plan->description,
            'price' => $plan->price,
            'annual_price' => $plan->annual_price,
            'interval' => $plan->interval,
            'interval_count' => $plan->interval_count,
            'features' => $plan->features,
            'limits' => $plan->limits,
            'is_featured' => $plan->is_featured,
            'is_active' => $plan->is_active,
        ]);

        return Inertia::render('Admin/Subscriptions/Plans', [
            'plans' => $plans,
        ]);
    }

    /**
     * Store a new subscription plan.
     */
    public function storePlan(StoreSubscriptionPlanRequest $request): RedirectResponse
    {
        SubscriptionPlan::create($request->validated());

        return back()->with('success', 'Obuna rejasi yaratildi!');
    }

    /**
     * Update subscription plan.
     */
    public function updatePlan(StoreSubscriptionPlanRequest $request, SubscriptionPlan $plan): RedirectResponse
    {
        $plan->update($request->validated());

        return back()->with('success', 'Obuna rejasi yangilandi!');
    }

    /**
     * Delete subscription plan.
     */
    public function destroyPlan(SubscriptionPlan $plan): RedirectResponse
    {
        // Check if plan has active subscriptions
        $activeCount = Subscription::where('plan_id', $plan->id)
            ->where('status', 'active')
            ->count();

        if ($activeCount > 0) {
            return back()->withErrors(['error' => 'Bu rejada faol obunalar mavjud!']);
        }

        $plan->delete();

        return back()->with('success', 'Obuna rejasi o\'chirildi!');
    }

    /**
     * Display active subscriptions.
     */
    public function index(Request $request): Response
    {
        $query = Subscription::with(['user', 'plan']);

        // Search
        if ($search = $request->get('search')) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Filter by plan
        if ($planId = $request->get('plan_id')) {
            $query->where('plan_id', $planId);
        }

        // Filter by status
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $subscriptions = $query->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn ($subscription) => [
                'id' => $subscription->id,
                'user_name' => $subscription->user ? $subscription->user->first_name . ' ' . $subscription->user->last_name : 'N/A',
                'user_id' => $subscription->user_id,
                'plan_name' => $subscription->plan->name ?? 'N/A',
                'status' => $subscription->status,
                'started_at' => $subscription->started_at->format('d.m.Y'),
                'ends_at' => $subscription->ends_at->format('d.m.Y'),
                'is_expiring_soon' => $subscription->ends_at->diffInDays(now()) <= 7,
            ]);

        // Get all plans for filter
        $plans = SubscriptionPlan::where('is_active', true)
            ->orderBy('price')
            ->get(['id', 'name']);

        return Inertia::render('Admin/Subscriptions/Index', [
            'subscriptions' => $subscriptions,
            'plans' => $plans,
            'filters' => $request->only(['search', 'plan_id', 'status']),
        ]);
    }
}
