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
        $user = Auth::user();
        $user->load(['studentProfile', 'enrollments.course']);
        
        // Stats
        $stats = [
            'courses_enrolled' => $user->enrollments()->count(),
            'courses_completed' => $user->studentProfile->courses_completed ?? 0,
            'lessons_completed' => $user->studentProfile->lessons_completed ?? 0,
            'certificates' => $user->certificates()->count(),
            'followers' => $user->followers()->count(),
            'following' => $user->following()->count(),
        ];
        
        //Recent activities (not implemented yet)
        $recentActivities = collect([]);
        
        return Inertia::render('Student/Profile/Show', [
            'user' => $user,
            'profile' => $user->studentProfile,
            'stats' => $stats,
            'recentActivities' => $recentActivities,
            'isOwnProfile' => true,
        ]);
    }
    
    public function publicProfile(string $username)
    {
        $user = User::where('username', $username)
            ->where('role', 'student')
            ->firstOrFail();
        
        // Check privacy
        if (!$user->is_profile_public && Auth::id() !== $user->id) {
            return Inertia::render('Student/Profile/Private', [
                'user' => $user->only(['id', 'first_name', 'avatar']),
            ]);
        }
        
        $user->load('studentProfile');
        
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
        
        // Activities (if allowed - not implemented yet)
        $activities = null;
        if ($user->show_activity || Auth::id() === $user->id) {
            $activities = collect([]);
        }
        
        return Inertia::render('Student/Profile/Show', [
            'user' => $user,
            'profile' => $user->studentProfile,
            'stats' => $stats,
            'activities' => $activities,
            'isOwnProfile' => Auth::id() === $user->id,
            'isFollowing' => $isFollowing,
        ]);
    }
    
    public function edit()
    {
        $user = Auth::user();
        
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
}
