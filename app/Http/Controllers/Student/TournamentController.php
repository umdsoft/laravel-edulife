<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Tournament;
use App\Models\TournamentParticipant;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class TournamentController extends Controller
{
    public function index()
    {
        $tournaments = Tournament::whereIn('status', ['upcoming', 'registration', 'in_progress'])
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
    
    public function register(Tournament $tournament)
    {
        $user = Auth::user();
        $profile = $user->studentProfile ?? $user->studentProfile()->create([]);
        
        // Check if registration is open
        if (!$tournament->isRegistrationOpen()) {
            return response()->json(['success' => false, 'message' => 'Registration closed'], 400);
        }
        
        // Check level requirement
        if (($profile->level ?? 1) < $tournament->min_level) {
            return response()->json(['success' => false, 'message' => 'Level too low'], 400);
        }
        
        // Check if already registered
        if (TournamentParticipant::where('tournament_id', $tournament->id)
            ->where('user_id', $user->id)
            ->exists()) {
            return response()->json(['success' => false, 'message' => 'Already registered'], 400);
        }
        
        try {
            DB::transaction(function () use ($tournament, $user, $profile) {
                // Insert first: the unique (tournament_id, user_id) index rejects a concurrent duplicate
                TournamentParticipant::create([
                    'tournament_id' => $tournament->id,
                    'user_id' => $user->id,
                    'registered_at' => now(),
                ]);

                // Deduct entry fee with a guarded decrement (balance can't go negative)
                if ($tournament->entry_fee > 0) {
                    $charged = $profile->newQuery()
                        ->whereKey($profile->id)
                        ->where('coins', '>=', $tournament->entry_fee)
                        ->decrement('coins', $tournament->entry_fee);

                    if ($charged === 0) {
                        throw new \DomainException('Not enough coins');
                    }
                }
            });
        } catch (UniqueConstraintViolationException) {
            return response()->json(['success' => false, 'message' => 'Already registered'], 400);
        } catch (\DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }

        return response()->json(['success' => true]);
    }
    
    public function withdraw(Tournament $tournament)
    {
        $user = Auth::user();
        
        $participant = TournamentParticipant::where('tournament_id', $tournament->id)
            ->where('user_id', $user->id)
            ->where('status', 'registered')
            ->first();
        
        if (!$participant) {
            return response()->json(['success' => false, 'message' => 'Not registered'], 400);
        }
        
        DB::transaction(function () use ($tournament, $user, $participant) {
            // Only the request that actually removes the row refunds the fee
            $deleted = TournamentParticipant::whereKey($participant->id)
                ->where('status', 'registered')
                ->delete();

            if ($deleted === 1 && $tournament->entry_fee > 0) {
                $user->studentProfile->increment('coins', $tournament->entry_fee);
            }
        });

        return response()->json(['success' => true]);
    }
}
