<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\CoinTransaction;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class CoinController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $profile = $user->studentProfile;
        
        $transactions = CoinTransaction::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate(30, ['id', 'type', 'amount', 'balance_after', 'source', 'description', 'created_at']);

        return Inertia::render('Student/Coins/History', [
            'balance' => (int) ($profile->coins ?? 0),
            'total_earned' => (int) ($profile->total_coins_earned ?? 0),
            'total_spent' => (int) abs(CoinTransaction::where('user_id', $user->id)->where('amount', '<', 0)->sum('amount')),
            'transactions' => $transactions,
        ]);
    }
}
