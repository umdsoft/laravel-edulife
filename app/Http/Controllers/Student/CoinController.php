<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\CoinTransaction;
use App\Services\CoinService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CoinController extends Controller
{
    /**
     * The one-off move of users.coin_balance into student_profiles.coins
     * (2026_10_21_000006). Before it a student had two wallets: legacy helpers
     * wrote balance_after from users.coin_balance, CoinService from the profile,
     * so stored balances of older rows jump between the two sequences. Seen as
     * one wallet the move changes nothing, so it is balance-neutral below.
     */
    public const WALLET_MOVE_SOURCES = ['legacy_transfer'];

    public function index()
    {
        $user = Auth::user();
        $profile = $user->studentProfile;
        $balance = (int) ($profile->coins ?? 0);

        $ordered = fn () => CoinTransaction::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->orderByDesc('id'); // uuid7: a stable order for rows in the same second

        $transactions = $ordered()
            ->paginate(30, ['id', 'type', 'amount', 'balance_after', 'source', 'description', 'created_at']);

        // "Balans": a running balance anchored to the current profile balance, walking
        // back from the newest row, instead of the stored balance_after (out of sequence
        // on pre-migration rows). Rows on newer pages are summed first.
        $offset = ($transactions->currentPage() - 1) * $transactions->perPage();
        $newer = $offset > 0
            ? (int) DB::query()
                ->fromSub($ordered()->select(['amount', 'source'])->limit($offset), 'newer')
                ->whereNotIn('source', self::WALLET_MOVE_SOURCES)
                ->sum('amount')
            : 0;
        $running = $balance - $newer;

        $transactions->through(function (CoinTransaction $t) use (&$running) {
            $neutral = in_array($t->source, self::WALLET_MOVE_SOURCES, true);
            $t->balance_after = $running;
            $t->setAttribute('balance_neutral', $neutral);
            if (!$neutral) {
                $running -= (int) $t->amount;
            }

            return $t;
        });

        // a refund (tournament / olympiad) gives spent coins back: it is not an earning
        $history = CoinTransaction::where('user_id', $user->id);
        $refunded = (int) (clone $history)->where('amount', '>', 0)->where('source', 'like', '%refund')->sum('amount');
        // bought coin packages and the one-off wallet move are not earnings either
        $earned = (int) (clone $history)->where('amount', '>', 0)->where('source', 'not like', '%refund')
            ->whereNotIn('source', CoinService::NOT_EARNED_SOURCES)->sum('amount');
        $spent = (int) abs((clone $history)->where('amount', '<', 0)->sum('amount'));

        return Inertia::render('Student/Coins/History', [
            'balance' => $balance,
            'total_earned' => $earned,
            'total_spent' => max(0, $spent - $refunded),
            'transactions' => $transactions,
        ]);
    }
}
