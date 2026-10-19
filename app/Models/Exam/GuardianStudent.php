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
    ];

    protected function casts(): array
    {
        return [
            'relation' => GuardianRelation::class,
            'consent_at' => 'datetime',
            'receives_reports' => 'boolean',
            'verified_at' => 'datetime',
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

    public function hasConsent(): bool
    {
        return $this->consent_at !== null;
    }
}
