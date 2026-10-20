<?php

namespace App\Models\Exam;

use App\Enums\Exam\StudentGoal;
use App\Models\District;
use App\Models\Region;
use App\Models\School;
use App\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Exam specific student data (separate from the gamification heavy
 * student_profiles table; see docs/exam/02_DATA_MODEL.md).
 */
class StudentExamProfile extends Model
{
    use HasFactory, HasUuids;

    protected $table = 'exam_student_profiles';

    protected $fillable = [
        'user_id',
        'grade',
        'is_graduate',
        'subjects',
        'goal',
        'region_id',
        'district_id',
        'school_id',
        'school_name',
        'show_name_publicly',
        'public_alias',
        'placement_completed_at',
        // stage 7 onboarding
        'filled_by',
        'parent_phone',
        'parent_consent_at',
        'parent_consent_source',
        'public_name_consent_at',
        'onboarded_at',
    ];

    protected function casts(): array
    {
        return [
            'grade' => 'integer',
            'is_graduate' => 'boolean',
            'subjects' => 'array',
            'goal' => StudentGoal::class,
            'show_name_publicly' => 'boolean',
            'placement_completed_at' => 'datetime',
            'parent_consent_at' => 'datetime',
            'public_name_consent_at' => 'datetime',
            'onboarded_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (StudentExamProfile $profile) {
            if ($profile->grade !== null && ($profile->grade < 5 || $profile->grade > 11)) {
                throw new \InvalidArgumentException('Grade must be within 5..11.');
            }
            $unknown = array_diff($profile->subjects ?? [], [Subject::MATH, Subject::PHYSICS]);
            if ($unknown !== []) {
                throw new \InvalidArgumentException('Unknown subject code(s): '.implode(', ', $unknown));
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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

    /**
     * Onboarding finished: subjects chosen and the parent's consent stored
     * (spec section 11). The exam area sends students without it to
     * student.exam.onboarding.
     */
    public function isComplete(): bool
    {
        return $this->parent_consent_at !== null && ! empty($this->subjects);
    }

    public function studies(string $subjectCode): bool
    {
        return in_array($subjectCode, $this->subjects ?? [], true);
    }

    /** Name shown on public leaderboards (real name only with consent). */
    public function publicName(): string
    {
        if ($this->show_name_publicly) {
            $user = $this->user()->first();
            if ($user) {
                return trim($user->first_name.' '.mb_substr((string) $user->last_name, 0, 1).'.');
            }
        }

        return $this->public_alias ?: 'O\'quvchi #'.strtoupper(substr((string) $this->id, -4));
    }
}
