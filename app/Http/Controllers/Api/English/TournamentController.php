<?php

namespace App\Http\Controllers\Api\English;

use App\Http\Controllers\Controller;
use App\Models\English\EnglishTournament;
use App\Models\English\EnglishTournamentParticipant;
use App\Services\English\LevelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class TournamentController extends Controller
{
    public function __construct(
        private LevelService $levelService
    ) {
    }

    /**
     * Get available tournaments
     */
    public function index(Request $request): JsonResponse
    {
        $tournaments = EnglishTournament::with(['participants' => fn($q) => $q->limit(10)])
            ->where('status', '!=', 'cancelled')
            ->where(function ($q) {
                $q->where('registration_end', '>=', now())
                    ->orWhere('status', 'in_progress');
            })
            ->orderBy('registration_end')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $tournaments,
        ]);
    }

    /**
     * Get tournament details
     */
    public function show(Request $request, string $tournamentId): JsonResponse
    {
        $tournament = EnglishTournament::with([
            'participants.user',
            'rounds.battles',
        ])->findOrFail($tournamentId);

        $userParticipant = $tournament->participants
            ->where('user_id', $request->user()->id)
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'tournament' => $tournament,
                'user_participation' => $userParticipant,
            ],
        ]);
    }

    /**
     * Register for tournament
     */
    public function register(Request $request, string $tournamentId): JsonResponse
    {
        $tournament = EnglishTournament::where('id', $tournamentId)
            ->where('status', 'registration')
            ->where('registration_end', '>=', now())
            ->firstOrFail();

        $existingParticipant = EnglishTournamentParticipant::where('tournament_id', $tournamentId)
            ->where('user_id', $request->user()->id)
            ->first();

        if ($existingParticipant) {
            return response()->json([
                'success' => false,
                'message' => 'Already registered',
            ], 400);
        }

        $currentParticipants = $tournament->participants()->count();
        if ($currentParticipants >= $tournament->max_participants) {
            return response()->json([
                'success' => false,
                'message' => 'Tournament is full',
            ], 400);
        }

        $profile = $this->levelService->getOrCreateProfile($request->user());

        if ($tournament->entry_fee > 0 && $profile->coins < $tournament->entry_fee) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient coins',
            ], 400);
        }

        if ($tournament->entry_fee > 0) {
            $profile->coins -= $tournament->entry_fee;
            $profile->save();
        }

        $participant = EnglishTournamentParticipant::create([
            'id' => Str::uuid(),
            'tournament_id' => $tournamentId,
            'user_id' => $request->user()->id,
            'registration_elo' => $profile->elo_rating,
            'status' => 'registered',
        ]);

        $tournament->increment('current_participants');

        return response()->json([
            'success' => true,
            'data' => $participant,
        ]);
    }

    /**
     * Get user's tournament history
     */
    public function history(Request $request): JsonResponse
    {
        $participations = EnglishTournamentParticipant::with('tournament')
            ->where('user_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $participations,
        ]);
    }
}
