<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabRating extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'experiment_id',
        'attempt_id',
        'rating',
        'review_text',
        'liked_aspects',
        'disliked_aspects',
        'difficulty_rating',
        'would_recommend',
        'is_approved',
        'is_featured',
        'is_hidden',
        'helpful_count',
        'not_helpful_count',
        'admin_response',
        'admin_responded_at',
    ];

    protected $casts = [
        'rating' => 'integer',
        'liked_aspects' => 'array',
        'disliked_aspects' => 'array',
        'would_recommend' => 'boolean',
        'is_approved' => 'boolean',
        'is_featured' => 'boolean',
        'is_hidden' => 'boolean',
        'helpful_count' => 'integer',
        'not_helpful_count' => 'integer',
        'admin_responded_at' => 'datetime',
    ];

    protected $attributes = [
        'is_approved' => false,
        'is_featured' => false,
        'is_hidden' => false,
        'helpful_count' => 0,
        'not_helpful_count' => 0,
    ];

    // ═══════════════════════════════════════════════════════════════════════
    // CONSTANTS
    // ═══════════════════════════════════════════════════════════════════════

    public const LIKED_ASPECTS = [
        'clear_instructions' => "Aniq ko'rsatmalar",
        'realistic_simulation' => 'Real simulyatsiya',
        'good_visuals' => "Yaxshi vizuallar",
        'educational' => "O'rganishga foydali",
        'interactive' => 'Interaktiv',
        'fun' => 'Qiziqarli',
        'challenging' => 'Qiyinchiliksiz o\'tdi',
    ];

    public const DISLIKED_ASPECTS = [
        'too_long' => 'Juda uzun',
        'confusing' => 'Chalkash',
        'buggy' => "Xatolar bor",
        'too_easy' => 'Juda oson',
        'too_hard' => 'Juda qiyin',
        'boring' => 'Zerikarli',
        'slow_loading' => 'Sekin yuklanadi',
    ];

    public const DIFFICULTY_RATINGS = [
        'too_easy' => 'Juda oson',
        'just_right' => "O'rtacha (yaxshi)",
        'too_hard' => 'Juda qiyin',
    ];

    // ═══════════════════════════════════════════════════════════════════════
    // RELATIONSHIPS
    // ═══════════════════════════════════════════════════════════════════════

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function experiment(): BelongsTo
    {
        return $this->belongsTo(LabExperiment::class, 'experiment_id');
    }

    public function attempt(): BelongsTo
    {
        return $this->belongsTo(LabAttempt::class, 'attempt_id');
    }

    // ═══════════════════════════════════════════════════════════════════════
    // SCOPES
    // ═══════════════════════════════════════════════════════════════════════

    public function scopeApproved($query)
    {
        return $query->where('is_approved', true)->where('is_hidden', false);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true)->approved();
    }

    public function scopeByExperiment($query, $experimentId)
    {
        return $query->where('experiment_id', $experimentId);
    }

    public function scopeByRating($query, int $rating)
    {
        return $query->where('rating', $rating);
    }

    public function scopePositive($query)
    {
        return $query->where('rating', '>=', 4);
    }

    public function scopeNegative($query)
    {
        return $query->where('rating', '<=', 2);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // ACCESSORS
    // ═══════════════════════════════════════════════════════════════════════

    public function getRatingStarsAttribute(): string
    {
        return str_repeat('★', $this->rating) . str_repeat('☆', 5 - $this->rating);
    }

    public function getLikedAspectsLabelsAttribute(): array
    {
        $aspects = $this->liked_aspects ?? [];
        return array_map(
            fn($key) => self::LIKED_ASPECTS[$key] ?? $key,
            $aspects
        );
    }

    public function getDislikedAspectsLabelsAttribute(): array
    {
        $aspects = $this->disliked_aspects ?? [];
        return array_map(
            fn($key) => self::DISLIKED_ASPECTS[$key] ?? $key,
            $aspects
        );
    }

    public function getDifficultyRatingLabelAttribute(): ?string
    {
        if (!$this->difficulty_rating) return null;
        return self::DIFFICULTY_RATINGS[$this->difficulty_rating] ?? $this->difficulty_rating;
    }

    public function getHelpfulPercentAttribute(): float
    {
        $total = $this->helpful_count + $this->not_helpful_count;
        if ($total === 0) return 0;
        return round(($this->helpful_count / $total) * 100, 1);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // METHODS
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Approve the rating
     */
    public function approve(): self
    {
        $this->is_approved = true;
        $this->save();
        
        // Update experiment statistics
        $this->experiment->updateStatistics();
        
        return $this;
    }

    /**
     * Feature the rating
     */
    public function feature(): self
    {
        $this->is_featured = true;
        $this->save();
        
        return $this;
    }

    /**
     * Hide the rating
     */
    public function hide(): self
    {
        $this->is_hidden = true;
        $this->is_featured = false;
        $this->save();
        
        return $this;
    }

    /**
     * Mark as helpful by a user
     */
    public function markHelpful(): self
    {
        $this->increment('helpful_count');
        return $this;
    }

    /**
     * Mark as not helpful by a user
     */
    public function markNotHelpful(): self
    {
        $this->increment('not_helpful_count');
        return $this;
    }

    /**
     * Add admin response
     */
    public function addAdminResponse(string $response): self
    {
        $this->admin_response = $response;
        $this->admin_responded_at = now();
        $this->save();
        
        return $this;
    }

    /**
     * Export for display
     */
    public function toDisplayData(): array
    {
        return [
            'id' => $this->id,
            'rating' => $this->rating,
            'stars' => $this->rating_stars,
            'review' => $this->review_text,
            'liked' => $this->liked_aspects_labels,
            'disliked' => $this->disliked_aspects_labels,
            'difficulty' => $this->difficulty_rating_label,
            'would_recommend' => $this->would_recommend,
            'helpful_count' => $this->helpful_count,
            'helpful_percent' => $this->helpful_percent,
            'is_featured' => $this->is_featured,
            'user' => [
                'id' => $this->user_id,
                'name' => $this->user->name,
                'avatar' => $this->user->avatar_url ?? null,
            ],
            'created_at' => $this->created_at->diffForHumans(),
            'admin_response' => $this->admin_response,
        ];
    }
}
