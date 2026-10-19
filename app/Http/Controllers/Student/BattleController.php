<?php

namespace App\Http\Controllers\Student;

use App\Events\BattleEnded;
use App\Events\BattleRoundResult;
use App\Http\Controllers\Controller;
use App\Models\Battle;
use App\Models\BattleRound;
use App\Models\Direction;
use App\Models\User;
use App\Services\AchievementService;
use App\Services\CoinService;
use App\Services\EloRatingService;
use App\Services\MatchmakingService;
use App\Services\MissionService;
use App\Services\XPRewardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class BattleController extends Controller
{
    /** Coins for the winner of a battle. */

    /** Extra seconds after a round's limit before an unanswered round is closed. */
    private const GRACE_SECONDS = 2;

    public function __construct(
        protected MatchmakingService $matchmakingService,
        protected EloRatingService $eloService,
        protected AchievementService $achievementService,
        protected MissionService $missionService,
        protected XPRewardService $xpService,
        protected CoinService $coinService,
    ) {}

    public function index()
    {
        $user = Auth::user();
        $profile = $user->studentProfile ?? $user->studentProfile()->create([]);

        $this->matchmakingService->expireStaleSearches();

        $activeBattle = Battle::where(fn ($q) => $q->where('player1_id', $user->id)->orWhere('player2_id', $user->id))
            ->whereIn('status', ['waiting', 'in_progress'])
            ->first();

        $recentBattles = Battle::where(fn ($q) => $q->where('player1_id', $user->id)->orWhere('player2_id', $user->id))
            ->where('status', 'completed')
            ->with(['player1:id,first_name,last_name,avatar', 'player2:id,first_name,last_name,avatar', 'winner:id,first_name,last_name'])
            ->orderByDesc('ended_at')
            ->limit(10)
            ->get();

        $stats = [
            'elo_rating' => $profile->elo_rating,
            'rank' => $profile->rank,
            'battles_won' => $profile->battles_won,
            'battles_lost' => $profile->battles_lost,
            'battles_draw' => $profile->battles_draw,
            'win_rate' => $this->calculateWinRate($profile),
        ];

        return Inertia::render('Student/Battle/Index', [
            'activeBattle' => $activeBattle?->load(['player1:id,first_name,last_name,avatar', 'player2:id,first_name,last_name,avatar']),
            'recentBattles' => $recentBattles,
            'stats' => $stats,
            'directions' => Direction::active()->ordered()->withCount('courses')->get(),
            'questionsAvailable' => $this->matchmakingService->availableQuestions(),
        ]);
    }

    public function findMatch(Request $request)
    {
        $request->validate([
            'direction_id' => ['nullable', 'uuid', Rule::exists('directions', 'id')->where('is_active', true)],
        ]);

        try {
            $battle = $this->matchmakingService->startSearch(Auth::user(), $request->direction_id);
        } catch (\DomainException $e) {
            // our own Uzbek explanation; anything else (e.g. a database error) is not shown raw
            return response()->json(['match_found' => false, 'message' => $e->getMessage()], 422);
        }

        if ($battle->status === 'in_progress') {
            return response()->json([
                'match_found' => true,
                'battle' => $battle->load(['player1:id,first_name,last_name,avatar', 'player2:id,first_name,last_name,avatar']),
                'redirect' => route('student.battles.show', $battle),
            ]);
        }

        return response()->json([
            'match_found' => false,
            'battle_id' => $battle->id,
            'message' => 'Raqib qidirilmoqda...',
        ]);
    }

    public function cancelSearch()
    {
        $this->matchmakingService->cancelSearch(Auth::user());

        return response()->json(['success' => true]);
    }

    /**
     * The match page. It polls itself (partial reloads) for the opponent's
     * moves, so it works without a websocket server.
     */
    public function show(Battle $battle)
    {
        $user = Auth::user();
        $isPlayer1 = (string) $battle->player1_id === (string) $user->id;
        if (! $isPlayer1 && (string) $battle->player2_id !== (string) $user->id) {
            abort(403);
        }

        if ($battle->status === 'waiting' && $battle->expires_at && $battle->expires_at->isPast()) {
            $battle->update(['status' => 'expired']);
        }

        if ($battle->status === 'in_progress') {
            $this->closeTimedOutRound($battle);
            $battle->refresh();
        }

        $finished = ! in_array($battle->status, ['waiting', 'in_progress'], true);

        $battle->load([
            'player1:id,first_name,last_name,avatar', 'player1.studentProfile:id,user_id,elo_rating,level',
            'player2:id,first_name,last_name,avatar', 'player2.studentProfile:id,user_id,elo_rating,level',
            'direction',
            // resolved rounds only while playing: the open round would expose the opponent's answer
            'rounds' => fn ($q) => $q->when(! $finished, fn ($r) => $r->where('round_number', '<', (int) $battle->current_round))
                ->with('question:id,question_text')
                ->orderBy('round_number'),
        ]);

        $currentRound = null;
        if ($battle->status === 'in_progress') {
            $round = $battle->rounds()->with('question.options')->where('round_number', $battle->current_round)->first();
            $currentRound = $round ? $this->presentCurrentRound($battle, $round, $isPlayer1) : null;
        }

        return Inertia::render('Student/Battle/Match', [
            'battle' => $this->presentBattle($battle, $finished),
            'currentRound' => $currentRound,
            'isPlayer1' => $isPlayer1,
            'serverTime' => now()->toIso8601String(),
        ]);
    }

    public function submitAnswer(Request $request, Battle $battle)
    {
        $user = Auth::user();

        $request->validate([
            'answer' => ['required'],
            'time_taken' => ['required', 'integer', 'min:0'],
        ]);

        $isPlayer1 = (string) $battle->player1_id === (string) $user->id;
        $isPlayer2 = (string) $battle->player2_id === (string) $user->id;

        if (! $isPlayer1 && ! $isPlayer2) {
            abort(403);
        }

        if ($battle->status !== 'in_progress') {
            return response()->json(['error' => 'Jang yakunlangan'], 400);
        }

        $answerField = $isPlayer1 ? 'player1_answer' : 'player2_answer';

        // Lock the round so both players' answers are serialized and the
        // round is resolved exactly once
        $isCorrect = DB::transaction(function () use ($battle, $request, $isPlayer1, $answerField) {
            $round = $battle->rounds()
                ->where('round_number', $battle->current_round)
                ->lockForUpdate()
                ->first();

            if (! $round || $round->$answerField !== null) {
                return null;
            }

            $correctAnswer = $round->question->options->where('is_correct', true)->first();
            $isCorrect = (string) $request->answer === (string) $correctAnswer?->id;

            // Prefer server-side timing; never trust a client value beyond the round limit
            $limit = (int) ($battle->time_per_question ?: PHP_INT_MAX);
            $timeTaken = $round->started_at
                ? (int) $round->started_at->diffInSeconds(now())
                : (int) $request->time_taken;
            $timeTaken = min(max($timeTaken, 0), $limit);

            $round->update([
                $answerField => $request->answer,
                ($isPlayer1 ? 'player1_time' : 'player2_time') => $timeTaken,
                ($isPlayer1 ? 'player1_correct' : 'player2_correct') => $isCorrect,
            ]);

            if ($round->player1_answer !== null && $round->player2_answer !== null) {
                $this->processRoundResult($battle->fresh(), $round);
            }

            return $isCorrect;
        });

        if ($isCorrect === null) {
            return response()->json(['error' => 'Bu savolga allaqachon javob bergansiz'], 400);
        }

        return response()->json([
            'success' => true,
            'is_correct' => $isCorrect,
        ]);
    }

    public function history()
    {
        $user = Auth::user();

        $battles = Battle::where(fn ($q) => $q->where('player1_id', $user->id)->orWhere('player2_id', $user->id))
            ->where('status', 'completed')
            ->with(['player1:id,first_name,last_name,avatar', 'player2:id,first_name,last_name,avatar', 'direction'])
            ->orderByDesc('ended_at')
            ->paginate(20)
            ->through(function (Battle $b) use ($user) {
                $mine = (string) $b->player1_id === (string) $user->id;
                $opponent = $mine ? $b->player2 : $b->player1;

                return [
                    'id' => $b->id,
                    'opponent' => $opponent ? ['id' => $opponent->id, 'name' => $opponent->full_name, 'avatar' => $opponent->avatar] : null,
                    'direction' => $b->direction?->name_uz ?? $b->direction?->name,
                    'my_score' => $mine ? $b->player1_score : $b->player2_score,
                    'opponent_score' => $mine ? $b->player2_score : $b->player1_score,
                    'result' => $b->is_draw ? 'draw' : ((string) $b->winner_id === (string) $user->id ? 'win' : 'loss'),
                    'elo_change' => $mine ? $b->player1_elo_change : $b->player2_elo_change,
                    'ended_at' => $b->ended_at?->toIso8601String(),
                ];
            });

        $profile = $user->studentProfile()->first();

        return Inertia::render('Student/Battle/History', [
            'battles' => $battles,
            'stats' => [
                'elo_rating' => $profile?->elo_rating ?? 1000,
                'battles_won' => $profile?->battles_won ?? 0,
                'battles_lost' => $profile?->battles_lost ?? 0,
                'battles_draw' => $profile?->battles_draw ?? 0,
                'win_rate' => $profile ? $this->calculateWinRate($profile) : 0,
            ],
        ]);
    }

    /**
     * When the time for the open round is up, missing answers count as wrong
     * so a player who left cannot freeze the battle.
     */
    private function closeTimedOutRound(Battle $battle): void
    {
        DB::transaction(function () use ($battle) {
            $round = $battle->rounds()->where('round_number', $battle->current_round)->lockForUpdate()->first();
            if (! $round || ! $round->started_at || $round->ended_at) {
                return;
            }

            $limit = (int) ($battle->time_per_question ?: 20);
            if ($round->started_at->copy()->addSeconds($limit + self::GRACE_SECONDS)->isFuture()) {
                return;
            }

            foreach (['player1', 'player2'] as $player) {
                if ($round->{"{$player}_answer"} === null) {
                    $round->{"{$player}_answer"} = 'timeout';
                    $round->{"{$player}_correct"} = false;
                    $round->{"{$player}_time"} = $limit;
                }
            }
            $round->save();

            $this->processRoundResult($battle->fresh(), $round);
        });
    }

    private function processRoundResult(Battle $battle, BattleRound $round): void
    {
        $player1Points = 0;
        $player2Points = 0;
        $roundWinnerId = null;
        $isDraw = false;

        if ($round->player1_correct && ! $round->player2_correct) {
            $player1Points = 1;
            $roundWinnerId = $battle->player1_id;
        } elseif (! $round->player1_correct && $round->player2_correct) {
            $player2Points = 1;
            $roundWinnerId = $battle->player2_id;
        } elseif ($round->player1_correct && $round->player2_correct) {
            // both correct: the faster one wins the round
            if ($round->player1_time < $round->player2_time) {
                $player1Points = 1;
                $roundWinnerId = $battle->player1_id;
            } elseif ($round->player2_time < $round->player1_time) {
                $player2Points = 1;
                $roundWinnerId = $battle->player2_id;
            } else {
                $isDraw = true;
            }
        } else {
            $isDraw = true;
        }

        $round->update([
            'player1_points' => $player1Points,
            'player2_points' => $player2Points,
            'round_winner_id' => $roundWinnerId,
            'is_draw' => $isDraw,
            'ended_at' => now(),
        ]);

        $battle->increment('player1_score', $player1Points);
        $battle->increment('player2_score', $player2Points);

        rescue(fn () => broadcast(new BattleRoundResult($battle, $round))->toOthers(), report: false);

        if ($battle->current_round >= $battle->total_rounds) {
            $this->endBattle($battle->fresh());

            return;
        }

        $battle->increment('current_round');
        // the next question opens now
        $battle->rounds()->where('round_number', $battle->current_round)->update(['started_at' => now()]);
    }

    private function endBattle(Battle $battle): void
    {
        $winnerId = null;
        $isDraw = false;

        if ($battle->player1_score > $battle->player2_score) {
            $winnerId = $battle->player1_id;
        } elseif ($battle->player2_score > $battle->player1_score) {
            $winnerId = $battle->player2_id;
        } else {
            $isDraw = true;
        }

        $battle->winner_id = $winnerId;
        $battle->is_draw = $isDraw;
        $eloChanges = $this->eloService->calculateNewRatings($battle);

        $player1 = $battle->player1;
        $player2 = $battle->player2;
        $player1Profile = $player1->studentProfile ?? $player1->studentProfile()->create([]);
        $player2Profile = $player2->studentProfile ?? $player2->studentProfile()->create([]);

        $player1Profile->update(['elo_rating' => $eloChanges['player1']['new_rating']]);
        $player2Profile->update(['elo_rating' => $eloChanges['player2']['new_rating']]);

        $this->eloService->updateRank($player1);
        $this->eloService->updateRank($player2);

        if ($isDraw) {
            $player1Profile->increment('battles_draw');
            $player2Profile->increment('battles_draw');
        } elseif ((string) $winnerId === (string) $battle->player1_id) {
            $player1Profile->increment('battles_won');
            $player2Profile->increment('battles_lost');
        } else {
            $player1Profile->increment('battles_lost');
            $player2Profile->increment('battles_won');
        }

        $player1Result = (string) $winnerId === (string) $battle->player1_id ? 'win' : ($isDraw ? 'participate' : 'lose');
        $player2Result = (string) $winnerId === (string) $battle->player2_id ? 'win' : ($isDraw ? 'participate' : 'lose');
        $xp1 = $this->xpService->awardBattleXP($player1, $player1Result);
        $xp2 = $this->xpService->awardBattleXP($player2, $player2Result);

        // the winner's coins ("Battle g'alabasi uchun coin"); the monthly limit can lower them
        $winner = $winnerId ? ((string) $winnerId === (string) $player1->id ? $player1 : $player2) : null;
        $coinReward = $winner ? app(\App\Services\CoinRewardService::class)->awardBattleWinCoins($winner, $battle) : 0;
        $battle->update([
            'status' => 'completed',
            'winner_id' => $winnerId,
            'is_draw' => $isDraw,
            'player1_elo_change' => $eloChanges['player1']['change'],
            'player2_elo_change' => $eloChanges['player2']['change'],
            'xp_reward' => max($xp1, $xp2),
            'coin_reward' => $coinReward,
            'ended_at' => now(),
        ]);

        if ($winner) {
            $this->achievementService->checkAchievements($winner, 'battles_won', []);
            $this->missionService->updateProgress($winner, 'battle_win', 1);
        }

        foreach ([$player1, $player2] as $player) {
            $this->achievementService->checkAchievements($player, 'battles_played', []);
            $this->missionService->updateProgress($player, 'battle_complete', 1);
        }

        rescue(fn () => broadcast(new BattleEnded($battle))->toOthers(), report: false);
    }

    private function presentPlayer(?User $user): ?array
    {
        return $user ? [
            'id' => $user->id,
            'full_name' => $user->full_name,
            'avatar' => $user->avatar,
            'elo_rating' => $user->studentProfile?->elo_rating,
            'level' => $user->studentProfile?->level,
        ] : null;
    }

    private function presentBattle(Battle $battle, bool $finished): array
    {
        return [
            'id' => $battle->id,
            'status' => $battle->status,
            'total_rounds' => (int) $battle->total_rounds,
            'current_round' => (int) $battle->current_round,
            'time_per_question' => (int) ($battle->time_per_question ?: 20),
            'player1_score' => (int) $battle->player1_score,
            'player2_score' => (int) $battle->player2_score,
            'player1' => $this->presentPlayer($battle->player1),
            'player2' => $this->presentPlayer($battle->player2),
            'direction' => $battle->direction ? ['id' => $battle->direction->id, 'name' => $battle->direction->name_uz ?? $battle->direction->name] : null,
            'winner_id' => $battle->winner_id,
            'is_draw' => (bool) $battle->is_draw,
            'player1_elo_change' => (int) $battle->player1_elo_change,
            'player2_elo_change' => (int) $battle->player2_elo_change,
            'xp_reward' => (int) $battle->xp_reward,
            'coin_reward' => (int) $battle->coin_reward,
            'expires_at' => $battle->expires_at?->toIso8601String(),
            'started_at' => $battle->started_at?->toIso8601String(),
            'rounds' => $battle->rounds->map(fn (BattleRound $r) => [
                'round_number' => $r->round_number,
                'question_text' => $finished ? $r->question?->question_text : null,
                'player1_correct' => (bool) $r->player1_correct,
                'player2_correct' => (bool) $r->player2_correct,
                'player1_time' => $r->player1_time,
                'player2_time' => $r->player2_time,
                'round_winner_id' => $r->round_winner_id,
                'is_draw' => (bool) $r->is_draw,
            ])->values(),
        ];
    }

    /**
     * The open round without anything that gives the answer away.
     */
    private function presentCurrentRound(Battle $battle, BattleRound $round, bool $isPlayer1): array
    {
        $mine = $isPlayer1 ? 'player1' : 'player2';
        $theirs = $isPlayer1 ? 'player2' : 'player1';
        $limit = (int) ($battle->time_per_question ?: 20);

        return [
            'id' => $round->id,
            'round_number' => $round->round_number,
            'starts_at' => $round->started_at?->toIso8601String(),
            'ends_at' => $round->started_at?->copy()->addSeconds($limit)->toIso8601String(),
            'question' => [
                'id' => $round->question->id,
                'type' => $round->question->type,
                'question_text' => $round->question->question_text,
                'options' => $round->question->options->shuffle()->map(fn ($o) => [
                    'id' => $o->id,
                    'content' => $o->option_text,
                ])->values(),
            ],
            'answered' => $round->{"{$mine}_answer"} !== null,
            'my_correct' => $round->{"{$mine}_answer"} !== null ? (bool) $round->{"{$mine}_correct"} : null,
            'opponent_answered' => $round->{"{$theirs}_answer"} !== null,
        ];
    }

    private function calculateWinRate($profile): float
    {
        $total = $profile->battles_won + $profile->battles_lost + $profile->battles_draw;
        if ($total === 0) {
            return 0;
        }

        return round(($profile->battles_won / $total) * 100, 1);
    }
}
