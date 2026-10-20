<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Battle;
use App\Services\BattleQuestionSelector;
use App\Services\BattleScoringSettings;
use App\Services\MatchmakingService;
use App\Services\Telegram\BattleInviteLinks;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Telegram WebView (wave 2): battle with a friend.
 *
 * POST /student/battles/challenge          create a waiting battle with an invite code
 * GET  /student/battles/challenge/{code}   the friend's page: who invites, topic, level
 * POST /student/battles/challenge/{code}   the friend accepts -> the match starts for both
 * GET  /student/battles/{battle}/state     light poll for the match page (no question data)
 *
 * The match itself is the normal BattleController flow: server clock, same questions and
 * option order for both, so a player in Telegram and one on the website play fairly.
 */
class BattleChallengeController extends Controller
{
    public function __construct(private readonly MatchmakingService $matchmaking) {}

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'direction_id' => ['nullable', 'uuid', Rule::exists('directions', 'id')->where('is_active', true)],
            'difficulty' => ['nullable', Rule::in(BattleScoringSettings::LEVELS)],
        ]);

        try {
            $battle = $this->matchmaking->createChallenge($request->user(), $data['direction_id'] ?? null, $data['difficulty'] ?? null);
        } catch (\DomainException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json([
            'battle_id' => $battle->id,
            'redirect' => route('student.battles.show', $battle),
            'invite' => $battle->status === 'waiting' && $battle->invite_code ? BattleInviteLinks::for($battle->invite_code) : null,
        ]);
    }

    public function show(Request $request, string $code): Response|RedirectResponse
    {
        $battle = $this->find($code);
        if ($battle === null) {
            return redirect()->route('student.battles.index')->with('error', "Chaqiruv topilmadi yoki eskirgan.");
        }
        $user = $request->user();
        $mine = (string) $battle->player1_id === (string) $user->id;
        if ($mine || (string) $battle->player2_id === (string) $user->id) {
            return redirect()->route('student.battles.show', $battle);
        }

        $battle->load(['player1:id,first_name,last_name,avatar', 'player1.studentProfile:id,user_id,elo_rating,level', 'direction']);
        $open = $battle->status === 'waiting' && $battle->player2_id === null && ! ($battle->expires_at && $battle->expires_at->isPast());

        return Inertia::render('Student/Battle/Challenge', [
            'challenge' => [
                'code' => $battle->invite_code,
                'open' => $open,
                'expires_at' => $battle->expires_at?->toIso8601String(),
                'challenger' => [
                    'name' => $battle->player1?->full_name,
                    'avatar' => $battle->player1?->avatar_url,
                    'elo_rating' => $battle->player1?->studentProfile?->elo_rating,
                ],
                'direction' => $battle->direction?->name_uz ?? $battle->direction?->name,
                'difficulty_label' => BattleQuestionSelector::LEVEL_LABELS[$battle->requested_difficulty] ?? null,
                'total_rounds' => (int) $battle->total_rounds,
                'time_per_question' => (int) $battle->time_per_question,
            ],
            'serverTime' => now()->toIso8601String(),
        ]);
    }

    public function accept(Request $request, string $code): RedirectResponse
    {
        if ($this->find($code) === null) {
            return redirect()->route('student.battles.index')->with('error', "Chaqiruv topilmadi yoki eskirgan.");
        }

        try {
            $battle = $this->matchmaking->acceptChallenge($code, $request->user());
        } catch (\DomainException $e) {
            return redirect()->route('student.battles.challenge.show', $code)->with('error', $e->getMessage());
        }

        return redirect()->route('student.battles.show', $battle);
    }

    /**
     * What changed since the last poll, without the question or the answers: the match page
     * reloads its full props only when this signature changes (or the round's time is up).
     * Timing stays on the server (rounds.started_at); nothing here is trusted from the client.
     */
    public function state(Request $request, Battle $battle): JsonResponse
    {
        $user = $request->user();
        $isPlayer1 = (string) $battle->player1_id === (string) $user->id;
        abort_unless($isPlayer1 || (string) $battle->player2_id === (string) $user->id, 403);

        $round = $battle->status === 'in_progress'
            ? $battle->rounds()->where('round_number', $battle->current_round)->first(['id', 'player1_answer', 'player2_answer', 'started_at', 'ended_at'])
            : null;
        $expired = $battle->status === 'waiting' && $battle->expires_at && $battle->expires_at->isPast();

        $parts = [
            $expired ? 'expired' : $battle->status,
            (int) $battle->current_round,
            $round?->id,
            $round && $round->player1_answer !== null ? 1 : 0,
            $round && $round->player2_answer !== null ? 1 : 0,
            (int) $battle->player1_score,
            (int) $battle->player2_score,
            $battle->player2_id ? 1 : 0,
        ];

        return response()->json([
            // the match page builds the same string from its props (Match.vue localSig)
            'v' => implode('|', array_map(fn ($p) => (string) ($p ?? ''), $parts)),
            'status' => $expired ? 'expired' : $battle->status,
            'current_round' => (int) $battle->current_round,
            'server_time' => now()->toIso8601String(),
        ]);
    }

    private function find(string $code): ?Battle
    {
        return preg_match('/^[A-Za-z0-9]{8}$/', $code) ? Battle::query()->where('invite_code', $code)->first() : null;
    }
}
