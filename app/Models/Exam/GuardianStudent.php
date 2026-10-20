<?php

namespace App\Models\Exam;

use App\Enums\Exam\GuardianRelation;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

/**
 * Pivot guardian user <-> student user (guardian_student).
 * consent_at: when the guardian consented to processing the child's data.
 */
class GuardianStudent extends Pivot
{
    use HasFactory, HasUuids;

    protected $table = 'guardian_student';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'guardian_id',
        'student_id',
        'relation',
        'consent_at',
        'consent_source',
        'receives_reports',
        'verified_at',
        'approved_by',
        'rejected_at',
        'reject_reason',
    ];

    protected function casts(): array
    {
        return [
            'relation' => GuardianRelation::class,
            'consent_at' => 'datetime',
            'receives_reports' => 'boolean',
            'verified_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (GuardianStudent $link) {
            if ($link->guardian_id === $link->student_id) {
                throw new \InvalidArgumentException('A user can not be their own guardian.');
            }
        });
    }

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guardian_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /** Approved (teacher/admin confirmed) links are the only ones a guardian can see. */
    public function scopeVerified(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereNotNull('verified_at')->whereNull('rejected_at');
    }

    public function scopePending(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereNull('verified_at')->whereNull('rejected_at');
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null && $this->rejected_at === null;
    }

    public function hasConsent(): bool
    {
        return $this->consent_at !== null;
    }
}
