<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserOlympiadHistory extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'user_olympiad_history';

    // Status constants
    public const STATUS_PARTICIPATED = 'participated';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_DISQUALIFIED = 'disqualified';
    public const STATUS_NO_SHOW = 'no_show';
    public const STATUS_ADVANCED = 'advanced';

    protected $fillable = [
        'user_id',
        'olympiad_id',
        'olympiad_type_id',
        'stage_id',
        'series_id',
        'attempt_id',
        'status',
        'rank',
        'total_participants',
        'percentile',
        'score',
        'max_score',
        'score_percent',
        'section_scores',
        'badges_earned',
        'coins_earned',
        'cash_prize',
        'certificate_issued',
        'advanced_to_next_stage',
        'next_olympiad_id',
    ];

    protected function casts(): array
    {
        return [
            'rank' => 'integer',
            'total_participants' => 'integer',
            'percentile' => 'integer',
            'score' => 'decimal:2',
            'max_score' => 'decimal:2',
            'score_percent' => 'decimal:2',
            'section_scores' => 'array',
            'badges_earned' => 'integer',
            'coins_earned' => 'integer',
            'cash_prize' => 'decimal:2',
            'certificate_issued' => 'boolean',
            'advanced_to_next_stage' => 'boolean',
        ];
    }

    // ==================== RELATIONSHIPS ====================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function olympiad(): BelongsTo
    {
        return $this->belongsTo(Olympiad::class);
    }

    public function olympiadType(): BelongsTo
    {
        return $this->belongsTo(OlympiadType::class);
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(OlympiadStage::class, 'stage_id');
    }

    public function series(): BelongsTo
    {
        return $this->belongsTo(OlympiadSeries::class);
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(OlympiadAttempt::class, 'attempt_id');
    }

    public function nextOlympiad(): BelongsTo
    {
        return $this->belongsTo(Olympiad::class, 'next_olympiad_id');
    }

    // ==================== SCOPES ====================

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeAdvanced($query)
    {
        return $query->where('status', self::STATUS_ADVANCED);
    }

    public function scopeByType($query, string $typeId)
    {
        return $query->where('olympiad_type_id', $typeId);
    }

    public function scopeByYear($query, int $year)
    {
        return $query->whereHas('olympiad', function ($q) use ($year) {
            $q->whereYear('olympiad_start_at', $year);
        });
    }

    // ==================== ACCESSORS ====================

    public function getIsWinnerAttribute(): bool
    {
        return $this->rank !== null && $this->rank <= 3;
    }

    public function getIsTopTenAttribute(): bool
    {
        return $this->rank !== null && $this->rank <= 10;
    }

    public function getTotalEarningsAttribute(): float
    {
        return $this->cash_prize + ($this->coins_earned * 100); // 1 coin = 100 UZS
    }

    public function getRankLabelAttribute(): string
    {
        if (!$this->rank) {
            return '-';
        }
        
        $suffix = ['th', 'st', 'nd', 'rd', 'th', 'th', 'th', 'th', 'th', 'th'];
        $mod = $this->rank % 100;
        
        return $this->rank . ($suffix[($mod - 20) % 10] ?? $suffix[$mod] ?? $suffix[0]);
    }

    // ==================== METHODS ====================

    /**
     * Create history record from completed attempt
     */
    public static function createFromAttempt(OlympiadAttempt $attempt): self
    {
        $olympiad = $attempt->olympiad;
        $leaderboardEntry = $attempt->leaderboardEntry;

        return self::create([
            'user_id' => $attempt->user_id,
            'olympiad_id' => $attempt->olympiad_id,
            'olympiad_type_id' => $olympiad->olympiad_type_id,
            'stage_id' => $olympiad->stage_id,
            'series_id' => $olympiad->series_id,
            'attempt_id' => $attempt->id,
            'status' => $attempt->is_disqualified 
                ? self::STATUS_DISQUALIFIED 
                : self::STATUS_COMPLETED,
            'rank' => $leaderboardEntry?->rank,
            'total_participants' => OlympiadLiveLeaderboard::where('olympiad_id', $olympiad->id)->count(),
            'percentile' => $leaderboardEntry?->rank 
                ? round((1 - ($leaderboardEntry->rank / OlympiadLiveLeaderboard::where('olympiad_id', $olympiad->id)->count())) * 100)
                : null,
            'score' => $attempt->total_weighted_score,
            'max_score' => $attempt->total_max_score,
            'score_percent' => $attempt->score_percent,
            'section_scores' => $attempt->sections_results,
        ]);
    }
}
