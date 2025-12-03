<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\UpdateProfileRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ProfileController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $user->load(['profile', 'teacherProfile']);
        
        return Inertia::render('Teacher/Profile/Index', [
            'user' => $user,
        ]);
    }
    
    public function edit()
    {
        $user = Auth::user();
        $user->load(['profile', 'teacherProfile']);
        
        return Inertia::render('Teacher/Profile/Edit', [
            'user' => $user,
        ]);
    }
    
    public function update(UpdateProfileRequest $request)
    {
        $user = Auth::user();
        
        // Update user
        $user->update([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
        ]);
        
        // Update user profile
        $user->profile->update([
            'bio' => $request->bio,
            'website' => $request->website,
            'social_links' => $request->social_links,
        ]);
        
        // Update teacher profile
        $user->teacherProfile->update([
            'headline' => $request->headline,
            'expertise' => $request->expertise,
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
        
        // Delete old avatar
        if ($user->avatar_url) {
            Storage::disk('public')->delete($user->avatar_url);
        }
        
        // Upload new avatar
        $path = $request->file('avatar')->store('avatars', 'public');
        $user->update(['avatar_url' => $path]);
        
        return back()->with('success', 'Avatar yangilandi');
    }
    
    public function deleteAvatar()
    {
        $user = Auth::user();
        
        if ($user->avatar_url) {
            Storage::disk('public')->delete($user->avatar_url);
            $user->update(['avatar_url' => null]);
        }
        
        return back()->with('success', 'Avatar o\'chirildi');
    }
}
