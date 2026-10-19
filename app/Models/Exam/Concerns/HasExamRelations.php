<?php

namespace App\Models\Exam\Concerns;

use App\Models\Exam\ExamAttempt;
use App\Models\Exam\GuardianStudent;
use App\Models\Exam\ReferralCode;
use App\Models\Exam\Referral;
use App\Models\Exam\StudentExamProfile;
use App\Models\Exam\StudentTopicMastery;
use App\Models\Exam\StudentWeeklyResult;
use App\Models\Exam\TelegramAccount;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Exam module relations of App\Models\User.
 */
trait HasExamRelations
{
    public const ROLE_GUARDIAN = 'guardian';

    public function isGuardian(): bool
    {
        return $this->role === self::ROLE_GUARDIAN;
    }

    /** Students this user is guardian (ota-ona) of. */
    public function wards(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'guardian_student', 'guardian_id', 'student_id')
            ->using(GuardianStudent::class)
            ->withPivot(['id', 'relation', 'consent_at', 'consent_source', 'receives_reports', 'verified_at'])
            ->withTimestamps();
    }

    /** Guardians of this student. */
    public function guardians(): BelongsToMany
    {
        return $this->belongsToMany(self::class, 'guardian_student', 'student_id', 'guardian_id')
            ->using(GuardianStudent::class)
            ->withPivot(['id', 'relation', 'consent_at', 'consent_source', 'receives_reports', 'verified_at'])
            ->withTimestamps();
    }

    public function examProfile(): HasOne
    {
        return $this->hasOne(StudentExamProfile::class);
    }

    public function telegramAccount(): HasOne
    {
        return $this->hasOne(TelegramAccount::class);
    }

    public function referralCode(): HasOne
    {
        return $this->hasOne(ReferralCode::class, 'owner_id');
    }

    /** People this user invited. */
    public function referralsMade(): HasMany
    {
        return $this->hasMany(Referral::class, 'referrer_id');
    }

    /** How this user was invited (if at all). */
    public function referredBy(): HasOne
    {
        return $this->hasOne(Referral::class, 'referred_id');
    }

    public function examAttempts(): HasMany
    {
        return $this->hasMany(ExamAttempt::class);
    }

    public function topicMasteries(): HasMany
    {
        return $this->hasMany(StudentTopicMastery::class);
    }

    public function weeklyResults(): HasMany
    {
        return $this->hasMany(StudentWeeklyResult::class);
    }
}
