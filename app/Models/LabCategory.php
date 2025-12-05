<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabCategory extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'slug',
        'name',
        'name_uz',
        'name_ru',
        'description',
        'description_uz',
        'description_ru',
        'grade_levels',
        'min_grade',
        'max_grade',
        'icon',
        'icon_svg',
        'color',
        'gradient',
        'banner_image',
        'thumbnail',
        'order_number',
        'is_active',
        'is_featured',
        'meta_title',
        'meta_description',
    ];

    protected $casts = [
        'grade_levels' => 'array',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'total_experiments' => 'integer',
        'free_experiments' => 'integer',
        'total_completions' => 'integer',
        'avg_rating' => 'decimal:2',
        'total_ratings' => 'integer',
        'order_number' => 'integer',
    ];

    protected $attributes = [
        'is_active' => true,
        'is_featured' => false,
        'order_number' => 0,
        'total_experiments' => 0,
        'free_experiments' => 0,
        'total_completions' => 0,
        'avg_rating' => 0,
        'total_ratings' => 0,
    ];

    // ═══════════════════════════════════════════════════════════════════════
    // RELATIONSHIPS
    // ═══════════════════════════════════════════════════════════════════════

    public function experiments(): HasMany
    {
        return $this->hasMany(LabExperiment::class, 'category_id');
    }

    public function activeExperiments(): HasMany
    {
        return $this->hasMany(LabExperiment::class, 'category_id')
            ->where('status', 'active');
    }

    public function freeExperiments(): HasMany
    {
        return $this->hasMany(LabExperiment::class, 'category_id')
            ->where('is_free', true)
            ->where('status', 'active');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // SCOPES
    // ═══════════════════════════════════════════════════════════════════════

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeForGrade($query, int $grade)
    {
        return $query->whereJsonContains('grade_levels', $grade);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order_number')->orderBy('name_uz');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // ACCESSORS
    // ═══════════════════════════════════════════════════════════════════════

    public function getLocalizedNameAttribute(): string
    {
        $locale = app()->getLocale();
        return $this->{"name_{$locale}"} ?? $this->name_uz ?? $this->name;
    }

    public function getLocalizedDescriptionAttribute(): ?string
    {
        $locale = app()->getLocale();
        return $this->{"description_{$locale}"} ?? $this->description_uz ?? $this->description;
    }

    public function getGradeLevelsTextAttribute(): string
    {
        $levels = $this->grade_levels ?? [];
        if (empty($levels)) return '';
        
        $min = min($levels);
        $max = max($levels);
        
        return $min === $max ? "{$min}-sinf" : "{$min}-{$max}-sinf";
    }

    public function getCompletionPercentAttribute(): float
    {
        if ($this->total_experiments === 0) return 0;
        return round(($this->total_completions / $this->total_experiments) * 100, 1);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // METHODS
    // ═══════════════════════════════════════════════════════════════════════

    public function updateStatistics(): void
    {
        $this->total_experiments = $this->experiments()->where('status', 'active')->count();
        $this->free_experiments = $this->experiments()
            ->where('status', 'active')
            ->where('is_free', true)
            ->count();
        
        $this->total_completions = LabAttempt::whereHas('experiment', function ($q) {
            $q->where('category_id', $this->id);
        })->where('status', 'completed')->count();
        
        $ratings = LabRating::whereHas('experiment', function ($q) {
            $q->where('category_id', $this->id);
        });
        
        $this->total_ratings = $ratings->count();
        $this->avg_rating = $ratings->avg('rating') ?? 0;
        
        $this->save();
    }

    public function getProgressForUser(User $user): array
    {
        $experiments = $this->activeExperiments()->pluck('id');
        
        $completed = LabAttempt::where('user_id', $user->id)
            ->whereIn('experiment_id', $experiments)
            ->where('status', 'completed')
            ->distinct('experiment_id')
            ->count('experiment_id');
        
        $inProgress = LabAttempt::where('user_id', $user->id)
            ->whereIn('experiment_id', $experiments)
            ->where('status', 'in_progress')
            ->distinct('experiment_id')
            ->count('experiment_id');
        
        return [
            'total' => $experiments->count(),
            'completed' => $completed,
            'in_progress' => $inProgress,
            'locked' => $experiments->count() - $completed - $inProgress,
            'percentage' => $experiments->count() > 0 
                ? round(($completed / $experiments->count()) * 100, 1) 
                : 0,
        ];
    }
}
