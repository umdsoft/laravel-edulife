<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabExperiment extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'category_id',
        'slug',
        'experiment_number',
        'title',
        'title_uz',
        'title_ru',
        'short_description',
        'short_description_uz',
        'short_description_ru',
        'description',
        'description_uz',
        'description_ru',
        'grade_level',
        'difficulty',
        'difficulty_score',
        'estimated_duration',
        'min_duration',
        'max_duration',
        'is_free',
        'is_premium',
        'required_subscription',
        'free_preview_enabled',
        'free_preview_steps',
        'objectives',
        'theory_introduction',
        'formulas',
        'important_notes',
        'required_equipment',
        'simulation_type',
        'simulation_config',
        'tasks',
        'total_points',
        'passing_points',
        'video_tutorial_url',
        'video_duration_seconds',
        'additional_resources',
        'faq',
        'xp_reward',
        'xp_reward_premium',
        'coin_reward',
        'coin_reward_premium',
        'badge_on_complete',
        'badge_on_perfect',
        'achievement_triggers',
        'thumbnail',
        'banner_image',
        'preview_gif',
        'related_lesson_id',
        'related_course_id',
        'prerequisite_labs',
        'status',
        'is_featured',
        'featured_order',
        'meta_title',
        'meta_description',
        'meta_keywords',
        'created_by',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'grade_level' => 'integer',
        'difficulty_score' => 'integer',
        'experiment_number' => 'integer',
        'estimated_duration' => 'integer',
        'min_duration' => 'integer',
        'max_duration' => 'integer',
        'is_free' => 'boolean',
        'is_premium' => 'boolean',
        'free_preview_enabled' => 'boolean',
        'free_preview_steps' => 'integer',
        'objectives' => 'array',
        'theory_introduction' => 'array',
        'formulas' => 'array',
        'important_notes' => 'array',
        'required_equipment' => 'array',
        'simulation_config' => 'array',
        'tasks' => 'array',
        'total_points' => 'integer',
        'passing_points' => 'integer',
        'video_duration_seconds' => 'integer',
        'additional_resources' => 'array',
        'faq' => 'array',
        'xp_reward' => 'integer',
        'xp_reward_premium' => 'integer',
        'coin_reward' => 'integer',
        'coin_reward_premium' => 'integer',
        'achievement_triggers' => 'array',
        'prerequisite_labs' => 'array',
        'is_featured' => 'boolean',
        'featured_order' => 'integer',
        'reviewed_at' => 'datetime',
        'total_attempts' => 'integer',
        'total_completions' => 'integer',
        'completion_rate' => 'decimal:2',
        'avg_score' => 'decimal:2',
        'avg_duration_seconds' => 'integer',
        'avg_rating' => 'decimal:2',
        'total_ratings' => 'integer',
    ];

    protected $attributes = [
        'difficulty' => 'medium',
        'difficulty_score' => 5,
        'estimated_duration' => 30,
        'is_free' => false,
        'is_premium' => true,
        'required_subscription' => 'basic',
        'free_preview_enabled' => true,
        'free_preview_steps' => 3,
        'total_points' => 100,
        'passing_points' => 60,
        'xp_reward' => 50,
        'xp_reward_premium' => 100,
        'coin_reward' => 10,
        'coin_reward_premium' => 25,
        'status' => 'draft',
        'is_featured' => false,
        'total_attempts' => 0,
        'total_completions' => 0,
    ];

    // ═══════════════════════════════════════════════════════════════════════
    // CONSTANTS
    // ═══════════════════════════════════════════════════════════════════════

    public const DIFFICULTIES = [
        'easy' => ['label' => 'Oson', 'color' => '#10B981', 'score_range' => [1, 3]],
        'medium' => ['label' => "O'rtacha", 'color' => '#F59E0B', 'score_range' => [4, 6]],
        'hard' => ['label' => 'Qiyin', 'color' => '#EF4444', 'score_range' => [7, 10]],
    ];

    public const SIMULATION_TYPES = [
        // Mechanics
        'pendulum_simple' => 'Oddiy mayatnik',
        'pendulum_spring' => 'Prujinali mayatnik',
        'projectile_motion' => 'Egri chiziqli harakat',
        'free_fall' => 'Erkin tushish',
        'inclined_plane' => 'Qiya tekislik',
        'friction' => 'Ishqalanish kuchi',
        'lever' => 'Richag',
        'pulley' => 'Blok',
        'archimedes' => 'Arximed kuchi',
        
        // Electricity
        'circuit_simple' => 'Oddiy elektr zanjiri',
        'circuit_series' => 'Ketma-ket ulash',
        'circuit_parallel' => 'Parallel ulash',
        'ohm_law' => 'Om qonuni',
        'electromagnet' => 'Elektromagnit',
        
        // Optics
        'lens_converging' => "Yig'uvchi linza",
        'lens_diverging' => 'Sochuuchi linza',
        'mirror_plane' => 'Tekis ko\'zgu',
        'mirror_curved' => 'Sferik ko\'zgu',
        'refraction' => 'Yorug\'lik sinishi',
        'prism' => 'Prizma',
        
        // Waves
        'wave_transverse' => "Ko'ndalang to'lqin",
        'wave_longitudinal' => "Bo'ylama to'lqin",
        'sound_resonance' => 'Tovush rezonansi',
        'doppler' => 'Dopler effekti',
        
        // Magnetism
        'magnetic_field' => 'Magnit maydon',
        'electromagnetic_induction' => 'Elektromagnit induksiya',
        
        // Atomic
        'atom_model' => 'Atom modeli',
        'radioactive_decay' => 'Radioaktiv yemirilish',
    ];

    public const STATUSES = [
        'draft' => ['label' => 'Qoralama', 'color' => '#9CA3AF'],
        'active' => ['label' => 'Faol', 'color' => '#10B981'],
        'archived' => ['label' => 'Arxivlangan', 'color' => '#6B7280'],
        'maintenance' => ['label' => 'Texnik xizmat', 'color' => '#F59E0B'],
    ];

    // ═══════════════════════════════════════════════════════════════════════
    // RELATIONSHIPS
    // ═══════════════════════════════════════════════════════════════════════

    public function category(): BelongsTo
    {
        return $this->belongsTo(LabCategory::class, 'category_id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(LabAttempt::class, 'experiment_id');
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(LabRating::class, 'experiment_id');
    }

    public function savedExperiments(): HasMany
    {
        return $this->hasMany(LabSavedExperiment::class, 'experiment_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(LabAssignment::class, 'experiment_id');
    }

    public function badgeOnComplete(): BelongsTo
    {
        return $this->belongsTo(LabBadge::class, 'badge_on_complete');
    }

    public function badgeOnPerfect(): BelongsTo
    {
        return $this->belongsTo(LabBadge::class, 'badge_on_perfect');
    }

    public function relatedLesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class, 'related_lesson_id');
    }

    public function relatedCourse(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'related_course_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reviewedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // SCOPES
    // ═══════════════════════════════════════════════════════════════════════

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeFree($query)
    {
        return $query->where('is_free', true);
    }

    public function scopePremium($query)
    {
        return $query->where('is_premium', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true)->orderBy('featured_order');
    }

    public function scopeForGrade($query, int $grade)
    {
        return $query->where('grade_level', $grade);
    }

    public function scopeByDifficulty($query, string $difficulty)
    {
        return $query->where('difficulty', $difficulty);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeBySimulationType($query, string $type)
    {
        return $query->where('simulation_type', $type);
    }

    public function scopeAccessibleBy($query, User $user)
    {
        // Free experiments are always accessible
        // Premium users can access all
        // Basic users can access based on subscription
        
        if ($user->hasActiveSubscription('premium')) {
            return $query;
        }
        
        if ($user->hasActiveSubscription('basic')) {
            return $query->where(function ($q) {
                $q->where('is_free', true)
                  ->orWhere('required_subscription', 'basic');
            });
        }
        
        // Free users
        return $query->where('is_free', true);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // ACCESSORS
    // ═══════════════════════════════════════════════════════════════════════

    public function getLocalizedTitleAttribute(): string
    {
        $locale = app()->getLocale();
        return $this->{"title_{$locale}"} ?? $this->title_uz ?? $this->title;
    }

    public function getLocalizedDescriptionAttribute(): ?string
    {
        $locale = app()->getLocale();
        return $this->{"description_{$locale}"} ?? $this->description_uz ?? $this->description;
    }

    public function getLocalizedShortDescriptionAttribute(): ?string
    {
        $locale = app()->getLocale();
        return $this->{"short_description_{$locale}"} ?? $this->short_description_uz ?? $this->short_description;
    }

    public function getLocalizedObjectivesAttribute(): array
    {
        $locale = app()->getLocale();
        $objectives = $this->objectives ?? [];
        return $objectives[$locale] ?? $objectives['uz'] ?? [];
    }

    public function getLocalizedTheoryAttribute(): ?string
    {
        $locale = app()->getLocale();
        $theory = $this->theory_introduction ?? [];
        return $theory[$locale] ?? $theory['uz'] ?? null;
    }

    public function getDifficultyInfoAttribute(): array
    {
        return self::DIFFICULTIES[$this->difficulty] ?? self::DIFFICULTIES['medium'];
    }

    public function getDifficultyLabelAttribute(): string
    {
        return $this->difficulty_info['label'];
    }

    public function getDifficultyColorAttribute(): string
    {
        return $this->difficulty_info['color'];
    }

    public function getSimulationTypeLabelAttribute(): string
    {
        return self::SIMULATION_TYPES[$this->simulation_type] ?? $this->simulation_type;
    }

    public function getDurationTextAttribute(): string
    {
        $duration = $this->estimated_duration;
        return $duration >= 60 
            ? sprintf('%d soat %d daqiqa', intdiv($duration, 60), $duration % 60)
            : "{$duration} daqiqa";
    }

    public function getTasksCountAttribute(): int
    {
        return count($this->tasks ?? []);
    }

    public function getFormulasCountAttribute(): int
    {
        return count($this->formulas ?? []);
    }

    public function getIsLockedForAttribute(): \Closure
    {
        return fn(User $user) => !$this->isAccessibleBy($user);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // METHODS
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Check if user can access this experiment
     */
    public function isAccessibleBy(User $user): bool
    {
        // Free experiments are always accessible
        if ($this->is_free) {
            return true;
        }
        
        // Check subscription
        if ($user->hasActiveSubscription('premium')) {
            return true;
        }
        
        if ($user->hasActiveSubscription('basic') && $this->required_subscription === 'basic') {
            return true;
        }
        
        // Check if assigned by teacher
        $assigned = LabAssignment::where('experiment_id', $this->id)
            ->where(function ($q) use ($user) {
                $q->whereJsonContains('student_ids', $user->id)
                  ->orWhereHas('class', function ($q2) use ($user) {
                      $q2->whereHas('students', fn($q3) => $q3->where('user_id', $user->id));
                  });
            })
            ->where('status', 'active')
            ->exists();
        
        return $assigned;
    }

    /**
     * Get user's progress for this experiment
     */
    public function getProgressForUser(User $user): array
    {
        $latestAttempt = LabAttempt::where('user_id', $user->id)
            ->where('experiment_id', $this->id)
            ->latest()
            ->first();
        
        $bestAttempt = LabAttempt::where('user_id', $user->id)
            ->where('experiment_id', $this->id)
            ->where('status', 'completed')
            ->orderByDesc('percentage')
            ->first();
        
        $completedCount = LabAttempt::where('user_id', $user->id)
            ->where('experiment_id', $this->id)
            ->where('status', 'completed')
            ->count();
        
        return [
            'status' => $latestAttempt?->status ?? 'not_started',
            'latest_attempt_id' => $latestAttempt?->id,
            'best_score' => $bestAttempt?->percentage ?? 0,
            'best_attempt_id' => $bestAttempt?->id,
            'total_attempts' => $completedCount,
            'can_continue' => $latestAttempt && in_array($latestAttempt->status, ['in_progress', 'paused']),
        ];
    }

    /**
     * Get XP reward for user
     */
    public function getXpRewardFor(User $user): int
    {
        if ($user->hasActiveSubscription('premium')) {
            return $this->xp_reward_premium;
        }
        return $this->xp_reward;
    }

    /**
     * Get coin reward for user
     */
    public function getCoinRewardFor(User $user): int
    {
        if ($user->hasActiveSubscription('premium')) {
            return $this->coin_reward_premium;
        }
        return $this->coin_reward;
    }

    /**
     * Update statistics
     */
    public function updateStatistics(): void
    {
        $attempts = $this->attempts()->where('status', 'completed');
        
        $this->total_attempts = $this->attempts()->count();
        $this->total_completions = $attempts->count();
        $this->completion_rate = $this->total_attempts > 0 
            ? ($this->total_completions / $this->total_attempts) * 100 
            : 0;
        $this->avg_score = $attempts->avg('percentage') ?? 0;
        $this->avg_duration_seconds = $attempts->avg('time_spent_seconds') ?? 0;
        
        $ratings = $this->ratings()->where('is_approved', true);
        $this->total_ratings = $ratings->count();
        $this->avg_rating = $ratings->avg('rating') ?? 0;
        
        $this->save();
    }

    /**
     * Get prerequisite experiments
     */
    public function getPrerequisites(): \Illuminate\Database\Eloquent\Collection
    {
        $ids = $this->prerequisite_labs ?? [];
        return self::whereIn('id', $ids)->active()->get();
    }

    /**
     * Check if user has completed prerequisites
     */
    public function hasCompletedPrerequisites(User $user): bool
    {
        $ids = $this->prerequisite_labs ?? [];
        if (empty($ids)) return true;
        
        $completed = LabAttempt::where('user_id', $user->id)
            ->whereIn('experiment_id', $ids)
            ->where('status', 'completed')
            ->distinct('experiment_id')
            ->pluck('experiment_id')
            ->toArray();
        
        return count(array_diff($ids, $completed)) === 0;
    }
}
