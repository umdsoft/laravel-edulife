<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabAssignmentSubmission extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'assignment_id',
        'student_id',
        'attempt_id',
        'report_id',
        'status',
        'started_at',
        'submitted_at',
        'is_late',
        'late_hours',
        'auto_score',
        'auto_percentage',
        'teacher_grade',
        'late_penalty',
        'final_grade',
        'grade_letter',
        'teacher_feedback',
        'feedback_audio_url',
        'feedback_annotations',
        'graded_by',
        'graded_at',
        'resubmission_allowed',
        'resubmission_deadline',
        'resubmission_count',
        'max_resubmissions',
        'student_notes',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
        'graded_at' => 'datetime',
        'resubmission_deadline' => 'datetime',
        'is_late' => 'boolean',
        'late_hours' => 'decimal:2',
        'auto_score' => 'integer',
        'auto_percentage' => 'decimal:2',
        'teacher_grade' => 'integer',
        'late_penalty' => 'integer',
        'final_grade' => 'integer',
        'feedback_annotations' => 'array',
        'resubmission_allowed' => 'boolean',
        'resubmission_count' => 'integer',
        'max_resubmissions' => 'integer',
    ];

    protected $attributes = [
        'status' => 'pending',
        'is_late' => false,
        'late_penalty' => 0,
        'resubmission_allowed' => false,
        'resubmission_count' => 0,
        'max_resubmissions' => 1,
    ];

    // ═══════════════════════════════════════════════════════════════════════
    // CONSTANTS
    // ═══════════════════════════════════════════════════════════════════════

    public const STATUSES = [
        'pending' => ['label' => 'Kutilmoqda', 'color' => '#9CA3AF'],
        'in_progress' => ['label' => 'Bajarilmoqda', 'color' => '#3B82F6'],
        'submitted' => ['label' => 'Topshirilgan', 'color' => '#10B981'],
        'late_submitted' => ['label' => 'Kech topshirilgan', 'color' => '#F59E0B'],
        'graded' => ['label' => 'Baholangan', 'color' => '#8B5CF6'],
        'returned' => ['label' => 'Qaytarilgan', 'color' => '#EF4444'],
        'resubmitted' => ['label' => 'Qayta topshirilgan', 'color' => '#06B6D4'],
    ];

    public const GRADE_LETTERS = [
        'A+' => ['min' => 97, 'color' => '#10B981'],
        'A' => ['min' => 93, 'color' => '#10B981'],
        'A-' => ['min' => 90, 'color' => '#10B981'],
        'B+' => ['min' => 87, 'color' => '#3B82F6'],
        'B' => ['min' => 83, 'color' => '#3B82F6'],
        'B-' => ['min' => 80, 'color' => '#3B82F6'],
        'C+' => ['min' => 77, 'color' => '#F59E0B'],
        'C' => ['min' => 73, 'color' => '#F59E0B'],
        'C-' => ['min' => 70, 'color' => '#F59E0B'],
        'D' => ['min' => 60, 'color' => '#EF4444'],
        'F' => ['min' => 0, 'color' => '#6B7280'],
    ];

    // ═══════════════════════════════════════════════════════════════════════
    // RELATIONSHIPS
    // ═══════════════════════════════════════════════════════════════════════

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(LabAssignment::class, 'assignment_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(LabAttempt::class, 'attempt_id');
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(LabReport::class, 'report_id');
    }

    public function gradedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // SCOPES
    // ═══════════════════════════════════════════════════════════════════════

    public function scopeSubmitted($query)
    {
        return $query->whereIn('status', ['submitted', 'late_submitted', 'resubmitted']);
    }

    public function scopeGraded($query)
    {
        return $query->where('status', 'graded');
    }

    public function scopeNeedsGrading($query)
    {
        return $query->whereIn('status', ['submitted', 'late_submitted', 'resubmitted']);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeByStudent($query, $studentId)
    {
        return $query->where('student_id', $studentId);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // ACCESSORS
    // ═══════════════════════════════════════════════════════════════════════

    public function getStatusInfoAttribute(): array
    {
        return self::STATUSES[$this->status] ?? self::STATUSES['pending'];
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status_info['label'];
    }

    public function getStatusColorAttribute(): string
    {
        return $this->status_info['color'];
    }

    public function getGradeColorAttribute(): string
    {
        if (!$this->grade_letter) return '#6B7280';
        return self::GRADE_LETTERS[$this->grade_letter]['color'] ?? '#6B7280';
    }

    public function getCanResubmitAttribute(): bool
    {
        return $this->resubmission_allowed 
            && $this->resubmission_count < $this->max_resubmissions
            && ($this->resubmission_deadline === null || $this->resubmission_deadline > now());
    }

    public function getIsCompletedAttribute(): bool
    {
        return in_array($this->status, ['submitted', 'late_submitted', 'graded', 'resubmitted']);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // METHODS
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Start working on the assignment
     */
    public function start(): self
    {
        $this->status = 'in_progress';
        $this->started_at = now();
        $this->save();
        
        return $this;
    }

    /**
     * Submit the assignment
     */
    public function submit(LabAttempt $attempt, ?LabReport $report = null): self
    {
        $assignment = $this->assignment;
        $dueAt = $assignment->due_at;
        
        $this->attempt_id = $attempt->id;
        $this->report_id = $report?->id;
        $this->submitted_at = now();
        
        // Check if late
        if ($dueAt && now() > $dueAt) {
            $this->is_late = true;
            $this->late_hours = now()->floatDiffInHours($dueAt);
            $this->status = 'late_submitted';
            
            // Calculate late penalty
            if ($assignment->late_submission_allowed) {
                $penaltyPerDay = $assignment->late_penalty_percent;
                $days = ceil($this->late_hours / 24);
                $this->late_penalty = min(50, $days * $penaltyPerDay); // Max 50% penalty
            }
        } else {
            $this->status = 'submitted';
        }
        
        // Set auto score from attempt
        $this->auto_score = $attempt->raw_score;
        $this->auto_percentage = $attempt->percentage;
        
        $this->save();
        
        // Update assignment statistics
        $assignment->updateStatistics();
        
        return $this;
    }

    /**
     * Grade the submission
     */
    public function grade(User $teacher, int $grade, ?string $feedback = null): self
    {
        $this->teacher_grade = $grade;
        $this->teacher_feedback = $feedback;
        $this->graded_by = $teacher->id;
        $this->graded_at = now();
        $this->status = 'graded';
        
        // Calculate final grade with late penalty
        $this->final_grade = max(0, $grade - $this->late_penalty);
        
        // Determine letter grade
        $this->grade_letter = $this->calculateGradeLetter($this->final_grade);
        
        $this->save();
        
        // Update assignment statistics
        $this->assignment->updateStatistics();
        
        return $this;
    }

    /**
     * Calculate grade letter from score
     */
    protected function calculateGradeLetter(int $score): string
    {
        foreach (self::GRADE_LETTERS as $letter => $info) {
            if ($score >= $info['min']) {
                return $letter;
            }
        }
        return 'F';
    }

    /**
     * Return for revision
     */
    public function returnForRevision(string $feedback, ?\DateTime $deadline = null): self
    {
        $this->status = 'returned';
        $this->teacher_feedback = $feedback;
        $this->resubmission_allowed = true;
        $this->resubmission_deadline = $deadline ?? now()->addDays(3);
        $this->save();
        
        return $this;
    }

    /**
     * Resubmit the assignment
     */
    public function resubmit(LabAttempt $attempt, ?LabReport $report = null): self
    {
        if (!$this->can_resubmit) {
            throw new \Exception('Qayta topshirish mumkin emas');
        }
        
        $this->attempt_id = $attempt->id;
        $this->report_id = $report?->id;
        $this->submitted_at = now();
        $this->status = 'resubmitted';
        $this->resubmission_count += 1;
        $this->auto_score = $attempt->raw_score;
        $this->auto_percentage = $attempt->percentage;
        
        $this->save();
        
        return $this;
    }

    /**
     * Add feedback annotation
     */
    public function addAnnotation(string $section, string $feedback): self
    {
        $annotations = $this->feedback_annotations ?? [];
        $annotations[] = [
            'section' => $section,
            'feedback' => $feedback,
            'created_at' => now()->toIso8601String(),
        ];
        $this->feedback_annotations = $annotations;
        $this->save();
        
        return $this;
    }

    /**
     * Export for display
     */
    public function toDisplayData(): array
    {
        return [
            'id' => $this->id,
            'student' => [
                'id' => $this->student_id,
                'name' => $this->student->name,
                'avatar' => $this->student->avatar_url ?? null,
            ],
            'status' => $this->status,
            'status_label' => $this->status_label,
            'status_color' => $this->status_color,
            'submitted_at' => $this->submitted_at?->format('d.m.Y H:i'),
            'is_late' => $this->is_late,
            'late_hours' => round($this->late_hours ?? 0, 1),
            'scores' => [
                'auto' => $this->auto_score,
                'auto_percentage' => $this->auto_percentage,
                'teacher' => $this->teacher_grade,
                'penalty' => $this->late_penalty,
                'final' => $this->final_grade,
                'grade_letter' => $this->grade_letter,
                'grade_color' => $this->grade_color,
            ],
            'feedback' => $this->teacher_feedback,
            'can_resubmit' => $this->can_resubmit,
            'resubmission_deadline' => $this->resubmission_deadline?->format('d.m.Y H:i'),
        ];
    }
}
