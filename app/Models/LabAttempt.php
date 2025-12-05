<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Carbon\Carbon;

class LabAttempt extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'experiment_id',
        'attempt_number',
        'started_at',
        'last_activity_at',
        'completed_at',
        'time_spent_seconds',
        'status',
        'simulation_state',
        'current_task',
        'tasks_progress',
        'completed_tasks',
        'total_tasks',
        'measurements',
        'calculations',
        'graph_data',
        'raw_score',
        'max_score',
        'percentage',
        'grade',
        'grade_points',
        'passed',
        'xp_earned',
        'coins_earned',
        'badges_earned',
        'achievements_unlocked',
        'screenshots',
        'screen_recording_url',
        'user_notes',
        'conclusion_text',
        'error_analysis',
        'device_type',
        'browser',
        'screen_resolution',
        'ip_address',
    ];

    protected $casts = [
        'attempt_number' => 'integer',
        'started_at' => 'datetime',
        'last_activity_at' => 'datetime',
        'completed_at' => 'datetime',
        'time_spent_seconds' => 'integer',
        'simulation_state' => 'array',
        'current_task' => 'integer',
        'tasks_progress' => 'array',
        'completed_tasks' => 'integer',
        'total_tasks' => 'integer',
        'measurements' => 'array',
        'calculations' => 'array',
        'graph_data' => 'array',
        'raw_score' => 'integer',
        'max_score' => 'integer',
        'percentage' => 'decimal:2',
        'grade_points' => 'decimal:2',
        'passed' => 'boolean',
        'xp_earned' => 'integer',
        'coins_earned' => 'integer',
        'badges_earned' => 'array',
        'achievements_unlocked' => 'array',
        'screenshots' => 'array',
    ];

    protected $attributes = [
        'status' => 'in_progress',
        'current_task' => 1,
        'completed_tasks' => 0,
        'time_spent_seconds' => 0,
        'raw_score' => 0,
        'passed' => false,
        'xp_earned' => 0,
        'coins_earned' => 0,
    ];

    // ═══════════════════════════════════════════════════════════════════════
    // CONSTANTS
    // ═══════════════════════════════════════════════════════════════════════

    public const STATUSES = [
        'in_progress' => ['label' => 'Davom etmoqda', 'color' => '#3B82F6'],
        'paused' => ['label' => 'To\'xtatilgan', 'color' => '#F59E0B'],
        'completed' => ['label' => 'Yakunlangan', 'color' => '#10B981'],
        'abandoned' => ['label' => 'Bekor qilingan', 'color' => '#EF4444'],
        'expired' => ['label' => 'Muddati o\'tgan', 'color' => '#6B7280'],
    ];

    public const GRADES = [
        'A+' => ['min' => 97, 'points' => 4.0, 'color' => '#10B981'],
        'A'  => ['min' => 93, 'points' => 4.0, 'color' => '#10B981'],
        'A-' => ['min' => 90, 'points' => 3.7, 'color' => '#10B981'],
        'B+' => ['min' => 87, 'points' => 3.3, 'color' => '#3B82F6'],
        'B'  => ['min' => 83, 'points' => 3.0, 'color' => '#3B82F6'],
        'B-' => ['min' => 80, 'points' => 2.7, 'color' => '#3B82F6'],
        'C+' => ['min' => 77, 'points' => 2.3, 'color' => '#F59E0B'],
        'C'  => ['min' => 73, 'points' => 2.0, 'color' => '#F59E0B'],
        'C-' => ['min' => 70, 'points' => 1.7, 'color' => '#F59E0B'],
        'D+' => ['min' => 67, 'points' => 1.3, 'color' => '#EF4444'],
        'D'  => ['min' => 63, 'points' => 1.0, 'color' => '#EF4444'],
        'D-' => ['min' => 60, 'points' => 0.7, 'color' => '#EF4444'],
        'F'  => ['min' => 0,  'points' => 0.0, 'color' => '#6B7280'],
    ];

    // ═══════════════════════════════════════════════════════════════════════
    // RELATIONSHIPS
    // ═══════════════════════════════════════════════════════════════════════

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function experiment(): BelongsTo
    {
        return $this->belongsTo(LabExperiment::class, 'experiment_id');
    }

    public function report(): HasOne
    {
        return $this->hasOne(LabReport::class, 'attempt_id');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // SCOPES
    // ═══════════════════════════════════════════════════════════════════════

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeInProgress($query)
    {
        return $query->whereIn('status', ['in_progress', 'paused']);
    }

    public function scopePassed($query)
    {
        return $query->where('passed', true);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByExperiment($query, $experimentId)
    {
        return $query->where('experiment_id', $experimentId);
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('started_at', '>=', now()->subDays($days));
    }

    // ═══════════════════════════════════════════════════════════════════════
    // ACCESSORS
    // ═══════════════════════════════════════════════════════════════════════

    public function getStatusInfoAttribute(): array
    {
        return self::STATUSES[$this->status] ?? self::STATUSES['in_progress'];
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status_info['label'];
    }

    public function getStatusColorAttribute(): string
    {
        return $this->status_info['color'];
    }

    public function getGradeInfoAttribute(): ?array
    {
        return $this->grade ? self::GRADES[$this->grade] ?? null : null;
    }

    public function getGradeColorAttribute(): string
    {
        return $this->grade_info['color'] ?? '#6B7280';
    }

    public function getTimeSpentTextAttribute(): string
    {
        $seconds = $this->time_spent_seconds;
        
        if ($seconds < 60) {
            return "{$seconds} soniya";
        }
        
        $minutes = intdiv($seconds, 60);
        $secs = $seconds % 60;
        
        if ($minutes < 60) {
            return "{$minutes} daqiqa" . ($secs > 0 ? " {$secs} soniya" : '');
        }
        
        $hours = intdiv($minutes, 60);
        $mins = $minutes % 60;
        
        return "{$hours} soat {$mins} daqiqa";
    }

    public function getProgressPercentAttribute(): float
    {
        if ($this->total_tasks === 0) return 0;
        return round(($this->completed_tasks / $this->total_tasks) * 100, 1);
    }

    public function getMeasurementsCountAttribute(): int
    {
        return count($this->measurements ?? []);
    }

    public function getCalculationsCountAttribute(): int
    {
        return count($this->calculations ?? []);
    }

    public function getCanResumeAttribute(): bool
    {
        return in_array($this->status, ['in_progress', 'paused']);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // METHODS
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Start or resume the attempt
     */
    public function start(): self
    {
        if (!$this->started_at) {
            $this->started_at = now();
        }
        
        $this->status = 'in_progress';
        $this->last_activity_at = now();
        $this->save();
        
        return $this;
    }

    /**
     * Pause the attempt
     */
    public function pause(): self
    {
        $this->status = 'paused';
        $this->updateTimeSpent();
        $this->save();
        
        return $this;
    }

    /**
     * Update activity timestamp and time spent
     */
    public function recordActivity(): self
    {
        $now = now();
        
        // Calculate time since last activity (max 5 minutes per interval)
        if ($this->last_activity_at) {
            $diff = min(300, $this->last_activity_at->diffInSeconds($now));
            $this->time_spent_seconds += $diff;
        }
        
        $this->last_activity_at = $now;
        $this->save();
        
        return $this;
    }

    /**
     * Update time spent
     */
    protected function updateTimeSpent(): void
    {
        if ($this->started_at) {
            $this->time_spent_seconds = $this->started_at->diffInSeconds(
                $this->completed_at ?? now()
            );
        }
    }

    /**
     * Save simulation state
     */
    public function saveState(array $state): self
    {
        $this->simulation_state = $state;
        $this->last_activity_at = now();
        $this->save();
        
        return $this;
    }

    /**
     * Add measurement
     */
    public function addMeasurement(array $measurement): self
    {
        $measurements = $this->measurements ?? [];
        $measurement['timestamp'] = now()->toIso8601String();
        $measurements[] = $measurement;
        $this->measurements = $measurements;
        $this->save();
        
        return $this;
    }

    /**
     * Add calculation result
     */
    public function addCalculation(array $calculation): self
    {
        $calculations = $this->calculations ?? [];
        $calculations[] = $calculation;
        $this->calculations = $calculations;
        $this->save();
        
        return $this;
    }

    /**
     * Update task progress
     */
    public function updateTaskProgress(int $taskNumber, array $data): self
    {
        $progress = $this->tasks_progress ?? [];
        
        // Find or create task entry
        $found = false;
        foreach ($progress as &$task) {
            if ($task['step'] === $taskNumber) {
                $task = array_merge($task, $data);
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            $progress[] = array_merge(['step' => $taskNumber], $data);
        }
        
        $this->tasks_progress = $progress;
        
        // Update completed count
        $this->completed_tasks = collect($progress)
            ->where('status', 'completed')
            ->count();
        
        $this->save();
        
        return $this;
    }

    /**
     * Complete a task
     */
    public function completeTask(int $taskNumber, int $score, int $maxScore, $userInput = null): self
    {
        $this->updateTaskProgress($taskNumber, [
            'status' => 'completed',
            'score' => $score,
            'max_score' => $maxScore,
            'user_input' => $userInput,
            'completed_at' => now()->toIso8601String(),
        ]);
        
        // Move to next task
        if ($taskNumber >= $this->current_task) {
            $this->current_task = $taskNumber + 1;
            $this->save();
        }
        
        return $this;
    }

    /**
     * Complete the attempt
     */
    public function complete(): self
    {
        $this->status = 'completed';
        $this->completed_at = now();
        $this->updateTimeSpent();
        
        // Calculate final score
        $this->calculateFinalScore();
        
        // Determine grade
        $this->determineGrade();
        
        $this->save();
        
        // Award rewards
        $this->awardRewards();
        
        // Update experiment statistics
        $this->experiment->updateStatistics();
        
        return $this;
    }

    /**
     * Calculate final score from task progress
     */
    protected function calculateFinalScore(): void
    {
        $progress = $this->tasks_progress ?? [];
        
        $rawScore = 0;
        $maxScore = 0;
        
        foreach ($progress as $task) {
            $rawScore += $task['score'] ?? 0;
            $maxScore += $task['max_score'] ?? 0;
        }
        
        $this->raw_score = $rawScore;
        $this->max_score = $maxScore ?: $this->experiment->total_points;
        $this->percentage = $this->max_score > 0 
            ? round(($rawScore / $this->max_score) * 100, 2) 
            : 0;
        $this->passed = $this->percentage >= ($this->experiment->passing_points / $this->experiment->total_points * 100);
    }

    /**
     * Determine letter grade from percentage
     */
    protected function determineGrade(): void
    {
        $percentage = $this->percentage;
        
        foreach (self::GRADES as $letter => $info) {
            if ($percentage >= $info['min']) {
                $this->grade = $letter;
                $this->grade_points = $info['points'];
                break;
            }
        }
    }

    /**
     * Award XP, coins, and badges
     */
    protected function awardRewards(): void
    {
        $experiment = $this->experiment;
        $user = $this->user;
        
        if (!$this->passed) {
            return;
        }
        
        // XP and coins
        $this->xp_earned = $experiment->getXpRewardFor($user);
        $this->coins_earned = $experiment->getCoinRewardFor($user);
        
        // Update user progress
        $progress = LabUserProgress::firstOrCreate(['user_id' => $user->id]);
        $progress->total_xp += $this->xp_earned;
        $progress->completed_experiments += 1;
        
        if ($this->percentage >= 100) {
            $progress->perfect_scores += 1;
        }
        
        $progress->save();
        
        // Award coins to user
        if ($this->coins_earned > 0) {
            $user->addCoins($this->coins_earned, 'lab_experiment', "Lab yakunlandi: {$experiment->localized_title}");
        }
        
        // Check for completion badge
        if ($experiment->badge_on_complete && $this->passed) {
            $badge = $experiment->badgeOnComplete;
            if ($badge && $badge->awardTo($user)) {
                $badges = $this->badges_earned ?? [];
                $badges[] = $badge->id;
                $this->badges_earned = $badges;
            }
        }
        
        // Check for perfect score badge
        if ($experiment->badge_on_perfect && $this->percentage >= 100) {
            $badge = $experiment->badgeOnPerfect;
            if ($badge && $badge->awardTo($user)) {
                $badges = $this->badges_earned ?? [];
                $badges[] = $badge->id;
                $this->badges_earned = $badges;
            }
        }
        
        $this->save();
    }

    /**
     * Add screenshot
     */
    public function addScreenshot(string $url, ?string $caption = null, ?int $step = null): self
    {
        $screenshots = $this->screenshots ?? [];
        $screenshots[] = [
            'url' => $url,
            'caption' => $caption,
            'step' => $step ?? $this->current_task,
            'timestamp' => now()->toIso8601String(),
        ];
        $this->screenshots = $screenshots;
        $this->save();
        
        return $this;
    }

    /**
     * Export attempt data for report
     */
    public function toReportData(): array
    {
        return [
            'experiment' => [
                'title' => $this->experiment->localized_title,
                'category' => $this->experiment->category->localized_name,
                'difficulty' => $this->experiment->difficulty_label,
            ],
            'user' => [
                'name' => $this->user->name,
                'grade' => $this->user->student_profile?->class ?? 'N/A',
            ],
            'attempt' => [
                'number' => $this->attempt_number,
                'date' => $this->started_at->format('d.m.Y'),
                'duration' => $this->time_spent_text,
            ],
            'score' => [
                'raw' => $this->raw_score,
                'max' => $this->max_score,
                'percentage' => $this->percentage,
                'grade' => $this->grade,
                'passed' => $this->passed,
            ],
            'measurements' => $this->measurements ?? [],
            'calculations' => $this->calculations ?? [],
            'graph_data' => $this->graph_data ?? [],
            'conclusion' => $this->conclusion_text,
            'error_analysis' => $this->error_analysis,
        ];
    }
}
