<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\Student\UpdateProfileRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class ProfileController extends Controller
{
    public function show()
    {
        $user = Auth::user()->makeVisible(['phone', 'email']);
        $user->load(['studentProfile', 'enrollments.course', 'equippedAvatar.item', 'equippedBadge.item']);
        
        // Stats
        $stats = [
            'courses_enrolled' => $user->enrollments()->count(),
            'courses_completed' => $user->studentProfile->courses_completed ?? 0,
            'lessons_completed' => $user->studentProfile->lessons_completed ?? 0,
            'certificates' => $user->certificates()->count(),
            'followers' => $user->followers()->count(),
            'following' => $user->following()->count(),
        ];
        
        // the page renders `activities` (it was sent as recentActivities and always empty)
        return Inertia::render('Student/Profile/Show', [
            'user' => $user,
            'profile' => $user->studentProfile,
            'stats' => $stats,
            'activities' => $this->recentActivities($user),
            'isOwnProfile' => true,
            'equippedBadge' => $this->badgeOf($user),
        ]);
    }
    
    public function publicProfile(string $user)
    {
        // linked by id from friends / leaderboards; a username works too
        $user = User::where('role', 'student')
            ->where(fn ($q) => $q->where('id', $user)->orWhere('username', $user))
            ->firstOrFail();
        
        // Check privacy
        if (!$user->is_profile_public && Auth::id() !== $user->id) {
            return Inertia::render('Student/Profile/Private', [
                'user' => $user->only(['id', 'first_name', 'avatar']),
            ]);
        }
        
        $user->load(['studentProfile', 'equippedAvatar.item', 'equippedBadge.item']);
        
        $authUser = Auth::user();
        $isFollowing = $authUser ? $authUser->isFollowing($user) : false;
        
        // Stats (if allowed)
        $stats = null;
        if ($user->show_stats || Auth::id() === $user->id) {
            $stats = [
                'xp' => $user->studentProfile->xp ?? 0,
                'level' => $user->studentProfile->level ?? 1,
                'streak_days' => $user->studentProfile->streak_days ?? 0,
                'followers' => $user->followers()->count(),
                'following' => $user->following()->count(),
            ];
        }
        
        $activities = null;
        if ($user->show_activity || Auth::id() === $user->id) {
            $activities = $this->recentActivities($user);
        }
        
        return Inertia::render('Student/Profile/Show', [
            'user' => $user,
            'profile' => $user->studentProfile,
            'stats' => $stats,
            'activities' => $activities,
            'isOwnProfile' => Auth::id() === $user->id,
            'isFollowing' => $isFollowing,
            'equippedBadge' => $this->badgeOf($user),
        ]);
    }

    /** The shop badge the student wears, for the chip next to the name. */
    private function badgeOf(User $user): ?array
    {
        $item = $user->equippedBadge?->item;

        return $item ? ['name' => $item->name, 'image_url' => $item->image_url] : null;
    }
    
    public function edit()
    {
        $user = Auth::user()->makeVisible(['phone', 'email']);
        
        return Inertia::render('Student/Profile/Edit', [
            'user' => $user,
        ]);
    }
    
    public function update(UpdateProfileRequest $request)
    {
        $user = Auth::user();
        
        $user->update($request->validated());
        
        return back()->with('success', 'Profil yangilandi');
    }
    
    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);
        
        $user = Auth::user();
        
        // Delete old avatar
        if ($user->avatar && !str_starts_with($user->avatar, 'http')) {
            Storage::disk('public')->delete($user->avatar);
        }
        
        // Store new avatar
        $path = $request->file('avatar')->store('avatars', 'public');
        
        $user->update(['avatar' => $path]);
        
        return back()->with('success', 'Avatar yangilandi');
    }
    
    public function deleteAvatar()
    {
        $user = Auth::user();
        
        if ($user->avatar && !str_starts_with($user->avatar, 'http')) {
            Storage::disk('public')->delete($user->avatar);
        }
        
        $user->update(['avatar' => null]);
        
        return back()->with('success', 'Avatar o\'chirildi');
    }

    /**
     * Latest learning events: completed lessons, passed tests, certificates.
     */
    private function recentActivities(User $user, int $limit = 10)
    {
        $lessons = \App\Models\LessonProgress::with('lesson:id,title')
            ->where('user_id', $user->id)->where('is_completed', true)->whereNotNull('completed_at')
            ->latest('completed_at')->limit($limit)->get()
            ->map(fn ($p) => [
                'id' => 'lesson-'.$p->id,
                'title' => 'Darsni tugatdi',
                'description' => $p->lesson?->title,
                'occurred_at' => $p->completed_at?->toIso8601String(),
            ]);

        $tests = \App\Models\TestAttempt::with('test:id,title')
            ->where('user_id', $user->id)->where('is_passed', true)->whereNotNull('submitted_at')
            ->latest('submitted_at')->limit($limit)->get()
            ->map(fn ($a) => [
                'id' => 'test-'.$a->id,
                'title' => "Testdan o'tdi",
                'description' => trim(($a->test?->title ?? 'Test').' — '.round((float) $a->score).'%'),
                'occurred_at' => \Illuminate\Support\Carbon::parse($a->submitted_at)->toIso8601String(),
            ]);

        $certificates = $user->certificates()->with('course:id,title')->latest('issued_at')->limit($limit)->get()
            ->map(fn ($c) => [
                'id' => 'certificate-'.$c->id,
                'title' => 'Sertifikat oldi',
                'description' => $c->course?->title ?? $c->course_title,
                'occurred_at' => $c->issued_at?->toIso8601String(),
            ]);

        return $lessons->concat($tests)->concat($certificates)
            ->sortByDesc('occurred_at')
            ->take($limit)
            ->values();
    }
}
