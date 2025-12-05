<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OlympiadDeviceLock extends Model
{
    use HasFactory, HasUuids;

    // Status constants
    public const STATUS_ACTIVE = 'active';
    public const STATUS_RELEASED = 'released';
    public const STATUS_VIOLATED = 'violated';

    // Lock reason constants
    public const REASON_EXAM_START = 'exam_start';
    public const REASON_DEVICE_VERIFICATION = 'device_verification';
    public const REASON_MANUAL_LOCK = 'manual_lock';

    protected $fillable = [
        'olympiad_id',
        'user_id',
        'device_id',
        'attempt_id',
        'locked_at',
        'released_at',
        'locked_ip',
        'lock_reason',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'locked_at' => 'datetime',
            'released_at' => 'datetime',
        ];
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

    public function device(): BelongsTo
    {
        return $this->belongsTo(UserDevice::class, 'device_id');
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(OlympiadAttempt::class, 'attempt_id');
    }

    // ==================== SCOPES ====================

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeForOlympiad($query, string $olympiadId)
    {
        return $query->where('olympiad_id', $olympiadId);
    }

    // ==================== ACCESSORS ====================

    public function getIsActiveAttribute(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function getIsViolatedAttribute(): bool
    {
        return $this->status === self::STATUS_VIOLATED;
    }

    // ==================== METHODS ====================

    /**
     * Release the lock
     */
    public function release(): void
    {
        $this->status = self::STATUS_RELEASED;
        $this->released_at = now();
        $this->save();
    }

    /**
     * Mark as violated
     */
    public function markViolated(): void
    {
        $this->status = self::STATUS_VIOLATED;
        $this->save();
    }

    /**
     * Check if current device matches the lock
     */
    public function matchesDevice(string $deviceFingerprint): bool
    {
        return $this->device && $this->device->device_fingerprint === $deviceFingerprint;
    }
}
