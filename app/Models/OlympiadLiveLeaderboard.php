<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OlympiadLiveLeaderboard extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'olympiad_live_leaderboard';

    protected $fillable = [
        'olympiad_id',
        'user_id',
        'attempt_id',
        'rank',
        'previous_rank',
        'rank_change',
        'score',
        'weighted_score',
        'max_score',
        'score_percent',
        'time_spent_seconds',
        'questions_answered',
        'questions_correct',
        'section_scores',
        'is_disqualified',
    ];

    protected function casts(): array
    {
        return [
            'rank' => 'integer',
            'previous_rank' => 'integer',
            'rank_change' => 'integer',
            'score' => 'decimal:2',
            'weighted_score' => 'decimal:2',
            'max_score' => 'decimal:2',
            'score_percent' => 'decimal:2',
            'time_spent_seconds' => 'integer',
            'questions_answered' => 'integer',
            'questions_correct' => 'integer',
            'section_scores' => 'array',
            'is_disqualified' => 'boolean',
        ];
    }

    // ==================== RELATIONSHIPS ====================

    public function olympiad(): BelongsTo
    {
        return $this->belongsTo(Olympiad::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(OlympiadAttempt::class, 'attempt_id');
    }

    // ==================== SCOPES ====================

    public function scopeActive($query)
    {
        return $query->where('is_disqualified', false);
    }

    public function scopeTopN($query, int $n)
    {
        return $query->orderBy('rank')->limit($n);
    }

    public function scopeForOlympiad($query, string $olympiadId)
    {
        return $query->where('olympiad_id', $olympiadId);
    }

    // ==================== ACCESSORS ====================

    public function getRankMovementAttribute(): string
    {
        if ($this->rank_change > 0) {
            return '+' . $this->rank_change;
        } elseif ($this->rank_change < 0) {
            return (string) $this->rank_change;
        }
        return '-';
    }

    public function getRankMovementColorAttribute(): string
    {
        if ($this->rank_change > 0) {
            return 'green';
        } elseif ($this->rank_change < 0) {
            return 'red';
        }
        return 'gray';
    }

    public function getFormattedTimeAttribute(): string
    {
        $hours = floor($this->time_spent_seconds / 3600);
        $minutes = floor(($this->time_spent_seconds % 3600) / 60);
        $seconds = $this->time_spent_seconds % 60;
        
        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
        }
        return sprintf('%02d:%02d', $minutes, $seconds);
    }

    public function getAccuracyPercentAttribute(): float
    {
        return $this->questions_answered > 0 
            ? ($this->questions_correct / $this->questions_answered) * 100 
            : 0;
    }

    // ==================== STATIC METHODS ====================

    /**
     * Rank the olympiad's participants: higher score_percent first (the weighted
     * percentage, see OlympiadAttempt::calculateScores), then less time spent.
     * Equal score and time share a rank. The attempt's rank and percentile are
     * kept in step so results, history, CSV and certificates agree.
     */
    public static function recalculateRanks(string $olympiadId): void
    {
        $entries = self::where('olympiad_id', $olympiadId)
            ->where('is_disqualified', false)
            ->orderByDesc('score_percent')
            ->orderBy('time_spent_seconds')
            ->get();

        $total = $entries->count();
        $rank = 0;
        $previousScore = null;
        $previousTime = null;

        foreach ($entries as $index => $entry) {
            $score = round((float) $entry->score_percent, 2);
            if ($score !== $previousScore || $entry->time_spent_seconds !== $previousTime) {
                $rank = $index + 1;
            }

            $previousRank = (int) $entry->rank;
            $entry->previous_rank = $previousRank ?: null;
            $entry->rank = $rank;
            $entry->rank_change = $previousRank ? ($previousRank - $rank) : 0;
            $entry->save();

            OlympiadAttempt::whereKey($entry->attempt_id)->update([
                'rank' => $rank,
                'percentile' => self::percentileFor($rank, $total),
            ]);

            $previousScore = $score;
            $previousTime = $entry->time_spent_seconds;
        }

        // disqualified participants have no place
        $disqualified = self::where('olympiad_id', $olympiadId)->where('is_disqualified', true);
        OlympiadAttempt::whereIn('id', (clone $disqualified)->pluck('attempt_id'))->update(['rank' => null, 'percentile' => null]);
        $disqualified->update(['rank' => 0]);
    }

    /**
     * Share of participants ranked below this place (rank 1 of 4 = 75).
     */
    public static function percentileFor(int $rank, int $total): ?int
    {
        return $rank > 0 && $total > 0 ? (int) round((1 - ($rank / $total)) * 100) : null;
    }

    /**
     * What the student pages show as "Top X%": the share of participants at
     * or above this place, rounded up (1 of 2 = 50, 2 of 2 = 100, 1 of 200 = 1).
     * The results page and the leaderboard both use this one value.
     */
    public static function topPercentFor(int $rank, int $total): ?int
    {
        return $rank > 0 && $total > 0 ? max(1, min(100, (int) ceil($rank / $total * 100))) : null;
    }

    public static function getTopPerformers(string $olympiadId, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return self::with(['user:id,first_name,last_name,avatar'])
            ->where('olympiad_id', $olympiadId)
            ->where('is_disqualified', false)
            ->orderBy('rank')
            ->limit($limit)
            ->get();
    }
}
