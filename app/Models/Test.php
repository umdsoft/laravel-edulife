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

    /** Final test opens once this share of the course is done. */
    public const FINAL_TEST_MIN_PROGRESS = 80;

    /**
     * Why the student may not start this test yet, or null when they may:
     * a lesson test needs its lesson completed, a module test all lessons of
     * the module, the final test 80% course progress.
     */
    public function prerequisiteError(Enrollment $enrollment): ?string
    {
        $type = $this->type;
        $completed = fn ($lessonIds) => LessonProgress::where('enrollment_id', $enrollment->id)
            ->whereIn('lesson_id', $lessonIds)
            ->where('is_completed', true)
            ->count();

        if (in_array($type, ['lesson', 'lesson_test'], true) && $this->testable_type === Lesson::class && $this->testable_id) {
            // a quiz-type lesson *is* its test: passing the test completes the lesson
            if (Lesson::whereKey($this->testable_id)->value('type') === 'quiz') {
                return null;
            }

            return $completed([$this->testable_id]) > 0 ? null : 'Testni boshlashdan oldin darsni yakunlang.';
        }

        if (in_array($type, ['module', 'module_test'], true) && $this->testable_type === Module::class && $this->testable_id) {
            $lessonIds = Lesson::where('module_id', $this->testable_id)->pluck('id');

            return $completed($lessonIds) >= $lessonIds->count() ? null : 'Testni boshlashdan oldin moduldagi barcha darslarni yakunlang.';
        }

        if (in_array($type, ['final', 'final_test'], true) && (float) $enrollment->progress < self::FINAL_TEST_MIN_PROGRESS) {
            return 'Yakuniy test kursning kamida '.self::FINAL_TEST_MIN_PROGRESS."% qismi o'tilgach ochiladi.";
        }

        return null;
    }

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
