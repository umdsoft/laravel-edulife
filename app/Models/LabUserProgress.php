<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class LabUserProgress extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'lab_user_progress';

    protected $fillable = [
        'user_id',
        'total_experiments',
        'completed_experiments',
        'perfect_scores',
        'total_attempts',
        'total_time_spent_seconds',
        'overall_avg_score',
        'overall_completion_rate',
        'category_progress',
        'grade_progress',
        'best_experiments',
        'skills',
        'total_xp',
        'lab_level',
        'level_xp',
        'next_level_xp',
        'total_coins_earned',
        'badges_earned',
        'achievements_unlocked',
        'current_streak',
        'longest_streak',
        'last_lab_date',
        'weekly_labs_completed',
        'weekly_xp_earned',
        'weekly_reset_at',
        'preferred_language',
        'show_hints',
        'show_formulas',
        'auto_save',
        'sound_enabled',
        'show_tutorial',
    ];

    protected $casts = [
        'total_experiments' => 'integer',
        'completed_experiments' => 'integer',
        'perfect_scores' => 'integer',
        'total_attempts' => 'integer',
        'total_time_spent_seconds' => 'integer',
        'overall_avg_score' => 'decimal:2',
        'overall_completion_rate' => 'decimal:2',
        'category_progress' => 'array',
        'grade_progress' => 'array',
        'best_experiments' => 'array',
        'skills' => 'array',
        'total_xp' => 'integer',
        'lab_level' => 'integer',
        'level_xp' => 'integer',
        'next_level_xp' => 'integer',
        'total_coins_earned' => 'integer',
        'badges_earned' => 'array',
        'achievements_unlocked' => 'array',
        'current_streak' => 'integer',
        'longest_streak' => 'integer',
        'last_lab_date' => 'date',
        'weekly_labs_completed' => 'integer',
        'weekly_xp_earned' => 'integer',
        'weekly_reset_at' => 'datetime',
        'show_hints' => 'boolean',
        'show_formulas' => 'boolean',
        'auto_save' => 'boolean',
        'sound_enabled' => 'boolean',
        'show_tutorial' => 'boolean',
    ];

    protected $attributes = [
        'total_experiments' => 0,
        'completed_experiments' => 0,
        'perfect_scores' => 0,
        'total_attempts' => 0,
        'total_time_spent_seconds' => 0,
        'overall_avg_score' => 0,
        'overall_completion_rate' => 0,
        'total_xp' => 0,
        'lab_level' => 1,
        'level_xp' => 0,
        'next_level_xp' => 100,
        'total_coins_earned' => 0,
        'current_streak' => 0,
        'longest_streak' => 0,
        'weekly_labs_completed' => 0,
        'weekly_xp_earned' => 0,
        'preferred_language' => 'uz',
        'show_hints' => true,
        'show_formulas' => true,
        'auto_save' => true,
        'sound_enabled' => true,
        'show_tutorial' => true,
    ];

    // ═══════════════════════════════════════════════════════════════════════
    // CONSTANTS
    // ═══════════════════════════════════════════════════════════════════════

    // XP needed for each level
    public const LEVEL_XP_REQUIREMENTS = [
        1 => 0,
        2 => 100,
        3 => 250,
        4 => 500,
        5 => 850,
        6 => 1300,
        7 => 1850,
        8 => 2500,
        9 => 3250,
        10 => 4100,
        11 => 5050,
        12 => 6100,
        13 => 7250,
        14 => 8500,
        15 => 9850,
        16 => 11300,
        17 => 12850,
        18 => 14500,
        19 => 16250,
        20 => 18100,
    ];

    public const SKILLS = [
        'measurement_accuracy' => [
            'name' => "O'lchov aniqligi",
            'description' => "O'lchov asboblaridan to'g'ri foydalanish",
            'icon' => 'chart-bar',
        ],
        'calculation_speed' => [
            'name' => 'Hisoblash tezligi',
            'description' => "Formulalarni tez va to'g'ri qo'llash",
            'icon' => 'calculator',
        ],
        'graph_analysis' => [
            'name' => 'Grafik tahlili',
            'description' => "Ma'lumotlarni vizuallashtirish",
            'icon' => 'presentation-chart-line',
        ],
        'report_writing' => [
            'name' => 'Hisobot yozish',
            'description' => 'Ilmiy hisobot tayyorlash',
            'icon' => 'document-text',
        ],
        'experiment_design' => [
            'name' => 'Tajriba loyihalash',
            'description' => 'Tajribani mustaqil rejalashtirish',
            'icon' => 'beaker',
        ],
    ];

    public const LEVEL_TITLES = [
        1 => 'Boshlang\'ich',
        2 => 'O\'rganuvchi',
        3 => 'Izlanuvchi',
        4 => 'Amaliyotchi',
        5 => 'Tajribakor',
        6 => 'Mohir',
        7 => 'Usta',
        8 => 'Ekspert',
        9 => 'Mutaxassis',
        10 => 'Professor',
        11 => 'Akademik',
        12 => 'Olim',
        13 => 'Kashfiyotchi',
        14 => 'Innovator',
        15 => 'Virtuoz',
        16 => 'Legenda',
        17 => 'Grandmaster',
        18 => 'Dahosiy',
        19 => 'Fenomenal',
        20 => 'Afsonaviy',
    ];

    // ═══════════════════════════════════════════════════════════════════════
    // RELATIONSHIPS
    // ═══════════════════════════════════════════════════════════════════════

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // ACCESSORS
    // ═══════════════════════════════════════════════════════════════════════

    public function getLevelTitleAttribute(): string
    {
        return self::LEVEL_TITLES[$this->lab_level] ?? self::LEVEL_TITLES[1];
    }

    public function getLevelProgressPercentAttribute(): float
    {
        if ($this->next_level_xp <= $this->level_xp) return 100;
        
        $currentLevelXp = self::LEVEL_XP_REQUIREMENTS[$this->lab_level] ?? 0;
        $nextLevelXp = self::LEVEL_XP_REQUIREMENTS[$this->lab_level + 1] ?? $this->next_level_xp;
        
        $needed = $nextLevelXp - $currentLevelXp;
        $progress = $this->total_xp - $currentLevelXp;
        
        return $needed > 0 ? round(($progress / $needed) * 100, 1) : 0;
    }

    public function getTotalTimeTextAttribute(): string
    {
        $seconds = $this->total_time_spent_seconds;
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        
        if ($hours > 0) {
            return "{$hours} soat {$minutes} daqiqa";
        }
        
        return "{$minutes} daqiqa";
    }

    public function getBadgesCountAttribute(): int
    {
        return count($this->badges_earned ?? []);
    }

    public function getIsNewLabberAttribute(): bool
    {
        return $this->completed_experiments < 3;
    }

    // ═══════════════════════════════════════════════════════════════════════
    // METHODS
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Add XP and check for level up
     */
    public function addXp(int $amount): array
    {
        $leveledUp = false;
        $newLevel = $this->lab_level;
        
        $this->total_xp += $amount;
        $this->level_xp += $amount;
        $this->weekly_xp_earned += $amount;
        
        // Check for level up
        while ($this->level_xp >= $this->next_level_xp && $this->lab_level < 20) {
            $this->level_xp -= $this->next_level_xp;
            $this->lab_level += 1;
            $this->next_level_xp = self::LEVEL_XP_REQUIREMENTS[$this->lab_level + 1] ?? 
                                   ($this->next_level_xp * 1.2);
            $leveledUp = true;
            $newLevel = $this->lab_level;
        }
        
        $this->save();
        
        return [
            'leveled_up' => $leveledUp,
            'new_level' => $newLevel,
            'level_title' => self::LEVEL_TITLES[$newLevel] ?? '',
            'total_xp' => $this->total_xp,
            'level_progress' => $this->level_progress_percent,
        ];
    }

    /**
     * Update streak
     */
    public function updateStreak(): void
    {
        $today = now()->toDateString();
        $lastLab = $this->last_lab_date?->toDateString();
        
        if ($lastLab === $today) {
            // Already did lab today
            return;
        }
        
        $yesterday = now()->subDay()->toDateString();
        
        if ($lastLab === $yesterday) {
            // Continue streak
            $this->current_streak += 1;
        } elseif ($lastLab !== null) {
            // Streak broken
            $this->current_streak = 1;
        } else {
            // First lab
            $this->current_streak = 1;
        }
        
        if ($this->current_streak > $this->longest_streak) {
            $this->longest_streak = $this->current_streak;
        }
        
        $this->last_lab_date = $today;
        $this->save();
    }

    /**
     * Update category progress
     */
    public function updateCategoryProgress(string $categorySlug, array $data): void
    {
        $progress = $this->category_progress ?? [];
        $progress[$categorySlug] = array_merge(
            $progress[$categorySlug] ?? [],
            $data
        );
        $this->category_progress = $progress;
        $this->save();
    }

    /**
     * Add skill XP
     */
    public function addSkillXp(string $skill, int $amount): array
    {
        $skills = $this->skills ?? [];
        
        if (!isset($skills[$skill])) {
            $skills[$skill] = ['level' => 1, 'xp' => 0, 'next_level_xp' => 100];
        }
        
        $skills[$skill]['xp'] += $amount;
        
        // Check for skill level up
        $leveledUp = false;
        while ($skills[$skill]['xp'] >= $skills[$skill]['next_level_xp']) {
            $skills[$skill]['xp'] -= $skills[$skill]['next_level_xp'];
            $skills[$skill]['level'] += 1;
            $skills[$skill]['next_level_xp'] = intval($skills[$skill]['next_level_xp'] * 1.3);
            $leveledUp = true;
        }
        
        $this->skills = $skills;
        $this->save();
        
        return [
            'skill' => $skill,
            'leveled_up' => $leveledUp,
            'new_level' => $skills[$skill]['level'],
            'xp' => $skills[$skill]['xp'],
        ];
    }

    /**
     * Add to best experiments
     */
    public function addToBestExperiments(LabAttempt $attempt): void
    {
        $best = $this->best_experiments ?? [];
        
        // Add new entry
        $entry = [
            'experiment_id' => $attempt->experiment_id,
            'experiment_title' => $attempt->experiment->localized_title,
            'score' => $attempt->raw_score,
            'percentage' => $attempt->percentage,
            'completed_at' => $attempt->completed_at->toIso8601String(),
            'attempt_id' => $attempt->id,
        ];
        
        // Check if already in list
        $existingIndex = null;
        foreach ($best as $i => $item) {
            if ($item['experiment_id'] === $attempt->experiment_id) {
                // Only replace if better score
                if ($item['percentage'] >= $attempt->percentage) {
                    return;
                }
                $existingIndex = $i;
                break;
            }
        }
        
        if ($existingIndex !== null) {
            $best[$existingIndex] = $entry;
        } else {
            $best[] = $entry;
        }
        
        // Sort by percentage and keep top 10
        usort($best, fn($a, $b) => $b['percentage'] <=> $a['percentage']);
        $best = array_slice($best, 0, 10);
        
        $this->best_experiments = $best;
        $this->save();
    }

    /**
     * Reset weekly stats
     */
    public function resetWeeklyStats(): void
    {
        $this->weekly_labs_completed = 0;
        $this->weekly_xp_earned = 0;
        $this->weekly_reset_at = now();
        $this->save();
    }

    /**
     * Record completed experiment
     */
    public function recordCompletedExperiment(LabAttempt $attempt): void
    {
        $this->completed_experiments += 1;
        $this->total_attempts += 1;
        $this->total_time_spent_seconds += $attempt->time_spent_seconds;
        $this->weekly_labs_completed += 1;
        
        if ($attempt->percentage >= 100) {
            $this->perfect_scores += 1;
        }
        
        // Recalculate averages
        $this->recalculateAverages();
        
        // Update streak
        $this->updateStreak();
        
        // Add to best experiments
        $this->addToBestExperiments($attempt);
        
        // Update category progress
        $category = $attempt->experiment->category;
        $categoryProgress = $this->category_progress[$category->slug] ?? [
            'total' => 0,
            'completed' => 0,
            'avg_score' => 0,
            'total_time_seconds' => 0,
        ];
        
        $categoryProgress['completed'] += 1;
        $categoryProgress['total_time_seconds'] += $attempt->time_spent_seconds;
        
        $this->updateCategoryProgress($category->slug, $categoryProgress);
        
        $this->save();
    }

    /**
     * Recalculate average scores
     */
    protected function recalculateAverages(): void
    {
        $attempts = LabAttempt::where('user_id', $this->user_id)
            ->where('status', 'completed')
            ->get();
        
        if ($attempts->isEmpty()) {
            $this->overall_avg_score = 0;
            $this->overall_completion_rate = 0;
            return;
        }
        
        $this->overall_avg_score = $attempts->avg('percentage');
        
        $totalExperiments = LabExperiment::active()->count();
        $completed = $attempts->unique('experiment_id')->count();
        
        $this->overall_completion_rate = $totalExperiments > 0 
            ? ($completed / $totalExperiments) * 100 
            : 0;
    }

    /**
     * Get earned badges as models
     */
    public function getEarnedBadges(): \Illuminate\Database\Eloquent\Collection
    {
        $ids = $this->badges_earned ?? [];
        return LabBadge::whereIn('id', $ids)->ordered()->get();
    }

    /**
     * Check and award any earned badges
     */
    public function checkAndAwardBadges(): array
    {
        $awardedBadges = [];
        $earnedIds = $this->badges_earned ?? [];
        
        $badges = LabBadge::active()
            ->whereNotIn('id', $earnedIds)
            ->get();
        
        foreach ($badges as $badge) {
            if ($badge->checkEarnedBy($this->user)) {
                if ($badge->awardTo($this->user)) {
                    $awardedBadges[] = $badge;
                }
            }
        }
        
        return $awardedBadges;
    }

    /**
     * Get dashboard stats
     */
    public function getDashboardStats(): array
    {
        return [
            'level' => $this->lab_level,
            'level_title' => $this->level_title,
            'total_xp' => $this->total_xp,
            'level_progress' => $this->level_progress_percent,
            'completed_experiments' => $this->completed_experiments,
            'perfect_scores' => $this->perfect_scores,
            'avg_score' => round($this->overall_avg_score, 1),
            'current_streak' => $this->current_streak,
            'longest_streak' => $this->longest_streak,
            'badges_count' => $this->badges_count,
            'total_time' => $this->total_time_text,
            'weekly_labs' => $this->weekly_labs_completed,
            'weekly_xp' => $this->weekly_xp_earned,
        ];
    }
}
