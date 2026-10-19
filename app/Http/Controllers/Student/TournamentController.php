<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Tournament;
use App\Models\TournamentParticipant;
use App\Services\CoinService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class TournamentController extends Controller
{
    public function index()
    {
        $tournaments = Tournament::whereIn('status', ['registration', 'in_progress'])
            ->with('direction')
            ->withCount('participants')
            ->orderBy('starts_at')
            ->get();
        
        return Inertia::render('Student/Tournaments/Index', [
            'tournaments' => $tournaments,
        ]);
    }
    
    public function show(Tournament $tournament)
    {
        $tournament->load(['direction', 'participants.user', 'matches']);
        
        return Inertia::render('Student/Tournaments/Show', [
            'tournament' => $tournament,
        ]);
    }
    
    public function register(Tournament $tournament, CoinService $coins)
    {
        $user = Auth::user();
        $profile = $user->studentProfile ?? $user->studentProfile()->create([]);

        if (! $tournament->isRegistrationOpen()) {
            return response()->json(['success' => false, 'message' => "Ro'yxatdan o'tish yopiq"], 400);
        }

        // the table has an ELO bracket (min_elo / max_elo), not a level requirement
        if ($reason = $tournament->eligibilityError($profile->elo_rating)) {
            return response()->json(['success' => false, 'message' => $reason], 400);
        }

        if (TournamentParticipant::where('tournament_id', $tournament->id)->where('user_id', $user->id)->exists()) {
            return response()->json(['success' => false, 'message' => "Siz allaqachon ro'yxatdan o'tgansiz"], 400);
        }

        $fee = $tournament->entry_fee;

        try {
            DB::transaction(function () use ($tournament, $user, $coins, $fee) {
                // Insert first: the unique (tournament_id, user_id) index rejects a concurrent duplicate
                TournamentParticipant::create([
                    'tournament_id' => $tournament->id,
                    'user_id' => $user->id,
                    'registered_at' => now(),
                ]);

                // entry fee (entry_fee_coins): guarded and logged in the coin history
                if ($fee > 0 && ! $coins->spendCoins($user, $fee, 'tournament', "Turnirga kirish: {$tournament->name}", $tournament)) {
                    throw new \DomainException("Coin yetarli emas: {$fee} coin kerak");
                }
            });
        } catch (UniqueConstraintViolationException) {
            return response()->json(['success' => false, 'message' => "Siz allaqachon ro'yxatdan o'tgansiz"], 400);
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }

        return response()->json(['success' => true, 'message' => "Turnirga ro'yxatdan o'tdingiz"]);
    }

    public function withdraw(Tournament $tournament, CoinService $coins)
    {
        $user = Auth::user();

        $participant = TournamentParticipant::where('tournament_id', $tournament->id)
            ->where('user_id', $user->id)
            ->where('status', 'registered')
            ->first();

        if (! $participant) {
            return response()->json(['success' => false, 'message' => "Siz bu turnirda ro'yxatdan o'tmagansiz"], 400);
        }

        // once the bracket is drawn a seat cannot be given back
        if (! in_array($tournament->status, ['upcoming', 'registration'], true)) {
            return response()->json(['success' => false, 'message' => "Turnir boshlangan — endi chiqib bo'lmaydi"], 400);
        }

        DB::transaction(function () use ($tournament, $user, $participant, $coins) {
            // Only the request that actually removes the row refunds the fee
            $deleted = TournamentParticipant::whereKey($participant->id)
                ->where('status', 'registered')
                ->delete();

            if ($deleted === 1 && $tournament->entry_fee > 0) {
                $coins->addCoins($user, $tournament->entry_fee, 'tournament_refund', "Turnirdan chiqish: {$tournament->name}", $tournament);
            }
        });

        return response()->json([
            'success' => true,
            'refunded' => $tournament->entry_fee,
            'message' => $tournament->entry_fee > 0
                ? "Turnirdan chiqdingiz, {$tournament->entry_fee} coin qaytarildi"
                : 'Turnirdan chiqdingiz',
        ]);
    }
}
