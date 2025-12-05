<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserDevice extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'device_fingerprint',
        'browser_fingerprint',
        'device_type',
        'os_name',
        'os_version',
        'browser_name',
        'browser_version',
        'screen_resolution',
        'color_depth',
        'timezone',
        'timezone_offset',
        'language',
        'platform',
        'hardware_concurrency',
        'device_memory',
        'canvas_fingerprint',
        'webgl_fingerprint',
        'audio_fingerprint',
        'fonts_hash',
        'plugins_hash',
        'touch_support',
        'cookies_enabled',
        'local_storage_enabled',
        'session_storage_enabled',
        'indexed_db_enabled',
        'trust_score',
        'is_trusted',
        'is_blocked',
        'block_reason',
        'blocked_at',
        'first_seen_at',
        'last_seen_at',
    ];

    protected function casts(): array
    {
        return [
            'touch_support' => 'boolean',
            'cookies_enabled' => 'boolean',
            'local_storage_enabled' => 'boolean',
            'session_storage_enabled' => 'boolean',
            'indexed_db_enabled' => 'boolean',
            'trust_score' => 'decimal:2',
            'is_trusted' => 'boolean',
            'is_blocked' => 'boolean',
            'blocked_at' => 'datetime',
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
        ];
    }

    // ==================== RELATIONSHIPS ====================

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function deviceLocks(): HasMany
    {
        return $this->hasMany(OlympiadDeviceLock::class, 'device_id');
    }

    public function violations(): HasMany
    {
        return $this->hasMany(SecurityViolation::class, 'device_id');
    }

    // ==================== SCOPES ====================

    public function scopeTrusted($query)
    {
        return $query->where('is_trusted', true);
    }

    public function scopeBlocked($query)
    {
        return $query->where('is_blocked', true);
    }

    public function scopeActive($query)
    {
        return $query->where('is_blocked', false);
    }

    // ==================== ACCESSORS ====================

    public function getIsActiveAttribute(): bool
    {
        return !$this->is_blocked;
    }

    public function getShortFingerprintAttribute(): string
    {
        return substr($this->device_fingerprint, 0, 12) . '...';
    }

    public function getDeviceInfoAttribute(): string
    {
        return "{$this->browser_name} / {$this->os_name} {$this->os_version}";
    }

    // ==================== METHODS ====================

    /**
     * Calculate similarity with another device
     */
    public function calculateSimilarity(UserDevice $other): float
    {
        $matchingFields = 0;
        $totalFields = 0;

        $fieldsToCompare = [
            'screen_resolution',
            'timezone',
            'language',
            'platform',
            'hardware_concurrency',
            'device_memory',
            'canvas_fingerprint',
            'webgl_fingerprint',
            'audio_fingerprint',
            'fonts_hash',
        ];

        foreach ($fieldsToCompare as $field) {
            if ($this->$field && $other->$field) {
                $totalFields++;
                if ($this->$field === $other->$field) {
                    $matchingFields++;
                }
            }
        }

        return $totalFields > 0 ? ($matchingFields / $totalFields) * 100 : 0;
    }

    /**
     * Block the device
     */
    public function block(string $reason): void
    {
        $this->is_blocked = true;
        $this->block_reason = $reason;
        $this->blocked_at = now();
        $this->save();
    }

    /**
     * Unblock the device
     */
    public function unblock(): void
    {
        $this->is_blocked = false;
        $this->block_reason = null;
        $this->blocked_at = null;
        $this->save();
    }

    /**
     * Mark as trusted
     */
    public function markAsTrusted(): void
    {
        $this->is_trusted = true;
        $this->trust_score = 100;
        $this->save();
    }

    /**
     * Update last seen
     */
    public function updateLastSeen(): void
    {
        $this->last_seen_at = now();
        $this->save();
    }

    /**
     * Decrease trust score
     */
    public function decreaseTrust(float $amount): void
    {
        $this->trust_score = max(0, $this->trust_score - $amount);
        $this->save();
    }
}
