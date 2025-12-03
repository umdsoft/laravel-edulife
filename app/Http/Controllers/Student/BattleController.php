<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Battle;
use App\Models\BattleRound;
use App\Models\Direction;
use App\Services\MatchmakingService;
use App\Services\EloRatingService;
use App\Services\AchievementService;
use App\Services\MissionService;
use App\Events\BattleRoundResult;
use App\Events\BattleEnded;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class BattleController extends Controller
{
    public function __construct(
        protected MatchmakingService $matchmakingService,
        protected EloRatingService $eloService,
        protected AchievementService $achievementService,
        protected MissionService $missionService
    ) {}
    
    public function index()
    {
        $user = Auth::user();
        $profile = $user->studentProfile;
        
        // Check for active battle
        $activeBattle = Battle::where(function ($q) use ($user) {
            $q->where('player1_id', $user->id)
              ->orWhere('player2_id', $user->id);
        })->whereIn('status', ['waiting', 'in_progress'])->first();
        
        // Recent battles
        $recentBattles = Battle::where(function ($q) use ($user) {
            $q->where('player1_id', $user->id)
              ->orWhere('player2_id', $user->id);
        })
            ->where('status', 'completed')
            ->with(['player1', 'player2', 'winner'])
            ->orderByDesc('ended_at')
            ->limit(10)
            ->get();
        
        // Stats
        $stats = [
            'elo_rating' => $profile->elo_rating,
            'rank' => $profile->rank,
            'battles_won' => $profile->battles_won,
            'battles_lost' => $profile->battles_lost,
            'battles_draw' => $profile->battles_draw,
            'win_rate' => $this->calculateWinRate($profile),
        ];
        
        // Directions for topic selection
        $directions = Direction::withCount('courses')->get();
        
        return Inertia::render('Student/Battle/Index', [
            'activeBattle' => $activeBattle?->load(['player1', 'player2']),
            'recentBattles' => $recentBattles,
            'stats' => $stats,
            'directions' => $directions,
        ]);
    }
    
    public function findMatch(Request $request)
    {
        $request->validate([
            'direction_id' => ['nullable', 'uuid', 'exists:directions,id'],
        ]);
        
        $user = Auth::user();
        
        $battle = $this->matchmakingService->startSearch($user, $request->direction_id);
        
        if ($battle->status === 'in_progress') {
            return response()->json([
                'match_found' => true,
                'battle' => $battle->load(['player1', 'player2']),
                'redirect' => route('student.battle.show', $battle),
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
        $user = Auth::user();
        $this->matchmakingService->cancelSearch($user);
        
        return response()->json(['success' => true]);
    }
    
    public function show(Battle $battle)
    {
        $user = Auth::user();
        
        // Verify user is participant
        if ($battle->player1_id !== $user->id && $battle->player2_id !== $user->id) {
            abort(403);
        }
        
        if ($battle->status === 'completed') {
            return redirect()->route('student.battle.history');
        }
        
        $battle->load(['player1.studentProfile', 'player2.studentProfile', 'rounds.question.options', 'direction']);
        
        // Get current round
        $currentRound = $battle->rounds()->where('round_number', $battle->current_round)->first();
        
        // Hide correct answers
        if ($currentRound && $currentRound->question) {
            $currentRound->question->options = $currentRound->question->options->map(function ($option) {
                return [
                    'id' => $option->id,
                    'content' => $option->content,
                ];
            });
        }
        
        return Inertia::render('Student/Battle/Match', [
            'battle' => $battle,
            'currentRound' => $currentRound,
            'isPlayer1' => $battle->player1_id === $user->id,
        ]);
    }
    
    public function submitAnswer(Request $request, Battle $battle)
    {
        $user = Auth::user();
        
        // Validate
        $request->validate([
            'answer' => ['required'],
            'time_taken' => ['required', 'integer', 'min:0'],
        ]);
        
        // Verify user is participant
        $isPlayer1 = $battle->player1_id === $user->id;
        $isPlayer2 = $battle->player2_id === $user->id;
        
        if (!$isPlayer1 && !$isPlayer2) {
            abort(403);
        }
        
        // Get current round
        $round = $battle->rounds()->where('round_number', $battle->current_round)->first();
        
        // Check if already answered
        $answerField = $isPlayer1 ? 'player1_answer' : 'player2_answer';
        if ($round->$answerField !== null) {
            return response()->json(['error' => 'Already answered'], 400);
        }
        
        // Evaluate answer
        $question = $round->question;
        $correctAnswer = $question->options->where('is_correct', true)->first();
        $isCorrect = $request->answer === $correctAnswer?->id;
        
        // Save answer
        $timeField = $isPlayer1 ? 'player1_time' : 'player2_time';
        $correctField = $isPlayer1 ? 'player1_correct' : 'player2_correct';
        
        $round->update([
            $answerField => $request->answer,
            $timeField => $request->time_taken,
            $correctField => $isCorrect,
        ]);
        
        // Check if both answered
        if ($round->player1_answer !== null && $round->player2_answer !== null) {
            $this->processRoundResult($battle, $round);
        }
        
        return response()->json([
            'success' => true,
            'is_correct' => $isCorrect,
        ]);
    }
    
    private function processRoundResult(Battle $battle, BattleRound $round): void
    {
        // Determine round winner
        $player1Points = 0;
        $player2Points = 0;
        $roundWinnerId = null;
        
        if ($round->player1_correct && !$round->player2_correct) {
            $player1Points = 1;
            $roundWinnerId = $battle->player1_id;
        } elseif (!$round->player1_correct && $round->player2_correct) {
            $player2Points = 1;
            $roundWinnerId = $battle->player2_id;
        } elseif ($round->player1_correct && $round->player2_correct) {
            // Both correct - faster wins
            if ($round->player1_time < $round->player2_time) {
                $player1Points = 1;
                $roundWinnerId = $battle->player1_id;
            } elseif ($round->player2_time < $round->player1_time) {
                $player2Points = 1;
                $roundWinnerId = $battle->player2_id;
            } else {
                $round->is_draw = true;
            }
        }
        
        $round->update([
            'player1_points' => $player1Points,
            'player2_points' => $player2Points,
            'round_winner_id' => $roundWinnerId,
            'ended_at' => now(),
        ]);
        
        // Update battle scores
        $battle->increment('player1_score', $player1Points);
        $battle->increment('player2_score', $player2Points);
        
        // Broadcast round result
        broadcast(new BattleRoundResult($battle, $round))->toOthers();
        
        // Check if battle ended
        if ($battle->current_round >= $battle->total_rounds) {
            $this->endBattle($battle);
        } else {
            // Next round
            $battle->increment('current_round');
        }
    }
    
    private function endBattle(Battle $battle): void
    {
        // Determine winner
        $winnerId = null;
        $isDraw = false;
        
        if ($battle->player1_score > $battle->player2_score) {
            $winnerId = $battle->player1_id;
        } elseif ($battle->player2_score > $battle->player1_score) {
            $winnerId = $battle->player2_id;
        } else {
            $isDraw = true;
        }
        
        // Calculate ELO changes
        $battle->winner_id = $winnerId;
        $battle->is_draw = $isDraw;
        $eloChanges = $this->eloService->calculateNewRatings($battle);
        
        // Update player profiles
        $player1Profile = $battle->player1->studentProfile;
        $player2Profile = $battle->player2->studentProfile;
        
        $player1Profile->update([
            'elo_rating' => $eloChanges['player1']['new_rating'],
        ]);
        $player2Profile->update([
            'elo_rating' => $eloChanges['player2']['new_rating'],
        ]);
        
        // Update ranks
        $this->eloService->updateRank($battle->player1);
        $this->eloService->updateRank($battle->player2);
        
        // Update win/loss counts
        if ($isDraw) {
            $player1Profile->increment('battles_draw');
            $player2Profile->increment('battles_draw');
        } elseif ($winnerId === $battle->player1_id) {
            $player1Profile->increment('battles_won');
            $player2Profile->increment('battles_lost');
        } else {
            $player1Profile->increment('battles_lost');
            $player2Profile->increment('battles_won');
        }
        
        // Calculate rewards
        $xpReward = 20;
        $coinReward = 10;
        
        if ($winnerId) {
            $xpReward += 30;
            $coinReward += 15;
        }
        
        $battle->update([
            'status' => 'completed',
            'player1_elo_change' => $eloChanges['player1']['change'],
            'player2_elo_change' => $eloChanges['player2']['change'],
            'xp_reward' => $xpReward,
            'coin_reward' => $coinReward,
            'ended_at' => now(),
        ]);
        
        // Award XP and coins
        $player1Profile->addXp($winnerId === $battle->player1_id ? $xpReward : 20);
        $player2Profile->addXp($winnerId === $battle->player2_id ? $xpReward : 20);
        
        if ($winnerId) {
            ($winnerId === $battle->player1_id ? $player1Profile : $player2Profile)->addCoins($coinReward);
        }
        
        // Check achievements
        $this->achievementService->checkAchievements($battle->player1, 'battles_played', []);
        $this->achievementService->checkAchievements($battle->player2, 'battles_played', []);
        
        if ($winnerId) {
            $winner = $winnerId === $battle->player1_id ? $battle->player1 : $battle->player2;
            $this->achievementService->checkAchievements($winner, 'battles_won', []);
        }
        
        // Update mission progress
        $this->missionService->updateProgress($battle->player1, 'battle_complete', 1);
        $this->missionService->updateProgress($battle->player2, 'battle_complete', 1);
        
        if ($winnerId) {
            $winner = $winnerId === $battle->player1_id ? $battle->player1 : $battle->player2;
            $this->missionService->updateProgress($winner, 'battle_win', 1);
        }
        
        // Broadcast battle ended
        broadcast(new BattleEnded($battle))->toOthers();
    }
    
    public function history()
    {
        $user = Auth::user();
        
        $battles = Battle::where(function ($q) use ($user) {
            $q->where('player1_id', $user->id)
              ->orWhere('player2_id', $user->id);
        })
            ->where('status', 'completed')
            ->with(['player1.studentProfile', 'player2.studentProfile', 'winner', 'direction'])
            ->orderByDesc('ended_at')
            ->paginate(20);
        
        return Inertia::render('Student/Battle/History', [
            'battles' => $battles,
        ]);
    }
    
    private function calculateWinRate($profile): float
    {
        $total = $profile->battles_won + $profile->battles_lost;
        if ($total === 0) return 0;
        return round(($profile->battles_won / $total) * 100, 1);
    }
}
