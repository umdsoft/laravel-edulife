<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Models\TeacherEarning;
use App\Models\TeacherPayout;
use App\Models\TeacherBankAccount;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EarningsController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $teacherProfile = $user->teacherProfile;
        
        // Current balance
        $balance = $teacherProfile->balance ?? 0;
        
        // Stats
        $stats = [
            'total_earned' => TeacherEarning::where('teacher_id', $user->id)->sum('net_amount'),
            'this_month' => TeacherEarning::where('teacher_id', $user->id)
                ->whereMonth('created_at', now()->month)
                ->sum('net_amount'),
            'pending_payout' => TeacherPayout::where('teacher_id', $user->id)
                ->where('status', 'pending')
                ->sum('amount'),
            'total_withdrawn' => TeacherPayout::where('teacher_id', $user->id)
                ->where('status', 'completed')
                ->sum('amount'),
        ];
        
        // Recent earnings
        $recentEarnings = TeacherEarning::where('teacher_id', $user->id)
            ->with(['course', 'user'])
            ->latest()
            ->take(20)
            ->get();
        
        // Payouts
        $payouts = TeacherPayout::where('teacher_id', $user->id)
            ->with('bankAccount')
            ->latest()
            ->take(10)
            ->get();
        
        // Bank accounts for withdrawal
        $bankAccounts = TeacherBankAccount::where('teacher_id', $user->id)
            ->where('is_verified', true)
            ->get();
        
        // Monthly chart
        $monthlyChart = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = now()->subMonths($i);
            $monthlyChart[] = [
                'month' => $date->format('M Y'),
                'earnings' => TeacherEarning::where('teacher_id', $user->id)
                    ->whereMonth('created_at', $date->month)
                    ->whereYear('created_at', $date->year)
                    ->sum('net_amount'),
                'withdrawn' => TeacherPayout::where('teacher_id', $user->id)
                    ->where('status', 'completed')
                    ->whereMonth('created_at', $date->month)
                    ->whereYear('created_at', $date->year)
                    ->sum('amount'),
            ];
        }
        
        return Inertia::render('Teacher/Earnings/Index', [
            'balance' => $balance,
            'stats' => $stats,
            'recentEarnings' => $recentEarnings,
            'payouts' => $payouts,
            'bankAccounts' => $bankAccounts,
            'monthlyChart' => $monthlyChart,
            'commissionRate' => $teacherProfile->commission_rate ?? 30,
        ]);
    }
    
    public function history()
    {
        $earnings = TeacherEarning::where('teacher_id', Auth::id())
            ->with(['course', 'user'])
            ->latest()
            ->paginate(50);
        
        return Inertia::render('Teacher/Earnings/History', [
            'earnings' => $earnings,
        ]);
    }
    
    public function withdraw(Request $request)
    {
        $request->validate([
            'amount' => ['required', 'numeric', 'min:100000'], // Minimum 100,000 UZS
            'bank_account_id' => ['required', 'exists:teacher_bank_accounts,id'],
        ], [
            'amount.min' => 'Minimal yechib olish summasi 100,000 UZS',
        ]);
        
        $user = Auth::user();
        $teacherProfile = $user->teacherProfile;
        
        // Check balance
        if ($request->amount > $teacherProfile->balance) {
            return back()->with('error', 'Yetarli mablag\' mavjud emas');
        }
        
        // Check bank account ownership and verification
        $bankAccount = TeacherBankAccount::where('id', $request->bank_account_id)
            ->where('teacher_id', $user->id)
            ->where('is_verified', true)
            ->firstOrFail();
        
        // Create payout request
        TeacherPayout::create([
            'teacher_id' => $user->id,
            'bank_account_id' => $bankAccount->id,
            'amount' => $request->amount,
            'status' => 'pending',
        ]);
        
        // Deduct from balance
        $teacherProfile->decrement('balance', $request->amount);
        
        return back()->with('success', 'Yechib olish so\'rovi yuborildi. 24 soat ichida ko\'rib chiqiladi.');
    }
}
