<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TeacherPayout;
use App\Models\TeacherBankAccount;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TeacherPayoutController extends Controller
{
    public function index(Request $request)
    {
        $query = TeacherPayout::with(['teacher']);

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $payouts = $query->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/TeacherPayouts/Index', [
            'payouts' => $payouts,
            'filters' => $request->only(['status']),
        ]);
    }

    public function bankAccounts(Request $request)
    {
        $query = TeacherBankAccount::with(['teacher']);

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }

        $accounts = $query->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Admin/TeacherPayouts/BankAccounts', [
            'accounts' => $accounts,
            'filters' => $request->only(['status']),
        ]);
    }

    public function verifyBankAccount(TeacherBankAccount $account)
    {
        $account->update(['status' => 'verified', 'verified_at' => now()]);
        return back()->with('success', 'Bank hisobi tasdiqlandi');
    }

    public function markPaid(TeacherPayout $payout)
    {
        if ($payout->status !== 'pending') {
            return back()->with('error', 'Faqat kutilayotgan to\'lovlarni to\'landi deb belgilash mumkin');
        }

        $payout->update(['status' => 'paid', 'paid_at' => now()]);
        return back()->with('success', 'To\'lov amalga oshirildi');
    }

    public function cancel(TeacherPayout $payout)
    {
        if ($payout->status !== 'pending') {
            return back()->with('error', 'Faqat kutilayotgan to\'lovlarni bekor qilish mumkin');
        }

        // Refund amount to teacher's balance (logic depends on how balance is handled)
        // Assuming we just mark as cancelled for now
        $payout->update(['status' => 'cancelled']);
        
        // TODO: Refund logic if balance was deducted on request creation

        return back()->with('success', 'To\'lov bekor qilindi');
    }
}
