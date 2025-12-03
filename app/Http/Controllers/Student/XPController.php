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
        
        return Inertia::render('Student/XP/Index', [
            'profile' => $profile,
            'xp_to_next_level' => $profile->xp_to_next_level,
            'level_progress' => $profile->level_progress_percentage,
        ]);
    }
}
