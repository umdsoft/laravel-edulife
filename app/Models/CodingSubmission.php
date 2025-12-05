<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CodingSubmission extends Model
{
    use HasFactory, HasUuids;

    // Status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_RUNNING = 'running';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_WRONG_ANSWER = 'wrong_answer';
    public const STATUS_TIME_LIMIT = 'time_limit';
    public const STATUS_MEMORY_LIMIT = 'memory_limit';
    public const STATUS_RUNTIME_ERROR = 'runtime_error';
    public const STATUS_COMPILE_ERROR = 'compile_error';
    public const STATUS_PRESENTATION_ERROR = 'presentation_error';

    protected $fillable = [
        'attempt_id',
        'section_attempt_id',
        'olympiad_problem_id',
        'problem_id',
        'submission_number',
        'language',
        'source_code',
        'code_length',
        'status',
        'test_cases_passed',
        'test_cases_total',
        'points_earned',
        'max_points',
        'execution_time_ms',
        'memory_used_kb',
        'compile_output',
        'test_results',
        'is_best_submission',
    ];

    protected function casts(): array
    {
        return [
            'submission_number' => 'integer',
            'code_length' => 'integer',
            'test_cases_passed' => 'integer',
            'test_cases_total' => 'integer',
            'points_earned' => 'decimal:2',
            'max_points' => 'decimal:2',
            'execution_time_ms' => 'integer',
            'memory_used_kb' => 'integer',
            'test_results' => 'array',
            'is_best_submission' => 'boolean',
        ];
    }

    // ==================== RELATIONSHIPS ====================

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(OlympiadAttempt::class, 'attempt_id');
    }

    public function sectionAttempt(): BelongsTo
    {
        return $this->belongsTo(SectionAttempt::class, 'section_attempt_id');
    }

    public function olympiadProblem(): BelongsTo
    {
        return $this->belongsTo(OlympiadCodingProblem::class, 'olympiad_problem_id');
    }

    public function problem(): BelongsTo
    {
        return $this->belongsTo(CodingProblem::class, 'problem_id');
    }

    // ==================== SCOPES ====================

    public function scopeAccepted($query)
    {
        return $query->where('status', self::STATUS_ACCEPTED);
    }

    public function scopeBest($query)
    {
        return $query->where('is_best_submission', true);
    }

    public function scopeByProblem($query, string $problemId)
    {
        return $query->where('olympiad_problem_id', $problemId);
    }

    // ==================== ACCESSORS ====================

    public function getIsAcceptedAttribute(): bool
    {
        return $this->status === self::STATUS_ACCEPTED;
    }

    public function getIsErrorAttribute(): bool
    {
        return in_array($this->status, [
            self::STATUS_COMPILE_ERROR,
            self::STATUS_RUNTIME_ERROR,
        ]);
    }

    public function getPassPercentAttribute(): float
    {
        return $this->test_cases_total > 0 
            ? ($this->test_cases_passed / $this->test_cases_total) * 100 
            : 0;
    }

    public function getStatusLabelAttribute(): string
    {
        $labels = [
            self::STATUS_PENDING => 'Kutilmoqda',
            self::STATUS_RUNNING => 'Tekshirilmoqda',
            self::STATUS_ACCEPTED => 'Qabul qilindi',
            self::STATUS_WRONG_ANSWER => "Noto'g'ri javob",
            self::STATUS_TIME_LIMIT => 'Vaqt limiti',
            self::STATUS_MEMORY_LIMIT => 'Xotira limiti',
            self::STATUS_RUNTIME_ERROR => 'Runtime xatosi',
            self::STATUS_COMPILE_ERROR => 'Kompilatsiya xatosi',
            self::STATUS_PRESENTATION_ERROR => 'Format xatosi',
        ];
        return $labels[$this->status] ?? $this->status;
    }

    public function getStatusColorAttribute(): string
    {
        $colors = [
            self::STATUS_PENDING => 'gray',
            self::STATUS_RUNNING => 'blue',
            self::STATUS_ACCEPTED => 'green',
            self::STATUS_WRONG_ANSWER => 'red',
            self::STATUS_TIME_LIMIT => 'orange',
            self::STATUS_MEMORY_LIMIT => 'orange',
            self::STATUS_RUNTIME_ERROR => 'red',
            self::STATUS_COMPILE_ERROR => 'red',
            self::STATUS_PRESENTATION_ERROR => 'yellow',
        ];
        return $colors[$this->status] ?? 'gray';
    }

    // ==================== METHODS ====================

    /**
     * Update submission with judge results
     */
    public function updateWithResults(array $results): void
    {
        $this->status = $results['status'];
        $this->test_cases_passed = $results['passed'] ?? 0;
        $this->test_cases_total = $results['total'] ?? 0;
        $this->execution_time_ms = $results['time_ms'] ?? null;
        $this->memory_used_kb = $results['memory_kb'] ?? null;
        $this->compile_output = $results['compile_output'] ?? null;
        $this->test_results = $results['test_results'] ?? [];
        
        // Calculate points
        $this->points_earned = $this->problem->calculatePoints($this->test_cases_passed);
        
        $this->save();

        // Update best submission flag
        $this->updateBestSubmission();
    }

    /**
     * Update best submission for this problem
     */
    private function updateBestSubmission(): void
    {
        // Find best submission for this problem in this attempt
        $bestSubmission = CodingSubmission::where('attempt_id', $this->attempt_id)
            ->where('olympiad_problem_id', $this->olympiad_problem_id)
            ->orderByDesc('points_earned')
            ->orderBy('execution_time_ms')
            ->first();

        // Reset all is_best_submission flags
        CodingSubmission::where('attempt_id', $this->attempt_id)
            ->where('olympiad_problem_id', $this->olympiad_problem_id)
            ->update(['is_best_submission' => false]);

        // Set the best one
        if ($bestSubmission) {
            $bestSubmission->is_best_submission = true;
            $bestSubmission->save();
        }
    }
}
