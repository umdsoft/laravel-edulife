<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SecurityViolation extends Model
{
    use HasFactory, HasUuids;

    // Violation type constants
    public const TYPE_MULTIPLE_DEVICE = 'multiple_device';
    public const TYPE_TAB_SWITCH = 'tab_switch';
    public const TYPE_FULLSCREEN_EXIT = 'fullscreen_exit';
    public const TYPE_DEVTOOLS_OPEN = 'devtools_open';
    public const TYPE_COPY_PASTE = 'copy_paste';
    public const TYPE_RIGHT_CLICK = 'right_click';
    public const TYPE_SCREENSHOT = 'screenshot';
    public const TYPE_HEARTBEAT_MISS = 'heartbeat_miss';
    public const TYPE_SUSPICIOUS_PATTERN = 'suspicious_answer_pattern';
    public const TYPE_VPN_DETECTED = 'vpn_detected';
    public const TYPE_IP_MISMATCH = 'ip_mismatch';

    // Severity constants
    public const SEVERITY_WARNING = 'warning';
    public const SEVERITY_MEDIUM = 'medium';
    public const SEVERITY_HIGH = 'high';
    public const SEVERITY_CRITICAL = 'critical';

    // Action constants
    public const ACTION_WARNING_SENT = 'warning_sent';
    public const ACTION_EXAM_PAUSED = 'exam_paused';
    public const ACTION_DISQUALIFIED = 'disqualified';
    public const ACTION_IGNORED = 'ignored';

    protected $fillable = [
        'olympiad_id',
        'user_id',
        'attempt_id',
        'device_id',
        'violation_type',
        'occurrence_count',
        'severity',
        'action_taken',
        'details',
        'evidence',
        'ip_address',
        'user_agent',
        'is_resolved',
        'resolved_at',
        'resolved_by',
        'resolution_notes',
    ];

    protected function casts(): array
    {
        return [
            'occurrence_count' => 'integer',
            'evidence' => 'array',
            'is_resolved' => 'boolean',
            'resolved_at' => 'datetime',
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

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(OlympiadAttempt::class, 'attempt_id');
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(UserDevice::class, 'device_id');
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function appeal(): HasOne
    {
        return $this->hasOne(ViolationAppeal::class, 'violation_id');
    }

    // ==================== SCOPES ====================

    public function scopeUnresolved($query)
    {
        return $query->where('is_resolved', false);
    }

    public function scopeCritical($query)
    {
        return $query->where('severity', self::SEVERITY_CRITICAL);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('violation_type', $type);
    }

    // ==================== ACCESSORS ====================

    public function getIsCriticalAttribute(): bool
    {
        return $this->severity === self::SEVERITY_CRITICAL;
    }

    public function getCanAppealAttribute(): bool
    {
        return $this->action_taken === self::ACTION_DISQUALIFIED 
            && !$this->appeal 
            && now()->lt($this->created_at->addHours(24));
    }

    public function getSeverityColorAttribute(): string
    {
        $colors = [
            self::SEVERITY_WARNING => 'yellow',
            self::SEVERITY_MEDIUM => 'orange',
            self::SEVERITY_HIGH => 'red',
            self::SEVERITY_CRITICAL => 'purple',
        ];
        return $colors[$this->severity] ?? 'gray';
    }

    public function getViolationLabelAttribute(): string
    {
        $labels = [
            self::TYPE_MULTIPLE_DEVICE => 'Bir nechta qurilma',
            self::TYPE_TAB_SWITCH => "Tab o'zgartirish",
            self::TYPE_FULLSCREEN_EXIT => "To'liq ekrandan chiqish",
            self::TYPE_DEVTOOLS_OPEN => 'DevTools ochildi',
            self::TYPE_COPY_PASTE => 'Nusxalash/Joylashtirish',
            self::TYPE_RIGHT_CLICK => "O'ng tugma bosildi",
            self::TYPE_SCREENSHOT => 'Skrinshot',
            self::TYPE_HEARTBEAT_MISS => "Heartbeat o'tkazib yuborildi",
            self::TYPE_SUSPICIOUS_PATTERN => 'Shubhali javob namunasi',
            self::TYPE_VPN_DETECTED => 'VPN aniqlandi',
            self::TYPE_IP_MISMATCH => "IP nomuvofiqlik",
        ];
        return $labels[$this->violation_type] ?? $this->violation_type;
    }

    // ==================== METHODS ====================

    /**
     * Resolve the violation
     */
    public function resolve(string $userId, string $notes = null): void
    {
        $this->is_resolved = true;
        $this->resolved_at = now();
        $this->resolved_by = $userId;
        $this->resolution_notes = $notes;
        $this->save();
    }

    /**
     * Increment occurrence count
     */
    public function incrementCount(): void
    {
        $this->occurrence_count++;
        $this->save();
    }

    /**
     * Determine severity based on violation type and count
     */
    public static function determineSeverity(string $type, int $count): string
    {
        $criticalTypes = [self::TYPE_MULTIPLE_DEVICE, self::TYPE_VPN_DETECTED];
        
        if (in_array($type, $criticalTypes)) {
            return self::SEVERITY_CRITICAL;
        }
        
        if ($count >= 5) {
            return self::SEVERITY_HIGH;
        } elseif ($count >= 3) {
            return self::SEVERITY_MEDIUM;
        }
        
        return self::SEVERITY_WARNING;
    }
}
