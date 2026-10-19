<?php

namespace App\Models;

use App\Models\Exam\Concerns\HasExamRelations;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasUuids, SoftDeletes, HasRoles;
    use HasExamRelations; // guardian role, exam profile, telegram, referrals, results

    protected $fillable = [
        'phone',
        'email',
        'password',
        'first_name',
        'last_name',
        'avatar',
        'role',
        'status',
        'xp_total',
        'level',
        'title',
        'coin_balance',
        'coin_earned_this_month',
        'coin_spent_total',
        'elo_rating',
        'battle_rank',
        'battles_won',
        'battles_total',
        'streak_current',
        'streak_best',
        'streak_last_date',
        'failed_login_attempts',
        'locked_until',
        'last_login_at',
        'login_count',
        // Self-service profile/settings fields (were silently dropped on update)
        'bio',
        'username',
        'location',
        'website',
        'social_links',
        'timezone',
        'language',
        'is_profile_public',
        'show_activity',
        'show_courses',
        'show_achievements',
        'show_stats',
    ];

    /**
     * User models are serialized into other users' pages (reviews, Q&A, battles,
     * friends...), so contact and security fields are hidden by default.
     * Own-profile screens opt back in with makeVisible().
     */
    protected $hidden = [
        // only feed avatar_url (and the profile's badge chip); not the purchase rows
        'equippedAvatar',
        'equippedBadge',
        'password',
        'remember_token',
        'phone',
        'email',
        'email_verified_at',
        'phone_verified_at',
        'failed_login_attempts',
        'locked_until',
        'last_login_at',
        'login_count',
    ];

    protected $appends = ['full_name', 'avatar_url'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
            'locked_until' => 'datetime',
            'streak_last_date' => 'date',
            'social_links' => 'array',
            'is_profile_public' => 'boolean',
            'show_activity' => 'boolean',
            'show_courses' => 'boolean',
            'show_achievements' => 'boolean',
            'show_stats' => 'boolean',
        ];
    }

    // Accessors
    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    /**
     * There is no users.name column; olympiad, lab and leaderboard code reads
     * $user->name, so it resolves to the full name.
     */
    public function getNameAttribute(): string
    {
        return $this->full_name;
    }

    /**
     * Spendable coins live on student_profiles (CoinService); users.coin_balance
     * is a legacy column that nothing updates.
     */
    public function getCoinsAttribute(): int
    {
        return (int) ($this->studentProfile?->coins ?? 0);
    }

    /**
     * The picture shown for the user: the avatar bought in the shop and
     * equipped ("Taqish") when that relation is loaded, else the uploaded
     * photo. Pages that show other students eager-load `equippedAvatar.item`;
     * the shared auth props load it for the header.
     */
    public function getAvatarUrlAttribute(): ?string
    {
        if ($this->relationLoaded('equippedAvatar') && ($url = $this->equippedAvatar?->item?->image_url)) {
            return $url;
        }

        return $this->avatar ? asset('storage/' . $this->avatar) : null;
    }

    /** The shop avatar the student wears (one per type, ShopController::equip). */
    public function equippedAvatar(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(UserPurchase::class)
            ->where('is_equipped', true)
            ->whereHas('item', fn ($q) => $q->where('type', 'avatar'));
    }

    /** The shop badge (nishon) the student wears. */
    public function equippedBadge(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(UserPurchase::class)
            ->where('is_equipped', true)
            ->whereHas('item', fn ($q) => $q->where('type', 'badge'));
    }

    public function getWinRateAttribute(): float
    {
        if ($this->battles_total === 0) return 0;
        return round(($this->battles_won / $this->battles_total) * 100, 1);
    }

    // Relationships
    public function profile()
    {
        return $this->hasOne(UserProfile::class);
    }

    public function teacherProfile()
    {
        return $this->hasOne(TeacherProfile::class);
    }

    /**
     * The teacher's profile row, created on first use.
     */
    public function ensureTeacherProfile(): TeacherProfile
    {
        $profile = $this->teacherProfile()->firstOrCreate([]);
        $this->setRelation('teacherProfile', $profile);

        return $profile;
    }

    public function sessions()
    {
        return $this->hasMany(UserSession::class);
    }

    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    public function courses()
    {
        return $this->belongsToMany(Course::class, 'enrollments')
            ->withPivot('progress', 'status', 'completed_at')
            ->withTimestamps();
    }

    public function teacherCourses()
    {
        return $this->hasMany(Course::class, 'teacher_id');
    }

    public function lessonProgresses()
    {
        return $this->hasMany(LessonProgress::class);
    }

    public function lessonNotes()
    {
        return $this->hasMany(LessonNote::class);
    }

    public function videoWatchLogs()
    {
        return $this->hasMany(VideoWatchLog::class);
    }

    public function testAttempts()
    {
        return $this->hasMany(TestAttempt::class);
    }

    public function labAttempts()
    {
        return $this->hasMany(LabAttempt::class);
    }

    public function labProgress()
    {
        return $this->hasOne(LabUserProgress::class);
    }

    public function achievements()
    {
        return $this->belongsToMany(Achievement::class, 'user_achievements')
            ->withPivot('xp_rewarded', 'coin_rewarded', 'earned_at')
            ->withTimestamps();
    }

    public function battles()
    {
        return $this->hasMany(Battle::class, 'player1_id')
            ->orWhere('player2_id', $this->id);
    }

    public function tournaments()
    {
        return $this->belongsToMany(Tournament::class, 'tournament_participants')
            ->withPivot('seed', 'status', 'final_place')
            ->withTimestamps();
    }

    public function coinTransactions()
    {
        return $this->hasMany(CoinTransaction::class);
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function certificates()
    {
        return $this->hasMany(Certificate::class);
    }

    public function dailyMissions()
    {
        return $this->hasMany(UserDailyMission::class);
    }

    public function streaks()
    {
        return $this->hasMany(UserStreak::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeStudents($query)
    {
        return $query->where('role', 'student');
    }

    public function scopeTeachers($query)
    {
        return $query->where('role', 'teacher');
    }

    // Helper Methods
    public function isTeacher(): bool
    {
        return $this->role === 'teacher';
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'super_admin']);
    }

    /**
     * The student's profile row, created on demand. student_profiles is the
     * single source of truth for XP, level, streak and coins; the users.xp_total,
     * level, coin_balance and streak_* columns are legacy and never written.
     */
    public function ensureStudentProfile(): StudentProfile
    {
        $profile = $this->studentProfile ?? $this->studentProfile()->firstOrCreate([]);
        $this->setRelation('studentProfile', $profile);

        return $profile;
    }

    public function addXp(int $amount): void
    {
        if ($amount <= 0) {
            return;
        }

        $this->ensureStudentProfile()->addXp($amount);
    }

    public function addCoins(int $amount, string $source, string $description, $reference = null): void
    {
        if ($amount <= 0) {
            return;
        }

        app(\App\Services\CoinService::class)->addCoins($this, $amount, $source, $description, $reference);
    }

    public function spendCoins(int $amount, string $source, string $description, $reference = null): bool
    {
        return app(\App\Services\CoinService::class)->spendCoins($this, $amount, $source, $description, $reference);
    }

    public function studentProfile()
    {
        return $this->hasOne(StudentProfile::class);
    }

    public function wishlist()
    {
        return $this->hasMany(Wishlist::class);
    }

    public function isEnrolled(Course $course): bool
    {
        return $this->enrollments()->where('course_id', $course->id)->exists();
    }

    public function hasInWishlist(Course $course): bool
    {
        return $this->wishlist()->where('course_id', $course->id)->exists();
    }

    protected static function booted()
    {
        static::created(function (User $user) {
            if ($user->role === 'student') {
                $user->studentProfile()->create([]);
            }
        });

        // Teacher screens (profile, dashboard, reviews) expect a teacher_profiles
        // row; create it whenever a user becomes a teacher.
        static::saved(function (User $user) {
            if ($user->role === 'teacher' && ($user->wasRecentlyCreated || $user->wasChanged('role'))) {
                $user->ensureTeacherProfile();
            }
        });
    }

    // Part 5: Profile & Community relationships
    public function followers()
    {
        return $this->belongsToMany(User::class, 'user_follows', 'following_id', 'follower_id')
            ->withPivot('followed_at');
    }

    public function following()
    {
        return $this->belongsToMany(User::class, 'user_follows', 'follower_id', 'following_id')
            ->withPivot('followed_at');
    }

    public function activities()
    {
        return $this->hasMany(ActivityFeed::class)->orderByDesc('occurred_at');
    }

    public function supportTickets()
    {
        return $this->hasMany(SupportTicket::class);
    }

    // Part 5: Helper methods
    public function isFollowing(User $user): bool
    {
        return $this->following()->where('following_id', $user->id)->exists();
    }

    public function isFollowedBy(User $user): bool
    {
        return $this->followers()->where('follower_id', $user->id)->exists();
    }

    public function hasActiveSubscription(): bool
    {
        return true;
    }

    public function hasCompletedExperiment($experimentId): bool
    {
        return $this->labAttempts()
            ->where('experiment_id', $experimentId)
            ->where('status', 'completed')
            ->exists();
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->username ?? $this->full_name;
    }
}
