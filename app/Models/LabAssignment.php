<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabAssignment extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'teacher_id',
        'class_id',
        'group_id',
        'student_ids',
        'experiment_id',
        'title',
        'instructions',
        'assigned_at',
        'due_at',
        'late_submission_allowed',
        'late_penalty_percent',
        'min_score',
        'require_report',
        'require_conclusion',
        'min_time_minutes',
        'max_grade',
        'grade_weight',
        'custom_config',
        'notify_on_submit',
        'reminder_before_hours',
        'reminder_sent',
        'status',
    ];

    protected $casts = [
        'student_ids' => 'array',
        'assigned_at' => 'datetime',
        'due_at' => 'datetime',
        'late_submission_allowed' => 'boolean',
        'late_penalty_percent' => 'integer',
        'min_score' => 'integer',
        'require_report' => 'boolean',
        'require_conclusion' => 'boolean',
        'min_time_minutes' => 'integer',
        'max_grade' => 'integer',
        'grade_weight' => 'decimal:2',
        'custom_config' => 'array',
        'notify_on_submit' => 'boolean',
        'reminder_before_hours' => 'integer',
        'reminder_sent' => 'boolean',
        'total_assigned' => 'integer',
        'total_submitted' => 'integer',
        'total_graded' => 'integer',
        'avg_score' => 'decimal:2',
    ];

    protected $attributes = [
        'late_submission_allowed' => false,
        'late_penalty_percent' => 10,
        'min_score' => 60,
        'require_report' => true,
        'require_conclusion' => true,
        'max_grade' => 100,
        'grade_weight' => 1.0,
        'notify_on_submit' => true,
        'reminder_before_hours' => 24,
        'reminder_sent' => false,
        'status' => 'active',
        'total_assigned' => 0,
        'total_submitted' => 0,
        'total_graded' => 0,
    ];

    // ═══════════════════════════════════════════════════════════════════════
    // CONSTANTS
    // ═══════════════════════════════════════════════════════════════════════

    public const STATUSES = [
        'draft' => ['label' => 'Qoralama', 'color' => '#9CA3AF'],
        'active' => ['label' => 'Faol', 'color' => '#10B981'],
        'closed' => ['label' => 'Yopilgan', 'color' => '#6B7280'],
        'grading' => ['label' => 'Baholash', 'color' => '#F59E0B'],
        'graded' => ['label' => 'Baholangan', 'color' => '#3B82F6'],
        'archived' => ['label' => 'Arxivlangan', 'color' => '#6B7280'],
    ];

    // ═══════════════════════════════════════════════════════════════════════
    // RELATIONSHIPS
    // ═══════════════════════════════════════════════════════════════════════

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Classes::class, 'class_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(StudentGroup::class, 'group_id');
    }

    public function experiment(): BelongsTo
    {
        return $this->belongsTo(LabExperiment::class, 'experiment_id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(LabAssignmentSubmission::class, 'assignment_id');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // SCOPES
    // ═══════════════════════════════════════════════════════════════════════

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeByTeacher($query, $teacherId)
    {
        return $query->where('teacher_id', $teacherId);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('due_at', '>', now());
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_at', '<', now())->active();
    }

    public function scopeNeedsReminder($query)
    {
        return $query->active()
            ->where('reminder_sent', false)
            ->whereRaw('due_at <= DATE_ADD(NOW(), INTERVAL reminder_before_hours HOUR)');
    }

    public function scopeForStudent($query, User $student)
    {
        return $query->where(function ($q) use ($student) {
            $q->whereJsonContains('student_ids', $student->id)
              ->orWhereHas('class', function ($q2) use ($student) {
                  $q2->whereHas('students', fn($q3) => $q3->where('user_id', $student->id));
              })
              ->orWhereHas('group', function ($q2) use ($student) {
                  $q2->whereHas('students', fn($q3) => $q3->where('user_id', $student->id));
              });
        });
    }

    // ═══════════════════════════════════════════════════════════════════════
    // ACCESSORS
    // ═══════════════════════════════════════════════════════════════════════

    public function getStatusInfoAttribute(): array
    {
        return self::STATUSES[$this->status] ?? self::STATUSES['active'];
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status_info['label'];
    }

    public function getStatusColorAttribute(): string
    {
        return $this->status_info['color'];
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->due_at < now() && $this->status === 'active';
    }

    public function getTimeRemainingAttribute(): ?string
    {
        if ($this->due_at < now()) return null;
        return $this->due_at->diffForHumans();
    }

    public function getSubmissionRateAttribute(): float
    {
        if ($this->total_assigned === 0) return 0;
        return round(($this->total_submitted / $this->total_assigned) * 100, 1);
    }

    public function getGradingProgressAttribute(): float
    {
        if ($this->total_submitted === 0) return 0;
        return round(($this->total_graded / $this->total_submitted) * 100, 1);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // METHODS
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Get all assigned students
     */
    public function getAssignedStudents(): \Illuminate\Database\Eloquent\Collection
    {
        $studentIds = collect($this->student_ids ?? []);
        
        // Add students from class
        if ($this->class_id) {
            $classStudents = $this->class
                ->students()
                ->pluck('user_id');
            $studentIds = $studentIds->merge($classStudents);
        }
        
        // Add students from group
        if ($this->group_id) {
            $groupStudents = $this->group
                ->students()
                ->pluck('user_id');
            $studentIds = $studentIds->merge($groupStudents);
        }
        
        return User::whereIn('id', $studentIds->unique())->get();
    }

    /**
     * Check if a student is assigned
     */
    public function isAssignedTo(User $student): bool
    {
        $studentIds = $this->student_ids ?? [];
        
        if (in_array($student->id, $studentIds)) {
            return true;
        }
        
        if ($this->class_id) {
            $inClass = $this->class
                ->students()
                ->where('user_id', $student->id)
                ->exists();
            if ($inClass) return true;
        }
        
        if ($this->group_id) {
            $inGroup = $this->group
                ->students()
                ->where('user_id', $student->id)
                ->exists();
            if ($inGroup) return true;
        }
        
        return false;
    }

    /**
     * Get submission for a student
     */
    public function getSubmissionFor(User $student): ?LabAssignmentSubmission
    {
        return $this->submissions()
            ->where('student_id', $student->id)
            ->first();
    }

    /**
     * Calculate statistics
     */
    public function updateStatistics(): void
    {
        $this->total_assigned = $this->getAssignedStudents()->count();
        $this->total_submitted = $this->submissions()
            ->whereIn('status', ['submitted', 'late_submitted', 'graded', 'resubmitted'])
            ->count();
        $this->total_graded = $this->submissions()
            ->where('status', 'graded')
            ->count();
        $this->avg_score = $this->submissions()
            ->whereNotNull('final_grade')
            ->avg('final_grade');
        
        $this->save();
    }

    /**
     * Close the assignment
     */
    public function close(): self
    {
        $this->status = 'closed';
        $this->save();
        
        return $this;
    }

    /**
     * Mark reminder as sent
     */
    public function markReminderSent(): self
    {
        $this->reminder_sent = true;
        $this->save();
        
        return $this;
    }

    /**
     * Get pending submissions (need grading)
     */
    public function getPendingSubmissions(): \Illuminate\Database\Eloquent\Collection
    {
        return $this->submissions()
            ->whereIn('status', ['submitted', 'late_submitted', 'resubmitted'])
            ->with('student')
            ->get();
    }

    /**
     * Get custom config value
     */
    public function getCustomConfig(string $key, $default = null)
    {
        $config = $this->custom_config ?? [];
        return $config[$key] ?? $default;
    }

    /**
     * Export for display
     */
    public function toDisplayData(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'experiment' => [
                'id' => $this->experiment_id,
                'title' => $this->experiment->localized_title,
                'difficulty' => $this->experiment->difficulty,
            ],
            'teacher' => [
                'id' => $this->teacher_id,
                'name' => $this->teacher->name,
            ],
            'due_at' => $this->due_at->format('d.m.Y H:i'),
            'time_remaining' => $this->time_remaining,
            'is_overdue' => $this->is_overdue,
            'status' => $this->status,
            'status_label' => $this->status_label,
            'status_color' => $this->status_color,
            'stats' => [
                'assigned' => $this->total_assigned,
                'submitted' => $this->total_submitted,
                'graded' => $this->total_graded,
                'avg_score' => round($this->avg_score ?? 0, 1),
                'submission_rate' => $this->submission_rate,
            ],
            'requirements' => [
                'min_score' => $this->min_score,
                'require_report' => $this->require_report,
                'late_allowed' => $this->late_submission_allowed,
            ],
        ];
    }
}
