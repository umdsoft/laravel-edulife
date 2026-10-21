<?php

namespace App\Http\Controllers\Admin\Exam;

use App\Http\Controllers\Controller;
use App\Models\Exam\ExamSessionPurchase;
use App\Models\PaymentOrder;
use App\Services\Exam\ExamRefunds;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Paid exams: money to return by hand (cancelled sessions, entries paid twice). Provider refund
 * APIs are not called: the admin returns the money (Payme / Click cabinet, ...) and marks it here.
 */
class ExamRefundController extends Controller
{
    public function __construct(private readonly ExamRefunds $refunds) {}

    public function index(): Response
    {
        $lists = $this->refunds->lists();

        return Inertia::render('Admin/Exam/Refunds', [
            'pending' => $lists['pending'],
            'refunded' => $lists['refunded'],
        ]);
    }

    public function markPurchase(Request $request, ExamSessionPurchase $purchase): RedirectResponse
    {
        $data = $request->validate(['note' => ['nullable', 'string', 'max:200']]);

        return $this->refunds->markRefunded($purchase, $request->user(), $data['note'] ?? null)
            ? back()->with('success', 'Qaytarildi deb belgilandi.')
            : back()->with('error', "Bu to'lov qaytarish kutmayapti.");
    }

    public function markDuplicate(Request $request, PaymentOrder $order): RedirectResponse
    {
        $data = $request->validate(['note' => ['nullable', 'string', 'max:200']]);

        return $this->refunds->markDuplicateRefunded($order, $data['note'] ?? null)
            ? back()->with('success', 'Qaytarildi deb belgilandi.')
            : back()->with('error', "Bu to'lov qaytarish kutmayapti.");
    }
}
