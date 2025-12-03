<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class RegisterController extends Controller
{
    /**
     * Display the registration page.
     */
    public function showRegister(): Response
    {
        return Inertia::render('Auth/Register');
    }

    /**
     * Handle registration request.
     */
    public function register(RegisterRequest $request): RedirectResponse
    {
        // Create new user with default student role
        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => 'student',
            'status' => 'active',
            'phone_verified_at' => now(), // Auto-verify for now
            'xp_total' => 0,
            'level' => 1,
            'coin_balance' => 0,
            'elo_rating' => 1000,
            'battles_won' => 0,
            'battles_total' => 0,
            'streak_current' => 0,
            'streak_best' => 0,
        ]);

        // Log the user in
        Auth::login($user);

        // Redirect to student dashboard
        return redirect('/student/dashboard')->with('success', 'Xush kelibsiz! Hisobingiz muvaffaqiyatli yaratildi.');
    }
}
