<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class XPController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $profile = $user->studentProfile;
        
        $profile = $profile ?? $user->studentProfile()->create([]);

        // xp_to_next_level / level_progress_percentage were never defined (always 0)
        return Inertia::render('Student/XP/Index', [
            'profile' => $profile,
            'xp_to_next_level' => max(0, \App\Models\StudentProfile::xpForLevel((int) $profile->level + 1) - (int) $profile->xp),
            'level_progress' => $profile->level_progress,
        ]);
    }
}
