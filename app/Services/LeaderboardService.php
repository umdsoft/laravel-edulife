<?php

namespace App\Services;

use App\Models\Olympiad;
use App\Models\OlympiadAttempt;
use App\Models\OlympiadLiveLeaderboard;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class LeaderboardService
{
    protected int $cacheSeconds = 10;

    /**
     * Get live leaderboard for olympiad
     */
    public function getLiveLeaderboard(string $olympiadId, int $limit = 100): Collection
    {
        $cacheKey = "olympiad.leaderboard.{$olympiadId}";

        return Cache::remember($cacheKey, $this->cacheSeconds, function () use ($olympiadId, $limit) {
            return OlympiadLiveLeaderboard::with(['user:id,name,avatar'])
                ->where('olympiad_id', $olympiadId)
                ->where('is_disqualified', false)
                ->orderBy('rank')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Get user's position in leaderboard
     */
    public function getUserPosition(string $olympiadId, string $userId): ?array
    {
        $entry = OlympiadLiveLeaderboard::where('olympiad_id', $olympiadId)
            ->where('user_id', $userId)
            ->first();

        if (!$entry) {
            return null;
        }

        $totalParticipants = OlympiadLiveLeaderboard::where('olympiad_id', $olympiadId)
            ->where('is_disqualified', false)
            ->count();

        return [
            'rank' => $entry->rank,
            'previous_rank' => $entry->previous_rank,
            'rank_change' => $entry->rank_change,
            'score' => $entry->weighted_score,
            'score_percent' => $entry->score_percent,
            'total_participants' => $totalParticipants,
            'percentile' => $totalParticipants > 0 
                ? round((1 - ($entry->rank / $totalParticipants)) * 100)
                : null,
        ];
    }

    /**
     * Update leaderboard entry for an attempt
     */
    public function updateEntry(OlympiadAttempt $attempt): OlympiadLiveLeaderboard
    {
        $entry = OlympiadLiveLeaderboard::updateOrCreate(
            ['olympiad_id' => $attempt->olympiad_id, 'user_id' => $attempt->user_id],
            [
                'attempt_id' => $attempt->id,
                'score' => $attempt->total_raw_score,
                'weighted_score' => $attempt->total_weighted_score,
                'max_score' => $attempt->total_max_score,
                'score_percent' => $attempt->score_percent,
                'time_spent_seconds' => $attempt->total_duration_seconds ?? 0,
                'questions_answered' => $attempt->answers()->count(),
                'questions_correct' => $attempt->answers()->where('is_correct', true)->count(),
                'section_scores' => $attempt->sections_results,
                'is_disqualified' => $attempt->is_disqualified,
            ]
        );

        // Recalculate ranks after update
        $this->recalculateRanks($attempt->olympiad_id);

        // Clear cache
        Cache::forget("olympiad.leaderboard.{$attempt->olympiad_id}");

        return $entry->fresh();
    }

    /**
     * Recalculate ranks for olympiad
     */
    public function recalculateRanks(string $olympiadId): void
    {
        OlympiadLiveLeaderboard::recalculateRanks($olympiadId);
    }

    /**
     * Get top performers summary
     */
    public function getTopPerformers(string $olympiadId, int $count = 3): Collection
    {
        return OlympiadLiveLeaderboard::with(['user:id,name,avatar', 'attempt'])
            ->where('olympiad_id', $olympiadId)
            ->where('is_disqualified', false)
            ->orderBy('rank')
            ->limit($count)
            ->get();
    }

    /**
     * Get statistics for olympiad
     */
    public function getStatistics(string $olympiadId): array
    {
        $cacheKey = "olympiad.stats.{$olympiadId}";

        return Cache::remember($cacheKey, 60, function () use ($olympiadId) {
            $entries = OlympiadLiveLeaderboard::where('olympiad_id', $olympiadId)
                ->where('is_disqualified', false);

            return [
                'total_participants' => $entries->count(),
                'average_score' => round($entries->avg('score_percent'), 2),
                'highest_score' => $entries->max('weighted_score'),
                'lowest_score' => $entries->min('weighted_score'),
                'median_score' => $this->getMedianScore($olympiadId),
                'average_time' => round($entries->avg('time_spent_seconds')),
            ];
        });
    }

    /**
     * Get median score
     */
    private function getMedianScore(string $olympiadId): float
    {
        $count = OlympiadLiveLeaderboard::where('olympiad_id', $olympiadId)
            ->where('is_disqualified', false)
            ->count();

        if ($count === 0) {
            return 0;
        }

        $middleIndex = floor($count / 2);

        $median = OlympiadLiveLeaderboard::where('olympiad_id', $olympiadId)
            ->where('is_disqualified', false)
            ->orderByDesc('weighted_score')
            ->skip($middleIndex)
            ->first();

        return $median ? $median->weighted_score : 0;
    }

    /**
     * Get nearby competitors for a user
     */
    public function getNearbyCompetitors(string $olympiadId, string $userId, int $range = 5): array
    {
        $userEntry = OlympiadLiveLeaderboard::where('olympiad_id', $olympiadId)
            ->where('user_id', $userId)
            ->first();

        if (!$userEntry) {
            return ['above' => [], 'below' => [], 'user' => null];
        }

        $above = OlympiadLiveLeaderboard::with('user:id,name,avatar')
            ->where('olympiad_id', $olympiadId)
            ->where('is_disqualified', false)
            ->where('rank', '<', $userEntry->rank)
            ->orderByDesc('rank')
            ->limit($range)
            ->get()
            ->reverse()
            ->values();

        $below = OlympiadLiveLeaderboard::with('user:id,name,avatar')
            ->where('olympiad_id', $olympiadId)
            ->where('is_disqualified', false)
            ->where('rank', '>', $userEntry->rank)
            ->orderBy('rank')
            ->limit($range)
            ->get();

        return [
            'above' => $above,
            'user' => $userEntry,
            'below' => $below,
        ];
    }

    /**
     * Get score distribution for olympiad
     */
    public function getScoreDistribution(string $olympiadId): array
    {
        $entries = OlympiadLiveLeaderboard::where('olympiad_id', $olympiadId)
            ->where('is_disqualified', false)
            ->get();

        $distribution = [
            '0-20' => 0,
            '21-40' => 0,
            '41-60' => 0,
            '61-80' => 0,
            '81-100' => 0,
        ];

        foreach ($entries as $entry) {
            $percent = $entry->score_percent;
            if ($percent <= 20) {
                $distribution['0-20']++;
            } elseif ($percent <= 40) {
                $distribution['21-40']++;
            } elseif ($percent <= 60) {
                $distribution['41-60']++;
            } elseif ($percent <= 80) {
                $distribution['61-80']++;
            } else {
                $distribution['81-100']++;
            }
        }

        return $distribution;
    }
}
