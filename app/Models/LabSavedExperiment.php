<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class LabSavedExperiment extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'experiment_id',
        'saved_state',
        'name',
        'description',
        'thumbnail',
        'is_public',
        'share_code',
        'view_count',
        'copy_count',
        'like_count',
        'tags',
    ];

    protected $casts = [
        'saved_state' => 'array',
        'is_public' => 'boolean',
        'view_count' => 'integer',
        'copy_count' => 'integer',
        'like_count' => 'integer',
        'tags' => 'array',
    ];

    protected $attributes = [
        'is_public' => false,
        'view_count' => 0,
        'copy_count' => 0,
        'like_count' => 0,
    ];

    // ═══════════════════════════════════════════════════════════════════════
    // BOOT
    // ═══════════════════════════════════════════════════════════════════════

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($model) {
            if ($model->is_public && !$model->share_code) {
                $model->share_code = self::generateShareCode();
            }
        });
    }

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

    // ═══════════════════════════════════════════════════════════════════════
    // SCOPES
    // ═══════════════════════════════════════════════════════════════════════

    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByExperiment($query, $experimentId)
    {
        return $query->where('experiment_id', $experimentId);
    }

    public function scopePopular($query)
    {
        return $query->public()
            ->orderByDesc('like_count')
            ->orderByDesc('copy_count');
    }

    public function scopeRecent($query)
    {
        return $query->orderByDesc('created_at');
    }

    public function scopeWithTag($query, string $tag)
    {
        return $query->whereJsonContains('tags', $tag);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // ACCESSORS
    // ═══════════════════════════════════════════════════════════════════════

    public function getShareUrlAttribute(): ?string
    {
        if (!$this->share_code) return null;
        return route('lab.shared', $this->share_code);
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->name ?? $this->experiment->localized_title;
    }

    public function getParametersAttribute(): array
    {
        return $this->saved_state['parameters'] ?? [];
    }

    public function getMeasurementsAttribute(): array
    {
        return $this->saved_state['measurements'] ?? [];
    }

    // ═══════════════════════════════════════════════════════════════════════
    // METHODS
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Generate unique share code
     */
    public static function generateShareCode(): string
    {
        do {
            $code = Str::upper(Str::random(8));
        } while (self::where('share_code', $code)->exists());
        
        return $code;
    }

    /**
     * Make the saved experiment public
     */
    public function makePublic(): self
    {
        $this->is_public = true;
        $this->share_code = $this->share_code ?? self::generateShareCode();
        $this->save();
        
        return $this;
    }

    /**
     * Make the saved experiment private
     */
    public function makePrivate(): self
    {
        $this->is_public = false;
        $this->save();
        
        return $this;
    }

    /**
     * Record a view
     */
    public function recordView(): self
    {
        $this->increment('view_count');
        return $this;
    }

    /**
     * Record a copy
     */
    public function recordCopy(): self
    {
        $this->increment('copy_count');
        return $this;
    }

    /**
     * Toggle like
     */
    public function toggleLike(): self
    {
        // This would typically involve a separate likes table
        // For simplicity, we just increment/decrement
        $this->increment('like_count');
        return $this;
    }

    /**
     * Copy to another user
     */
    public function copyTo(User $user, ?string $newName = null): self
    {
        $copy = self::create([
            'user_id' => $user->id,
            'experiment_id' => $this->experiment_id,
            'saved_state' => $this->saved_state,
            'name' => $newName ?? ($this->name . ' (nusxa)'),
            'description' => $this->description,
            'tags' => $this->tags,
            'is_public' => false,
        ]);
        
        $this->recordCopy();
        
        return $copy;
    }

    /**
     * Update saved state
     */
    public function updateState(array $state): self
    {
        $this->saved_state = array_merge($this->saved_state ?? [], $state);
        $this->save();
        
        return $this;
    }

    /**
     * Add tag
     */
    public function addTag(string $tag): self
    {
        $tags = $this->tags ?? [];
        if (!in_array($tag, $tags)) {
            $tags[] = $tag;
            $this->tags = $tags;
            $this->save();
        }
        
        return $this;
    }

    /**
     * Remove tag
     */
    public function removeTag(string $tag): self
    {
        $tags = $this->tags ?? [];
        $this->tags = array_values(array_diff($tags, [$tag]));
        $this->save();
        
        return $this;
    }

    /**
     * Find by share code
     */
    public static function findByShareCode(string $code): ?self
    {
        return self::where('share_code', $code)->first();
    }

    /**
     * Export for display
     */
    public function toDisplayData(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->display_name,
            'description' => $this->description,
            'thumbnail' => $this->thumbnail,
            'experiment' => [
                'id' => $this->experiment_id,
                'title' => $this->experiment->localized_title,
                'category' => $this->experiment->category->localized_name,
            ],
            'user' => [
                'id' => $this->user_id,
                'name' => $this->user->name,
                'avatar' => $this->user->avatar_url ?? null,
            ],
            'is_public' => $this->is_public,
            'share_url' => $this->share_url,
            'stats' => [
                'views' => $this->view_count,
                'copies' => $this->copy_count,
                'likes' => $this->like_count,
            ],
            'tags' => $this->tags,
            'created_at' => $this->created_at->diffForHumans(),
        ];
    }

    /**
     * Export state for simulation
     */
    public function toSimulationState(): array
    {
        return [
            'id' => $this->id,
            'experiment_id' => $this->experiment_id,
            'state' => $this->saved_state,
            'name' => $this->display_name,
        ];
    }
}
