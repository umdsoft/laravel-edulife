<?php

namespace App\Models\Exam;

use App\Enums\Exam\ExamSessionStatus;
use App\Enums\Exam\ExamSessionType;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A scheduled exam: a Saturday exam, an open regional exam or a placement
 * window (placement sessions may have no start/end).
 */
class ExamSession extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'exam_sessions';

    protected $fillable = [
        'subject_id',
        'blueprint_id',
        'restricted_to_user_id',
        'type',
        'title',
        'starts_at',
        'ends_at',
        'registration_closes_at',
        'duration_min',
        'status',
        'variants_generated_at',
        'results_published_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => ExamSessionType::class,
            'status' => ExamSessionStatus::class,
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'registration_closes_at' => 'datetime',
            'variants_generated_at' => 'datetime',
            'results_published_at' => 'datetime',
            'duration_min' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (ExamSession $session) {
            if ($session->starts_at && $session->ends_at && $session->ends_at->lte($session->starts_at)) {
                throw new \InvalidArgumentException('Exam session must end after it starts.');
            }
        });
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function blueprint(): BelongsTo
    {
        return $this->belongsTo(ExamBlueprint::class, 'blueprint_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ExamVariant::class, 'session_id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class, 'session_id');
    }

    public function weeklyResults(): HasMany
    {
        return $this->hasMany(StudentWeeklyResult::class, 'session_id');
    }

    public function scopeOfType(Builder $query, ExamSessionType $type): Builder
    {
        return $query->where('type', $type->value);
    }

    /** Sessions every eligible student may take (not a private retake session). */
    public function scopePublic(Builder $query): Builder
    {
        return $query->whereNull('restricted_to_user_id');
    }

    public function isRestricted(): bool
    {
        return $this->restricted_to_user_id !== null;
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('starts_at', '>', now())->orderBy('starts_at');
    }

    /** Duration in minutes: session override or the blueprint's. */
    public function effectiveDurationMin(): int
    {
        return (int) ($this->duration_min ?? $this->blueprint()->value('duration_min'));
    }

    public function isOpenAt(\DateTimeInterface $at): bool
    {
        return $this->status === ExamSessionStatus::Open
            && ($this->starts_at === null || $this->starts_at->lte($at))
            && ($this->ends_at === null || $this->ends_at->gt($at));
    }
}
