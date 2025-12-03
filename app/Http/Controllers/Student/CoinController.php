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
            ->with('transactionable')
            ->orderByDesc('created_at')
            ->paginate(30);
        
        return Inertia::render('Student/Coins/History', [
            'balance' => $profile->coins,
            'total_earned' => $profile->total_coins_earned,
            'transactions' => $transactions,
        ]);
    }
}
