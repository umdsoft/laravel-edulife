<?php

namespace App\Services\English;

use App\Models\English\EnglishLeaderboard;
use App\Models\English\EnglishLeaderboardEntry;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Carbon\Carbon;

class LeaderboardService
{
    public function __construct(
        private LevelService $levelService
    ) {
    }

    /**
     * Get leaderboard rankings
     */
    public function getLeaderboard(
        string $boardType,
        string $period = 'weekly',
        int $limit = 100
    ): Collection {
        $leaderboard = EnglishLeaderboard::where('board_type', $boardType)->first();

        if (!$leaderboard) {
            return collect();
        }

        $periodDate = $this->getPeriodDate($period);

        return EnglishLeaderboardEntry::with(['user' => fn($q) => $q->select('id', 'name', 'avatar')])
            ->where('leaderboard_id', $leaderboard->id)
            ->where('period_type', $period)
            ->where('period_date', $periodDate)
            ->orderByDesc('score')
            ->orderBy('last_updated_at')
            ->limit($limit)
            ->get()
            ->map(function ($entry, $index) {
                $entry->rank = $index + 1;
                return $entry;
            });
    }

    /**
     * Get user's rank on a leaderboard
     */
    public function getUserRank(User $user, string $boardType, string $period = 'weekly'): array
    {
        $leaderboard = EnglishLeaderboard::where('board_type', $boardType)->first();

        if (!$leaderboard) {
            return ['rank' => null, 'score' => 0];
        }

        $periodDate = $this->getPeriodDate($period);

        $userEntry = EnglishLeaderboardEntry::where('leaderboard_id', $leaderboard->id)
            ->where('user_id', $user->id)
            ->where('period_type', $period)
            ->where('period_date', $periodDate)
            ->first();

        if (!$userEntry) {
            return ['rank' => null, 'score' => 0, 'total_participants' => $this->getTotalParticipants($leaderboard, $period, $periodDate)];
        }

        $rank = EnglishLeaderboardEntry::where('leaderboard_id', $leaderboard->id)
            ->where('period_type', $period)
            ->where('period_date', $periodDate)
            ->where('score', '>', $userEntry->score)
            ->count() + 1;

        return [
            'rank' => $rank,
            'score' => $userEntry->score,
            'change_from_previous' => $userEntry->rank_change ?? 0,
            'total_participants' => $this->getTotalParticipants($leaderboard, $period, $periodDate),
        ];
    }

    /**
     * Update user's score on a leaderboard
     */
    public function updateScore(User $user, string $boardType, int $scoreChange): EnglishLeaderboardEntry
    {
        $leaderboard = EnglishLeaderboard::where('board_type', $boardType)->first();

        if (!$leaderboard) {
            throw new \Exception("Leaderboard type '{$boardType}' not found");
        }

        foreach (['daily', 'weekly', 'monthly', 'all_time'] as $period) {
            $periodDate = $this->getPeriodDate($period);

            $entry = EnglishLeaderboardEntry::firstOrCreate(
                [
                    'leaderboard_id' => $leaderboard->id,
                    'user_id' => $user->id,
                    'period_type' => $period,
                    'period_date' => $periodDate,
                ],
                [
                    'id' => Str::uuid(),
                    'score' => 0,
                    'previous_rank' => null,
                    'rank_change' => 0,
                ]
            );

            $entry->score += $scoreChange;
            $entry->last_updated_at = now();
            $entry->save();
        }

        return $entry;
    }

    /**
     * Update XP leaderboard when user earns XP
     */
    public function updateXpLeaderboard(User $user, int $xp): void
    {
        $this->updateScore($user, 'xp', $xp);
    }

    /**
     * Update battle leaderboard after battle
     */
    public function updateBattleLeaderboard(User $user, bool $won): void
    {
        if ($won) {
            $this->updateScore($user, 'battle_wins', 1);
        }
    }

    /**
     * Update streak leaderboard
     */
    public function updateStreakLeaderboard(User $user, int $streak): void
    {
        $profile = $this->levelService->getOrCreateProfile($user);

        $leaderboard = EnglishLeaderboard::where('board_type', 'streak')->first();
        if (!$leaderboard)
            return;

        foreach (['weekly', 'monthly', 'all_time'] as $period) {
            $periodDate = $this->getPeriodDate($period);

            EnglishLeaderboardEntry::updateOrCreate(
                [
                    'leaderboard_id' => $leaderboard->id,
                    'user_id' => $user->id,
                    'period_type' => $period,
                    'period_date' => $periodDate,
                ],
                [
                    'id' => Str::uuid(),
                    'score' => $streak,
                    'last_updated_at' => now(),
                ]
            );
        }
    }

    /**
     * Get users around current user
     */
    public function getUsersAround(User $user, string $boardType, string $period = 'weekly', int $range = 5): Collection
    {
        $leaderboard = EnglishLeaderboard::where('board_type', $boardType)->first();
        if (!$leaderboard)
            return collect();

        $periodDate = $this->getPeriodDate($period);

        $userEntry = EnglishLeaderboardEntry::where('leaderboard_id', $leaderboard->id)
            ->where('user_id', $user->id)
            ->where('period_type', $period)
            ->where('period_date', $periodDate)
            ->first();

        if (!$userEntry)
            return collect();

        $userRank = EnglishLeaderboardEntry::where('leaderboard_id', $leaderboard->id)
            ->where('period_type', $period)
            ->where('period_date', $periodDate)
            ->where('score', '>', $userEntry->score)
            ->count() + 1;

        $startRank = max(1, $userRank - $range);
        $endRank = $userRank + $range;

        return EnglishLeaderboardEntry::with(['user' => fn($q) => $q->select('id', 'name', 'avatar')])
            ->where('leaderboard_id', $leaderboard->id)
            ->where('period_type', $period)
            ->where('period_date', $periodDate)
            ->orderByDesc('score')
            ->orderBy('last_updated_at')
            ->skip($startRank - 1)
            ->take(($endRank - $startRank) + 1)
            ->get()
            ->map(function ($entry, $index) use ($startRank) {
                $entry->rank = $startRank + $index;
                return $entry;
            });
    }

    private function getPeriodDate(string $period): string
    {
        return match ($period) {
            'daily' => now()->format('Y-m-d'),
            'weekly' => now()->startOfWeek()->format('Y-m-d'),
            'monthly' => now()->startOfMonth()->format('Y-m-d'),
            'all_time' => '2020-01-01',
            default => now()->startOfWeek()->format('Y-m-d'),
        };
    }

    private function getTotalParticipants(EnglishLeaderboard $leaderboard, string $period, string $periodDate): int
    {
        return EnglishLeaderboardEntry::where('leaderboard_id', $leaderboard->id)
            ->where('period_type', $period)
            ->where('period_date', $periodDate)
            ->count();
    }

    /**
     * Reset daily leaderboards (run via scheduler)
     */
    public function resetDailyLeaderboards(): void
    {
        EnglishLeaderboardEntry::where('period_type', 'daily')
            ->where('period_date', '<', now()->format('Y-m-d'))
            ->delete();
    }
}
