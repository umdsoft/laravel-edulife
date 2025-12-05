<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class OlympiadAttempt extends Model
{
    use HasFactory, HasUuids;

    // Status constants
    public const STATUS_NOT_STARTED = 'not_started';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_GRADING = 'grading';
    public const STATUS_GRADED = 'graded';
    public const STATUS_DISQUALIFIED = 'disqualified';
    public const STATUS_EXPIRED = 'expired';
    public const STATUS_ABANDONED = 'abandoned';

    protected $fillable = [
        'olympiad_id',
        'user_id',
        'registration_id',
        'session_token',
        'started_at',
        'completed_at',
        'total_duration_seconds',
        'current_section_id',
        'current_question_index',
        'total_raw_score',
        'total_weighted_score',
        'total_max_score',
        'score_percent',
        'sections_results',
        'device_lock_id',
        'tab_switches',
        'fullscreen_exits',
        'warnings_count',
        'is_disqualified',
        'disqualified_reason',
        'disqualified_at',
        'answers_snapshot',
        'rank',
        'percentile',
        'requires_manual_grading',
        'manual_grading_complete',
        'graded_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'disqualified_at' => 'datetime',
            'graded_at' => 'datetime',
            'total_duration_seconds' => 'integer',
            'current_question_index' => 'integer',
            'total_raw_score' => 'decimal:2',
            'total_weighted_score' => 'decimal:2',
            'total_max_score' => 'decimal:2',
            'score_percent' => 'decimal:2',
            'sections_results' => 'array',
            'tab_switches' => 'integer',
            'fullscreen_exits' => 'integer',
            'warnings_count' => 'integer',
            'is_disqualified' => 'boolean',
            'answers_snapshot' => 'array',
            'rank' => 'integer',
            'percentile' => 'integer',
            'requires_manual_grading' => 'boolean',
            'manual_grading_complete' => 'boolean',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($attempt) {
            if (empty($attempt->session_token)) {
                $attempt->session_token = Str::random(64);
            }
        });
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

    public function registration(): BelongsTo
    {
        return $this->belongsTo(OlympiadRegistration::class, 'registration_id');
    }

    public function currentSection(): BelongsTo
    {
        return $this->belongsTo(OlympiadSection::class, 'current_section_id');
    }

    public function sectionAttempts(): HasMany
    {
        return $this->hasMany(SectionAttempt::class, 'attempt_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(OlympiadAnswer::class, 'attempt_id');
    }

    public function codingSubmissions(): HasMany
    {
        return $this->hasMany(CodingSubmission::class, 'attempt_id');
    }

    public function deviceLock(): HasOne
    {
        return $this->hasOne(OlympiadDeviceLock::class, 'attempt_id');
    }

    public function violations(): HasMany
    {
        return $this->hasMany(SecurityViolation::class, 'attempt_id');
    }

    public function leaderboardEntry(): HasOne
    {
        return $this->hasOne(OlympiadLiveLeaderboard::class, 'attempt_id');
    }

    // ==================== SCOPES ====================

    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    public function scopeCompleted($query)
    {
        return $query->whereIn('status', [self::STATUS_SUBMITTED, self::STATUS_GRADED]);
    }

    public function scopeNotDisqualified($query)
    {
        return $query->where('is_disqualified', false);
    }

    // ==================== ACCESSORS ====================

    public function getIsInProgressAttribute(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function getIsCompletedAttribute(): bool
    {
        return in_array($this->status, [self::STATUS_SUBMITTED, self::STATUS_GRADED]);
    }

    public function getCanContinueAttribute(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS 
            && !$this->is_disqualified 
            && !$this->isExpired();
    }

    public function getRemainingSecondsAttribute(): int
    {
        if (!$this->started_at) {
            return $this->olympiad->total_duration_minutes * 60;
        }

        $totalDuration = $this->olympiad->total_duration_minutes * 60;
        $elapsed = $this->started_at->diffInSeconds(now());
        return max(0, $totalDuration - $elapsed);
    }

    public function getFormattedScoreAttribute(): string
    {
        return number_format($this->total_weighted_score, 2) . ' / ' . 
               number_format($this->total_max_score, 2);
    }

    // ==================== METHODS ====================

    /**
     * Start the exam attempt
     */
    public function start(): void
    {
        $this->started_at = now();
        $this->status = self::STATUS_IN_PROGRESS;
        
        // Set first section as current
        $firstSection = $this->olympiad->sections()->ordered()->first();
        if ($firstSection) {
            $this->current_section_id = $firstSection->id;
        }
        
        $this->save();

        // Create section attempts
        foreach ($this->olympiad->sections as $section) {
            SectionAttempt::create([
                'attempt_id' => $this->id,
                'section_id' => $section->id,
                'max_score' => $section->max_points,
                'requires_manual_grading' => $section->requires_manual_grading,
            ]);
        }
    }

    /**
     * Submit the exam
     */
    public function submit(): void
    {
        $this->completed_at = now();
        $this->total_duration_seconds = $this->started_at->diffInSeconds($this->completed_at);
        $this->status = self::STATUS_SUBMITTED;
        
        // Calculate scores
        $this->calculateScores();
        
        // Check if needs manual grading
        $this->requires_manual_grading = $this->sectionAttempts()
            ->where('requires_manual_grading', true)
            ->exists();
        
        if (!$this->requires_manual_grading) {
            $this->status = self::STATUS_GRADED;
            $this->graded_at = now();
        } else {
            $this->status = self::STATUS_GRADING;
        }
        
        $this->save();

        // Update leaderboard
        $this->updateLeaderboard();
    }

    /**
     * Calculate all scores
     */
    public function calculateScores(): void
    {
        $sectionsResults = [];
        $totalRaw = 0;
        $totalWeighted = 0;
        $totalMax = 0;

        foreach ($this->sectionAttempts as $sectionAttempt) {
            $sectionAttempt->calculateScore();
            
            $section = $sectionAttempt->section;
            $sectionsResults[$section->section_type] = [
                'raw_score' => $sectionAttempt->raw_score,
                'weighted_score' => $sectionAttempt->weighted_score,
                'max_score' => $sectionAttempt->max_score,
                'percent' => $sectionAttempt->score_percent,
            ];
            
            $totalRaw += $sectionAttempt->raw_score;
            $totalWeighted += $sectionAttempt->weighted_score;
            $totalMax += $sectionAttempt->max_score;
        }

        $this->sections_results = $sectionsResults;
        $this->total_raw_score = $totalRaw;
        $this->total_weighted_score = $totalWeighted;
        $this->total_max_score = $totalMax;
        $this->score_percent = $totalMax > 0 ? ($totalWeighted / $totalMax) * 100 : 0;
    }

    /**
     * Disqualify the attempt
     */
    public function disqualify(string $reason): void
    {
        // Save current answers as snapshot
        $this->answers_snapshot = $this->answers->map(fn($a) => [
            'question_id' => $a->question_id,
            'answer' => $a->user_answer,
            'time_spent' => $a->time_spent_seconds,
        ])->toArray();

        $this->is_disqualified = true;
        $this->disqualified_reason = $reason;
        $this->disqualified_at = now();
        $this->status = self::STATUS_DISQUALIFIED;
        $this->save();

        // Update registration status
        $this->registration->disqualify();
    }

    /**
     * Check if attempt is expired
     */
    public function isExpired(): bool
    {
        if (!$this->started_at) {
            return false;
        }

        $totalDuration = $this->olympiad->total_duration_minutes * 60;
        return $this->started_at->addSeconds($totalDuration)->isPast();
    }

    /**
     * Record security violation
     */
    public function recordViolation(string $type): void
    {
        $antiCheatConfig = $this->olympiad->anti_cheat_config ?? [];

        switch ($type) {
            case 'tab_switch':
                $this->tab_switches++;
                $maxSwitches = $antiCheatConfig['max_tab_switches'] ?? 3;
                if ($this->tab_switches > $maxSwitches) {
                    $this->disqualify('Exceeded tab switch limit');
                }
                break;
                
            case 'fullscreen_exit':
                $this->fullscreen_exits++;
                $maxExits = $antiCheatConfig['max_fullscreen_exits'] ?? 2;
                if ($this->fullscreen_exits > $maxExits) {
                    $this->disqualify('Exceeded fullscreen exit limit');
                }
                break;
                
            default:
                $this->warnings_count++;
        }

        $this->save();
    }

    /**
     * Update leaderboard entry
     */
    public function updateLeaderboard(): void
    {
        OlympiadLiveLeaderboard::updateOrCreate(
            ['olympiad_id' => $this->olympiad_id, 'user_id' => $this->user_id],
            [
                'attempt_id' => $this->id,
                'score' => $this->total_raw_score,
                'weighted_score' => $this->total_weighted_score,
                'max_score' => $this->total_max_score,
                'score_percent' => $this->score_percent,
                'time_spent_seconds' => $this->total_duration_seconds ?? 0,
                'questions_answered' => $this->answers()->count(),
                'questions_correct' => $this->answers()->where('is_correct', true)->count(),
                'section_scores' => $this->sections_results,
                'is_disqualified' => $this->is_disqualified,
                'rank' => 0, // Will be calculated separately
            ]
        );
    }
}
