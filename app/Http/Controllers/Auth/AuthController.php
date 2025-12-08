<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Controller for handling user authentication.
 * 
 * Manages login, logout, and session handling for all user types
 * (students, teachers, admins). Implements role-based redirects
 * after successful authentication.
 * 
 * @package App\Http\Controllers\Auth
 * @author EDULIFE Team
 */
class AuthController extends Controller
{
    /**
     * Display the login page.
     * 
     * Renders the Inertia login component.
     * Protected by 'guest' middleware to prevent authenticated users from accessing.
     * 
     * @return Response The Inertia response with login page
     */
    public function showLogin(): Response
    {
        return Inertia::render('Auth/Login');
    }

    /**
     * Handle login request.
     * 
     * Validates credentials using LoginRequest, attempts authentication,
     * regenerates session on success, and redirects based on user role.
     * 
     * Security features:
     * - Session regeneration to prevent session fixation
     * - Rate limiting via route middleware (5 attempts/min)
     * - Role-based redirection
     * 
     * @param LoginRequest $request Validated login request with phone and password
     * 
     * @return RedirectResponse Redirect to role-appropriate dashboard or back with errors
     * 
     * @example
     * // Successful login redirects:
     * // - super_admin/admin -> /admin/dashboard
     * // - teacher -> /teacher/dashboard
     * // - student -> /student/dashboard
     */
    public function login(LoginRequest $request): RedirectResponse
    {
        $login = $request->input('login'); // email or phone
        $password = $request->input('password');
        $remember = $request->boolean('remember');

        // Determine if login is email or phone
        $fieldType = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        $credentials = [
            $fieldType => $login,
            'password' => $password,
        ];

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
            'login' => 'Email/Telefon yoki parol noto\'g\'ri',
        ])->onlyInput('login');
    }

    /**
     * Handle logout request.
     * 
     * Logs out the user, invalidates the session, and regenerates
     * the CSRF token to prevent session-related attacks.
     * 
     * @return RedirectResponse Redirect to login page
     */
    public function logout(): RedirectResponse
    {
        Auth::logout();

        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login');
    }
}
