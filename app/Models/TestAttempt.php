<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TestAttempt extends Model
{
    use HasUuids;
    
    protected $fillable = [
        'user_id',
        'test_id',
        'course_id',
        'enrollment_id',
        'attempt_number',
        'status',
        'started_at',
        'submitted_at',
        'expires_at',
        'time_spent',
        'total_questions',
        'answered_questions',
        'correct_answers',
        'wrong_answers',
        'skipped_questions',
        'score',
        'is_passed',
        'tab_switches',
        'fullscreen_exits',
        'is_flagged',
        'flag_reason',
        'xp_awarded',
        'xp_amount',
    ];
    
    protected $casts = [
        'attempt_number' => 'integer',
        'started_at' => 'datetime',
        'submitted_at' => 'datetime',
        'expires_at' => 'datetime',
        'time_spent' => 'integer',
        'total_questions' => 'integer',
        'answered_questions' => 'integer',
        'correct_answers' => 'integer',
        'wrong_answers' => 'integer',
        'skipped_questions' => 'integer',
        'score' => 'decimal:2',
        'is_passed' => 'boolean',
        'tab_switches' => 'integer',
        'fullscreen_exits' => 'integer',
        'is_flagged' => 'boolean',
        'xp_awarded' => 'boolean',
        'xp_amount' => 'integer',
    ];
    
    protected $appends = ['time_remaining', 'is_expired', 'formatted_time_spent'];
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function test(): BelongsTo
    {
        return $this->belongsTo(Test::class);
    }
    
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
    
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }
    
    public function answers(): HasMany
    {
        return $this->hasMany(TestAnswer::class, 'attempt_id');
    }
    
    public function logs(): HasMany
    {
        return $this->hasMany(TestAttemptLog::class, 'attempt_id');
    }
    
    /**
     * Qolgan vaqt (sekundlarda)
     */
    public function getTimeRemainingAttribute(): int
    {
        if ($this->status !== 'in_progress') {
            return 0;
        }
        
        $remaining = $this->expires_at->diffInSeconds(now(), false);
        return max(0, -$remaining);
    }
    
    /**
     * Vaqt tugadimi?
     */
    public function getIsExpiredAttribute(): bool
    {
        return $this->expires_at->isPast();
    }
    
    /**
     * Formatlangan vaqt
     */
    public function getFormattedTimeSpentAttribute(): string
    {
        $minutes = floor($this->time_spent / 60);
        $seconds = $this->time_spent % 60;
        return sprintf('%02d:%02d', $minutes, $seconds);
    }
    
    /**
     * Testni expired qilish
     */
    public function markAsExpired(): void
    {
        $this->update([
            'status' => 'expired',
            'submitted_at' => now(),
        ]);
    }
    
    /**
     * Progress hisoblash
     */
    public function calculateProgress(): int
    {
        if ($this->total_questions === 0) {
            return 0;
        }
        
        return (int) round(($this->answered_questions / $this->total_questions) * 100);
    }
    
    /**
     * Anti-cheat event qo'shish
     */
    public function logEvent(string $eventType, array $data = []): void
    {
        $this->logs()->create([
            'event_type' => $eventType,
            'event_data' => $data,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'occurred_at' => now(),
        ]);
        
        // Update counters
        if ($eventType === 'tab_switch') {
            $this->increment('tab_switches');
            $this->checkAntiCheatThreshold();
        } elseif ($eventType === 'fullscreen_exit') {
            $this->increment('fullscreen_exits');
            $this->checkAntiCheatThreshold();
        }
    }
    
    /**
     * Anti-cheat threshold tekshirish
     */
    private function checkAntiCheatThreshold(): void
    {
        // 5 ta tab switch yoki 3 ta fullscreen exit = flagged
        if ($this->tab_switches >= 5 || $this->fullscreen_exits >= 3) {
            $this->update([
                'is_flagged' => true,
                'flag_reason' => 'Suspicious activity detected: ' . 
                    $this->tab_switches . ' tab switches, ' . 
                    $this->fullscreen_exits . ' fullscreen exits',
            ]);
        }
    }
}
