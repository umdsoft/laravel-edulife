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
            // 'paid' is the legacy UI value for the 'completed' enum state
            $query->where('status', $status === 'paid' ? 'completed' : $status);
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

        // The UI filters by pending/verified; the table stores is_verified
        if ($status = $request->get('status')) {
            $query->where('is_verified', $status === 'verified');
        }

        $accounts = $query->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn ($account) => $account->setAttribute('status', $account->is_verified ? 'verified' : 'pending'));

        return Inertia::render('Admin/TeacherPayouts/BankAccounts', [
            'accounts' => $accounts,
            'filters' => $request->only(['status']),
        ]);
    }

    public function verifyBankAccount(TeacherBankAccount $account)
    {
        $account->update(['is_verified' => true, 'verified_at' => now()]);
        return back()->with('success', 'Bank hisobi tasdiqlandi');
    }

    public function markPaid(TeacherPayout $payout)
    {
        if ($payout->status !== 'pending') {
            return back()->with('error', 'Faqat kutilayotgan to\'lovlarni to\'landi deb belgilash mumkin');
        }

        $payout->update([
            'status' => 'completed',
            'processed_at' => now(),
            'processed_by' => auth()->id(),
        ]);
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
