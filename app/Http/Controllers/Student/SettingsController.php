<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class SettingsController extends Controller
{
    public function index()
    {
        return redirect()->route('student.settings.account');
    }
    
    public function account()
    {
        $user = Auth::user();
        
        return Inertia::render('Student/Settings/Account', [
            'user' => $user->only([
                'id', 'first_name', 'last_name', 'email', 'phone',
                'username', 'timezone', 'language',
            ]),
        ]);
    }
    
    public function updateAccount(Request $request)
    {
        $user = Auth::user();
        
        $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:50', 'regex:/^[a-z0-9_]+$/', 'unique:users,username,' . $user->id],
            'phone' => ['nullable', 'string', 'max:20'],
            'timezone' => ['nullable', 'string'],
            'language' => ['nullable', 'in:uz,ru,en'],
        ]);
        
        $user->update($request->only([
            'first_name', 'last_name', 'username', 'phone', 'timezone', 'language',
        ]));
        
        return back()->with('success', 'Hisob ma\'lumotlari yangilandi');
    }
    
    public function privacy()
    {
        $user = Auth::user();
        
        return Inertia::render('Student/Settings/Privacy', [
            'settings' => [
                'is_profile_public' => $user->is_profile_public,
                'show_activity' => $user->show_activity,
                'show_courses' => $user->show_courses,
                'show_achievements' => $user->show_achievements,
                'show_stats' => $user->show_stats,
            ],
        ]);
    }
    
    public function updatePrivacy(Request $request)
    {
        $request->validate([
            'is_profile_public' => ['boolean'],
            'show_activity' => ['boolean'],
            'show_courses' => ['boolean'],
            'show_achievements' => ['boolean'],
            'show_stats' => ['boolean'],
        ]);
        
        Auth::user()->update($request->only([
            'is_profile_public',
            'show_activity',
            'show_courses',
            'show_achievements',
            'show_stats',
        ]));
        
        return back()->with('success', 'Maxfiylik sozlamalari saqlandi');
    }
    
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
        
        Auth::user()->update([
            'password' => Hash::make($request->password),
        ]);
        
        return back()->with('success', 'Parol yangilandi');
    }
    
    public function deleteAccount(Request $request)
    {
        $request->validate([
            'password' => ['required', 'current_password'],
            'confirmation' => ['required', 'in:DELETE'],
        ]);
        
        $user = Auth::user();
        
        // Soft delete - deactivate + anonymize
        $user->update([
            'email' => 'deleted_' . $user->id . '@deleted.local',
            'first_name' => 'Deleted',
            'last_name' => 'User',
            'is_active' => false,
        ]);
        
        Auth::logout();
        
        return redirect('/')->with('success', 'Hisobingiz o\'chirildi');
    }
}
