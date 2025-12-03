<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Course extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'teacher_id',
        'direction_id',
        'title',
        'slug',
        'short_description',
        'description',
        'thumbnail',
        'preview_video',
        'difficulty',
        'language',
        'status',
        'price',
        'original_price',
        'is_free',
        'what_you_learn',
        'requirements',
        'target_audience',
        'modules_count',
        'lessons_count',
        'total_duration',
        'students_count',
        'avg_rating',
        'reviews_count',
        'is_featured',
        'free_navigation',
        'submitted_at',
        'approved_at',
        'published_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'original_price' => 'decimal:2',
            'is_free' => 'boolean',
            'what_you_learn' => 'array',
            'requirements' => 'array',
            'target_audience' => 'array',
            'avg_rating' => 'decimal:2',
            'is_featured' => 'boolean',
            'free_navigation' => 'boolean',
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    // Accessors
    public function getThumbnailUrlAttribute(): ?string
    {
        return $this->thumbnail ? asset('storage/' . $this->thumbnail) : null;
    }

    public function getPreviewVideoUrlAttribute(): ?string
    {
        return $this->preview_video ? asset('storage/' . $this->preview_video) : null;
    }

    public function getDiscountPercentAttribute(): int
    {
        if (!$this->original_price || $this->original_price <= $this->price) return 0;
        return round((($this->original_price - $this->price) / $this->original_price) * 100);
    }

    public function getFormattedDurationAttribute(): string
    {
        $hours = floor($this->total_duration / 60);
        $minutes = $this->total_duration % 60;
        return $hours > 0 ? "{$hours}h {$minutes}m" : "{$minutes}m";
    }

    // Relationships
    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function direction()
    {
        return $this->belongsTo(Direction::class);
    }

    public function modules()
    {
        return $this->hasMany(Module::class)->orderBy('sort_order');
    }

    public function lessons()
    {
        return $this->hasMany(Lesson::class);
    }

    public function tests()
    {
        return $this->hasMany(Test::class);
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function students()
    {
        return $this->belongsToMany(User::class, 'enrollments')
            ->withPivot('progress', 'status', 'completed_at')
            ->withTimestamps();
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function certificates()
    {
        return $this->hasMany(Certificate::class);
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'course_tag');
    }

    public function battles()
    {
        return $this->hasMany(Battle::class);
    }

    public function tournaments()
    {
        return $this->hasMany(Tournament::class);
    }

    public function courseQuestions()
    {
        return $this->hasMany(CourseQuestion::class);
    }

    public function announcements()
    {
        return $this->hasMany(CourseAnnouncement::class);
    }

    // Scopes
    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['published', 'approved']);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function scopeFree($query)
    {
        return $query->where('is_free', true);
    }

    public function scopePaid($query)
    {
        return $query->where('is_free', false);
    }

    public function scopeByDifficulty($query, string $difficulty)
    {
        return $query->where('difficulty', $difficulty);
    }

    public function scopeByLanguage($query, string $language)
    {
        return $query->where('language', $language);
    }

    public function scopeByDirection($query, $directionId)
    {
        return $query->where('direction_id', $directionId);
    }

    // Helper Methods
    public function isEnrolledBy(User $user): bool
    {
        return $this->enrollments()->where('user_id', $user->id)->exists();
    }

    public function isCompletedBy(User $user): bool
    {
        return $this->enrollments()
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->exists();
    }

    public function canBeAccessedBy(User $user): bool
    {
        if ($this->is_free) return true;
        return $this->isEnrolledBy($user);
    }

    public function updateRating(): void
    {
        $approved = $this->reviews()->where('status', 'approved');
        $this->update([
            'avg_rating' => $approved->avg('rating') ?? 0,
            'reviews_count' => $approved->count(),
        ]);
    }

    public function updateCounts(): void
    {
        $this->update([
            'modules_count' => $this->modules()->count(),
            'lessons_count' => $this->lessons()->count(),
            'total_duration' => $this->lessons()->sum('video_duration'),
            'students_count' => $this->enrollments()->count(),
        ]);
    }

    public function incrementStudentsCount(): void
    {
        $this->increment('students_count');
    }
}
