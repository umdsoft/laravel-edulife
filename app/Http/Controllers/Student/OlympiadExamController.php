<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Olympiad;
use App\Models\OlympiadAttempt;
use App\Models\OlympiadRegistration;
use App\Services\AntiCheatService;
use App\Services\LeaderboardService;
use App\Services\OlympiadExamService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

use App\Services\XPRewardService;

class OlympiadExamController extends Controller
{
    protected OlympiadExamService $examService;
    protected AntiCheatService $antiCheatService;
    protected LeaderboardService $leaderboardService;
    protected XPRewardService $xpService;

    public function __construct(
        OlympiadExamService $examService,
        AntiCheatService $antiCheatService,
        LeaderboardService $leaderboardService,
        XPRewardService $xpService
    ) {
        $this->examService = $examService;
        $this->antiCheatService = $antiCheatService;
        $this->leaderboardService = $leaderboardService;
        $this->xpService = $xpService;
    }

    // ... (existing methods)

    /**
     * Submit entire exam
     */
    public function submit(Request $request, string $slug)
    {
        $olympiad = Olympiad::where('slug', $slug)->firstOrFail();

        $attempt = OlympiadAttempt::where('olympiad_id', $olympiad->id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        if ($request->header('X-Session-Token') !== $attempt->session_token) {
            return response()->json(['error' => 'Invalid session'], 401);
        }

        try {
            $this->examService->submitExam($attempt);
            
            // Award participation XP
            $this->xpService->awardOlympiadXP(auth()->user(), 'participation');

            return response()->json([
                'success' => true,
                'redirect' => route('student.olympiads.results', $slug),
            ]);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Report violation (AJAX)
     */
    public function reportViolation(Request $request, string $slug)
    {
        $olympiad = Olympiad::where('slug', $slug)->firstOrFail();

        $attempt = OlympiadAttempt::where('olympiad_id', $olympiad->id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$attempt) {
            return response()->json(['error' => 'Attempt not found'], 404);
        }

        $request->validate([
            'type' => 'required|string',
            'details' => 'nullable|array',
        ]);

        $attempt->recordViolation($request->type);

        return response()->json([
            'warnings_count' => $attempt->fresh()->warnings_count,
            'is_disqualified' => $attempt->is_disqualified,
        ]);
    }

    /**
     * Heartbeat (AJAX) - keep session alive
     */
    public function heartbeat(Request $request, string $slug)
    {
        $olympiad = Olympiad::where('slug', $slug)->firstOrFail();

        $attempt = OlympiadAttempt::where('olympiad_id', $olympiad->id)
            ->where('user_id', auth()->id())
            ->first();

        if (!$attempt || $request->header('X-Session-Token') !== $attempt->session_token) {
            return response()->json(['error' => 'Invalid session'], 401);
        }

        // Check heartbeat gap
        $lastHeartbeat = $request->input('last_heartbeat', now()->timestamp);
        $this->antiCheatService->checkHeartbeat($attempt, $lastHeartbeat);

        return response()->json([
            'status' => $attempt->status,
            'remaining_seconds' => $attempt->remaining_seconds,
            'is_disqualified' => $attempt->is_disqualified,
            'server_time' => now()->timestamp,
        ]);
    }

    /**
     * Get live leaderboard (AJAX)
     */
    public function leaderboard(string $slug)
    {
        $olympiad = Olympiad::where('slug', $slug)->firstOrFail();

        $leaderboard = $this->leaderboardService->getLiveLeaderboard($olympiad->id, 20);
        $userPosition = $this->leaderboardService->getUserPosition($olympiad->id, auth()->id());

        return response()->json([
            'leaderboard' => $leaderboard->map(fn($e) => [
                'rank' => $e->rank,
                'rank_change' => $e->rank_change,
                'user_name' => $e->user->name,
                'user_avatar' => $e->user->avatar,
                'score' => $e->weighted_score,
                'score_percent' => $e->score_percent,
            ]),
            'user_position' => $userPosition,
        ]);
    }
}
