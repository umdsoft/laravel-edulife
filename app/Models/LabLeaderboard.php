<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabLeaderboard extends Model
{
    use HasFactory, HasUuids;

    public $timestamps = false;

    protected $table = 'lab_leaderboard';

    protected $fillable = [
        'user_id',
        'period_type',
        'period_start',
        'period_end',
        'labs_completed',
        'total_score',
        'avg_score',
        'total_xp',
        'perfect_scores',
        'total_time_seconds',
        'rank',
        'previous_rank',
        'rank_change',
        'best_category',
        'best_category_score',
        'user_name',
        'user_avatar',
        'user_level',
        'user_school',
        'user_region',
        'user_grade',
    ];

    protected $casts = [
        'period_start' => 'date',
        'period_end' => 'date',
        'labs_completed' => 'integer',
        'total_score' => 'integer',
        'avg_score' => 'decimal:2',
        'total_xp' => 'integer',
        'perfect_scores' => 'integer',
        'total_time_seconds' => 'integer',
        'rank' => 'integer',
        'previous_rank' => 'integer',
        'rank_change' => 'integer',
        'best_category_score' => 'decimal:2',
        'user_level' => 'integer',
        'updated_at' => 'datetime',
    ];

    // ═══════════════════════════════════════════════════════════════════════
    // CONSTANTS
    // ═══════════════════════════════════════════════════════════════════════

    public const PERIOD_TYPES = [
        'daily' => 'Kunlik',
        'weekly' => 'Haftalik',
        'monthly' => 'Oylik',
        'yearly' => 'Yillik',
        'all_time' => 'Umumiy',
    ];

    // ═══════════════════════════════════════════════════════════════════════
    // RELATIONSHIPS
    // ═══════════════════════════════════════════════════════════════════════

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // SCOPES
    // ═══════════════════════════════════════════════════════════════════════

    public function scopeByPeriod($query, string $type, ?\Carbon\Carbon $date = null)
    {
        $date = $date ?? now();
        
        return $query->where('period_type', $type)
            ->where('period_start', '<=', $date)
            ->where('period_end', '>=', $date);
    }

    public function scopeDaily($query, ?\Carbon\Carbon $date = null)
    {
        return $query->byPeriod('daily', $date);
    }

    public function scopeWeekly($query, ?\Carbon\Carbon $date = null)
    {
        return $query->byPeriod('weekly', $date);
    }

    public function scopeMonthly($query, ?\Carbon\Carbon $date = null)
    {
        return $query->byPeriod('monthly', $date);
    }

    public function scopeAllTime($query)
    {
        return $query->where('period_type', 'all_time');
    }

    public function scopeTopRanks($query, int $limit = 100)
    {
        return $query->orderBy('rank')->limit($limit);
    }

    public function scopeByRegion($query, string $region)
    {
        return $query->where('user_region', $region);
    }

    public function scopeBySchool($query, string $school)
    {
        return $query->where('user_school', $school);
    }

    public function scopeByGrade($query, string $grade)
    {
        return $query->where('user_grade', $grade);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // ACCESSORS
    // ═══════════════════════════════════════════════════════════════════════

    public function getPeriodTypeLabelAttribute(): string
    {
        return self::PERIOD_TYPES[$this->period_type] ?? $this->period_type;
    }

    public function getRankChangeTextAttribute(): string
    {
        if ($this->rank_change > 0) {
            return "+{$this->rank_change} ↑";
        } elseif ($this->rank_change < 0) {
            return "{$this->rank_change} ↓";
        }
        return '—';
    }

    public function getRankChangeColorAttribute(): string
    {
        if ($this->rank_change > 0) return '#10B981'; // Green
        if ($this->rank_change < 0) return '#EF4444'; // Red
        return '#6B7280'; // Gray
    }

    public function getTotalTimeTextAttribute(): string
    {
        $seconds = $this->total_time_seconds;
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        
        return "{$hours}s {$minutes}d";
    }

    public function getRankBadgeAttribute(): ?string
    {
        return match($this->rank) {
            1 => '🥇',
            2 => '🥈',
            3 => '🥉',
            default => null,
        };
    }

    // ═══════════════════════════════════════════════════════════════════════
    // STATIC METHODS
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Update leaderboard for a period
     */
    public static function updateForPeriod(string $periodType): void
    {
        [$periodStart, $periodEnd] = self::getPeriodDates($periodType);
        
        // Get user stats for this period
        $stats = LabAttempt::query()
            ->where('status', 'completed')
            ->when($periodType !== 'all_time', function ($q) use ($periodStart, $periodEnd) {
                $q->whereBetween('completed_at', [$periodStart, $periodEnd]);
            })
            ->select('user_id')
            ->selectRaw('COUNT(*) as labs_completed')
            ->selectRaw('SUM(raw_score) as total_score')
            ->selectRaw('AVG(percentage) as avg_score')
            ->selectRaw('SUM(xp_earned) as total_xp')
            ->selectRaw('SUM(CASE WHEN percentage >= 100 THEN 1 ELSE 0 END) as perfect_scores')
            ->selectRaw('SUM(time_spent_seconds) as total_time_seconds')
            ->groupBy('user_id')
            ->having('labs_completed', '>', 0)
            ->orderByDesc('total_xp')
            ->get();
        
        // Clear existing entries for this period
        self::where('period_type', $periodType)
            ->where('period_start', $periodStart)
            ->delete();
        
        // Insert new entries with ranks
        $rank = 0;
        foreach ($stats as $stat) {
            $rank++;
            
            $user = User::find($stat->user_id);
            if (!$user) continue;
            
            $progress = LabUserProgress::where('user_id', $stat->user_id)->first();
            
            // Find previous rank
            $previousEntry = self::where('user_id', $stat->user_id)
                ->where('period_type', $periodType)
                ->orderByDesc('period_start')
                ->first();
            
            $previousRank = $previousEntry?->rank;
            
            self::create([
                'user_id' => $stat->user_id,
                'period_type' => $periodType,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'labs_completed' => $stat->labs_completed,
                'total_score' => $stat->total_score ?? 0,
                'avg_score' => $stat->avg_score ?? 0,
                'total_xp' => $stat->total_xp ?? 0,
                'perfect_scores' => $stat->perfect_scores ?? 0,
                'total_time_seconds' => $stat->total_time_seconds ?? 0,
                'rank' => $rank,
                'previous_rank' => $previousRank,
                'rank_change' => $previousRank ? ($previousRank - $rank) : 0,
                'user_name' => $user->name,
                'user_avatar' => $user->avatar_url ?? null,
                'user_level' => $progress?->lab_level ?? 1,
                'user_school' => $user->student_profile?->school ?? null,
                'user_region' => $user->student_profile?->region ?? null,
                'user_grade' => $user->student_profile?->class ?? null,
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Get period start and end dates
     */
    protected static function getPeriodDates(string $periodType): array
    {
        $now = now();
        
        return match($periodType) {
            'daily' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'weekly' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'monthly' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'yearly' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            'all_time' => [
                \Carbon\Carbon::create(2020, 1, 1),
                \Carbon\Carbon::create(2099, 12, 31)
            ],
            default => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
        };
    }

    /**
     * Get user's rank for a period
     */
    public static function getUserRank(string $userId, string $periodType): ?int
    {
        [$periodStart, $periodEnd] = self::getPeriodDates($periodType);
        
        return self::where('user_id', $userId)
            ->where('period_type', $periodType)
            ->where('period_start', $periodStart)
            ->value('rank');
    }

    /**
     * Get top users for display
     */
    public static function getTopUsers(string $periodType, int $limit = 100): \Illuminate\Database\Eloquent\Collection
    {
        [$periodStart, $periodEnd] = self::getPeriodDates($periodType);
        
        return self::where('period_type', $periodType)
            ->where('period_start', $periodStart)
            ->orderBy('rank')
            ->limit($limit)
            ->get();
    }

    /**
     * Export for display
     */
    public function toDisplayData(): array
    {
        return [
            'rank' => $this->rank,
            'rank_badge' => $this->rank_badge,
            'rank_change' => $this->rank_change,
            'rank_change_text' => $this->rank_change_text,
            'rank_change_color' => $this->rank_change_color,
            'user' => [
                'id' => $this->user_id,
                'name' => $this->user_name,
                'avatar' => $this->user_avatar,
                'level' => $this->user_level,
                'school' => $this->user_school,
                'grade' => $this->user_grade,
            ],
            'stats' => [
                'labs_completed' => $this->labs_completed,
                'total_xp' => $this->total_xp,
                'avg_score' => round($this->avg_score, 1),
                'perfect_scores' => $this->perfect_scores,
                'total_time' => $this->total_time_text,
            ],
        ];
    }
}
