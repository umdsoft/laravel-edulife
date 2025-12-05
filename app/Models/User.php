<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasUuids, SoftDeletes, HasRoles;

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
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
            'locked_until' => 'datetime',
            'streak_last_date' => 'date',
        ];
    }

    // Accessors
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function getAvatarUrlAttribute(): ?string
    {
        return $this->avatar ? asset('storage/' . $this->avatar) : null;
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

    public function scoreHistory()
    {
        return $this->hasMany(TeacherScoreHistory::class, 'teacher_id');
    }

    public function levelChanges()
    {
        return $this->hasMany(TeacherLevelChange::class, 'teacher_id');
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

    public function addXp(int $amount): void
    {
        $this->increment('xp_total', $amount);
        $this->checkLevelUp();
    }

    public function addCoins(int $amount, string $source, string $description): void
    {
        $this->increment('coin_balance', $amount);
        $this->increment('coin_earned_this_month', $amount);
        
        $this->coinTransactions()->create([
            'type' => 'earn',
            'amount' => $amount,
            'balance_after' => $this->coin_balance,
            'source' => $source,
            'description' => $description,
        ]);
    }

    public function spendCoins(int $amount, string $source, string $description): bool
    {
        if ($this->coin_balance < $amount) return false;
        
        $this->decrement('coin_balance', $amount);
        $this->increment('coin_spent_total', $amount);
        
        $this->coinTransactions()->create([
            'type' => 'spend',
            'amount' => -$amount,
            'balance_after' => $this->coin_balance,
            'source' => $source,
            'description' => $description,
        ]);
        
        return true;
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
    }

    protected function checkLevelUp(): void
    {
        $newLevel = $this->calculateLevel($this->xp_total);
        if ($newLevel > $this->level) {
            $this->update(['level' => $newLevel]);
            // TODO: Trigger level up event/notification
        }
    }

    protected function calculateLevel(int $xp): int
    {
        // XP_required(level) = 100 * level^1.5
        $level = 1;
        $totalRequired = 0;
        while ($totalRequired <= $xp) {
            $level++;
            $totalRequired += (int)(100 * pow($level, 1.5));
        }
        return $level - 1;
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
