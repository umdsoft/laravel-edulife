<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\UpdateProfileRequest;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Review;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProfileController extends Controller
{
    public function index()
    {
        return Inertia::render('Teacher/Profile/Index', [
            'user' => $this->profileUser(),
        ]);
    }

    public function edit()
    {
        return Inertia::render('Teacher/Profile/Edit', [
            'user' => $this->profileUser(),
        ]);
    }

    public function update(UpdateProfileRequest $request)
    {
        $user = Auth::user();
        $socialLinks = array_filter((array) $request->input('social_links', []), fn ($link) => filled($link));

        // bio / website / social links live on users (shared with the student-facing pages)
        $user->update([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'bio' => $request->bio,
            'website' => $request->website,
            'social_links' => $socialLinks ?: null,
        ]);

        $user->ensureTeacherProfile()->update([
            'headline' => $request->headline,
            // course pages show the teacher's bio from teacher_profiles
            'bio' => $request->bio,
            'website_url' => $request->website,
            'telegram_url' => $socialLinks['telegram'] ?? null,
            'youtube_url' => $socialLinks['youtube'] ?? null,
            'expertise' => array_values(array_filter((array) $request->input('expertise', []), fn ($skill) => filled($skill))),
            'experience_years' => $request->experience_years,
        ]);

        return redirect()->route('teacher.profile.index')
            ->with('success', 'Profil muvaffaqiyatli yangilandi');
    }

    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => ['required', 'image', 'max:2048'],
        ]);
        
        $user = Auth::user();
        
        // users.avatar holds the storage path; avatar_url is the derived public URL
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        $path = $request->file('avatar')->store('avatars', 'public');
        $user->update(['avatar' => $path]);
        
        return back()->with('success', 'Avatar yangilandi');
    }
    
    public function deleteAvatar()
    {
        $user = Auth::user();
        
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
            $user->update(['avatar' => null]);
        }
        
        return back()->with('success', 'Avatar o\'chirildi');
    }

    private function profileUser()
    {
        $user = Auth::user()->makeVisible(['phone', 'email']);
        $profile = $user->ensureTeacherProfile();

        // The counters on teacher_profiles are not maintained anywhere; show live numbers
        $courseIds = Course::where('teacher_id', $user->id)->pluck('id');
        $profile->forceFill([
            'total_courses' => $courseIds->count(),
            'total_students' => Enrollment::whereIn('course_id', $courseIds)->distinct('user_id')->count('user_id'),
            'total_reviews' => Review::whereIn('course_id', $courseIds)->count(),
            'avg_rating' => round((float) Review::whereIn('course_id', $courseIds)->avg('rating'), 2),
        ]);

        return $user;
    }
}
