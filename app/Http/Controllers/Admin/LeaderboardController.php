<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class LeaderboardController extends Controller
{
    public function index()
    {
        // Get top users
        // XP lives on student_profiles (same source as the student leaderboard);
        // users.xp_total is never updated when XP is awarded
        $topUsers = User::where('users.role', 'student')
            ->join('student_profiles', 'student_profiles.user_id', '=', 'users.id')
            ->orderBy('student_profiles.xp', 'desc')
            ->take(10)
            ->get(['users.id', 'users.first_name', 'users.last_name', 'users.avatar', 'student_profiles.xp']);

        // Get settings (mocked or from DB)
        $settings = [
            'period' => 'monthly', // weekly, monthly, all_time
            'reset_date' => now()->endOfMonth()->format('Y-m-d'),
            'is_active' => true,
        ];

        return Inertia::render('Admin/Leaderboard/Config', [
            'topUsers' => $topUsers,
            'settings' => $settings,
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'period' => 'required|in:weekly,monthly,all_time',
            'is_active' => 'boolean',
        ]);

        // Save settings to DB (e.g., settings table)
        // Setting::set('leaderboard_period', $request->period);
        // Setting::set('leaderboard_active', $request->is_active);

        return back()->with('success', 'Sozlamalar yangilandi');
    }

    public function reset(Request $request)
    {
        // Reset XP logic if needed, or just archive current leaderboard
        // For example, snapshot current standings and reset weekly XP
        
        // User::role('student')->update(['weekly_xp' => 0]);

        return back()->with('success', 'Leaderboard yangilandi');
    }
}
