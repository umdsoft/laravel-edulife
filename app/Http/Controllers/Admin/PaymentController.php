<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    /**
     * Display a listing of payments.
     */
    public function index(Request $request): Response
    {
        $query = Payment::with('user');

        // Search
        if ($search = $request->get('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('transaction_id', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($uq) use ($search) {
                        $uq->where('first_name', 'like', "%{$search}%")
                            ->orWhere('last_name', 'like', "%{$search}%");
                    });
            });
        }

        // Filter by status
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        // Filter by type
        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }

        // Paginate
        $payments = $query->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn ($payment) => [
                'id' => $payment->id,
                'transaction_id' => $payment->transaction_id,
                'user_name' => $payment->user ? $payment->user->first_name . ' ' . $payment->user->last_name : 'N/A',
                'amount' => $payment->amount,
                'type' => $payment->type,
                'status' => $payment->status,
                'created_at' => $payment->created_at->format('d.m.Y H:i'),
            ]);

        // Calculate summary
        $summary = [
            'total_amount' => Payment::where('status', 'completed')->sum('amount'),
            'total_count' => Payment::count(),
        ];

        return Inertia::render('Admin/Payments/Index', [
            'payments' => $payments,
            'summary' => $summary,
            'filters' => $request->only(['search', 'status', 'type']),
        ]);
    }

    /**
     * Display the specified payment.
     */
    public function show(Payment $payment): Response
    {
        $payment->load(['user', 'course', 'subscriptionPlan', 'promoCode']);

        return Inertia::render('Admin/Payments/Show', [
            'payment' => [
                'id' => $payment->id,
                'transaction_id' => $payment->transaction_id,
                'amount' => $payment->amount,
                'type' => $payment->type,
                'status' => $payment->status,
                'payment_method' => $payment->payment_method,
                'user' => $payment->user ? [
                    'id' => $payment->user->id,
                    'full_name' => $payment->user->first_name . ' ' . $payment->user->last_name,
                    'phone' => $payment->user->phone,
                    'email' => $payment->user->email,
                ] : null,
                'course' => $payment->course ? [
                    'id' => $payment->course->id,
                    'title' => $payment->course->title,
                ] : null,
                'subscription_plan' => $payment->subscriptionPlan ? [
                    'id' => $payment->subscriptionPlan->id,
                    'name' => $payment->subscriptionPlan->name,
                ] : null,
                'promo_code' => $payment->promoCode ? [
                    'code' => $payment->promoCode->code,
                    'discount' => $payment->promoCode->discount,
                ] : null,
                'created_at' => $payment->created_at->format('d.m.Y H:i'),
            ],
        ]);
    }
}
