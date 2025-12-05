<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Olympiad extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    // Status constants
    public const STATUS_DRAFT = 'draft';
    public const STATUS_UPCOMING = 'upcoming';
    public const STATUS_REGISTRATION_OPEN = 'registration_open';
    public const STATUS_REGISTRATION_CLOSED = 'registration_closed';
    public const STATUS_LIVE = 'live';
    public const STATUS_GRADING = 'grading';
    public const STATUS_RESULTS_READY = 'results_ready';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    // Visibility constants
    public const VISIBILITY_PUBLIC = 'public';
    public const VISIBILITY_PRIVATE = 'private';
    public const VISIBILITY_INVITE_ONLY = 'invite_only';
    public const VISIBILITY_REGION_ONLY = 'region_only';

    // Difficulty levels
    public const DIFFICULTY_BEGINNER = 'beginner';
    public const DIFFICULTY_INTERMEDIATE = 'intermediate';
    public const DIFFICULTY_ADVANCED = 'advanced';
    public const DIFFICULTY_EXPERT = 'expert';

    protected $fillable = [
        'title',
        'slug',
        'description',
        'short_description',
        'olympiad_type_id',
        'stage_id',
        'series_id',
        'previous_olympiad_id',
        'next_olympiad_id',
        'region_id',
        'district_id',
        'school_id',
        'difficulty_level',
        'grade_levels',
        'banner_image',
        'thumbnail',
        'promo_video_url',
        'registration_start_at',
        'registration_end_at',
        'olympiad_start_at',
        'olympiad_end_at',
        'results_publish_at',
        'sections_config',
        'total_duration_minutes',
        'total_max_points',
        'entry_fee',
        'entry_fee_coins',
        'max_participants',
        'min_participants',
        'demo_enabled',
        'demo_config',
        'demo_available_from',
        'demo_available_until',
        'ranking_config',
        'advancement_enabled',
        'advancement_config',
        'reward_config',
        'prize_pool',
        'prize_distribution',
        'results_config',
        'anti_cheat_config',
        'status',
        'visibility',
        'is_featured',
        'live_leaderboard_enabled',
        'rules',
        'terms_conditions',
        'certificate_enabled',
        'certificate_template_id',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'grade_levels' => 'array',
            'sections_config' => 'array',
            'demo_config' => 'array',
            'ranking_config' => 'array',
            'advancement_config' => 'array',
            'reward_config' => 'array',
            'prize_distribution' => 'array',
            'results_config' => 'array',
            'anti_cheat_config' => 'array',
            'registration_start_at' => 'datetime',
            'registration_end_at' => 'datetime',
            'olympiad_start_at' => 'datetime',
            'olympiad_end_at' => 'datetime',
            'results_publish_at' => 'datetime',
            'demo_available_from' => 'datetime',
            'demo_available_until' => 'datetime',
            'entry_fee' => 'decimal:2',
            'prize_pool' => 'decimal:2',
            'total_duration_minutes' => 'integer',
            'total_max_points' => 'integer',
            'max_participants' => 'integer',
            'min_participants' => 'integer',
            'entry_fee_coins' => 'integer',
            'demo_enabled' => 'boolean',
            'advancement_enabled' => 'boolean',
            'is_featured' => 'boolean',
            'live_leaderboard_enabled' => 'boolean',
            'certificate_enabled' => 'boolean',
        ];
    }

    // ==================== RELATIONSHIPS ====================

    public function olympiadType(): BelongsTo
    {
        return $this->belongsTo(OlympiadType::class);
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(OlympiadStage::class, 'stage_id');
    }

    public function series(): BelongsTo
    {
        return $this->belongsTo(OlympiadSeries::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function previousOlympiad(): BelongsTo
    {
        return $this->belongsTo(Olympiad::class, 'previous_olympiad_id');
    }

    public function nextOlympiad(): BelongsTo
    {
        return $this->belongsTo(Olympiad::class, 'next_olympiad_id');
    }

    public function certificateTemplate(): BelongsTo
    {
        return $this->belongsTo(CertificateTemplate::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(OlympiadSection::class)->orderBy('order_number');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(OlympiadQuestion::class);
    }

    public function codingProblems(): HasMany
    {
        return $this->hasMany(OlympiadCodingProblem::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(OlympiadRegistration::class);
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(OlympiadAttempt::class);
    }

    public function demoAttempts(): HasMany
    {
        return $this->hasMany(DemoAttempt::class);
    }

    public function certificates(): HasMany
    {
        return $this->hasMany(OlympiadCertificate::class);
    }

    public function leaderboard(): HasMany
    {
        return $this->hasMany(OlympiadLiveLeaderboard::class);
    }

    // ==================== SCOPES ====================

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [self::STATUS_DRAFT, self::STATUS_CANCELLED]);
    }

    public function scopePublic($query)
    {
        return $query->where('visibility', self::VISIBILITY_PUBLIC);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('olympiad_start_at', '>', now());
    }

    public function scopeLive($query)
    {
        return $query->where('status', self::STATUS_LIVE);
    }

    public function scopeByType($query, string $typeId)
    {
        return $query->where('olympiad_type_id', $typeId);
    }

    public function scopeByStage($query, string $stageId)
    {
        return $query->where('stage_id', $stageId);
    }

    public function scopeRegistrationOpen($query)
    {
        return $query->where('status', self::STATUS_REGISTRATION_OPEN)
            ->where('registration_start_at', '<=', now())
            ->where('registration_end_at', '>=', now());
    }

    // ==================== ACCESSORS ====================

    public function getIsDraftAttribute(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function getIsLiveAttribute(): bool
    {
        return $this->status === self::STATUS_LIVE;
    }

    public function getIsCompletedAttribute(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function getIsRegistrationOpenAttribute(): bool
    {
        return $this->status === self::STATUS_REGISTRATION_OPEN
            && now()->between($this->registration_start_at, $this->registration_end_at);
    }

    public function getCanRegisterAttribute(): bool
    {
        if (!$this->is_registration_open) {
            return false;
        }

        if ($this->max_participants && $this->registrations()->confirmed()->count() >= $this->max_participants) {
            return false;
        }

        return true;
    }

    public function getIsFreeAttribute(): bool
    {
        return $this->entry_fee <= 0 && $this->entry_fee_coins <= 0;
    }

    public function getDemoAvailableAttribute(): bool
    {
        if (!$this->demo_enabled) {
            return false;
        }

        $now = now();
        
        if ($this->demo_available_from && $now->lt($this->demo_available_from)) {
            return false;
        }

        if ($this->demo_available_until && $now->gt($this->demo_available_until)) {
            return false;
        }

        return true;
    }

    public function getRequiresManualGradingAttribute(): bool
    {
        return collect($this->sections_config)->contains(function ($config) {
            return $config['requires_manual_grading'] ?? false;
        });
    }

    public function getFormattedPriceAttribute(): string
    {
        if ($this->is_free) {
            return __('Bepul');
        }

        $parts = [];
        if ($this->entry_fee > 0) {
            $parts[] = number_format($this->entry_fee, 0, '', ' ') . ' UZS';
        }
        if ($this->entry_fee_coins > 0) {
            $parts[] = number_format($this->entry_fee_coins) . ' COIN';
        }

        return implode(' / ', $parts);
    }

    public function getTimeUntilStartAttribute(): ?string
    {
        if ($this->olympiad_start_at->isPast()) {
            return null;
        }

        return $this->olympiad_start_at->diffForHumans();
    }

    // ==================== METHODS ====================

    /**
     * Get default anti-cheat config
     */
    public static function getDefaultAntiCheatConfig(): array
    {
        return [
            'single_device_only' => true,
            'auto_disqualify_on_multiple_device' => true,
            'device_lock_enabled' => true,
            'tab_switch_detection' => true,
            'max_tab_switches' => 3,
            'fullscreen_required' => true,
            'max_fullscreen_exits' => 2,
            'devtools_detection' => true,
            'copy_paste_disabled' => true,
            'right_click_disabled' => true,
            'heartbeat_interval_seconds' => 10,
            'missed_heartbeats_limit' => 6,
            'save_answers_on_disqualify' => true,
            'allow_appeal' => true,
            'appeal_deadline_hours' => 24,
        ];
    }

    /**
     * Get default demo config
     */
    public static function getDefaultDemoConfig(): array
    {
        return [
            'questions_count' => 15,
            'duration_minutes' => 45,
            'price_coins' => 100,
            'max_attempts' => 3,
            'free_attempts' => 1,
            'show_answers' => true,
            'show_explanations' => true,
        ];
    }

    /**
     * Get default results config
     */
    public static function getDefaultResultsConfig(): array
    {
        return [
            'show_to_participant' => true,
            'show_correct_answers' => true,
            'show_explanations' => true,
            'allow_download' => true,
        ];
    }

    /**
     * Check if user is registered
     */
    public function isUserRegistered(string $userId): bool
    {
        return $this->registrations()->where('user_id', $userId)->exists();
    }

    /**
     * Get user registration
     */
    public function getUserRegistration(string $userId): ?OlympiadRegistration
    {
        return $this->registrations()->where('user_id', $userId)->first();
    }

    /**
     * Check if user has started exam
     */
    public function hasUserStartedExam(string $userId): bool
    {
        return $this->attempts()->where('user_id', $userId)->exists();
    }

    /**
     * Get user attempt
     */
    public function getUserAttempt(string $userId): ?OlympiadAttempt
    {
        return $this->attempts()->where('user_id', $userId)->first();
    }

    /**
     * Get participant count
     */
    public function getParticipantCount(): int
    {
        return $this->registrations()->confirmed()->count();
    }

    /**
     * Update status based on current time
     */
    public function updateStatusBasedOnTime(): void
    {
        $now = now();

        if ($this->status === self::STATUS_DRAFT || $this->status === self::STATUS_CANCELLED) {
            return;
        }

        if ($now->lt($this->registration_start_at)) {
            $this->status = self::STATUS_UPCOMING;
        } elseif ($now->between($this->registration_start_at, $this->registration_end_at)) {
            $this->status = self::STATUS_REGISTRATION_OPEN;
        } elseif ($now->between($this->registration_end_at, $this->olympiad_start_at)) {
            $this->status = self::STATUS_REGISTRATION_CLOSED;
        } elseif ($now->between($this->olympiad_start_at, $this->olympiad_end_at)) {
            $this->status = self::STATUS_LIVE;
        } elseif ($now->gt($this->olympiad_end_at)) {
            if ($this->requires_manual_grading) {
                $this->status = self::STATUS_GRADING;
            } else {
                $this->status = self::STATUS_RESULTS_READY;
            }
        }

        $this->save();
    }

    /**
     * Create sections from config
     */
    public function createSectionsFromConfig(): void
    {
        if (empty($this->sections_config)) {
            return;
        }

        foreach ($this->sections_config as $sectionType => $config) {
            if (!($config['enabled'] ?? true)) {
                continue;
            }

            OlympiadSection::create([
                'olympiad_id' => $this->id,
                'section_type' => $sectionType,
                'title' => ucfirst($sectionType) . ' Section',
                'duration_minutes' => $config['duration_minutes'] ?? 30,
                'order_number' => $config['order'] ?? 1,
                'max_points' => $config['max_points'] ?? 100,
                'weight_percent' => $this->olympiadType->section_weights[$sectionType] ?? 100,
                'passing_percent' => $config['passing_percent'] ?? 60,
                'section_config' => $config,
                'requires_manual_grading' => $config['requires_manual_grading'] ?? false,
            ]);
        }
    }
}
