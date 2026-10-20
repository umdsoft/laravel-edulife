<?php

namespace App\Models\Exam;

use App\Enums\Exam\AttemptStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A student's attempt of a session (olympiad_attempts pattern: session
 * token, server side deadline, score, anti-cheat counters).
 */
class ExamAttempt extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'exam_attempts';

    protected $fillable = [
        'session_id',
        'variant_id',
        'user_id',
        'session_token',
        'status',
        'started_at',
        'expires_at',
        'submitted_at',
        'graded_at',
        'regraded_at',
        'time_spent_seconds',
        'last_heartbeat_at',
        'score',
        'max_score',
        'score_percent',
        'correct_count',
        'wrong_count',
        'skipped_count',
        'device_fingerprint',
        'ip_address',
        'user_agent',
        'violations_count',
        'violation_log',
        'invalidated_reason',
    ];

    protected $hidden = ['session_token'];

    protected function casts(): array
    {
        return [
            'status' => AttemptStatus::class,
            'started_at' => 'datetime',
            'expires_at' => 'datetime',
            'submitted_at' => 'datetime',
            'graded_at' => 'datetime',
            'regraded_at' => 'datetime',
            'last_heartbeat_at' => 'datetime',
            'time_spent_seconds' => 'integer',
            'score' => 'decimal:2',
            'max_score' => 'decimal:2',
            'score_percent' => 'decimal:2',
            'correct_count' => 'integer',
            'wrong_count' => 'integer',
            'skipped_count' => 'integer',
            'violations_count' => 'integer',
            'violation_log' => 'array',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ExamSession::class, 'session_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ExamVariant::class, 'variant_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(ExamAnswer::class, 'attempt_id')->orderBy('position');
    }

    public function weeklyResult(): HasOne
    {
        return $this->hasOne(StudentWeeklyResult::class, 'attempt_id');
    }

    public function scopeInProgress(Builder $query): Builder
    {
        return $query->where('status', AttemptStatus::InProgress->value);
    }

    /** In-progress attempts whose deadline passed (auto-submit sweep). */
    public function scopeExpired(Builder $query, ?\DateTimeInterface $now = null): Builder
    {
        return $query->inProgress()->whereNotNull('expires_at')->where('expires_at', '<=', $now ?? now());
    }

    public function isExpired(?\DateTimeInterface $now = null): bool
    {
        return $this->expires_at !== null && $this->expires_at->lte($now ?? now());
    }
}
