<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Tournament;
use App\Models\TournamentParticipant;
use Illuminate\Support\Facades\Auth;
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
        $profile = $user->studentProfile;
        
        // Check if registration is open
        if (!$tournament->isRegistrationOpen()) {
            return response()->json(['success' => false, 'message' => 'Registration closed'], 400);
        }
        
        // Check level requirement
        if ($profile->level < $tournament->min_level) {
            return response()->json(['success' => false, 'message' => 'Level too low'], 400);
        }
        
        // Check if already registered
        if (TournamentParticipant::where('tournament_id', $tournament->id)
            ->where('user_id', $user->id)
            ->exists()) {
            return response()->json(['success' => false, 'message' => 'Already registered'], 400);
        }
        
        // Deduct entry fee if applicable
        if ($tournament->entry_fee > 0) {
            if ($profile->coins < $tournament->entry_fee) {
                return response()->json(['success' => false, 'message' => 'Not enough coins'], 400);
            }
            $profile->decrement('coins', $tournament->entry_fee);
        }
        
        // Register
        TournamentParticipant::create([
            'tournament_id' => $tournament->id,
            'user_id' => $user->id,
            'registered_at' => now(),
        ]);
        
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
        
        // Refund entry fee
        if ($tournament->entry_fee > 0) {
            $user->studentProfile->increment('coins', $tournament->entry_fee);
        }
        
        $participant->delete();
        
        return response()->json(['success' => true]);
    }
}
