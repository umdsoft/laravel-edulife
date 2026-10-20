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

        // a refund (tournament / olympiad) gives spent coins back: it is not an earning
        $history = CoinTransaction::where('user_id', $user->id);
        $refunded = (int) (clone $history)->where('amount', '>', 0)->where('source', 'like', '%refund')->sum('amount');
        $earned = (int) (clone $history)->where('amount', '>', 0)->where('source', 'not like', '%refund')->sum('amount');
        $spent = (int) abs((clone $history)->where('amount', '<', 0)->sum('amount'));

        return Inertia::render('Student/Coins/History', [
            'balance' => (int) ($profile->coins ?? 0),
            'total_earned' => $earned,
            'total_spent' => max(0, $spent - $refunded),
            'transactions' => $transactions,
        ]);
    }
}
