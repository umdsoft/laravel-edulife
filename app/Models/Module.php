<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Module extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $fillable = [
        'course_id',
        'title',
        'description',
        'sort_order',
        'lessons_count',
        'total_duration',
        'is_free',
    ];

    protected function casts(): array
    {
        return [
            'is_free' => 'boolean',
        ];
    }

    // Accessors
    public function getFormattedDurationAttribute(): string
    {
        $hours = floor($this->total_duration / 60);
        $minutes = $this->total_duration % 60;
        return $hours > 0 ? "{$hours}h {$minutes}m" : "{$minutes}m";
    }

    // Relationships
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function lessons()
    {
        return $this->hasMany(Lesson::class)->orderBy('sort_order');
    }

    public function tests()
    {
        return $this->morphMany(Test::class, 'testable');
    }

    public function progress()
    {
        return $this->hasMany(ModuleProgress::class);
    }

    // Helper Methods
    public function updateCounts(): void
    {
        $this->update([
            'lessons_count' => $this->lessons()->count(),
            'total_duration' => $this->lessons()->sum('video_duration'),
        ]);
    }

    public function isCompletedBy(User $user): bool
    {
        return $this->progress()
            ->where('user_id', $user->id)
            ->where('is_completed', true)
            ->exists();
    }
}
