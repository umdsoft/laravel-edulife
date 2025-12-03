<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    /**
     * Display the login page.
     */
    public function showLogin(): Response
    {
        return Inertia::render('Auth/Login');
    }

    /**
     * Handle login request.
     */
    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->only('phone', 'password');
        $remember = $request->boolean('remember');

        if (Auth::attempt($credentials, $remember)) {
            $request->session()->regenerate();

            $user = Auth::user();

            // Redirect based on user role
            return match ($user->role) {
                'super_admin', 'admin' => redirect()->intended('/admin/dashboard'),
                'teacher' => redirect()->intended('/teacher/dashboard'),
                default => redirect()->intended('/student/dashboard'),
            };
        }

        return back()->withErrors([
            'phone' => 'Telefon yoki parol noto\'g\'ri',
        ])->onlyInput('phone');
    }

    /**
     * Handle logout request.
     */
    public function logout(): RedirectResponse
    {
        Auth::logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login');
    }
}
