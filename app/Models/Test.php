<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Test extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'exam_topic_id',
        'course_id',
        'type',
        'testable_type',
        'testable_id',
        'title',
        'description',
        'questions_count',
        'time_limit',
        'passing_score',
        'max_attempts',
        'shuffle_questions',
        'shuffle_options',
        'show_correct_answers',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'passing_score' => 'decimal:2',
            'shuffle_questions' => 'boolean',
            'shuffle_options' => 'boolean',
            'show_correct_answers' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    // Relationships
    public function course()
    {
        return $this->belongsTo(Course::class);
    }

    public function testable()
    {
        return $this->morphTo(); // Lesson yoki Module
    }

    public function questions()
    {
        return $this->hasMany(Question::class);
    }

    public function attempts()
    {
        return $this->hasMany(TestAttempt::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('type', $type);
    }

    // Helper Methods

    /**
     * tests.max_attempts = 0 means unlimited attempts.
     */
    public function hasUnlimitedAttempts(): bool
    {
        return (int) $this->max_attempts === 0;
    }

    /**
     * Whether another attempt is allowed after $usedAttempts finished attempts.
     */
    public function allowsAnotherAttempt(int $usedAttempts): bool
    {
        return $this->hasUnlimitedAttempts() || $usedAttempts < (int) $this->max_attempts;
    }

    /**
     * Remaining attempts, or null when unlimited.
     */
    public function remainingAttempts(int $usedAttempts): ?int
    {
        return $this->hasUnlimitedAttempts() ? null : max(0, (int) $this->max_attempts - $usedAttempts);
    }

    public function canBeAttemptedBy(User $user): bool
    {
        if (!$this->is_active) return false;

        $attempts = $this->attempts()
            ->where('user_id', $user->id)
            ->count();

        return $this->allowsAnotherAttempt($attempts);
    }

    public function updateQuestionsCount(): void
    {
        $this->update(['questions_count' => $this->questions()->count()]);
    }
}
