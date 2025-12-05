<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ViolationAppeal extends Model
{
    use HasFactory, HasUuids;

    // Status constants
    public const STATUS_PENDING = 'pending';
    public const STATUS_UNDER_REVIEW = 'under_review';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    // Resolution constants
    public const RESOLUTION_REINSTATED = 'reinstated';
    public const RESOLUTION_WARNING_REDUCED = 'warning_reduced';
    public const RESOLUTION_UPHELD = 'upheld';
    public const RESOLUTION_PARTIAL = 'partial';

    protected $fillable = [
        'violation_id',
        'olympiad_id',
        'user_id',
        'appeal_reason',
        'supporting_evidence',
        'status',
        'submitted_at',
        'deadline_at',
        'reviewed_at',
        'reviewed_by',
        'review_notes',
        'resolution',
    ];

    protected function casts(): array
    {
        return [
            'supporting_evidence' => 'array',
            'submitted_at' => 'datetime',
            'deadline_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    // ==================== RELATIONSHIPS ====================

    public function violation(): BelongsTo
    {
        return $this->belongsTo(SecurityViolation::class, 'violation_id');
    }

    public function olympiad(): BelongsTo
    {
        return $this->belongsTo(Olympiad::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // ==================== SCOPES ====================

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeUnderReview($query)
    {
        return $query->where('status', self::STATUS_UNDER_REVIEW);
    }

    public function scopeExpiringSoon($query)
    {
        return $query->where('deadline_at', '<=', now()->addHours(6))
            ->where('status', self::STATUS_PENDING);
    }

    // ==================== ACCESSORS ====================

    public function getIsPendingAttribute(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function getIsApprovedAttribute(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->deadline_at && now()->gt($this->deadline_at) 
            && $this->status === self::STATUS_PENDING;
    }

    public function getRemainingTimeAttribute(): ?string
    {
        if (!$this->deadline_at || $this->is_expired) {
            return null;
        }
        return $this->deadline_at->diffForHumans();
    }

    // ==================== METHODS ====================

    /**
     * Approve the appeal
     */
    public function approve(string $reviewerId, string $resolution, ?string $notes = null): void
    {
        $this->status = self::STATUS_APPROVED;
        $this->reviewed_at = now();
        $this->reviewed_by = $reviewerId;
        $this->resolution = $resolution;
        $this->review_notes = $notes;
        $this->save();

        // Handle reinstatement logic
        if ($resolution === self::RESOLUTION_REINSTATED) {
            $this->reinstateUser();
        }
    }

    /**
     * Reject the appeal
     */
    public function reject(string $reviewerId, ?string $notes = null): void
    {
        $this->status = self::STATUS_REJECTED;
        $this->reviewed_at = now();
        $this->reviewed_by = $reviewerId;
        $this->resolution = self::RESOLUTION_UPHELD;
        $this->review_notes = $notes;
        $this->save();
    }

    /**
     * Reinstate the user
     */
    private function reinstateUser(): void
    {
        $violation = $this->violation;
        $attempt = $violation->attempt;
        
        if ($attempt) {
            $attempt->is_disqualified = false;
            $attempt->disqualified_reason = null;
            $attempt->status = OlympiadAttempt::STATUS_SUBMITTED;
            $attempt->save();
        }
        
        $violation->resolve($this->reviewed_by, 'Appeal approved - user reinstated');
    }
}
