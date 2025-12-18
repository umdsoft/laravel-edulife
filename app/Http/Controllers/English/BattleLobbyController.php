<?php

namespace App\Http\Controllers\English;

use App\Http\Controllers\Controller;
use App\Services\English\BattleService;
use App\Services\English\LevelService;
use App\Models\English\EnglishBattle;
use App\Models\English\UserEnglishProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class BattleLobbyController extends Controller
{
    private const BATTLE_CREATE_COST = 30;

    public function __construct(
        private BattleService $battleService,
        private LevelService $levelService
    ) {}

    /**
     * Show battle lobby page
     */
    public function lobby(Request $request): Response
    {
        $user = $request->user();
        $profile = $this->levelService->getOrCreateProfile($user);

        // Get battle history with full details (last 20 battles)
        $battleHistory = $this->battleService->getBattleHistory($user, 20);

        // Format battle history for frontend with full details
        $formattedHistory = $battleHistory->map(function ($battle) use ($user) {
            $isPlayer1 = $battle->player1_id === $user->id;
            $opponent = $isPlayer1 ? $battle->player2 : $battle->player1;
            $won = $battle->winner_id === $user->id;
            $isDraw = $battle->result === 'draw';
            $isAIBattle = $battle->battle_type === 'practice' ||
                ($battle->settings['opponent_type'] ?? null) === 'ai';

            $eloChange = 0;
            if (!$isAIBattle && $battle->player1_elo_after && $battle->player1_elo_before) {
                $eloChange = $isPlayer1
                    ? ($battle->player1_elo_after - $battle->player1_elo_before)
                    : (($battle->player2_elo_after ?? 0) - ($battle->player2_elo_before ?? 0));
            }

            // Calculate coins earned
            $coinsEarned = 0;
            if (!$isAIBattle) {
                if ($won) {
                    $coinsEarned = $battle->winner_coins ?? 25;
                } elseif (!$isDraw) {
                    $coinsEarned = $battle->loser_coins ?? 5;
                } else {
                    $coinsEarned = 10;
                }
            }

            // Calculate XP earned
            $xpEarned = 0;
            if ($won) {
                $xpEarned = $battle->winner_xp ?? ($isAIBattle ? 30 : 50);
            } elseif ($isDraw) {
                $xpEarned = $isAIBattle ? 15 : 25;
            } else {
                $xpEarned = $battle->loser_xp ?? ($isAIBattle ? 10 : 15);
            }

            return [
                'id' => $battle->id,
                'opponent_name' => $isAIBattle ? 'AI Bot' : ($opponent ? $this->getUserName($opponent) : 'Unknown'),
                'opponent_avatar' => $isAIBattle ? null : $opponent?->avatar,
                'won' => $won,
                'is_draw' => $isDraw,
                'is_ai_battle' => $isAIBattle,
                'player_score' => $isPlayer1 ? $battle->player1_score : $battle->player2_score,
                'opponent_score' => $isPlayer1 ? $battle->player2_score : $battle->player1_score,
                'player_correct' => $isPlayer1 ? $battle->player1_correct : $battle->player2_correct,
                'total_rounds' => $battle->settings['rounds'] ?? 10,
                'elo_change' => $eloChange,
                'elo_before' => $isPlayer1 ? $battle->player1_elo_before : ($battle->player2_elo_before ?? 1000),
                'elo_after' => $isPlayer1 ? $battle->player1_elo_after : ($battle->player2_elo_after ?? 1000),
                'coins_earned' => $coinsEarned,
                'xp_earned' => $xpEarned,
                'battle_type' => $battle->battle_type,
                'difficulty' => $battle->settings['difficulty'] ?? 'mixed',
                'completed_at' => $battle->completed_at?->diffForHumans(),
                'completed_at_date' => $battle->completed_at?->format('d.m.Y H:i'),
            ];
        });

        // Calculate stats from battle history (includes AI battles)
        $wins = $formattedHistory->filter(fn($b) => $b['won'])->count();
        $losses = $formattedHistory->filter(fn($b) => !$b['won'] && !$b['is_draw'])->count();
        $draws = $formattedHistory->filter(fn($b) => $b['is_draw'])->count();
        $totalBattles = $formattedHistory->count();
        $winRate = $totalBattles > 0 ? round(($wins / $totalBattles) * 100) : 0;

        // Get ELO tier
        $eloRating = $profile->elo_rating ?? 1000;
        $eloTier = $this->getEloTier($eloRating);

        // Get active battles waiting for opponents
        $activeBattles = EnglishBattle::where('status', 'waiting')
            ->where('player1_id', '!=', $user->id)
            ->with('player1')
            ->orderByDesc('created_at')
            ->limit(5)
            ->get()
            ->map(function ($battle) {
                return [
                    'id' => $battle->id,
                    'name' => $battle->name ?? 'Battle #' . substr($battle->id, 0, 8),
                    'code' => $battle->code,
                    'mode' => $battle->battle_type,
                    'players_count' => 1,
                    'host_name' => $battle->player1 ? $this->getUserName($battle->player1) : 'Unknown',
                    'host_elo' => $battle->player1_elo_before,
                    'created_at' => $battle->created_at->diffForHumans(),
                ];
            });

        // Get daily challenge
        $dailyChallenge = $this->getDailyChallenge($profile);

        // Simulate online users (in real app, use Redis/cache)
        $onlineUsers = rand(80, 200);

        // Get coins from main StudentProfile for consistency with header
        $studentProfile = $user->studentProfile;
        $mainCoins = $studentProfile ? $studentProfile->coins : ($profile->coins ?? 0);

        return Inertia::render('English/BattleLobby', [
            'profile' => [
                'id' => $user->id,
                'name' => $this->getUserName($user),
                'avatar' => $user->avatar,
                'elo_rating' => $eloRating,
                'elo_tier' => $eloTier,
                'coins' => $mainCoins,
                'english_coins' => $profile->coins ?? 0,
                'current_streak' => $profile->win_streak ?? 0,
                'best_streak' => $profile->best_win_streak ?? 0,
            ],
            'battleHistory' => $formattedHistory,
            'stats' => [
                'wins' => $wins,
                'losses' => $losses,
                'total' => $totalBattles,
                'win_rate' => $winRate,
                'current_streak' => $profile->win_streak ?? 0,
            ],
            'leaderboard' => $this->getTopPlayers(5),
            'activeBattles' => $activeBattles,
            'onlineUsers' => $onlineUsers,
            'dailyChallenge' => $dailyChallenge,
        ]);
    }

    /**
     * Show battle arena page
     */
    public function arena(Request $request, string $battleId): Response
    {
        $user = $request->user();
        $battle = EnglishBattle::with(['player1', 'player2', 'rounds'])
            ->findOrFail($battleId);

        // Check if user is part of this battle
        if ($battle->player1_id !== $user->id && $battle->player2_id !== $user->id) {
            // If battle is waiting, allow joining
            if ($battle->status === 'waiting') {
                return $this->showWaitingRoom($battle, $user);
            }
            abort(403, 'You are not part of this battle');
        }

        $isPlayer1 = $battle->player1_id === $user->id;
        $opponent = $isPlayer1 ? $battle->player2 : $battle->player1;

        // Get current round
        $currentRound = $battle->rounds()
            ->whereIn('status', ['active', 'pending'])
            ->orderBy('round_number')
            ->first();

        // Format player data
        $playerData = [
            'id' => $user->id,
            'name' => $this->getUserName($user),
            'avatar' => $user->avatar,
            'score' => $isPlayer1 ? $battle->player1_score : $battle->player2_score,
            'correct' => $isPlayer1 ? $battle->player1_correct : $battle->player2_correct,
        ];

        // For AI battles (practice mode with no player2), provide AI opponent data
        $isAIBattle = $battle->battle_type === 'practice' && !$opponent;

        $opponentData = $opponent ? [
            'id' => $opponent->id,
            'name' => $this->getUserName($opponent),
            'avatar' => $opponent->avatar,
            'score' => $isPlayer1 ? $battle->player2_score : $battle->player1_score,
            'correct' => $isPlayer1 ? $battle->player2_correct : $battle->player1_correct,
        ] : ($isAIBattle ? [
            'id' => 'ai',
            'name' => 'AI Bot',
            'avatar' => null,
            'score' => 0,
            'correct' => 0,
            'is_ai' => true,
        ] : null);

        return Inertia::render('English/BattleArena', [
            'battle' => [
                'id' => $battle->id,
                'code' => $battle->code,
                'name' => $battle->name,
                'status' => $battle->status,
                'battle_type' => $battle->battle_type,
                'settings' => $battle->settings,
                'total_rounds' => $battle->settings['rounds'] ?? 10,
                'current_round_number' => $currentRound?->round_number ?? 1,
                'started_at' => $battle->started_at,
            ],
            'currentRound' => $currentRound ? [
                'id' => $currentRound->id,
                'round_number' => $currentRound->round_number,
                'question' => $currentRound->question_data,
                'status' => $currentRound->status,
                'started_at' => $currentRound->started_at,
            ] : null,
            'player' => $playerData,
            'opponent' => $opponentData,
            'isPlayer1' => $isPlayer1,
        ]);
    }

    /**
     * Show waiting room for battle
     */
    private function showWaitingRoom(EnglishBattle $battle, $user): Response
    {
        $host = $battle->player1;

        return Inertia::render('English/BattleWaiting', [
            'battle' => [
                'id' => $battle->id,
                'code' => $battle->code,
                'name' => $battle->name ?? 'Battle #' . substr($battle->id, 0, 8),
                'status' => $battle->status,
                'battle_type' => $battle->battle_type,
                'settings' => $battle->settings,
                'created_at' => $battle->created_at->diffForHumans(),
            ],
            'host' => [
                'id' => $host->id,
                'name' => $this->getUserName($host),
                'avatar' => $host->avatar,
                'elo_rating' => $battle->player1_elo_before,
            ],
            'canJoin' => true,
        ]);
    }

    /**
     * Get user full name
     */
    private function getUserName($user): string
    {
        return trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) ?: 'Player';
    }

    /**
     * Get ELO tier based on rating
     */
    private function getEloTier(int $elo): string
    {
        return match(true) {
            $elo >= 1800 => 'master',
            $elo >= 1600 => 'diamond',
            $elo >= 1400 => 'platinum',
            $elo >= 1200 => 'gold',
            $elo >= 1000 => 'silver',
            default => 'bronze',
        };
    }

    /**
     * Get top players for leaderboard
     */
    private function getTopPlayers(int $limit = 5): array
    {
        $topProfiles = UserEnglishProfile::with('user')
            ->where('battles_played', '>', 0)
            ->orderByDesc('elo_rating')
            ->limit($limit)
            ->get();

        return $topProfiles->map(function ($profile, $index) {
            $user = $profile->user;
            return [
                'rank' => $index + 1,
                'id' => $user?->id,
                'name' => $user ? $this->getUserName($user) : 'Unknown',
                'avatar' => $user?->avatar,
                'elo_rating' => $profile->elo_rating,
                'elo_tier' => $this->getEloTier($profile->elo_rating),
                'wins' => $profile->battles_won ?? 0,
                'win_rate' => $profile->battles_played > 0
                    ? round(($profile->battles_won / $profile->battles_played) * 100)
                    : 0,
            ];
        })->toArray();
    }

    /**
     * Get daily challenge data
     */
    private function getDailyChallenge($profile): ?array
    {
        // Simple daily challenge: Win 3 battles
        $todayWins = EnglishBattle::where('winner_id', $profile->user_id)
            ->whereDate('completed_at', today())
            ->count();

        return [
            'id' => 'daily_wins',
            'description' => 'Bugun 3 ta battle yuting',
            'current' => min($todayWins, 3),
            'target' => 3,
            'reward' => 50,
            'completed' => $todayWins >= 3,
        ];
    }
}
